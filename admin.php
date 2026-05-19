<?php
require __DIR__ . '/../config.php';
require_admin();
$pdo = db();

$totalUsers    = $pdo->query('SELECT COUNT(*) FROM visiteur')->fetchColumn();
$totalArticles = $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$totalOrders   = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

// Revenue total
try { $totalRevenue = $pdo->query('SELECT SUM(total_price) FROM orders WHERE status != "annulee"')->fetchColumn() ?: 0; }
catch(Exception $e) { $totalRevenue = 0; }

// Recent orders
try {
    $recentOrders = $pdo->query('SELECT id, user_id, total_price, status, order_date FROM orders ORDER BY order_date DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $recentOrders = []; }

function statusColor($s) {
    return match($s) {
        'validee','livree' => ['#00ff88','rgba(0,255,136,0.12)'],
        'annulee'          => ['#ff4466','rgba(255,68,102,0.12)'],
        'en_cours'         => ['#00cfff','rgba(0,207,255,0.12)'],
        default            => ['#ffaa00','rgba(255,170,0,0.12)'],
    };
}
function formatStatus($s) {
    return match($s) {
        'en_attente'=>'En attente','validee'=>'Validée','annulee'=>'Annulée','en_cours'=>'En cours','livree'=>'Livrée',
        default=>ucfirst(str_replace('_',' ',$s))
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);
            --glow-green:0 0 20px rgba(0,255,136,0.45),0 0 60px rgba(0,255,136,0.12);
            --glow-blue:0 0 20px rgba(0,207,255,0.45),0 0 60px rgba(0,207,255,0.12);
            --glow-red:0 0 20px rgba(255,68,102,0.45),0 0 60px rgba(255,68,102,0.12);
            --sidebar-w:240px;--nav-h:70px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}

        /* ── BG ── */
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.035) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.035) 1px,transparent 1px);background-size:50px 50px;animation:gridMove 10s linear infinite;z-index:0;pointer-events:none;}
        @keyframes gridMove{from{background-position:0 0;}to{background-position:50px 50px;}}
        .orb{position:fixed;border-radius:50%;filter:blur(90px);opacity:0.18;animation:orbFloat linear infinite;z-index:0;pointer-events:none;}
        .orb-1{width:400px;height:400px;background:#ff4466;top:-120px;left:-120px;animation-duration:18s;}
        .orb-2{width:320px;height:320px;background:#00cfff;bottom:-80px;right:-80px;animation-duration:13s;}
        .orb-3{width:260px;height:260px;background:#8b5cf6;top:45%;left:55%;animation-duration:22s;}
        @keyframes orbFloat{0%,100%{transform:translate(0,0);}33%{transform:translate(30px,-20px);}66%{transform:translate(-18px,28px);}}
        .scanlines{position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.02) 2px,rgba(0,0,0,0.02) 4px);pointer-events:none;z-index:1;}
        .particles{position:fixed;inset:0;z-index:0;pointer-events:none;}
        .particle{position:absolute;border-radius:50%;animation:particleFly linear infinite;}
        @keyframes particleFly{from{transform:translateY(100vh) translateX(0);opacity:0;}10%{opacity:1;}90%{opacity:1;}to{transform:translateY(-100px) translateX(var(--drift));opacity:0;}}

        /* ── LAYOUT ── */
        .layout{display:flex;min-height:100vh;position:relative;z-index:2;}

        /* ── SIDEBAR ── */
        .sidebar{
            width:var(--sidebar-w);flex-shrink:0;
            background:rgba(2,8,17,0.92);
            border-right:1px solid rgba(255,68,102,0.15);
            backdrop-filter:blur(20px);
            display:flex;flex-direction:column;
            position:fixed;top:0;left:0;bottom:0;
            z-index:50;
            box-shadow:4px 0 30px rgba(0,0,0,0.4);
        }
        .sidebar::after{content:'';position:absolute;top:0;right:0;bottom:0;width:1px;background:linear-gradient(180deg,transparent,var(--neon-red),transparent);opacity:0.4;}

        .sidebar-logo{
            padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.05);
            display:flex;flex-direction:column;gap:4px;
        }
        .sidebar-logo-text{font-family:'Orbitron',sans-serif;font-weight:900;font-size:14px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .sidebar-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(255,68,102,0.12);border:1px solid rgba(255,68,102,0.25);border-radius:6px;color:var(--neon-red);font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;width:fit-content;}

        .sidebar-nav{flex:1;padding:20px 12px;display:flex;flex-direction:column;gap:4px;}
        .nav-section-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.25);padding:12px 8px 6px;text-transform:uppercase;}

        .nav-item{
            display:flex;align-items:center;gap:10px;
            padding:11px 14px;border-radius:10px;
            text-decoration:none;color:rgba(255,255,255,0.55);
            font-size:14px;letter-spacing:0.5px;
            transition:all 0.25s;position:relative;overflow:hidden;
        }
        .nav-item:hover,.nav-item.active{color:#fff;background:rgba(255,255,255,0.06);border:1px solid rgba(0,207,255,0.12);}
        .nav-item.active{color:var(--neon-blue);border-color:rgba(0,207,255,0.2);background:rgba(0,207,255,0.06);}
        .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2px;background:var(--neon-blue);border-radius:2px;box-shadow:0 0 8px var(--neon-blue);}
        .nav-icon{font-size:16px;width:20px;text-align:center;}

        .sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.05);}
        .logout-btn{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,68,102,0.7);font-size:14px;transition:all 0.25s;width:100%;}
        .logout-btn:hover{color:var(--neon-red);background:rgba(255,68,102,0.08);border:1px solid rgba(255,68,102,0.2);}

        /* ── MAIN ── */
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}

        /* ── TOP BAR ── */
        .topbar{
            height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;
            padding:0 30px;
            background:rgba(2,8,17,0.85);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(255,68,102,0.1);
            position:sticky;top:0;z-index:40;
            box-shadow:0 4px 24px rgba(0,0,0,0.4);
        }
        .topbar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-red),transparent);opacity:0.4;}

        .page-breadcrumb{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:rgba(255,255,255,0.5);}
        .page-breadcrumb span{color:var(--neon-red);}

        .topbar-user{display:flex;align-items:center;gap:10px;padding:8px 16px;border:1px solid rgba(255,68,102,0.2);border-radius:8px;background:rgba(255,68,102,0.05);font-size:13px;letter-spacing:1px;}
        .admin-dot{width:8px;height:8px;border-radius:50%;background:var(--neon-red);box-shadow:0 0 8px var(--neon-red);animation:dotBlink 2s ease-in-out infinite;}
        @keyframes dotBlink{0%,100%{opacity:1;}50%{opacity:0.3;}}

        /* ── CONTENT ── */
        .content{padding:32px 30px 60px;flex:1;}

        .page-title-row{margin-bottom:32px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(20px,3vw,30px);letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-red),#ff8844);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 15px rgba(255,68,102,0.3));margin-bottom:6px;}
        .page-desc{color:rgba(255,255,255,0.38);font-size:14px;letter-spacing:1px;}

        /* ── STATS ── */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;}

        .stat-card{
            background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:16px;
            padding:24px 20px;backdrop-filter:blur(16px);
            display:flex;flex-direction:column;gap:10px;
            transition:transform 0.3s,border-color 0.3s,box-shadow 0.3s;
            animation:cardIn 0.6s ease both;position:relative;overflow:hidden;
        }
        .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:opacity 0.3s;}
        .stat-card:hover{transform:translateY(-6px);}
        .stat-card:hover::before{opacity:1;}
        .stat-card.green{border-color:rgba(0,255,136,0.15);}
        .stat-card.green:hover{border-color:var(--neon-green);box-shadow:var(--glow-green);}
        .stat-card.green::before{background:linear-gradient(90deg,transparent,var(--neon-green),transparent);}
        .stat-card.blue{border-color:rgba(0,207,255,0.15);}
        .stat-card.blue:hover{border-color:var(--neon-blue);box-shadow:var(--glow-blue);}
        .stat-card.blue::before{background:linear-gradient(90deg,transparent,var(--neon-blue),transparent);}
        .stat-card.red{border-color:rgba(255,68,102,0.15);}
        .stat-card.red:hover{border-color:var(--neon-red);box-shadow:var(--glow-red);}
        .stat-card.red::before{background:linear-gradient(90deg,transparent,var(--neon-red),transparent);}
        .stat-card.purple{border-color:rgba(139,92,246,0.15);}
        .stat-card.purple:hover{border-color:#8b5cf6;box-shadow:0 0 20px rgba(139,92,246,0.4);}
        .stat-card.purple::before{background:linear-gradient(90deg,transparent,#8b5cf6,transparent);}

        @keyframes cardIn{to{opacity:1;transform:translateY(0);}}

        .stat-icon{font-size:26px;}
        .stat-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.38);text-transform:uppercase;}
        .stat-value{font-family:'Orbitron',sans-serif;font-weight:900;font-size:32px;}
        .stat-card.green .stat-value{color:var(--neon-green);text-shadow:0 0 12px rgba(0,255,136,0.4);}
        .stat-card.blue .stat-value{color:var(--neon-blue);text-shadow:0 0 12px rgba(0,207,255,0.4);}
        .stat-card.red .stat-value{color:var(--neon-red);text-shadow:0 0 12px rgba(255,68,102,0.4);}
        .stat-card.purple .stat-value{color:#a78bfa;text-shadow:0 0 12px rgba(139,92,246,0.4);}

        /* ── GRID 2 COL ── */
        .dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
        @media(max-width:900px){.dashboard-grid{grid-template-columns:1fr;}}

        /* ── PANEL ── */
        .panel{background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:16px;padding:24px;backdrop-filter:blur(16px);position:relative;overflow:hidden;}
        .panel::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,255,136,0.2),rgba(0,207,255,0.2),transparent);}
        .panel-title{font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:2px;color:var(--neon-blue);margin-bottom:20px;display:flex;align-items:center;gap:8px;}

        /* ── ORDERS TABLE ── */
        .orders-table{width:100%;border-collapse:collapse;}
        .orders-table th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;color:rgba(255,255,255,0.3);padding:8px 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05);}
        .orders-table td{padding:12px;font-size:14px;color:rgba(255,255,255,0.75);border-bottom:1px solid rgba(255,255,255,0.03);}
        .orders-table tr:last-child td{border-bottom:none;}
        .orders-table tr:hover td{background:rgba(255,255,255,0.02);}

        .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1px;}
        .status-dot-sm{width:5px;height:5px;border-radius:50%;}

        /* ── QUICK ACTIONS ── */
        .actions-list{display:flex;flex-direction:column;gap:10px;}
        .action-link{
            display:flex;align-items:center;gap:12px;
            padding:13px 16px;border-radius:12px;text-decoration:none;
            background:rgba(255,255,255,0.03);border:1px solid rgba(0,207,255,0.1);
            color:rgba(255,255,255,0.7);font-size:14px;letter-spacing:0.5px;
            transition:all 0.25s;
        }
        .action-link:hover{background:rgba(0,207,255,0.06);border-color:rgba(0,207,255,0.25);color:#fff;transform:translateX(4px);}
        .action-link-icon{font-size:18px;width:24px;text-align:center;}
        .action-link-arrow{margin-left:auto;color:rgba(255,255,255,0.25);font-size:12px;}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .main{margin-left:0;}
            .content{padding:20px 16px 40px;}
        }
    </style>
