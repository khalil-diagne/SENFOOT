<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_seller();

$pdo      = db();
$userId   = current_user_id();
$username = $_SESSION['username'];
$role     = $_SESSION['role'] ?? 'seller';

// Stats du vendeur
$totalArticles  = (int)$pdo->prepare('SELECT COUNT(*) FROM articles WHERE author_username = :u')->execute([':u'=>$username]) ? 0 : 0;
try {
    $stmtStats = $pdo->prepare('SELECT
        COUNT(*) as total,
        SUM(approval_status = "approved") as approved,
        SUM(approval_status = "pending")  as pending,
        SUM(approval_status = "rejected") as rejected
        FROM articles WHERE author_username = :u');
    $stmtStats->execute([':u' => $username]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $stats = ['total'=>0,'approved'=>0,'pending'=>0,'rejected'=>0]; }

// Vérification KYC
try {
    $stmtKyc = $pdo->prepare('SELECT seller_verified, seller_id_type FROM visiteur WHERE username = :u');
    $stmtKyc->execute([':u' => $username]);
    $kyc = $stmtKyc->fetch(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $kyc = ['seller_verified'=>0,'seller_id_type'=>'']; }

// Derniers articles
try {
    $stmtLast = $pdo->prepare('SELECT id, title, approval_status, product_status, created_at FROM articles WHERE author_username = :u ORDER BY created_at DESC LIMIT 5');
    $stmtLast->execute([':u' => $username]);
    $lastArticles = $stmtLast->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $lastArticles = []; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendeur · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;--neon-gold:#ffb703;--deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);--sidebar-w:240px;--nav-h:70px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.03) 1px,transparent 1px);background-size:50px 50px;animation:gridMove 10s linear infinite;z-index:0;pointer-events:none;}
        @keyframes gridMove{from{background-position:0 0}to{background-position:50px 50px}}
        .orb{position:fixed;border-radius:50%;filter:blur(90px);opacity:0.14;z-index:0;pointer-events:none;}
        .orb-1{width:400px;height:400px;background:#ffb703;top:-100px;left:-100px;}
        .orb-2{width:320px;height:320px;background:#00cfff;bottom:-80px;right:-80px;}
        .layout{display:flex;min-height:100vh;position:relative;z-index:2;}
        /* SIDEBAR */
        .sidebar{width:var(--sidebar-w);flex-shrink:0;background:rgba(2,8,17,0.92);border-right:1px solid rgba(255,183,3,0.15);backdrop-filter:blur(20px);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;}
        .sidebar-logo{padding:22px 20px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;flex-direction:column;gap:4px;}
        .sidebar-logo-text{font-family:'Orbitron',sans-serif;font-weight:900;font-size:13px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-gold),var(--neon-green));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .seller-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(255,183,3,0.12);border:1px solid rgba(255,183,3,0.25);border-radius:6px;color:var(--neon-gold);font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;width:fit-content;}
        .sidebar-nav{flex:1;padding:20px 12px;display:flex;flex-direction:column;gap:4px;}
        .nav-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.22);padding:10px 8px 5px;}
        .nav-item{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,0.5);font-size:14px;transition:all 0.25s;position:relative;}
        .nav-item:hover{color:#fff;background:rgba(255,255,255,0.05);border:1px solid rgba(255,183,3,0.12);}
        .nav-item.active{color:var(--neon-gold);border:1px solid rgba(255,183,3,0.2);background:rgba(255,183,3,0.06);}
        .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2px;background:var(--neon-gold);border-radius:2px;box-shadow:0 0 8px var(--neon-gold);}
        .nav-icon{font-size:16px;width:20px;text-align:center;}
        .sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.05);}
        .logout-btn{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,68,102,0.7);font-size:14px;transition:all 0.25s;width:100%;}
        .logout-btn:hover{color:var(--neon-red);background:rgba(255,68,102,0.08);}
        /* MAIN */
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
        .topbar{height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,183,3,0.1);position:sticky;top:0;z-index:40;}
        .topbar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-gold),transparent);opacity:0.4;}
        .page-title-row{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:rgba(255,255,255,0.45);}
        .topbar-user{display:flex;align-items:center;gap:10px;padding:8px 16px;border:1px solid rgba(255,183,3,0.2);border-radius:8px;background:rgba(255,183,3,0.05);font-size:13px;}
        .gold-dot{width:8px;height:8px;border-radius:50%;background:var(--neon-gold);box-shadow:0 0 8px var(--neon-gold);animation:pulse 2s ease-in-out infinite;}
        @keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.3;}}
        .content{padding:32px 30px 60px;flex:1;}
        .page-heading{font-family:'Orbitron',sans-serif;font-weight:900;font-size:26px;letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px;}
        .page-sub{color:rgba(255,255,255,0.32);font-size:14px;letter-spacing:1px;margin-bottom:28px;}
        /* KYC ALERT */
        .kyc-alert{padding:14px 18px;border-radius:12px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:14px;}
        .kyc-pending{background:rgba(255,183,3,0.08);border:1px solid rgba(255,183,3,0.25);color:var(--neon-gold);}
        .kyc-ok{background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.22);color:var(--neon-green);}
        /* STATS */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px;}
        .stat-card{background:var(--card-bg);border-radius:14px;padding:20px 16px;backdrop-filter:blur(14px);transition:transform 0.2s;}
        .stat-card:hover{transform:translateY(-4px);}
        .stat-icon{font-size:22px;margin-bottom:8px;}
        .stat-val{font-family:'Orbitron',sans-serif;font-weight:900;font-size:28px;}
        .stat-lbl{font-size:12px;color:rgba(255,255,255,0.38);letter-spacing:1px;margin-top:2px;}
        /* TABLE */
        .panel{background:var(--card-bg);border:1px solid rgba(0,207,255,0.1);border-radius:16px;padding:24px;backdrop-filter:blur(14px);}
        .panel::before{content:'';display:block;height:2px;background:linear-gradient(90deg,transparent,var(--neon-gold),transparent);margin:-24px -24px 20px;border-radius:16px 16px 0 0;}
        .panel-title{font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:2px;color:var(--neon-gold);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        table{width:100%;border-collapse:collapse;}
        thead th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.28);padding:10px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05);}
        tbody td{padding:12px 14px;font-size:14px;color:rgba(255,255,255,0.7);border-bottom:1px solid rgba(255,255,255,0.03);}
        tbody tr:last-child td{border-bottom:none;}
        .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1px;}
        .pill-pending{background:rgba(255,183,3,0.12);border:1px solid rgba(255,183,3,0.3);color:var(--neon-gold);}
        .pill-approved{background:rgba(0,255,136,0.1);border:1px solid rgba(0,255,136,0.25);color:var(--neon-green);}
        .pill-rejected{background:rgba(255,68,102,0.1);border:1px solid rgba(255,68,102,0.25);color:var(--neon-red);}
        .btn-new{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,var(--neon-gold),#e69a00);color:#000;border:none;border-radius:10px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:11px;letter-spacing:1.5px;cursor:pointer;text-decoration:none;transition:all 0.3s;box-shadow:0 4px 16px rgba(255,183,3,0.3);}
        .btn-new:hover{transform:translateY(-2px);box-shadow:0 0 20px rgba(255,183,3,0.4);}
        .empty-row td{text-align:center;padding:40px;color:rgba(255,255,255,0.22);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:2px;}
        @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:20px 16px 40px;}}
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div><div class="orb orb-2"></div>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-text">Dribbleur Store</div>
            <div class="seller-badge">⭐ VENDEUR</div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Dashboard</div>
            <a href="index.php" class="nav-item active"><span class="nav-icon">📊</span> Vue d'ensemble</a>
            <div class="nav-label">Mes articles</div>
            <a href="submit_article.php" class="nav-item"><span class="nav-icon">➕</span> Soumettre un article</a>
            <a href="my_articles.php" class="nav-item"><span class="nav-icon">📦</span> Mes articles</a>
            <div class="nav-label">Site</div>
            <a href="../accueil.php" class="nav-item"><span class="nav-icon">🏠</span> Voir la boutique</a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn"><span>🚪</span> Déconnexion</a>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="page-title-row">Vendeur <span style="color:var(--neon-gold);">/</span> Dashboard</div>
            <div class="topbar-user"><span class="gold-dot"></span> <?= htmlspecialchars($username) ?></div>
        </div>
        <div class="content">
            <div class="page-heading">Tableau de bord</div>
            <div class="page-sub">Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] ?? $username) ?> ! Gérez vos articles depuis ici.</div>

            <!-- KYC status -->
            <?php if (empty($kyc['seller_verified'])): ?>
            <div class="kyc-alert kyc-pending">
                ⏳ <span><strong>Vérification en cours</strong> — Votre identité est en cours d'examen par l'équipe. Vous pouvez soumettre des articles, ils seront publiés après validation de votre compte.</span>
            </div>
            <?php else: ?>
            <div class="kyc-alert kyc-ok">✅ <span><strong>Compte vérifié</strong> — Votre identité a été confirmée par l'équipe Dribbleur Store.</span></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" style="border:1px solid rgba(0,207,255,0.15);">
                    <div class="stat-icon">📦</div>
                    <div class="stat-val" style="color:var(--neon-blue);"><?= (int)($stats['total']??0) ?></div>
                    <div class="stat-lbl">Total articles</div>
                </div>
                <div class="stat-card" style="border:1px solid rgba(0,255,136,0.15);">
                    <div class="stat-icon">✅</div>
                    <div class="stat-val" style="color:var(--neon-green);"><?= (int)($stats['approved']??0) ?></div>
                    <div class="stat-lbl">Approuvés</div>
                </div>
                <div class="stat-card" style="border:1px solid rgba(255,183,3,0.15);">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-val" style="color:var(--neon-gold);"><?= (int)($stats['pending']??0) ?></div>
                    <div class="stat-lbl">En attente</div>
                </div>
                <div class="stat-card" style="border:1px solid rgba(255,68,102,0.15);">
                    <div class="stat-icon">❌</div>
                    <div class="stat-val" style="color:var(--neon-red);"><?= (int)($stats['rejected']??0) ?></div>
                    <div class="stat-lbl">Refusés</div>
                </div>
            </div>

            <!-- Recent articles + quick action -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div class="panel-title" style="margin:0;">📋 Derniers articles soumis</div>
                <a href="submit_article.php" class="btn-new">➕ Soumettre un article</a>
            </div>
            <div class="panel">
                <div class="panel-title" style="display:none;"></div>
                <table>
                    <thead><tr><th>Titre</th><th>Statut</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (empty($lastArticles)): ?>
                        <tr class="empty-row"><td colspan="3">📭 Aucun article soumis pour l'instant</td></tr>
                    <?php else: foreach ($lastArticles as $a):
                        $ap = $a['approval_status'] ?? 'pending';
                        $pillClass = match($ap){ 'approved'=>'pill-approved','rejected'=>'pill-rejected',default=>'pill-pending' };
                        $pillLabel = match($ap){ 'approved'=>'✅ Approuvé','rejected'=>'❌ Refusé',default=>'⏳ En attente' };
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td><span class="status-pill <?= $pillClass ?>"><?= $pillLabel ?></span></td>
                            <td style="font-size:12px;color:rgba(255,255,255,0.4);"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
