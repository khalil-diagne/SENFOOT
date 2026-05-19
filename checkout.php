<?php
require __DIR__ . '/config.php';
require __DIR__ . '/email_template.php';

ensure_store_schema();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || !isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecte.']);
    exit();
}

$json = file_get_contents('php://input');
$payload = json_decode($json, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donnees invalides.']);
    exit();
}

$payerPhoneNumber = $payload['payerPhoneNumber'] ?? '';
if ($payerPhoneNumber === '') {
    foreach ($payload as $value) {
        if (is_array($value) && isset($value['payerPhoneNumber'])) {
            $payerPhoneNumber = (string) $value['payerPhoneNumber'];
            break;
        }
    }
}

$payerPhoneNumber = preg_replace('/\D+/', '', (string) $payerPhoneNumber);
$cart = array_values(array_filter($payload, static function ($item) {
    return is_array($item) && isset($item['id']);
}));

if ($cart === []) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le panier est vide.']);
    exit();
}

if ($payerPhoneNumber === '' || !preg_match('/^\d{9}$/', $payerPhoneNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Numero WhatsApp invalide.']);
    exit();
}

$pdo = db();

try {
    $userId = current_user_id();
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit();
    }

    $profile = fetch_user_profile_by_id($userId);
    $missingFields = $profile ? customer_profile_missing_fields($profile) : customer_profile_required_fields();
    if (!$profile || $missingFields !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Completez d abord toutes vos informations personnelles avant de commander.',
            'missing_fields' => array_values($missingFields),
            'redirect_url' => 'profile_edit.php?checkout_required=1',
        ]);
        exit();
    }

    $articleIds = array_map('intval', array_column($cart, 'id'));
    if ($articleIds === []) {
        throw new Exception('Le panier ne contient aucun article valide.');
    }

    $articleQuantities = array_count_values($articleIds);
    $uniqueArticleIds = array_keys($articleQuantities);
    $placeholders = implode(',', array_fill(0, count($uniqueArticleIds), '?'));

    $stmtCheck = $pdo->prepare("SELECT id, price, title, product_status FROM articles WHERE id IN ($placeholders)");
    $stmtCheck->execute($uniqueArticleIds);
    $dbArticles = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dbArticles as $article) {
        $statusMeta = article_status_meta($article['product_status'] ?? null);
        if ($statusMeta['value'] !== 'available') {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'L article "' . ($article['title'] ?? 'selectionne') . '" est actuellement ' . strtolower($statusMeta['label']) . '. Retire-le du panier puis reessaie.',
            ]);
            exit();
        }
    }

    if (count($dbArticles) !== count($uniqueArticleIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Certains articles de votre panier ne sont plus disponibles. Veuillez rafraichir la page.']);
        exit();
    }

    $totalPrice = array_reduce($dbArticles, static function ($sum, $item) use ($articleQuantities) {
        $quantity = $articleQuantities[(int) $item['id']] ?? 1;
        return $sum + ((float) $item['price'] * $quantity);
    }, 0.0);

    $pdo->beginTransaction();
    try {
        $status = 'en_attente';
        $stmtOrder = $pdo->prepare('INSERT INTO orders (user_id, total_price, status) VALUES (:user_id, :total_price, :status)');
        $stmtOrder->execute([
            ':user_id' => $userId,
            ':total_price' => $totalPrice,
            ':status' => $status,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, article_id, quantity, price) VALUES (:order_id, :article_id, :quantity, :price)');
        foreach ($dbArticles as $article) {
            $quantity = $articleQuantities[(int) $article['id']] ?? 1;
            $stmtItem->execute([
                ':order_id' => $orderId,
                ':article_id' => (int) $article['id'],
                ':quantity' => $quantity,
                ':price' => (float) $article['price'],
            ]);
        }

        $stmtReserve = $pdo->prepare('UPDATE articles SET product_status = :status WHERE id = :id AND product_status = :current_status');
        foreach ($dbArticles as $article) {
            $stmtReserve->execute([
                ':status' => 'reserved',
                ':id' => (int) $article['id'],
                ':current_status' => 'available',
            ]);
            if ($stmtReserve->rowCount() === 0) {
                throw new Exception('Un article est devenu indisponible durant la commande.');
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    $clientName = trim(($profile['prenom'] ?? '') . ' ' . ($profile['nom'] ?? '')) ?: ($_SESSION['username'] ?? 'Client');
    $whatsappNumber = '221775072936';
    $formattedTotal = number_format($totalPrice, 0, ',', ' ') . ' FCFA';
    $articlesList = implode(', ', array_map(static function ($article) use ($articleQuantities) {
        $quantity = $articleQuantities[(int) $article['id']] ?? 1;
        return ($article['title'] ?? 'Article') . ' x' . $quantity . ' (' . number_format((float) $article['price'], 0, ',', ' ') . ' FCFA)';
    }, $dbArticles));

    // Génération du message WhatsApp
    $whatsappMsg = "Bonjour Dribbleur Store,\n\n";
    $whatsappMsg .= "Je souhaite confirmer ma commande #$orderId.\n\n";
    $whatsappMsg .= "Client : $clientName\n";
    $whatsappMsg .= "Articles : $articlesList\n";
    $whatsappMsg .= "Total : $formattedTotal\n";
    $whatsappMsg .= "Ville : " . ($profile['ville'] ?? 'N/A') . "\n\n";
    $whatsappMsg .= "Merci de me confirmer la disponibilité.";

    $whatsappUrl = "https://wa.me/$whatsappNumber?text=" . urlencode($whatsappMsg);

    $_SESSION['cart_for_checkout'] = $dbArticles;
    $_SESSION['cart_total_price'] = $totalPrice;

    echo json_encode([
        'success' => true,
        'message' => 'Commande enregistrée ! Redirection vers WhatsApp...',
        'order_id' => $orderId,
        'whatsappUrl' => $whatsappUrl
    ]);
} catch (Exception $e) {
    error_log('Checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la preparation de la commande. Veuillez reessayer.']);
}
