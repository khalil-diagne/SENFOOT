<?php
/**
 * API pour recuperer les details d'un article.
 * Utilisee par la lightbox pour afficher les informations completes.
 */
require __DIR__ . '/config.php';
ensure_store_schema();

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID d\'article manquant']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, title, price, content, image, platform,
                delivery_time, binding_status, product_status, gallery_images, why_choose_us,
                approval_status, author_username, author_user_id
         FROM articles
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        http_response_code(404);
        echo json_encode(['error' => 'Article non trouve']);
        exit;
    }

    $role = $_SESSION['role'] ?? null;
    $userId = current_user_id();
    $isOwner = ($article['author_username'] ?? '') === ($_SESSION['username'] ?? '')
        || ((int) ($article['author_user_id'] ?? 0) > 0 && (int) ($article['author_user_id'] ?? 0) === (int) $userId);
    $isApproved = ($article['approval_status'] ?? 'approved') === 'approved'
        || empty($article['approval_status'])
        || empty($article['author_username']);

    if (!$isApproved && $role !== 'admin' && !$isOwner) {
        http_response_code(404);
        echo json_encode(['error' => 'Article non trouve']);
        exit;
    }

    $article['gallery_images'] = $article['gallery_images'] ? json_decode($article['gallery_images'], true) : [];
    $article['why_choose_us'] = $article['why_choose_us'] ? json_decode($article['why_choose_us'], true) : [];

    if (!is_array($article['gallery_images'])) {
        $article['gallery_images'] = [];
    }

    if (!is_array($article['why_choose_us'])) {
        $article['why_choose_us'] = [];
    }

    if (!empty($article['image']) && !in_array($article['image'], $article['gallery_images'], true)) {
        array_unshift($article['gallery_images'], $article['image']);
    }

    $article['gallery_images'] = array_values(array_unique(array_filter(array_map('strval', $article['gallery_images']))));
    $article['status_meta'] = article_status_meta($article['product_status'] ?? null);
    $article['is_favorite'] = false;

    if ($userId) {
        $stmtFavorite = $pdo->prepare('SELECT 1 FROM wishlist_items WHERE user_id = :user_id AND article_id = :article_id LIMIT 1');
        $stmtFavorite->execute([
            ':user_id' => $userId,
            ':article_id' => $id,
        ]);
        $article['is_favorite'] = (bool) $stmtFavorite->fetchColumn();
    }

    echo json_encode($article, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
