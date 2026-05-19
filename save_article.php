<?php
require __DIR__ . '/config.php';
ensure_store_schema();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/article_new.php');
}

// Autoriser Admin ET Vendeur
$role = $_SESSION['role'] ?? null;
if (empty($_SESSION['logged']) || ($role !== 'admin' && $role !== 'seller')) {
    redirect('/index.php');
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    die('Jeton CSRF invalide');
}

function decodeGalleryImages(?string $json): array
{
    if (!$json) {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $decoded)));
}

function uploadedImageCount(array $files): int
{
    if (!isset($files['error']) || !is_array($files['error'])) {
        return 0;
    }

    $count = 0;
    foreach ($files['error'] as $error) {
        if ($error !== UPLOAD_ERR_NO_FILE) {
            $count++;
        }
    }

    return $count;
}

function storeUploadedImages(array $files, string $destDir): array
{
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    $stored = [];

    if (!isset($files['error']) || !is_array($files['error'])) {
        return $stored;
    }

    foreach ($files['error'] as $index => $error) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur upload image');
        }

        $tmpName = $files['tmp_name'][$index] ?? '';
        $size = (int) ($files['size'][$index] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier image invalide');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Type de fichier non autorisé');
        }

        if ($size > 2 * 1024 * 1024) {
            throw new RuntimeException('Fichier trop volumineux (max 2MB)');
        }

        $fileName = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $destPath = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($tmpName, $destPath)) {
            throw new RuntimeException('Impossible de déplacer le fichier');
        }

        $stored[] = $fileName;
    }

    return $stored;
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
$platform = trim((string) ($_POST['platform'] ?? 'Multi'));
$deliveryTime = trim((string) ($_POST['delivery_time'] ?? 'Livraison a confirmer'));
$bindingStatus = trim((string) ($_POST['binding_status'] ?? 'Lie a un email'));
$productStatus = article_status_meta($_POST['product_status'] ?? 'available')['value'];
$articleId = isset($_POST['article_id']) ? (int) $_POST['article_id'] : 0;
$isEdit = $articleId > 0;

// Statut d'approbation : 'approved' pour admin, 'pending' pour vendeur
$approvalStatus = ($role === 'admin') ? 'approved' : 'pending';

if ($title === '' || $content === '' || $price === false || $price < 0) {
    die('Titre, contenu et prix valides sont requis');
}

$destDir = __DIR__ . '/uploads/articles';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

try {
    $pdo = db();

    $currentImage = null;
    $currentGallery = [];
    if ($isEdit) {
        $stmtCurrent = $pdo->prepare('SELECT image, gallery_images, author_username, author_user_id FROM articles WHERE id = :id LIMIT 1');
        $stmtCurrent->execute([':id' => $articleId]);
        $currentArticle = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
        if (!$currentArticle) {
            die('Article introuvable');
        }

        // Sécurité : un vendeur ne peut éditer que ses propres articles
        $currentUserId = current_user_id();
        $isOwner = ($currentArticle['author_username'] ?? '') === ($_SESSION['username'] ?? '')
            || ((int) ($currentArticle['author_user_id'] ?? 0) > 0 && (int) ($currentArticle['author_user_id'] ?? 0) === (int) $currentUserId);
        if ($role === 'seller' && !$isOwner) {
            die('Accès non autorisé');
        }

        $currentImage = $currentArticle['image'] ?? null;
        $currentGallery = decodeGalleryImages($currentArticle['gallery_images'] ?? null);
        if (empty($currentGallery) && !empty($currentImage)) {
            $currentGallery = [$currentImage];
        }
    }

    $uploadedImages = storeUploadedImages($_FILES['images'] ?? [], $destDir);
    $uploadedCount = count($uploadedImages);

    if (!$isEdit && $uploadedCount < 1) {
        throw new RuntimeException('Ajoutez au moins 1 photo pour chaque article.');
    }

    $galleryImages = $isEdit
        ? array_values(array_merge($currentGallery, $uploadedImages))
        : $uploadedImages;
    
    // Limiter a 6 photos maximum
    if (count($galleryImages) > 6) {
        $galleryImages = array_slice($galleryImages, 0, 6);
    }
    
    if (count($galleryImages) < 1) {
        throw new RuntimeException('Chaque article doit contenir au moins 1 photo.');
    }

    $mainImage = $galleryImages[0];

    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($title)));
    $slug = trim($slug, '-');
    $base = $slug;
    $i = 1;
    while (true) {
        $stmtSlug = $pdo->prepare('SELECT id FROM articles WHERE slug = :slug LIMIT 1');
        $stmtSlug->execute([':slug' => $slug]);
        $existingId = $stmtSlug->fetchColumn();

        if ($existingId === false || (int) $existingId === $articleId) {
            break;
        }

        $slug = $base . '-' . $i;
        $i++;
    }

    $galleryJson = json_encode($galleryImages, JSON_UNESCAPED_UNICODE);

    if ($isEdit) {
        $stmt = $pdo->prepare('UPDATE articles SET title = :t, slug = :s, content = :c, price = :p, platform = :platform, delivery_time = :delivery_time, binding_status = :binding_status, product_status = :product_status, image = :img, gallery_images = :gallery, approval_status = :approval, seller_note = NULL WHERE id = :id');
        $stmt->execute([
            ':t' => $title,
            ':s' => $slug,
            ':c' => $content,
            ':p' => $price,
            ':platform' => $platform,
            ':delivery_time' => $deliveryTime,
            ':binding_status' => $bindingStatus,
            ':product_status' => $productStatus,
            ':img' => $mainImage,
            ':gallery' => $galleryJson,
            ':approval' => $approvalStatus,
            ':id' => $articleId,
        ]);

    } else {
        $stmt = $pdo->prepare('INSERT INTO articles (title, slug, content, price, platform, delivery_time, binding_status, product_status, image, gallery_images, author_username, author_user_id, approval_status) VALUES (:t, :s, :c, :p, :platform, :delivery_time, :binding_status, :product_status, :img, :gallery, :author, :author_user_id, :approval)');
        $stmt->execute([
            ':t' => $title,
            ':s' => $slug,
            ':c' => $content,
            ':p' => $price,
            ':platform' => $platform,
            ':delivery_time' => $deliveryTime,
            ':binding_status' => $bindingStatus,
            ':product_status' => $productStatus,
            ':img' => $mainImage,
            ':gallery' => $galleryJson,
            ':author' => $_SESSION['username'],
            ':author_user_id' => current_user_id(),
            ':approval' => $approvalStatus,
        ]);
    }

    if ($role === 'admin') {
        redirect('/admin/articles.php');
    } else {
        redirect('/accueil.php?article_submitted=1');
    }
} catch (RuntimeException $e) {
    die($e->getMessage());
} catch (PDOException $e) {
    error_log('Save article error: ' . $e->getMessage());
    die('Une erreur serveur est survenue lors de l\'enregistrement de l\'article.');
}
