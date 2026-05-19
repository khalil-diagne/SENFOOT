<?php
require __DIR__ . '/../config.php';
require_seller();

$pdo = db();
$userId = current_user_id();

// Supprimer un article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id AND author_user_id = :user_id");
        $stmt->execute([':id' => $deleteId, ':user_id' => $userId]);
        header('Location: seller_products.php?deleted=1');
        exit();
    } catch (PDOException $e) {
        error_log('Delete article error: ' . $e->getMessage());
    }
}

// Récupérer les articles du vendeur
try {
    $stmt = $pdo->prepare("
        SELECT id, title, price, binding_status, approval_status, created_at, gallery_images
        FROM articles
        WHERE author_user_id = :user_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Seller products error: ' . $e->getMessage());
    die('Une erreur serveur est survenue.');
}

$profile = current_user_profile();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Produits - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --border: rgba(0, 207, 255, 0.16);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Rajdhani', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(0, 255, 136, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(0, 207, 255, 0.12), transparent 28%),
                var(--deep-bg);
            color: #fff;
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 207, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 207, 255, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.55;
            pointer-events: none;
            z-index: 0;
        }

        .layout {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
        }

        .admin-sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 2px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .admin-sidebar ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .admin-sidebar a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.72);
            background: transparent;
            border: 1px solid transparent;
            transition: 0.25s ease;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            color: #fff;
            border-color: rgba(0, 207, 255, 0.14);
            background: rgba(0, 207, 255, 0.08);
            box-shadow: inset 0 0 0 1px rgba(0, 207, 255, 0.06);
        }

        .content {
            padding: 32px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-green);
        }

        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.25);
            color: var(--neon-green);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .product-card:hover {
            border-color: rgba(0, 207, 255, 0.3);
            box-shadow: var(--glow-blue);
            transform: translateY(-4px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: rgba(0, 207, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 20px;
        }

        .product-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 12px;
            color: #fff;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--neon-green);
            margin-bottom: 12px;
        }

        .product-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .badge-available {
            background: rgba(0, 255, 136, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .badge-reserved {
            background: rgba(255, 183, 3, 0.15);
            color: #ffb703;
            border: 1px solid rgba(255, 183, 3, 0.3);
        }

        .badge-sold {
            background: rgba(255, 68, 102, 0.15);
            color: var(--neon-red);
            border: 1px solid rgba(255, 68, 102, 0.3);
        }

        .badge-pending {
            background: rgba(0, 207, 255, 0.15);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 207, 255, 0.3);
        }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(0, 207, 255, 0.2);
            background: rgba(0, 207, 255, 0.1);
            color: var(--neon-blue);
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: rgba(0, 207, 255, 0.2);
            box-shadow: var(--glow-blue);
        }

        .action-btn.delete {
            border-color: rgba(255, 68, 102, 0.2);
            background: rgba(255, 68, 102, 0.1);
            color: var(--neon-red);
        }

        .action-btn.delete:hover {
            background: rgba(255, 68, 102, 0.2);
            box-shadow: 0 0 20px rgba(255, 68, 102, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state-text {
            font-size: 16px;
            color: var(--text-soft);
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .page-header {
                flex-direction: column;
                gap: 16px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <aside class="admin-sidebar">
            <h2>VENDEUR</h2>
            <ul>
                <li><a href="seller_dashboard.php">📊 Tableau de Bord</a></li>
                <li><a href="seller_products.php" class="active">📦 Mes Produits</a></li>
                <li><a href="seller_orders.php">📋 Mes Commandes</a></li>
                <li><a href="seller_profile.php">👤 Mon Profil</a></li>
                <li><a href="../profile.php">🔙 Retour Profil</a></li>
            </ul>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1 class="page-title">Mes Produits</h1>
                <a href="article_new.php" class="btn-primary">+ Nouvel Article</a>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">✓ Article supprimé avec succès</div>
            <?php endif; ?>

            <?php if (count($articles) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($articles as $article): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php
                                $images = !empty($article['gallery_images']) ? json_decode($article['gallery_images'], true) : [];
                                if (!empty($images) && !empty($images[0])):
                                ?>
                                    <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php else: ?>
                                    📦
                                <?php endif; ?>
                            </div>

                            <div class="product-info">
                                <div class="product-title"><?= htmlspecialchars($article['title']) ?></div>
                                <div class="product-price"><?= number_format($article['price'], 0, ',', ' ') ?> FCFA</div>

                                <div class="product-meta">
                                    <span class="badge badge-<?= htmlspecialchars($article['binding_status'] ?? 'available') ?>">
                                        <?= htmlspecialchars(ucfirst($article['binding_status'] ?? 'available')) ?>
                                    </span>
                                    <span class="badge badge-<?= ($article['approval_status'] === 'approved' ? 'available' : 'pending') ?>">
                                        <?= htmlspecialchars(ucfirst($article['approval_status'] ?? 'pending')) ?>
                                    </span>
                                </div>

                                <div class="product-actions">
                                    <a href="edit_article.php?id=<?= $article['id'] ?>" class="action-btn">✏️ Éditer</a>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="delete_id" value="<?= $article['id'] ?>">
                                        <button type="submit" class="action-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')">🗑️ Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">Aucun produit pour le moment</div>
                    <a href="article_new.php" class="btn-primary">Créer votre premier article</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
