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

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
$platform = trim((string) ($_POST['platform'] ?? 'Multi'));
$deliveryTime = trim((string) ($_POST['delivery_time'] ?? 'Livraison a confirmer'));
$bindingStatus = trim((string) ($_POST['binding_status'] ?? 'Lie a un email'));
$productStatus = article_status_meta($_POST['product_status'] ?? 'available')['value'];
$articleId = isset($_POST['article_id']) ? (int) $_POST['article_id'] : 0;
$isEdit = $articleId > 0;

if ($title === '' || $content === '' || $price === false || $price < 0) {
    die('Titre, contenu et prix valides sont requis');
}

try {
    $pdo = db();

    $currentImage = null;
    $currentGallery = [];
    if ($isEdit) {
        $stmtCurrent = $pdo->prepare('SELECT image, gallery_images, author_username, author_user_id, approval_status FROM articles WHERE id = :id LIMIT 1');
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

    $uploadResult = store_article_uploaded_images($_FILES['images'] ?? []);
    $uploadedImages = $uploadResult['stored'];
    $uploadErrors = $uploadResult['errors'];
    $uploadedCount = count($uploadedImages);

    if ($uploadErrors !== []) {
        $errorSummary = implode(' | ', $uploadErrors);
        if ($uploadedCount < 1) {
            throw new RuntimeException($errorSummary);
        }
        $_SESSION['article_upload_warnings'] = $errorSummary;
    }

    if (!$isEdit && $uploadedCount < 1) {
        throw new RuntimeException('Ajoutez au moins 1 photo pour chaque article (JPG, PNG, GIF ou WEBP, max 5 Mo).');
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
        $currentApproval = (string) ($currentArticle['approval_status'] ?? 'pending');
        $approvalStatus = $role === 'admin'
            ? 'approved'
            : (in_array($currentApproval, ['approved', 'pending', 'rejected'], true) ? $currentApproval : 'pending');
    } else {
        $approvalStatus = $role === 'admin' ? 'approved' : 'pending';
    }

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
        redirect('/seller/my_articles.php?success=1');
    }
} catch (RuntimeException $e) {
    die($e->getMessage());
} catch (PDOException $e) {
    error_log('Save article error: ' . $e->getMessage());
    die('Une erreur serveur est survenue lors de l\'enregistrement de l\'article.');
}
