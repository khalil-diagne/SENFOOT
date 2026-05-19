<?php
require __DIR__ . '/../config.php';
require_admin();
$pdo = db();

$stmt = $pdo->query('SELECT o.id, o.total_price, o.status, o.order_date, v.username FROM orders AS o JOIN visiteur AS v ON o.user_id = v.id ORDER BY o.order_date DESC');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusColor($s) {
    return match($s) {
        'validee','livree' => ['#00ff88','rgba(0,255,136,0.12)','rgba(0,255,136,0.3)'],
        'annulee'          => ['#ff4466','rgba(255,68,102,0.12)','rgba(255,68,102,0.3)'],
        'en_cours'         => ['#00cfff','rgba(0,207,255,0.12)','rgba(0,207,255,0.3)'],
        default            => ['#ffaa00','rgba(255,170,0,0.12)','rgba(255,170,0,0.3)'],
    };
}
function formatStatus($s) {
    return match($s) {
        'en_attente'=>'En attente','validee'=>'Validée','annulee'=>'Annulée','en_cours'=>'En cours','livree'=>'Livrée',
        default=>ucfirst(str_replace('_',' ',$s))
    };
}

// Counts per status
$counts = ['total'=>count($orders),'validee'=>0,'en_attente'=>0,'annulee'=>0,'en_cours'=>0];
foreach($orders as $o) { if(isset($counts[$o['status']])) $counts[$o['status']]++; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Admin · Dribbleur Store</title>
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
        .page-title-row{margin-bottom:28px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(18px,2.5vw,28px);letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-red),#ff8844);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 12px rgba(255,68,102,0.3));margin-bottom:6px;}
        .page-desc{color:rgba(255,255,255,0.35);font-size:14px;letter-spacing:1px;}

        /* ── MINI STATS ── */
        .mini-stats{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
        .mini-stat{padding:12px 20px;background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);border-radius:12px;backdrop-filter:blur(12px);display:flex;align-items:center;gap:10px;transition:transform 0.2s,box-shadow 0.2s;}
        .mini-stat:hover{transform:translateY(-3px);}
        .mini-stat-val{font-family:'Orbitron',sans-serif;font-weight:900;font-size:20px;}
        .mini-stat-label{font-size:12px;letter-spacing:1px;color:rgba(255,255,255,0.4);}

        /* ── SEARCH ── */
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
        thead th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.3);padding:14px 18px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05);white-space:nowrap;}
        tbody td{padding:14px 18px;font-size:14px;color:rgba(255,255,255,0.72);border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.2s;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:rgba(255,255,255,0.025);}

        .order-id{font-family:'Orbitron',sans-serif;font-size:12px;color:var(--neon-blue);}
        .username-cell{display:flex;align-items:center;gap:8px;}
        .user-avatar-sm{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,rgba(0,255,136,0.2),rgba(0,207,255,0.15));display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:11px;color:var(--neon-green);flex-shrink:0;}
        .price-cell{font-family:'Orbitron',sans-serif;font-size:13px;color:var(--neon-green);}
        .date-cell{font-size:12px;color:rgba(255,255,255,0.4);}

        .status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1px;white-space:nowrap;}
        .status-dot-sm{width:6px;height:6px;border-radius:50%;animation:dotPulse 2s ease-in-out infinite;}
        @keyframes dotPulse{0%,100%{opacity:1;}50%{opacity:0.4;}}

        .btn-details{
            display:inline-flex;align-items:center;gap:6px;
            padding:7px 14px;border-radius:8px;text-decoration:none;
            background:rgba(0,207,255,0.08);border:1px solid rgba(0,207,255,0.2);
            color:var(--neon-blue);font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1px;
            transition:all 0.25s;
        }
        .btn-details:hover{background:rgba(0,207,255,0.16);border-color:var(--neon-blue);box-shadow:var(--glow-blue);transform:translateY(-2px);}

        /* ── EMPTY ── */
        .empty-row td{text-align:center;padding:50px;color:rgba(255,255,255,0.25);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:2px;}

        /* ── PAGINATION info ── */
        .table-footer{padding:14px 18px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid rgba(255,255,255,0.04);color:rgba(255,255,255,0.3);font-size:12px;letter-spacing:1px;}
        .count-shown{font-family:'Orbitron',sans-serif;font-size:11px;color:var(--neon-blue);}

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
                <a href="articles.php" class="nav-item"><span class="nav-icon">📦</span> Articles</a>
                <a href="pending_articles.php" class="nav-item"><span class="nav-icon">⏳</span> Articles Vendeurs</a>
                <a href="pending_sellers.php" class="nav-item"><span class="nav-icon">🚀</span> Candidatures Vendeurs</a>
                <a href="orders.php" class="nav-item active"><span class="nav-icon">🛒</span> Commandes</a>
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
                <div class="page-breadcrumb">Admin <span>/</span> Commandes</div>
                <div class="topbar-user">
                    <span class="admin-dot"></span>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
            </div>

            <div class="content">

                <div class="page-title-row">
                    <div class="page-title">Gestion des Commandes</div>
                    <div class="page-desc"><?= count($orders) ?> commande(s) au total</div>
                </div>

                <!-- MINI STATS -->
                <div class="mini-stats">
                    <div class="mini-stat" style="border-color:rgba(0,207,255,0.2);">
                        <span style="font-size:20px;">🛒</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-blue);"><?= $counts['total'] ?></div><div class="mini-stat-label">Total</div></div>
                    </div>
                    <div class="mini-stat" style="border-color:rgba(0,255,136,0.2);">
                        <span style="font-size:20px;">✅</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-green);"><?= $counts['validee'] ?></div><div class="mini-stat-label">Validées</div></div>
                    </div>
                    <div class="mini-stat" style="border-color:rgba(255,170,0,0.2);">
                        <span style="font-size:20px;">⏳</span>
                        <div><div class="mini-stat-val" style="color:#ffaa00;"><?= $counts['en_attente'] ?></div><div class="mini-stat-label">En attente</div></div>
                    </div>
                    <div class="mini-stat" style="border-color:rgba(255,68,102,0.2);">
                        <span style="font-size:20px;">❌</span>
                        <div><div class="mini-stat-val" style="color:var(--neon-red);"><?= $counts['annulee'] ?></div><div class="mini-stat-label">Annulées</div></div>
                    </div>
                </div>

                <!-- TOOLBAR -->
                <div class="table-toolbar">
                    <div class="search-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" id="searchInput" placeholder="Rechercher par client, ID...">
                    </div>
                    <select class="filter-select" id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente">En attente</option>
                        <option value="validee">Validée</option>
                        <option value="en_cours">En cours</option>
                        <option value="livree">Livrée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>

                <!-- TABLE -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <?php if (empty($orders)): ?>
                                <tr class="empty-row"><td colspan="6">📭 Aucune commande pour le moment</td></tr>
                            <?php else: ?>
                                <?php foreach($orders as $o):
                                    [$col,$bg,$border] = statusColor($o['status']); ?>
                                <tr data-username="<?= strtolower(htmlspecialchars($o['username'])) ?>"
                                    data-id="<?= $o['id'] ?>"
                                    data-status="<?= htmlspecialchars($o['status']) ?>">
                                    <td><span class="order-id">#<?= htmlspecialchars($o['id']) ?></span></td>
                                    <td>
                                        <div class="username-cell">
                                            <div class="user-avatar-sm"><?= strtoupper(substr($o['username'],0,1)) ?></div>
                                            <?= htmlspecialchars($o['username']) ?>
                                        </div>
                                    </td>
                                    <td><span class="price-cell"><?= number_format($o['total_price'],0,',',' ') ?> FCFA</span></td>
                                    <td><span class="date-cell"><?= date('d/m/Y H:i', strtotime($o['order_date'])) ?></span></td>
                                    <td>
                                        <span class="status-pill" style="background:<?= $bg ?>;border:1px solid <?= $border ?>;color:<?= $col ?>;">
                                            <span class="status-dot-sm" style="background:<?= $col ?>;box-shadow:0 0 5px <?= $col ?>;"></span>
                                            <?= formatStatus($o['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?= $o['id'] ?>" class="btn-details">
                                            Détails →
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="table-footer">
                        <div class="count-shown" id="countShown"><?= count($orders) ?> résultat(s)</div>
                        <div><?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

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

        /* ── Search & filter ── */
        const searchInput  = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const countShown   = document.getElementById('countShown');
        const rows         = document.querySelectorAll('#ordersBody tr[data-username]');

        function filterTable() {
            const query  = searchInput.value.toLowerCase();
            const status = statusFilter.value;
            let visible  = 0;
            rows.forEach(row => {
                const username = row.dataset.username;
                const id       = row.dataset.id;
                const rowStatus= row.dataset.status;
                const matchQ   = !query || username.includes(query) || id.includes(query);
                const matchS   = !status || rowStatus === status;
                row.style.display = (matchQ && matchS) ? '' : 'none';
                if(matchQ && matchS) visible++;
            });
            countShown.textContent = visible + ' résultat(s)';
        }

        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>