</head>
<body>

    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>
    <div class="scanlines"></div>

    <div class="layout">

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-text">Dribbleur Store</div>
                <div class="sidebar-badge">🔴 ADMIN</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-label">Dashboard</div>
                <a href="admin.php" class="nav-item active">
                    <span class="nav-icon">📊</span> Vue d'ensemble
                </a>

                <div class="nav-section-label">Gestion</div>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span> Utilisateurs
                </a>
                <a href="articles.php" class="nav-item">
                    <span class="nav-icon">📦</span> Articles
                </a>
                <a href="pending_articles.php" class="nav-item">
                    <span class="nav-icon">⏳</span> Articles Vendeurs
                </a>
                <a href="pending_sellers.php" class="nav-item">
                    <span class="nav-icon">🚀</span> Candidatures Vendeurs
                </a>
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">🛒</span> Commandes
                </a>
                <a href="chat.php" class="nav-item">
                    <span class="nav-icon">💬</span> Messages
                </a>

                <div class="nav-section-label">Site</div>
                <a href="../accueil.php" class="nav-item">
                    <span class="nav-icon">🏠</span> Voir le site
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <span>🚪</span> Déconnexion
                </a>
            </div>
        </aside>

        <!-- ══ MAIN ══ -->
        <div class="main">

            <!-- Top bar -->
            <div class="topbar">
                <div class="page-breadcrumb">Admin <span>/</span> Dashboard</div>
                <div class="topbar-user">
                    <span class="admin-dot"></span>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
            </div>

            <!-- Content -->
            <div class="content">

                <div class="page-title-row">
                    <div class="page-title">Tableau de bord</div>
                    <div class="page-desc">Bienvenue, <?= htmlspecialchars($_SESSION['username']) ?>. Voici un aperçu de votre boutique.</div>
                </div>

                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card blue" style="animation-delay:0s;opacity:0;transform:translateY(16px);">
                        <div class="stat-icon">👥</div>
                        <div class="stat-label">Utilisateurs</div>
                        <div class="stat-value"><?= $totalUsers ?></div>
                    </div>
                    <div class="stat-card green" style="animation-delay:0.08s;opacity:0;transform:translateY(16px);">
                        <div class="stat-icon">📦</div>
                        <div class="stat-label">Articles</div>
                        <div class="stat-value"><?= $totalArticles ?></div>
                    </div>
                    <div class="stat-card red" style="animation-delay:0.16s;opacity:0;transform:translateY(16px);">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-label">Commandes</div>
                        <div class="stat-value"><?= $totalOrders ?></div>
                    </div>
                    <div class="stat-card purple" style="animation-delay:0.24s;opacity:0;transform:translateY(16px);">
                        <div class="stat-icon">💰</div>
                        <div class="stat-label">Revenus FCFA</div>
                        <div class="stat-value"><?= number_format($totalRevenue, 0, ',', ' ') ?></div>
                    </div>
                </div>

                <!-- DASHBOARD GRID -->
                <div class="dashboard-grid">

                    <!-- Recent orders -->
                    <div class="panel">
                        <div class="panel-title">🛒 Dernières commandes</div>
                        <?php if (empty($recentOrders)): ?>
                            <p style="color:rgba(255,255,255,0.3);font-size:13px;letter-spacing:1px;">Aucune commande pour le moment.</p>
                        <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentOrders as $o):
                                    [$col,$bg] = statusColor($o['status']); ?>
                                <tr>
                                    <td style="font-family:'Orbitron',sans-serif;font-size:12px;color:var(--neon-blue);">#<?= $o['id'] ?></td>
                                    <td style="color:var(--neon-green);font-family:'Orbitron',sans-serif;font-size:12px;"><?= number_format($o['total_price'],0,',',' ') ?> FCFA</td>
                                    <td>
                                        <span class="status-pill" style="background:<?= $bg ?>;color:<?= $col ?>;border:1px solid <?= $col ?>33;">
                                            <span class="status-dot-sm" style="background:<?= $col ?>;box-shadow:0 0 5px <?= $col ?>;"></span>
                                            <?= formatStatus($o['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px;color:rgba(255,255,255,0.4);"><?= date('d/m/Y', strtotime($o['order_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <!-- Quick actions -->
                    <div class="panel">
                        <div class="panel-title">⚡ Actions rapides</div>
                        <div class="actions-list">
                            <a href="users.php" class="action-link">
                                <span class="action-link-icon">👥</span>
                                Gérer les utilisateurs
                                <span class="action-link-arrow">→</span>
                            </a>
                            <a href="articles.php" class="action-link">
                                <span class="action-link-icon">➕</span>
                                Ajouter un article
                                <span class="action-link-arrow">→</span>
                            </a>
                            <a href="orders.php" class="action-link">
                                <span class="action-link-icon">📋</span>
                                Voir toutes les commandes
                                <span class="action-link-arrow">→</span>
                            </a>
                            <a href="chat.php" class="action-link">
                                <span class="action-link-icon">💬</span>
                                Lire les messages support
                                <span class="action-link-arrow">→</span>
                            </a>
                            <a href="../accueil.php" class="action-link">
                                <span class="action-link-icon">🌐</span>
                                Voir la boutique
                                <span class="action-link-arrow">→</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<30;i++){
                const p=document.createElement('div');p.className='particle';
                const g=Math.random()>0.6;
                const r=Math.random()>0.8;
                const col=r?'#ff4466':g?'#00ff88':'#00cfff';
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${6+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*100}px;background:${col};box-shadow:0 0 6px ${col};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();
    </script>
</body>
</html>