<?php
require __DIR__ . '/../config.php';
require_seller();

$pdo = db();
$userId = current_user_id();

// Récupérer les statistiques du vendeur
try {
    // Nombre d'articles
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_articles,
               SUM(CASE WHEN product_status = 'available' THEN 1 ELSE 0 END) as available,
               SUM(CASE WHEN product_status = 'reserved' THEN 1 ELSE 0 END) as reserved,
               SUM(CASE WHEN product_status = 'sold' THEN 1 ELSE 0 END) as sold
        FROM articles
        WHERE author_user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Nombre de commandes (articles vendus)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT oi.order_id) as total_orders,
               SUM(oi.quantity) as total_items_sold,
               SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN articles a ON oi.article_id = a.id
        WHERE a.author_user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $orders = $stmt->fetch(PDO::FETCH_ASSOC);

    // Articles récents
    $stmt = $pdo->prepare("
        SELECT id, title, price, product_status, approval_status, created_at
        FROM articles
        WHERE author_user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $userId]);
    $recentArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Seller dashboard error: ' . $e->getMessage());
    die('Une erreur serveur est survenue.');
}

$profile = current_user_profile();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Vendeur - Admin</title>
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
            margin-bottom: 32px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            letter-spacing: 2px;
            margin-bottom: 8px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-soft);
            font-size: 14px;
            letter-spacing: 1px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .stat-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--neon-green);
            margin-bottom: 8px;
        }

        .stat-detail {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
        }

        .section {
            margin-bottom: 32px;
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            letter-spacing: 1.5px;
            margin-bottom: 16px;
            color: #fff;
        }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .articles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .articles-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
        }

        .articles-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(0, 207, 255, 0.08);
        }

        .articles-table tr:hover {
            background: rgba(0, 207, 255, 0.04);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: rgba(0, 255, 136, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .status-reserved {
            background: rgba(255, 183, 3, 0.15);
            color: #ffb703;
            border: 1px solid rgba(255, 183, 3, 0.3);
        }

        .status-sold {
            background: rgba(255, 68, 102, 0.15);
            color: var(--neon-red);
            border: 1px solid rgba(255, 68, 102, 0.3);
        }

        .status-pending {
            background: rgba(0, 207, 255, 0.15);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 207, 255, 0.3);
        }

        .action-links {
            display: flex;
            gap: 8px;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(0, 207, 255, 0.1);
            border: 1px solid rgba(0, 207, 255, 0.2);
            color: var(--neon-blue);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
        }

        .action-link:hover {
            background: rgba(0, 207, 255, 0.2);
            box-shadow: var(--glow-blue);
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

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-soft);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state-text {
            font-size: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .stats-grid {
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
                <li><a href="seller_dashboard.php" class="active">📊 Tableau de Bord</a></li>
                <li><a href="seller_products.php">📦 Mes Produits</a></li>
                <li><a href="seller_orders.php">📋 Mes Commandes</a></li>
                <li><a href="seller_profile.php">👤 Mon Profil</a></li>
                <li><a href="../profile.php">🔙 Retour Profil</a></li>
            </ul>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1 class="page-title">Tableau de Bord</h1>
                <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($profile['prenom'] ?? 'Vendeur') ?></p>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📦 Articles Totaux</div>
                    <div class="stat-value"><?= (int)($stats['total_articles'] ?? 0) ?></div>
                    <div class="stat-detail">
                        <span style="color: var(--neon-green);"><?= (int)($stats['available'] ?? 0) ?></span> disponibles
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">🛒 Commandes</div>
                    <div class="stat-value"><?= (int)($orders['total_orders'] ?? 0) ?></div>
                    <div class="stat-detail">
                        <span style="color: var(--neon-blue);"><?= (int)($orders['total_items_sold'] ?? 0) ?></span> articles vendus
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">💰 Chiffre d'Affaires</div>
                    <div class="stat-value"><?= number_format((float)($orders['total_revenue'] ?? 0), 0, ',', ' ') ?> FCFA</div>
                    <div class="stat-detail">Revenus totaux</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">⏳ En Attente</div>
                    <div class="stat-value"><?= (int)($stats['reserved'] ?? 0) ?></div>
                    <div class="stat-detail">Articles réservés</div>
                </div>
            </div>

            <!-- Articles Récents -->
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 class="section-title">Articles Récents</h2>
                    <a href="article_new.php" class="btn-primary">+ Nouvel Article</a>
                </div>

                <div class="card">
                    <?php if (count($recentArticles) > 0): ?>
                        <table class="articles-table">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Approbation</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArticles as $article): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($article['title'], 0, 40)) ?></td>
                                        <td><?= number_format($article['price'], 0, ',', ' ') ?> FCFA</td>
                                        <td>
                                            <?php $stockMeta = article_status_meta($article['product_status'] ?? null); ?>
                                            <span class="status-badge status-<?= htmlspecialchars($stockMeta['value']) ?>">
                                                <?= htmlspecialchars($stockMeta['label']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= ($article['approval_status'] === 'approved' ? 'available' : 'pending') ?>">
                                                <?= htmlspecialchars(ucfirst($article['approval_status'] ?? 'pending')) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                                        <td>
                                            <div class="action-links">
                                                <a href="edit_article.php?id=<?= $article['id'] ?>" class="action-link" title="Éditer">✏️</a>
                                                <a href="seller_products.php?delete=<?= $article['id'] ?>" class="action-link" title="Supprimer" onclick="return confirm('Êtes-vous sûr ?')">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <div class="empty-state-text">Aucun article pour le moment</div>
                            <a href="article_new.php" class="btn-primary">Créer un article</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
