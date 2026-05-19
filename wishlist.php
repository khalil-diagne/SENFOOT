<?php
require __DIR__ . '/config.php';
require_login();
ensure_store_schema();

$userId = current_user_id();
$items = [];

if ($userId) {
    $stmt = db()->prepare(
        'SELECT a.id, a.title, a.slug, a.price, a.image, a.platform, a.delivery_time, a.binding_status, a.product_status, a.content, a.created_at
         FROM wishlist_items w
         INNER JOIN articles a ON a.id = w.article_id
         WHERE w.user_id = :user_id
         ORDER BY w.created_at DESC'
    );
    $stmt->execute([':user_id' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Ma liste d envies - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --deep-bg: #020811;
            --card-bg: rgba(0, 20, 40, 0.82);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(0,255,136,0.12), transparent 22%),
                radial-gradient(circle at bottom right, rgba(0,207,255,0.14), transparent 26%),
                var(--deep-bg);
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            padding: 24px;
        }
        .shell {
            max-width: 1200px;
            margin: 0 auto;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(20px, 3vw, 32px);
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid rgba(0, 207, 255, 0.2);
            background: rgba(0,20,40,0.7);
            color: #e6fbff;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            border-color: var(--neon-blue);
            box-shadow: var(--glow-blue);
        }
        .hero {
            display: grid;
            gap: 18px;
            margin-bottom: 26px;
            padding: 24px;
            border-radius: 24px;
            border: 1px solid rgba(0, 207, 255, 0.14);
            background: rgba(0, 20, 40, 0.7);
            backdrop-filter: blur(14px);
        }
        .hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(22px, 4vw, 40px);
            line-height: 1.2;
            letter-spacing: 2px;
        }
        .hero p {
            color: rgba(255,255,255,0.68);
            max-width: 760px;
            line-height: 1.65;
            font-size: 16px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
        }
        .card {
            border: 1px solid rgba(0, 207, 255, 0.14);
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(16px);
            box-shadow: 0 18px 50px rgba(0,0,0,0.24);
        }
        .card img,
        .card-placeholder {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
        }
        .card-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0,207,255,0.08), rgba(0,255,136,0.08));
            color: rgba(255,255,255,0.34);
            font-size: 42px;
        }
        .card-body {
            padding: 18px;
            display: grid;
            gap: 10px;
            flex: 1;
        }
        .card-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 1.2px;
            line-height: 1.45;
        }
        .status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 7px 10px;
            border-radius: 999px;
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            border: 1px solid transparent;
        }
        .status.status-available { color: var(--neon-green); background: rgba(0,255,136,0.08); border-color: rgba(0,255,136,0.24); }
        .status.status-reserved { color: #ffb703; background: rgba(255,183,3,0.1); border-color: rgba(255,183,3,0.24); }
        .status.status-sold { color: #ff5d73; background: rgba(255,93,115,0.1); border-color: rgba(255,93,115,0.26); }
        .meta {
            color: rgba(255,255,255,0.56);
            font-size: 14px;
        }
        .price {
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            text-shadow: 0 0 10px rgba(0,255,136,0.32);
        }
        .desc {
            color: rgba(255,255,255,0.66);
            font-size: 14px;
            line-height: 1.55;
        }
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            border-color: transparent;
            box-shadow: var(--glow-green);
        }
        .empty-state {
            padding: 34px;
            text-align: center;
            border-radius: 24px;
            border: 1px dashed rgba(0,207,255,0.24);
            background: rgba(0,20,40,0.42);
            color: rgba(255,255,255,0.7);
        }
        .empty-state p {
            margin-top: 10px;
            color: rgba(255,255,255,0.5);
        }
        @media (max-width: 640px) {
            body { padding: 16px; }
            .hero { padding: 18px; }
            .card-actions .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div class="brand">Liste d envies</div>
            <div class="top-actions">
                <a class="btn" href="list_articles.php">Voir les articles</a>
                <a class="btn" href="accueil.php">Retour accueil</a>
            </div>
        </div>

        <section class="hero">
            <h1>Retrouve rapidement les comptes que tu veux surveiller ou acheter plus tard.</h1>
            <p>Ta liste d envies est liee a ton compte. Tu peux y conserver les meilleurs comptes, suivre leur statut en direct et revenir plus tard quand tu es pret a commander.</p>
        </section>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <strong>Aucun favori pour le moment</strong>
                <p>Ajoute des comptes a ta liste d envies depuis la page des articles ou depuis la fiche detail.</p>
            </div>
        <?php else: ?>
            <section class="cards">
                <?php foreach ($items as $item): ?>
                    <?php $statusMeta = article_status_meta($item['product_status'] ?? null); ?>
                    <article class="card">
                        <?php if (!empty($item['image'])): ?>
                            <img src="uploads/articles/<?= htmlspecialchars((string) $item['image']) ?>" alt="<?= htmlspecialchars((string) $item['title']) ?>">
                        <?php else: ?>
                            <div class="card-placeholder">⚽</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <span class="status <?= htmlspecialchars($statusMeta['class']) ?>"><?= htmlspecialchars($statusMeta['label']) ?></span>
                            <h2 class="card-title"><?= htmlspecialchars((string) $item['title']) ?></h2>
                            <div class="meta"><?= htmlspecialchars((string) ($item['platform'] ?? 'Multi')) ?> • <?= htmlspecialchars((string) ($item['delivery_time'] ?? 'Livraison a confirmer')) ?></div>
                            <div class="price"><?= number_format((float) $item['price'], 0, ',', ' ') ?> FCFA</div>
                            <p class="desc"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) $item['content']), 0, 130, '...')) ?></p>
                            <div class="card-actions">
                                <a class="btn btn-primary" href="list_articles.php">Commander</a>
                                <button class="btn" type="button" onclick="toggleWishlist(<?= (int) $item['id'] ?>, this)">Retirer</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>

    <script>
        function toggleWishlist(articleId, button) {
            fetch('wishlist_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Impossible de modifier la liste d envies.');
                    return;
                }

                const card = button.closest('.card');
                if (card) {
                    card.remove();
                }

                if (!document.querySelector('.card')) {
                    window.location.reload();
                }
            })
            .catch(() => {
                alert('Une erreur technique est survenue.');
            });
        }
    </script>
</body>
</html>
