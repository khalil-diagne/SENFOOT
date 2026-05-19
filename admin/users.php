<?php
require __DIR__ . '/../config.php';
require_admin();
$pdo = db();

$message = ''; $error = '';

// Get current admin ID
$stmtAdmin = $pdo->prepare('SELECT id FROM visiteur WHERE username = :username');
$stmtAdmin->execute([':username' => $_SESSION['username']]);
$_SESSION['user_id_from_db'] = $stmtAdmin->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Jeton de sécurité invalide.';
    } else {
        $userId = $_POST['user_id'] ?? null;

        if (isset($_POST['action']) && $_POST['action'] === 'delete' && $userId) {
            if ($userId == ($_SESSION['user_id_from_db'] ?? null)) {
                $error = 'Vous ne pouvez pas supprimer votre propre compte.';
            } else {
                $pdo->prepare('DELETE FROM visiteur WHERE id = :id')->execute([':id' => $userId]);
                $message = 'Utilisateur supprimé avec succès.';
            }
        }

        if (isset($_POST['action']) && $_POST['action'] === 'change_role' && $userId) {
            $newRole = $_POST['new_role'] ?? 'user';
            if ($userId == ($_SESSION['user_id_from_db'] ?? null)) {
                $error = 'Vous ne pouvez pas modifier votre propre rôle.';
            } elseif ($newRole === 'admin' || $newRole === 'user' || $newRole === 'seller') {
                $pdo->prepare('UPDATE visiteur SET role = :role WHERE id = :id')->execute([':role'=>$newRole,':id'=>$userId]);
                $message = 'Rôle mis à jour avec succès.';
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$users = $pdo->query('SELECT id, username, prenom, nom, email, role FROM visiteur ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$totalAdmins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$totalUsers  = count(array_filter($users, fn($u) => $u['role'] === 'user'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Admin · Dribbleur Store</title>
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
        .page-title-row{margin-bottom:24px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(18px,2.5vw,28px);letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-red),#ff8844);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 12px rgba(255,68,102,0.3));margin-bottom:4px;}
        .page-desc{color:rgba(255,255,255,0.35);font-size:14px;letter-spacing:1px;}

        /* ── ALERTS ── */
        .alert{padding:12px 18px;border-radius:10px;margin-bottom:20px;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1px;display:flex;align-items:center;gap:10px;animation:alertIn 0.4s ease;}
        @keyframes alertIn{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}
        .alert-success{background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.22);color:var(--neon-green);}
        .alert-error{background:rgba(255,68,102,0.07);border:1px solid rgba(255,68,102,0.22);color:var(--neon-red);}

        /* ── MINI STATS ── */
        .mini-stats{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
        .mini-stat{padding:12px 20px;background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:12px;backdrop-filter:blur(12px);display:flex;align-items:center;gap:10px;transition:transform 0.2s;}
        .mini-stat:hover{transform:translateY(-3px);}
        .mini-stat-val{font-family:'Orbitron',sans-serif;font-weight:900;font-size:22px;}
        .mini-stat-label{font-size:12px;letter-spacing:1px;color:rgba(255,255,255,0.38);}

        /* ── TOOLBAR ── */
        .table-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;}
        .search-wrap{position:relative;flex:1;min-width:200px;}
        .search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.25);}
        #searchInput{width:100%;padding:10px 14px 10px 38px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:14px;outline:none;transition:border-color 0.3s,box-shadow 0.3s;}
        #searchInput:focus{border-color:var(--neon-blue);box-shadow:0 0 14px rgba(0,207,255,0.15);}
        #searchInput::placeholder{color:rgba(255,255,255,0.22);}
        .filter-select{padding:10px 14px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:14px;outline:none;cursor:pointer;transition:border-color 0.3s;}
        .filter-select:focus{border-color:var(--neon-blue);}
        .filter-select option{background:#0a1628;}

        /* ── TABLE ── */
        .table-wrap{background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:16px;backdrop-filter:blur(16px);overflow:hidden;animation:cardIn 0.6s ease both;}
        @keyframes cardIn{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
        .table-wrap::before{content:'';display:block;height:2px;background:linear-gradient(90deg,transparent,var(--neon-red),var(--neon-blue),transparent);}

        table{width:100%;border-collapse:collapse;}
        thead th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.3);padding:14px 16px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05);white-space:nowrap;}
        tbody td{padding:12px 16px;font-size:14px;color:rgba(255,255,255,0.72);border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.2s;vertical-align:middle;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:rgba(255,255,255,0.02);}

        /* ── USER CELL ── */
        .user-cell{display:flex;align-items:center;gap:10px;}
        .user-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:12px;font-weight:700;flex-shrink:0;}
        .user-av.admin-av{background:linear-gradient(135deg,rgba(255,68,102,0.2),rgba(255,68,102,0.1));color:var(--neon-red);border:1px solid rgba(255,68,102,0.3);}
        .user-av.user-av-cls{background:linear-gradient(135deg,rgba(0,207,255,0.15),rgba(0,207,255,0.08));color:var(--neon-blue);border:1px solid rgba(0,207,255,0.2);}
        .user-av.me-av{background:linear-gradient(135deg,rgba(0,255,136,0.2),rgba(0,255,136,0.1));color:var(--neon-green);border:1px solid rgba(0,255,136,0.3);}

        .id-cell{font-family:'Orbitron',sans-serif;font-size:11px;color:rgba(255,255,255,0.35);}
        .email-cell{font-size:13px;color:rgba(255,255,255,0.45);}
        .name-cell{font-size:13px;color:rgba(255,255,255,0.6);}

        /* ── ROLE BADGE ── */
        .role-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1px;}
        .role-pill.admin{background:rgba(255,68,102,0.12);border:1px solid rgba(255,68,102,0.3);color:var(--neon-red);}
        .role-pill.user{background:rgba(0,207,255,0.1);border:1px solid rgba(0,207,255,0.2);color:var(--neon-blue);}
        .role-pill.me{background:rgba(0,255,136,0.1);border:1px solid rgba(0,255,136,0.2);color:var(--neon-green);}

        /* ── ROLE SELECT ── */
        .role-select{
            padding:6px 10px;
            background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.2);border-radius:8px;
            color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:13px;
            outline:none;cursor:pointer;transition:border-color 0.3s;
        }
        .role-select:focus{border-color:var(--neon-blue);}
        .role-select option{background:#0a1628;}
        .role-select:disabled{opacity:0.35;cursor:not-allowed;}

        /* ── ACTIONS ── */
        .btn-delete-user{
            display:inline-flex;align-items:center;gap:5px;
            padding:7px 13px;border-radius:8px;
            background:rgba(255,68,102,0.1);border:1px solid rgba(255,68,102,0.25);
            color:var(--neon-red);font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1px;
            cursor:pointer;transition:all 0.25s;
        }
        .btn-delete-user:hover{background:rgba(255,68,102,0.2);border-color:var(--neon-red);box-shadow:var(--glow-red);transform:translateY(-2px);}
        .me-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1px;color:var(--neon-green);padding:6px 10px;background:rgba(0,255,136,0.06);border:1px solid rgba(0,255,136,0.15);border-radius:8px;}

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
        .modal-title{font-family:'Orbitron',sans-serif;font-size:16px;letter-spacing:2px;color:var(--neon-red);text-align:center;margin-bottom:8px;}
        .modal-user{font-family:'Orbitron',sans-serif;font-size:13px;color:var(--neon-blue);text-align:center;margin-bottom:10px;}
        .modal-desc{color:rgba(255,255,255,0.45);font-size:14px;text-align:center;margin-bottom:28px;line-height:1.6;}
        .modal-actions{display:flex;gap:12px;}
        .modal-cancel{flex:1;padding:12px;background:transparent;border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:rgba(255,255,255,0.5);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1.5px;cursor:pointer;transition:all 0.3s;}
        .modal-cancel:hover{border-color:rgba(255,255,255,0.3);color:#fff;}
        .modal-confirm{flex:1;padding:12px;background:linear-gradient(135deg,var(--neon-red),#cc0022);color:#fff;border:none;border-radius:10px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:11px;letter-spacing:1.5px;cursor:pointer;box-shadow:0 4px 16px rgba(255,68,102,0.3);transition:all 0.2s;}
        .modal-confirm:hover{transform:translateY(-2px);box-shadow:var(--glow-red);}

        @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:20px 16px 40px;}}
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
            <span class="modal-icon">⚠️</span>
            <div class="modal-title">Supprimer l'utilisateur ?</div>
            <div class="modal-user" id="modalUsername"></div>
            <div class="modal-desc">Cette action est irréversible. Toutes les données de cet utilisateur seront supprimées définitivement.</div>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeModal()">Annuler</button>
                <button class="modal-confirm" onclick="submitDelete()">Supprimer</button>
            </div>
        </div>
    </div>

    <div class="layout">

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-text">Dribbleur Store</div>
                <div class="sidebar-badge">🔴 ADMIN</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-label">Dashboard</div>
                <a href="admin.php" class="nav-item"><span class="nav-icon">📊</span> Vue d'ensemble</a>
                <div class="nav-section-label">Gestion</div>
                <a href="users.php" class="nav-item active"><span class="nav-icon">👥</span> Utilisateurs</a>
                <a href="articles.php" class="nav-item"><span class="nav-icon">📦</span> Articles</a>
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
        </aside>    <!-- MAIN -->
        <div class="main">
            <div class="topbar">
                <div class="page-breadcrumb">Admin <span>/</span> Utilisateurs</div>
                <div class="topbar-user">
                    <span class="admin-dot"></span>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
            </div>

            <div class="content">

                <div class="page-title-row">
                    <div class="page-title">Gestion des Utilisateurs</div>
                    <div class="page-desc"><?= count($users) ?> compte(s) enregistré(s)</div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- MINI STATS -->
                <div class="mini-stats">
                    <div class="mini-stat" style="border-color:rgba(0,207,255,0.2);">
                        <span style="font-size:20px;">👥</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-blue);"><?= count($users) ?></div><div class="mini-stat-label">Total</div></div>
                    </div>
                    <div class="mini-stat" style="border-color:rgba(255,68,102,0.2);">
                        <span style="font-size:20px;">🔴</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-red);"><?= $totalAdmins ?></div><div class="mini-stat-label">Admins</div></div>
                    </div>
                    <div class="mini-stat" style="border-color:rgba(0,207,255,0.2);">
                        <span style="font-size:20px;">👤</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-blue);"><?= $totalUsers ?></div><div class="mini-stat-label">Utilisateurs</div></div>
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div class="table-toolbar">
                    <div class="search-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" id="searchInput" placeholder="Rechercher par nom, email, username...">
                    </div>
                    <select class="filter-select" id="roleFilter">
                        <option value="">Tous les rôles</option>
                        <option value="admin">Admin</option>
                        <option value="seller">Vendeur</option>
                        <option value="user">Utilisateur</option>
                    </select>
                </div>

                <!-- TABLE -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                            <?php if (empty($users)): ?>
                                <tr class="empty-row"><td colspan="6">📭 Aucun utilisateur trouvé</td></tr>
                            <?php else: foreach($users as $u):
                                $isMe = ($u['id'] == ($_SESSION['user_id_from_db'] ?? null));
                                $isAdmin = $u['role'] === 'admin';
                                $avClass = $isMe ? 'me-av' : ($isAdmin ? 'admin-av' : 'user-av-cls');
                                $pillClass = $isMe ? 'me' : $u['role'];
                            ?>
                            <tr data-search="<?= strtolower(htmlspecialchars($u['username'].' '.$u['prenom'].' '.$u['nom'].' '.$u['email'])) ?>"
                                data-role="<?= htmlspecialchars($u['role']) ?>">
                                <td><span class="id-cell">#<?= htmlspecialchars($u['id']) ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-av <?= $avClass ?>"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                                        <div>
                                            <div style="font-weight:600;color:#e8f4ff;"><?= htmlspecialchars($u['username']) ?></div>
                                            <?php if($isMe): ?><div style="font-size:10px;color:var(--neon-green);font-family:'Orbitron',sans-serif;letter-spacing:1px;">Vous</div><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="name-cell"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></span></td>
                                <td><span class="email-cell"><?= htmlspecialchars($u['email']) ?></span></td>
                                <td>
                                    <?php if ($isMe): ?>
                                        <span class="role-pill <?= $pillClass ?>">⭐ <?= $isAdmin ? 'Admin' : 'User' ?></span>
                                    <?php else: ?>
                                        <form action="users.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="action" value="change_role">
                                            <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                <option value="user" <?= $u['role']==='user'?'selected':'' ?>>👤 Utilisateur</option>
                                                <option value="seller" <?= $u['role']==='seller'?'selected':'' ?>>🚀 Vendeur</option>
                                                <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>🔴 Admin</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isMe): ?>
                                        <span class="me-label">Compte actuel</span>
                                    <?php else: ?>
                                        <button class="btn-delete-user" onclick="openModal(<?= $u['id'] ?>, '<?= addslashes(htmlspecialchars($u['username'])) ?>')">🗑 Supprimer</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <div class="count-shown" id="countShown"><?= count($users) ?> résultat(s)</div>
                        <div><?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form id="deleteForm" action="users.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="user_id" id="deleteUserId">
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

        /* ── Search & Filter ── */
        const searchInput = document.getElementById('searchInput');
        const roleFilter  = document.getElementById('roleFilter');
        const rows        = document.querySelectorAll('#usersBody tr[data-search]');
        const countShown  = document.getElementById('countShown');

        function filterTable(){
            const q    = searchInput.value.toLowerCase();
            const role = roleFilter.value;
            let v = 0;
            rows.forEach(r=>{
                const matchQ = !q || r.dataset.search.includes(q);
                const matchR = !role || r.dataset.role === role;
                r.style.display = (matchQ && matchR) ? '' : 'none';
                if(matchQ && matchR) v++;
            });
            countShown.textContent = v + ' résultat(s)';
        }
        searchInput.addEventListener('input', filterTable);
        roleFilter.addEventListener('change', filterTable);

        /* ── Delete modal ── */
        let pendingId = null;
        function openModal(id, username){
            pendingId = id;
            document.getElementById('modalUsername').textContent = '@' + username;
            document.getElementById('deleteModal').classList.add('open');
        }
        function closeModal(){
            pendingId = null;
            document.getElementById('deleteModal').classList.remove('open');
        }
        function submitDelete(){
            if(!pendingId) return;
            document.getElementById('deleteUserId').value = pendingId;
            document.getElementById('deleteForm').submit();
        }
        document.getElementById('deleteModal').addEventListener('click',function(e){
            if(e.target===this) closeModal();
        });
    </script>
</body>
</html>