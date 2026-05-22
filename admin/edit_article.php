<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_seller();

$pdo = db();
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($articleId <= 0) {
    redirect('/admin/articles.php');
}

$stmt = $pdo->prepare('SELECT id, title, content, price, platform, delivery_time, binding_status, product_status, image, gallery_images, author_username, author_user_id FROM articles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $articleId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    redirect('/admin/articles.php');
}

$role = $_SESSION['role'] ?? null;
$currentUserId = current_user_id();
$isOwner = ($article['author_username'] ?? '') === ($_SESSION['username'] ?? '')
    || ((int) ($article['author_user_id'] ?? 0) > 0 && (int) ($article['author_user_id'] ?? 0) === (int) $currentUserId);

if ($role !== 'admin' && !$isOwner) {
    redirect('/accueil.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$galleryImages = [];
if (!empty($article['gallery_images'])) {
    $decodedGallery = json_decode($article['gallery_images'], true);
    if (is_array($decodedGallery)) {
        $galleryImages = array_values(array_filter(array_map('strval', $decodedGallery)));
    }
}
if (empty($galleryImages) && !empty($article['image'])) {
    $galleryImages = [(string) $article['image']];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier article - Admin</title>
    <link rel="stylesheet" href="../style/admin_styles.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="admin-content">
            <h1>Modifier l'article</h1>

            <form action="../save_article.php" method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="article_id" value="<?php echo (int) $article['id']; ?>">
                <div class="form-group">
                    <label for="title">Titre de l'article</label>
                    <input type="text" id="title" name="title" required maxlength="255" value="<?php echo htmlspecialchars((string) $article['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="content">Contenu</label>
                    <textarea id="content" name="content" rows="8" required><?php echo htmlspecialchars((string) $article['content']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Prix (en FCFA)</label>
                    <input type="number" id="price" name="price" required step="1" min="0" value="<?php echo htmlspecialchars((string) $article['price']); ?>">
                </div>

                <div class="form-group">
                    <label for="platform">Plateforme</label>
                    <input type="text" id="platform" name="platform" maxlength="50" value="<?php echo htmlspecialchars((string) ($article['platform'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="product_status">Statut produit</label>
                    <select id="product_status" name="product_status">
                        <?php $statusValue = article_status_meta($article['product_status'] ?? null)['value']; ?>
                        <option value="available" <?php echo $statusValue === 'available' ? 'selected' : ''; ?>>Disponible</option>
                        <option value="reserved" <?php echo $statusValue === 'reserved' ? 'selected' : ''; ?>>Reserve</option>
                        <option value="sold" <?php echo $statusValue === 'sold' ? 'selected' : ''; ?>>Vendu</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="delivery_time">Delai de livraison</label>
                    <input type="text" id="delivery_time" name="delivery_time" maxlength="100" value="<?php echo htmlspecialchars((string) ($article['delivery_time'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="binding_status">Statut de liaison</label>
                    <input type="text" id="binding_status" name="binding_status" maxlength="255" value="<?php echo htmlspecialchars((string) ($article['binding_status'] ?? '')); ?>">
                </div>

                <?php if (!empty($galleryImages)): ?>
                    <div class="form-group">
                        <label>Photos actuelles</label>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;">
                            <?php foreach ($galleryImages as $image): ?>
                                <img src="../uploads/articles/<?php echo htmlspecialchars($image); ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:12px;display:block;">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="images">Ajouter d autres photos</label>
                    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                    <p style="margin-top:8px;color:#666;font-size:13px;">Les nouvelles photos seront ajoutees aux photos actuelles. Laissez vide si vous ne voulez rien ajouter.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="admin-btn-primary">Enregistrer les modifications</button>
                    <a href="<?= ($_SESSION['role'] ?? null) === 'admin' ? 'articles.php' : '../seller/my_articles.php' ?>" class="admin-btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
