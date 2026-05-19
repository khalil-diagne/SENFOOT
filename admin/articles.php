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
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);
            --glow-green:0 0 20px rgba(0,255,136,0.45),0 0 60px rgba(0,255,136,0.12);
            --glow-blue:0 0 20px rgba(0,207,255,0.45),0 0 60px rgba(0,207,255,0.12);
            --glow-red:0 0 20px rgba(255,68,102,0.45),0 0 60px rgba(255,68,102,0.12);
            --sidebar-w:240px;--nav-h:70px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
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
        .sidebar{width:var(--sidebar-w);flex-shrink:0;background:rgba(2,8,17,0.92);border-right:1px solid rgba(255,68,102,0.15);backdrop-filter:blur(20px);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;box-shadow:4px 0 30px rgba(0,0,0,0.4);}
        .sidebar::after{content:'';position:absolute;top:0;right:0;bottom:0;width:1px;background:linear-gradient(180deg,transparent,var(--neon-red),transparent);opacity:0.4;}
        .sidebar-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;flex-direction:column;gap:4px;}
        .sidebar-logo-text{font-family:'Orbitron',sans-serif;font-weight:900;font-size:14px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .sidebar-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(255,68,102,0.12);border:1px solid rgba(255,68,102,0.25);border-radius:6px;color:var(--neon-red);font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;width:fit-content;}
        .sidebar-nav{flex:1;padding:20px 12px;display:flex;flex-direction:column;gap:4px;}
        .nav-section-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.25);padding:12px 8px 6px;}
        .nav-item{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,0.55);font-size:14px;transition:all 0.25s;position:relative;}
        .nav-item:hover{color:#fff;background:rgba(255,255,255,0.06);border:1px solid rgba(0,207,255,0.12);}
        .nav-item.active{color:var(--neon-blue);border:1px solid rgba(0,207,255,0.2);background:rgba(0,207,255,0.06);}
        .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2px;background:var(--neon-blue);border-radius:2px;box-shadow:0 0 8px var(--neon-blue);}
        .nav-icon{font-size:16px;width:20px;text-align:center;}
        .sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.05);}
        .logout-btn{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,68,102,0.7);font-size:14px;transition:all 0.25s;width:100%;}
        .logout-btn:hover{color:var(--neon-red);background:rgba(255,68,102,0.08);border:1px solid rgba(255,68,102,0.2);}

        /* ── MAIN ── */
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
        .topbar{height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,68,102,0.1);position:sticky;top:0;z-index:40;box-shadow:0 4px 24px rgba(0,0,0,0.4);position:relative;}
        .topbar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-red),transparent);opacity:0.4;}
        .page-breadcrumb{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:rgba(255,255,255,0.5);}
        .page-breadcrumb span{color:var(--neon-red);}
        .topbar-user{display:flex;align-items:center;gap:10px;padding:8px 16px;border:1px solid rgba(255,68,102,0.2);border-radius:8px;background:rgba(255,68,102,0.05);font-size:13px;letter-spacing:1px;}
        .admin-dot{width:8px;height:8px;border-radius:50%;background:var(--neon-red);box-shadow:0 0 8px var(--neon-red);animation:dotBlink 2s ease-in-out infinite;}
        @keyframes dotBlink{0%,100%{opacity:1;}50%{opacity:0.3;}}

        /* ── CONTENT ── */
        .content{padding:32px 30px 60px;flex:1;}
        .page-title-row{margin-bottom:24px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(18px,2.5vw,28px);letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-red),#ff8844);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 12px rgba(255,68,102,0.3));margin-bottom:4px;}
        .page-desc{color:rgba(255,255,255,0.35);font-size:14px;letter-spacing:1px;}

        /* ── ALERTS ── */
        .alert{padding:12px 18px;border-radius:10px;margin-bottom:20px;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1px;display:flex;align-items:center;gap:10px;animation:alertIn 0.4s ease;}
        @keyframes alertIn{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
        .alert-success{background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.22);color:var(--neon-green);}
        .alert-error{background:rgba(255,68,102,0.07);border:1px solid rgba(255,68,102,0.22);color:var(--neon-red);}

        /* ── ADD BUTTON ── */
        .btn-add{
            position:relative;overflow:hidden;
            display:inline-flex;align-items:center;gap:8px;
            padding:12px 24px;border-radius:10px;
            background:linear-gradient(135deg,var(--neon-green),#00b86b);
            color:#001a0d;font-family:'Orbitron',sans-serif;font-weight:700;font-size:11px;letter-spacing:2px;
            text-decoration:none;border:none;cursor:pointer;
            box-shadow:0 4px 20px rgba(0,255,136,0.3);
            transition:transform 0.2s,box-shadow 0.3s;
            white-space:nowrap;
        }
        .btn-add::before{content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;background:rgba(255,255,255,0.25);transform:skewX(-20deg);animation:btnShine 3s ease-in-out infinite;}
        @keyframes btnShine{0%{left:-60%;opacity:0;}20%{opacity:1;}50%{left:130%;opacity:0;}100%{left:130%;opacity:0;}}
        .btn-add:hover{transform:translateY(-3px);box-shadow:var(--glow-green);}

        /* ── TOOLBAR ── */
        .table-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;}
        .search-wrap{position:relative;flex:1;min-width:200px;}
        .search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.25);}
        #searchInput{width:100%;padding:10px 14px 10px 38px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:14px;outline:none;transition:border-color 0.3s,box-shadow 0.3s;}
        #searchInput:focus{border-color:var(--neon-blue);box-shadow:0 0 14px rgba(0,207,255,0.15);}
        #searchInput::placeholder{color:rgba(255,255,255,0.22);}

        /* ── TABLE ── */
        .table-wrap{background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:16px;backdrop-filter:blur(16px);overflow:hidden;animation:cardIn 0.6s ease both;}
        @keyframes cardIn{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
        .table-wrap::before{content:'';display:block;height:2px;background:linear-gradient(90deg,transparent,var(--neon-green),var(--neon-blue),transparent);}

        table{width:100%;border-collapse:collapse;}
        thead th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.3);padding:14px 16px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05);white-space:nowrap;}
        tbody td{padding:12px 16px;font-size:14px;color:rgba(255,255,255,0.72);border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.2s;vertical-align:middle;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:rgba(255,255,255,0.02);}

        /* ── ARTICLE IMAGE ── */
        .article-thumb{
            width:56px;height:56px;border-radius:10px;object-fit:cover;
            border:1px solid rgba(0,207,255,0.15);
            transition:transform 0.3s,box-shadow 0.3s;
        }
        tbody tr:hover .article-thumb{transform:scale(1.08);box-shadow:var(--glow-blue);}
        .no-img{width:56px;height:56px;border-radius:10px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;color:rgba(255,255,255,0.2);}

        .article-title-cell{font-weight:600;color:#e8f4ff;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .price-cell{font-family:'Orbitron',sans-serif;font-size:13px;color:var(--neon-green);white-space:nowrap;}
        .author-cell{font-size:13px;color:rgba(255,255,255,0.45);}
        .date-cell{font-size:12px;color:rgba(255,255,255,0.35);white-space:nowrap;}
        .status-badge{display:inline-flex;align-items:center;justify-content:center;padding:7px 10px;border-radius:999px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.3px;text-transform:uppercase;border:1px solid transparent;white-space:nowrap;}
        .status-badge.status-available{color:var(--neon-green);background:rgba(0,255,136,0.08);border-color:rgba(0,255,136,0.22);}
        .status-badge.status-reserved{color:#ffb703;background:rgba(255,183,3,0.1);border-color:rgba(255,183,3,0.24);}
        .status-badge.status-sold{color:var(--neon-red);background:rgba(255,68,102,0.1);border-color:rgba(255,68,102,0.24);}

        /* ── ACTION BUTTONS ── */
        .actions-cell{display:flex;align-items:center;gap:8px;}

        .btn-edit{
            display:inline-flex;align-items:center;gap:5px;
            padding:7px 13px;border-radius:8px;text-decoration:none;
            background:rgba(255,170,0,0.1);border:1px solid rgba(255,170,0,0.25);
            color:#ffaa00;font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1px;
            transition:all 0.25s;white-space:nowrap;
        }
        .btn-edit:hover{background:rgba(255,170,0,0.18);border-color:#ffaa00;box-shadow:0 0 14px rgba(255,170,0,0.3);transform:translateY(-2px);}

        .btn-delete{
            display:inline-flex;align-items:center;gap:5px;
            padding:7px 13px;border-radius:8px;
            background:rgba(255,68,102,0.1);border:1px solid rgba(255,68,102,0.25);
            color:var(--neon-red);font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1px;
            cursor:pointer;transition:all 0.25s;white-space:nowrap;
        }
        .btn-delete:hover{background:rgba(255,68,102,0.2);border-color:var(--neon-red);box-shadow:var(--glow-red);transform:translateY(-2px);}

        .empty-row td{text-align:center;padding:50px;color:rgba(255,255,255,0.25);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:2px;}

        .table-footer{padding:12px 16px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid rgba(255,255,255,0.04);color:rgba(255,255,255,0.3);font-size:12px;letter-spacing:1px;}
        .count-shown{font-family:'Orbitron',sans-serif;font-size:11px;color:var(--neon-blue);}

        /* ── CONFIRM MODAL ── */
        .modal-overlay{position:fixed;inset:0;background:rgba(2,8,17,0.88);backdrop-filter:blur(10px);z-index:200;display:none;justify-content:center;align-items:center;}
        .modal-overlay.open{display:flex;}
        .modal-box{background:rgba(0,20,40,0.96);border:1px solid rgba(255,68,102,0.25);border-radius:18px;padding:36px 32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.7),var(--glow-red);animation:modalIn 0.4s cubic-bezier(0.16,1,0.3,1);position:relative;}
        @keyframes modalIn{from{opacity:0;transform:scale(0.9) translateY(16px);}to{opacity:1;transform:scale(1) translateY(0);}}
        .modal-box::before,.modal-box::after{content:'';position:absolute;width:30px;height:30px;border-color:var(--neon-red);border-style:solid;}
        .modal-box::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:18px 0 0 0;}
        .modal-box::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 18px 0;}
        .modal-icon{font-size:40px;margin-bottom:14px;display:block;text-align:center;}
        .modal-title{font-family:'Orbitron',sans-serif;font-size:16px;letter-spacing:2px;color:var(--neon-red);text-align:center;margin-bottom:10px;}
        .modal-desc{color:rgba(255,255,255,0.5);font-size:14px;text-align:center;margin-bottom:28px;line-height:1.6;}
        .modal-actions{display:flex;gap:12px;}
        .modal-cancel{flex:1;padding:12px;background:transparent;border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:rgba(255,255,255,0.5);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1.5px;cursor:pointer;transition:all 0.3s;}
        .modal-cancel:hover{border-color:rgba(255,255,255,0.3);color:#fff;}
        .modal-confirm{flex:1;padding:12px;background:linear-gradient(135deg,var(--neon-red),#cc0022);color:#fff;border:none;border-radius:10px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:11px;letter-spacing:1.5px;cursor:pointer;box-shadow:0 4px 16px rgba(255,68,102,0.3);transition:all 0.2s;}
        .modal-confirm:hover{transform:translateY(-2px);box-shadow:var(--glow-red);}

        @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:20px 16px 40px;}.article-title-cell{max-width:120px;}}
    </style>
</head>
<body>

    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>
    <div class="scanlines"></div>

    <!-- CONFIRM MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <span class="modal-icon">🗑️</span>
            <div class="modal-title">Supprimer l'article ?</div>
            <div class="modal-desc">Cette action est irréversible. L'article et son image seront définitivement supprimés.</div>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeModal()">Annuler</button>
                <button class="modal-confirm" onclick="submitDelete()">Supprimer</button>
            </div>
        </div>
    </div>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-text">Dribbleur Store</div>
                <div class="sidebar-badge">🔴 ADMIN</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-label">Dashboard</div>
                <a href="admin.php" class="nav-item"><span class="nav-icon">📊</span> Vue d'ensemble</a>
                <div class="nav-section-label">Gestion</div>
                <a href="users.php" class="nav-item"><span class="nav-icon">👥</span> Utilisateurs</a>
                <a href="articles.php" class="nav-item active"><span class="nav-icon">📦</span> Articles</a>
                <a href="pending_articles.php" class="nav-item"><span class="nav-icon">⏳</span> Articles Vendeurs</a>
                <a href="pending_sellers.php" class="nav-item"><span class="nav-icon">🚀</span> Candidatures Vendeurs</a>
                <a href="orders.php" class="nav-item"><span class="nav-icon">🛒</span> Commandes</a>
                <a href="chat.php" class="nav-item"><span class="nav-icon">💬</span> Messages</a>
                <div class="nav-section-label">Site</div>
                <a href="../accueil.php" class="nav-item"><span class="nav-icon">🏠</span> Voir le site</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn"><span>🚪</span> Déconnexion</a>
            </div>
        </aside>

        <!-- MAIN -->
        <div class="main">
            <div class="topbar">
                <div class="page-breadcrumb">Admin <span>/</span> Articles</div>
                <div class="topbar-user">
                    <span class="admin-dot"></span>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
            </div>

            <div class="content">

                <div class="page-title-row">
                    <div>
                        <div class="page-title">Gestion des Articles</div>
                        <div class="page-desc"><?= count($articles) ?> article(s) en ligne</div>
                    </div>
                    <a href="article_new.php" class="btn-add">＋ Ajouter un article</a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- TOOLBAR -->
                <div class="table-toolbar">
                    <div class="search-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" id="searchInput" placeholder="Rechercher par titre, auteur...">
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Auteur</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="articlesBody">
                            <?php if (empty($articles)): ?>
                                <tr class="empty-row"><td colspan="7">📭 Aucun article publié</td></tr>
                            <?php else: foreach($articles as $a): ?>
                            <?php $statusMeta = article_status_meta($a['product_status'] ?? null); ?>
                            <tr data-search="<?= strtolower(htmlspecialchars($a['title'].' '.$a['author_username'].' '.$statusMeta['label'])) ?>">
                                <td>
                                    <?php if (!empty($a['image'])): ?>
                                        <img class="article-thumb" src="../uploads/articles/<?= htmlspecialchars($a['image']) ?>" alt="">
                                    <?php else: ?>
                                        <div class="no-img">⚽</div>
                                    <?php endif; ?>
                                </td>
                                <td><div class="article-title-cell" title="<?= htmlspecialchars($a['title']) ?>"><?= htmlspecialchars($a['title']) ?></div></td>
                                <td><span class="price-cell"><?= number_format($a['price'],0,',',' ') ?> FCFA</span></td>
                                <td><span class="status-badge <?= htmlspecialchars($statusMeta['class']) ?>"><?= htmlspecialchars($statusMeta['label']) ?></span></td>
                                <td><span class="author-cell"><?= htmlspecialchars($a['author_username']) ?></span></td>
                                <td><span class="date-cell"><?= date('d/m/Y', strtotime($a['created_at'])) ?></span></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="edit_article.php?id=<?= $a['id'] ?>" class="btn-edit">✏️ Éditer</a>
                                        <button class="btn-delete" onclick="openModal(<?= $a['id'] ?>)">🗑 Sup.</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <div class="count-shown" id="countShown"><?= count($articles) ?> article(s)</div>
                        <div><?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form id="deleteForm" action="articles.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="article_id" id="deleteArticleId">
        <input type="hidden" name="action" value="delete">
    </form>

    <script>
        /* ── Particles ── */
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<30;i++){
                const p=document.createElement('div');p.className='particle';
                const t=Math.random();
                const col=t>0.7?'#ff4466':t>0.4?'#00ff88':'#00cfff';
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${6+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*100}px;background:${col};box-shadow:0 0 6px ${col};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();

        /* ── Search ── */
        const searchInput = document.getElementById('searchInput');
        const rows        = document.querySelectorAll('#articlesBody tr[data-search]');
        const countShown  = document.getElementById('countShown');

        searchInput.addEventListener('input', function(){
            const q = this.value.toLowerCase();
            let v = 0;
            rows.forEach(r => {
                const match = !q || r.dataset.search.includes(q);
                r.style.display = match ? '' : 'none';
                if(match) v++;
            });
            countShown.textContent = v + ' article(s)';
        });

        /* ── Delete modal ── */
        let pendingId = null;
        function openModal(id) {
            pendingId = id;
            document.getElementById('deleteModal').classList.add('open');
        }
        function closeModal() {
            pendingId = null;
            document.getElementById('deleteModal').classList.remove('open');
        }
        function submitDelete() {
            if(!pendingId) return;
            document.getElementById('deleteArticleId').value = pendingId;
            document.getElementById('deleteForm').submit();
        }
        document.getElementById('deleteModal').addEventListener('click', function(e){
            if(e.target === this) closeModal();
        });
    </script>
</body>
</html>
