<?php
require __DIR__ . '/../config.php';
require_seller();

$pdo = db();
$userId = current_user_id();

// Récupérer les commandes concernant les articles du vendeur
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            o.id as order_id,
            o.customer_name,
            o.customer_email,
            o.address,
            o.city,
            o.phone,
            o.created_at,
            o.total_price,
            COUNT(oi.id) as item_count,
            SUM(oi.quantity) as total_quantity
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN articles a ON oi.article_id = a.id
        WHERE a.author_user_id = :user_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total_orders,
               SUM(oi.quantity) as total_items,
               SUM(o.total_price) as total_revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN articles a ON oi.article_id = a.id
        WHERE a.author_user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Seller orders error: ' . $e->getMessage());
    die('Une erreur serveur est survenue.');
}

$profile = current_user_profile();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - Admin</title>
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
        }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
        }

        .orders-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(0, 207, 255, 0.08);
        }

        .orders-table tr:hover {
            background: rgba(0, 207, 255, 0.04);
        }

        .order-id {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: var(--neon-blue);
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .customer-name {
            font-weight: 600;
            color: #fff;
        }

        .customer-email {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(0, 207, 255, 0.1);
            border: 1px solid rgba(0, 207, 255, 0.2);
            color: var(--neon-blue);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .action-link:hover {
            background: rgba(0, 207, 255, 0.2);
            box-shadow: var(--glow-blue);
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

            .orders-table {
                font-size: 12px;
            }

            .orders-table td,
            .orders-table th {
                padding: 8px;
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
                <li><a href="seller_products.php">📦 Mes Produits</a></li>
                <li><a href="seller_orders.php" class="active">📋 Mes Commandes</a></li>
                <li><a href="seller_profile.php">👤 Mon Profil</a></li>
                <li><a href="../profile.php">🔙 Retour Profil</a></li>
            </ul>
        </aside>

        <main class="content">
            <div class="page-header">
                <h1 class="page-title">Mes Commandes</h1>
                <p class="page-subtitle">Suivi des ventes de vos articles</p>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">📋 Commandes Totales</div>
                    <div class="stat-value"><?= (int)($stats['total_orders'] ?? 0) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">📦 Articles Vendus</div>
                    <div class="stat-value"><?= (int)($stats['total_items'] ?? 0) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">💰 Chiffre d'Affaires</div>
                    <div class="stat-value"><?= number_format((float)($stats['total_revenue'] ?? 0), 0, ',', ' ') ?> FCFA</div>
                </div>
            </div>

            <!-- Liste des Commandes -->
            <div class="card">
                <?php if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Articles</th>
                                <th>Quantité</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><span class="order-id">#<?= $order['order_id'] ?></span></td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></span>
                                            <span class="customer-email"><?= htmlspecialchars($order['customer_email']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= (int)$order['item_count'] ?></td>
                                    <td><?= (int)$order['total_quantity'] ?></td>
                                    <td><?= number_format($order['total_price'], 0, ',', ' ') ?> FCFA</td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?= $order['order_id'] ?>" class="action-link" title="Voir les détails">👁️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-text">Aucune commande pour le moment</div>
                        <p style="color: rgba(255, 255, 255, 0.5); font-size: 14px;">Vos commandes apparaîtront ici une fois que vos articles seront vendus.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
