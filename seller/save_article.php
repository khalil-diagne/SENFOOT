<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_seller();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/seller/submit_article.php');
}

if (empty($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    die('Jeton CSRF invalide.');
}

$pdo      = db();
$userId   = current_user_id();
$username = $_SESSION['username'];

// ── Validation des champs ──────────────────────────────────────────────────
$title        = trim($_POST['title']        ?? '');
$content      = trim($_POST['content']      ?? '');
$price        = trim($_POST['price']        ?? '');
$platform     = trim($_POST['platform']     ?? 'Multi');
$deliveryTime = trim($_POST['delivery_time'] ?? 'Livraison immédiate');
$bindingStatus= trim($_POST['binding_status'] ?? 'Lie a un email');

$errors = [];

if ($title === '' || strlen($title) < 5) {
    $errors[] = 'Le titre doit faire au moins 5 caractères.';
}
if ($content === '' || strlen($content) < 20) {
    $errors[] = 'La description doit faire au moins 20 caractères.';
}
if (!is_numeric($price) || (float)$price < 0) {
    $errors[] = 'Le prix doit être un nombre positif.';
}

// ── Upload des images ──────────────────────────────────────────────────────
$allowedMime   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize       = 2 * 1024 * 1024; // 2 Mo
$uploadDir     = __DIR__ . '/../uploads/articles/';
$uploadUrlBase = BASE_URL . '/uploads/articles/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$savedImages = [];

if (!empty($_FILES['images']['name'][0])) {
    $files = $_FILES['images'];
    $count = min(count($files['name']), 5);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        if ($files['size'][$i] > $maxSize) {
            $errors[] = 'Image ' . ($i + 1) . ' dépasse 2 Mo.';
            continue;
        }
        $mime = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($mime, $allowedMime, true)) {
            $errors[] = 'Image ' . ($i + 1) . ' : format non autorisé (JPG/PNG/GIF/WEBP uniquement).';
            continue;
        }
        $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'article_' . $userId . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) {
            $savedImages[] = $filename;
        }
    }
}

if (empty($savedImages)) {
    $errors[] = 'Veuillez ajouter au moins une photo du compte.';
}

if ($errors !== []) {
    $msg = urlencode(implode(' | ', $errors));
    redirect('/seller/submit_article.php?error=' . $msg);
}

// ── Insertion BDD ──────────────────────────────────────────────────────────
// On stocke les images en JSON dans la colonne `image` (première) et `images` si elle existe
// Compatible avec la structure existante de la table articles
$imageJson   = json_encode($savedImages);
$firstImage  = $savedImages[0];

try {
    // Vérifie si la colonne `images` existe pour stocker le JSON multiple
    $cols = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'images'")->fetchAll();
    $hasImagesCol = count($cols) > 0;

    // Assure la colonne author_username
    $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_username` VARCHAR(120) NULL AFTER `seller_note`");
    $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_user_id` INT UNSIGNED NULL AFTER `author_username`");

    if ($hasImagesCol) {
        $stmt = $pdo->prepare(
            "INSERT INTO articles
             (title, content, price, image, images, platform, delivery_time, binding_status,
              product_status, approval_status, seller_note, author_username, author_user_id)
             VALUES
             (:title, :content, :price, :image, :images, :platform, :delivery_time, :binding_status,
              'available', 'pending', NULL, :author_username, :author_user_id)"
        );
        $stmt->execute([
            ':title'           => $title,
            ':content'         => $content,
            ':price'           => (float)$price,
            ':image'           => $firstImage,
            ':images'          => $imageJson,
            ':platform'        => $platform,
            ':delivery_time'   => $deliveryTime,
            ':binding_status'  => $bindingStatus,
            ':author_username' => $username,
            ':author_user_id'  => $userId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO articles
             (title, content, price, image, platform, delivery_time, binding_status,
              product_status, approval_status, seller_note, author_username, author_user_id)
             VALUES
             (:title, :content, :price, :image, :platform, :delivery_time, :binding_status,
              'available', 'pending', NULL, :author_username, :author_user_id)"
        );
        $stmt->execute([
            ':title'           => $title,
            ':content'         => $content,
            ':price'           => (float)$price,
            ':image'           => $firstImage,
            ':platform'        => $platform,
            ':delivery_time'   => $deliveryTime,
            ':binding_status'  => $bindingStatus,
            ':author_username' => $username,
            ':author_user_id'  => $userId,
        ]);
    }

    redirect('/seller/my_articles.php?success=1');

} catch (Throwable $e) {
    error_log('save_article error: ' . $e->getMessage());
    redirect('/seller/submit_article.php?error=' . urlencode('Erreur serveur lors de l\'enregistrement. Réessayez.'));
}
