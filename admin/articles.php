<?php
require __DIR__ . '/../config.php';
require_admin();
ensure_store_schema();
$pdo = db();

$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Jeton de sécurité invalide.';
    } else {
        $article_id = $_POST['article_id'] ?? null;
        if ($article_id) {
            $article_id = (int) $article_id;

            $pdo->beginTransaction();
            try {
                $stmtOrders = $pdo->prepare('SELECT DISTINCT order_id FROM order_items WHERE article_id = :id');
                $stmtOrders->execute([':id' => $article_id]);
                $linkedOrders = $stmtOrders->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($linkedOrders)) {
                    $stmtDeleteItems = $pdo->prepare('DELETE FROM order_items WHERE article_id = :id');
                    $stmtDeleteItems->execute([':id' => $article_id]);

                    $stmtSum = $pdo->prepare('SELECT COALESCE(SUM(price * quantity), 0) FROM order_items WHERE order_id = :order_id');
                    $stmtUpdateOrder = $pdo->prepare('UPDATE orders SET total_price = :total_price WHERE id = :order_id');
                    $stmtCancelEmptyOrder = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :order_id');

                    foreach ($linkedOrders as $orderId) {
                        $stmtSum->execute([':order_id' => $orderId]);
                        $newTotal = (float) $stmtSum->fetchColumn();
                        $stmtUpdateOrder->execute([':total_price' => $newTotal, ':order_id' => $orderId]);

                        if ($newTotal <= 0) {
                            $stmtCancelEmptyOrder->execute([':status' => 'annulee', ':order_id' => $orderId]);
                        }
                    }
                }

                $stmtImg = $pdo->prepare('SELECT image FROM articles WHERE id = :id');
                $stmtImg->execute([':id' => $article_id]);
                $image_name = $stmtImg->fetchColumn();

                $stmtDelete = $pdo->prepare('DELETE FROM articles WHERE id = :id');
                $stmtDelete->execute([':id' => $article_id]);

                $imagePath = __DIR__ . '/../uploads/articles/' . $image_name;
                if ($image_name && file_exists($imagePath)) {
                    @unlink($imagePath);
                }

                $pdo->commit();
                $message = 'Article supprimé avec succès.';
                if (!empty($linkedOrders)) {
                    $message .= ' Les références de commande associées ont été mises à jour.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Erreur lors de la suppression de l article : ' . $e->getMessage();
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$articles = $pdo->query('SELECT id, title, price, image, author_username, product_status, created_at FROM articles ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - Admin · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --panel-strong: rgba(3, 14, 28, 0.95);
            --border: rgba(0, 207, 255, 0.16);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
            --sidebar-w: 260px;
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
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
            height: 100vh;
            position: sticky;
            top: 0;
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-desc {
            color: var(--text-soft);
            font-size: 16px;
        }

        .btn-add {
            padding: 14px 24px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 2px;
            text-decoration: none;
            text-transform: uppercase;
            box-shadow: var(--glow-green);
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.6);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: alertIn 0.4s ease;
        }

        @keyframes alertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.2); color: var(--neon-green); }
        .alert-error { background: rgba(255, 68, 102, 0.08); border: 1px solid rgba(255, 68, 102, 0.2); color: var(--neon-red); }

        .table-wrap {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            backdrop-filter: blur(18px);
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 2px;
            color: rgba(255, 255, 255, 0.4);
            padding: 18px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-transform: uppercase;
        }

        tbody td {
            padding: 16px 20px;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            transition: background 0.2s;
            vertical-align: middle;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .article-cell {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .article-img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--border);
            background: #000;
        }

        .article-title {
            font-weight: 600;
            color: #fff;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .article-meta {
            font-size: 12px;
            color: var(--text-soft);
            display: flex;
            gap: 10px;
        }

        .price-tag {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: var(--neon-green);
            font-size: 14px;
        }

        .status-badge {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .status-available { background: rgba(0, 255, 136, 0.1); border-color: var(--neon-green); color: var(--neon-green); }
        .status-reserved { background: rgba(255, 183, 3, 0.1); border-color: #ffb703; color: #ffb703; }
        .status-sold { background: rgba(255, 68, 102, 0.1); border-color: var(--neon-red); color: var(--neon-red); }

        .actions-cell {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            text-decoration: none;
            text-transform: uppercase;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: rgba(0, 207, 255, 0.1);
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
        }

        .btn-edit:hover {
            background: var(--neon-blue);
            color: #001a0d;
            box-shadow: var(--glow-blue);
        }

        .btn-delete {
            background: rgba(255, 68, 102, 0.1);
            border: 1px solid var(--neon-red);
            color: var(--neon-red);
            cursor: pointer;
        }

        .btn-delete:hover {
            background: var(--neon-red);
            color: #fff;
            box-shadow: var(--glow-red);
        }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; }
            .admin-sidebar { display: none; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Gestion des Articles</h1>
                    <p class="page-desc">Gérez l'inventaire de la boutique, modifiez les prix ou retirez des produits.</p>
                </div>
                <a href="article_new.php" class="btn-add">
                    <span>➕ Nouvel Article</span>
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Article</th>
                            <th>Prix</th>
                            <th>Vendeur</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:50px; opacity:0.5;">Aucun article trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $a): ?>
                                <tr>
                                    <td>
                                        <div class="article-cell">
                                            <?php if ($a['image']): ?>
                                                <img src="../uploads/articles/<?= htmlspecialchars($a['image']) ?>" class="article-img" alt="">
                                            <?php else: ?>
                                                <div class="article-img" style="display:flex; align-items:center; justify-content:center; opacity:0.3;">🖼️</div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="article-title"><?= htmlspecialchars($a['title']) ?></div>
                                                <div class="article-meta">
                                                    <span>ID: #<?= $a['id'] ?></span>
                                                    <span>•</span>
                                                    <span><?= date('d/m/Y', strtotime($a['created_at'])) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price-tag"><?= number_format($a['price'], 0, ',', ' ') ?> FCFA</div>
                                    </td>
                                    <td>
                                        <div style="font-size:13px; opacity:0.7;">
                                            <?= $a['author_username'] ? '@' . htmlspecialchars($a['author_username']) : '<span style="color:var(--neon-red);">Admin</span>' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $a['product_status'] ?>">
                                            <?= $a['product_status'] === 'available' ? 'Disponible' : ($a['product_status'] === 'reserved' ? 'Réservé' : 'Vendu') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="edit_article.php?id=<?= $a['id'] ?>" class="btn-action btn-edit">Modifier</a>
                                            <form method="POST" onsubmit="return confirm('Supprimer cet article définitivement ?');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="article_id" value="<?= $a['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn-action btn-delete">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
