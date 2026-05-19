<?php
require __DIR__ . '/config.php';
$pdo = db();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    redirect('/index.php');
}

$user_id = $_SESSION['user_id_from_db'] ?? null;
$username = $_SESSION['username'] ?? 'Utilisateur';
$orders = [];

if ($user_id) {
    try {
        $stmt = $pdo->prepare('SELECT id, total_price, status, order_date FROM orders WHERE user_id = :user_id ORDER BY order_date DESC');
        $stmt->execute([':user_id' => $user_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération des commandes.";
    }
}

function formatStatus($status) {
    $statuses = [
        'en_attente' => 'En attente',
        'validee'    => 'Validée',
        'annulee'    => 'Annulée',
        'en_cours'   => 'En cours',
        'livree'     => 'Livrée'
    ];
    return $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function statusColor($status) {
    return match($status) {
        'validee','livree' => ['#00ff88','rgba(0,255,136,0.12)','rgba(0,255,136,0.3)'],
        'annulee'          => ['#ff4466','rgba(255,68,102,0.12)','rgba(255,68,102,0.3)'],
        'en_cours'         => ['#00cfff','rgba(0,207,255,0.12)','rgba(0,207,255,0.3)'],
        default            => ['#ffaa00','rgba(255,170,0,0.12)','rgba(255,170,0,0.3)'],
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Commandes - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --deep-bg: #020811;
            --card-bg: rgba(0,20,40,0.8);
            --glow-green: 0 0 20px rgba(0,255,136,0.5),0 0 60px rgba(0,255,136,0.15);
            --glow-blue: 0 0 20px rgba(0,207,255,0.5),0 0 60px rgba(0,207,255,0.15);
            --nav-h: 70px;
        }

        * { margin:0;padding:0;box-sizing:border-box; }
        html { scroll-behavior:smooth; }

        body {
            background: var(--deep-bg);
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── BG ── */
        .bg-grid {
            position:fixed;inset:0;
            background-image:
                linear-gradient(rgba(0,207,255,0.045) 1px,transparent 1px),
                linear-gradient(90deg,rgba(0,207,255,0.045) 1px,transparent 1px);
            background-size:50px 50px;
            animation:gridMove 10s linear infinite;
            z-index:0;pointer-events:none;
        }
        @keyframes gridMove{from{background-position:0 0;}to{background-position:50px 50px;}}

        .orb{position:fixed;border-radius:50%;filter:blur(90px);opacity:0.22;animation:orbFloat linear infinite;z-index:0;pointer-events:none;}
        .orb-1{width:450px;height:450px;background:#00ff88;top:-130px;left:-130px;animation-duration:16s;}
        .orb-2{width:350px;height:350px;background:#00cfff;bottom:-80px;right:-80px;animation-duration:12s;}
        .orb-3{width:280px;height:280px;background:#8b5cf6;top:40%;left:58%;animation-duration:20s;}
        @keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(35px,-25px) scale(1.06);}66%{transform:translate(-20px,35px) scale(0.94);}}

        .scanlines{position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.025) 2px,rgba(0,0,0,0.025) 4px);pointer-events:none;z-index:1;}

        .particles{position:fixed;inset:0;z-index:0;pointer-events:none;}
        .particle{position:absolute;border-radius:50%;animation:particleFly linear infinite;}
        @keyframes particleFly{from{transform:translateY(100vh) translateX(0);opacity:0;}10%{opacity:1;}90%{opacity:1;}to{transform:translateY(-100px) translateX(var(--drift));opacity:0;}}

        /* ── NAV ── */
        nav {
            position:fixed;top:0;left:0;right:0;height:var(--nav-h);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 30px;
            background:rgba(2,8,17,0.88);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(0,207,255,0.15);
            z-index:100;box-shadow:0 4px 30px rgba(0,0,0,0.5);
        }
        nav::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-green),var(--neon-blue),transparent);animation:scanH 4s ease-in-out infinite;}
        @keyframes scanH{0%,100%{opacity:0.4;}50%{opacity:1;}}

        .nav-logo{
            font-family:'Orbitron',sans-serif;font-weight:900;font-size:17px;letter-spacing:2px;
            background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
            text-decoration:none;
        }

        .nav-user{
            display:flex;align-items:center;gap:8px;
            padding:8px 16px;
            border:1px solid rgba(0,207,255,0.2);border-radius:8px;
            background:rgba(0,207,255,0.05);
            font-family:'Rajdhani',sans-serif;font-size:14px;letter-spacing:1px;
            color:#e0f7ff;
        }

        /* ── LAYOUT ── */
        .container{
            position:relative;z-index:2;
            max-width:1100px;margin:0 auto;
            padding:calc(var(--nav-h)+50px) 24px 80px;
        }

        /* ── HEADER ── */
        .page-header{text-align:center;margin-bottom:50px;animation:heroReveal 0.9s cubic-bezier(0.16,1,0.3,1);}
        @keyframes heroReveal{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}

        .page-title{
            font-family:'Orbitron',sans-serif;font-weight:900;
            font-size:clamp(22px,4vw,40px);letter-spacing:3px;
            background:linear-gradient(135deg,#fff 0%,var(--neon-green) 50%,var(--neon-blue) 100%);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
            filter:drop-shadow(0 0 20px rgba(0,255,136,0.3));
            margin-bottom:10px;
        }
        .page-subtitle{color:rgba(255,255,255,0.4);font-size:15px;letter-spacing:2px;}

        /* ── ERROR ── */
        .error-box{
            padding:16px 24px;margin-bottom:30px;
            background:rgba(255,68,102,0.08);
            border:1px solid rgba(255,68,102,0.25);border-radius:12px;
            color:#ff4466;font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:1px;
            text-align:center;
        }

        /* ── STATS ── */
        .stats-grid{
            display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:16px;margin-bottom:40px;
        }

        .stat-card{
            background:var(--card-bg);
            border:1px solid rgba(0,207,255,0.12);border-radius:16px;
            padding:24px 20px;text-align:center;
            backdrop-filter:blur(16px);
            transition:transform 0.3s,border-color 0.3s,box-shadow 0.3s;
            animation:cardIn 0.6s ease both;
        }
        .stat-card:hover{transform:translateY(-6px);border-color:var(--neon-blue);box-shadow:var(--glow-blue);}

        .stat-icon{font-size:28px;margin-bottom:10px;}
        .stat-value{
            font-family:'Orbitron',sans-serif;font-weight:900;font-size:26px;
            color:var(--neon-blue);margin-bottom:6px;
        }
        .stat-label{font-size:12px;letter-spacing:1.5px;color:rgba(255,255,255,0.4);text-transform:uppercase;}

        /* ── ORDERS ── */
        .orders-list{display:grid;gap:20px;}

        .order-card{
            background:var(--card-bg);
            border:1px solid rgba(0,207,255,0.12);border-radius:18px;
            overflow:hidden;backdrop-filter:blur(16px);
            transition:transform 0.35s,border-color 0.35s,box-shadow 0.35s;
            opacity:0;transform:translateY(20px);
            animation:cardIn 0.6s ease forwards;
        }
        .order-card:hover{transform:translateY(-6px);border-color:var(--neon-blue);box-shadow:var(--glow-blue);}

        @keyframes cardIn{to{opacity:1;transform:translateY(0);}}

        .order-header{
            padding:20px 28px;
            display:flex;justify-content:space-between;align-items:center;
            border-bottom:1px solid rgba(255,255,255,0.06);
            background:linear-gradient(90deg,rgba(0,207,255,0.06),transparent);
        }

        .order-id{
            font-family:'Orbitron',sans-serif;font-weight:700;font-size:14px;letter-spacing:2px;
            color:var(--neon-blue);
        }
        .order-id span{
            display:inline-block;margin-left:8px;padding:3px 10px;
            background:rgba(0,207,255,0.12);border-radius:6px;font-size:13px;
        }

        .status-badge{
            display:inline-flex;align-items:center;gap:8px;
            padding:7px 16px;border-radius:20px;
            font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;font-weight:700;
        }
        .status-dot{width:7px;height:7px;border-radius:50%;animation:dotPulse 2s ease-in-out infinite;}
        @keyframes dotPulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.5;transform:scale(0.7);}}

        .order-body{
            padding:24px 28px;
            display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:16px;
        }

        .detail-item{
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.05);
            border-radius:12px;padding:16px 18px;
            transition:background 0.3s,transform 0.2s;
        }
        .detail-item:hover{background:rgba(255,255,255,0.06);transform:translateY(-2px);}

        .detail-label{
            font-size:11px;letter-spacing:1.5px;text-transform:uppercase;
            color:rgba(255,255,255,0.4);margin-bottom:8px;
            display:flex;align-items:center;gap:6px;
        }
        .detail-value{font-size:17px;font-weight:600;letter-spacing:0.5px;}
        .detail-value.green{color:var(--neon-green);font-family:'Orbitron',sans-serif;font-size:20px;}
        .detail-value.blue{color:var(--neon-blue);}

        /* ── EMPTY ── */
        .empty-state{
            background:var(--card-bg);border:1px solid rgba(0,207,255,0.12);
            border-radius:20px;padding:70px 40px;text-align:center;
            backdrop-filter:blur(16px);
        }
        .empty-icon{font-size:64px;margin-bottom:18px;opacity:0.4;display:block;}
        .empty-state h3{
            font-family:'Orbitron',sans-serif;font-size:16px;letter-spacing:2px;
            color:var(--neon-blue);margin-bottom:12px;
        }
        .empty-state p{color:rgba(255,255,255,0.45);font-size:15px;margin-bottom:30px;}

        /* ── BUTTONS ── */
        .btn-primary{
            position:relative;overflow:hidden;
            display:inline-flex;align-items:center;gap:8px;
            padding:13px 36px;
            background:linear-gradient(135deg,var(--neon-green),#00b86b);
            color:#001a0d;font-family:'Orbitron',sans-serif;font-weight:700;
            font-size:12px;letter-spacing:2px;
            border:none;border-radius:50px;cursor:pointer;text-decoration:none;
            box-shadow:0 6px 28px rgba(0,255,136,0.3);
            transition:transform 0.3s,box-shadow 0.3s;
        }
        .btn-primary::before{
            content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;
            background:rgba(255,255,255,0.25);transform:skewX(-20deg);
            animation:btnShine 3s ease-in-out infinite;
        }
        @keyframes btnShine{0%{left:-60%;opacity:0;}20%{opacity:1;}50%{left:130%;opacity:0;}100%{left:130%;opacity:0;}}
        .btn-primary:hover{transform:translateY(-3px);box-shadow:var(--glow-green);}

        .btn-outline{
            display:inline-flex;align-items:center;gap:8px;
            padding:13px 36px;
            background:transparent;border:1px solid rgba(0,207,255,0.25);border-radius:50px;
            color:var(--neon-blue);font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:2px;
            text-decoration:none;transition:all 0.3s;
        }
        .btn-outline:hover{border-color:var(--neon-blue);box-shadow:var(--glow-blue);transform:translateY(-3px);}

        .btn-wrap{text-align:center;margin-top:40px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;}

        /* ── RESPONSIVE ── */
        @media(max-width:600px){
            .order-header{flex-direction:column;gap:12px;align-items:flex-start;}
            .page-title{font-size:22px;}
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

    <!-- NAV -->
    <nav>
        <a href="accueil.php" class="nav-logo">Dribbleur Store</a>
        <div class="nav-user">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.4"/><path d="M4 20c0-3.3 2.7-6 6-6h4c3.3 0 6 2.7 6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= htmlspecialchars($username) ?>
        </div>
    </nav>

    <div class="container">

        <!-- HEADER -->
        <div class="page-header">
            <h1 class="page-title">Historique des Commandes</h1>
            <p class="page-subtitle">Suivez l'état de vos commandes en temps réel</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-box">⚠ <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($orders)):
            $total = array_sum(array_column($orders, 'total_price'));
            $validees = count(array_filter($orders, fn($o)=>$o['status']==='validee'));
            $attente  = count(array_filter($orders, fn($o)=>$o['status']==='en_attente'));
        ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card" style="animation-delay:0s">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= count($orders) ?></div>
                <div class="stat-label">Total commandes</div>
            </div>
            <div class="stat-card" style="animation-delay:0.08s">
                <div class="stat-icon">✅</div>
                <div class="stat-value" style="color:var(--neon-green)"><?= $validees ?></div>
                <div class="stat-label">Validées</div>
            </div>
            <div class="stat-card" style="animation-delay:0.16s">
                <div class="stat-icon">⏳</div>
                <div class="stat-value" style="color:#ffaa00"><?= $attente ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-card" style="animation-delay:0.24s">
                <div class="stat-icon">💰</div>
                <div class="stat-value" style="color:var(--neon-green)"><?= number_format($total, 0, ',', ' ') ?></div>
                <div class="stat-label">Total FCFA</div>
            </div>
        </div>

        <!-- ORDERS -->
        <div class="orders-list">
            <?php foreach($orders as $i => $order):
                [$col, $bg, $border] = statusColor($order['status']);
            ?>
            <div class="order-card" style="animation-delay:<?= $i*0.08 ?>s">
                <div class="order-header">
                    <div class="order-id">Commande <span>#<?= htmlspecialchars($order['id']) ?></span></div>
                    <span class="status-badge" style="background:<?= $bg ?>;border:1px solid <?= $border ?>;color:<?= $col ?>;">
                        <span class="status-dot" style="background:<?= $col ?>;box-shadow:0 0 8px <?= $col ?>;"></span>
                        <?= htmlspecialchars(formatStatus($order['status'])) ?>
                    </span>
                </div>
                <div class="order-body">
                    <div class="detail-item">
                        <div class="detail-label">📅 Date de commande</div>
                        <div class="detail-value blue"><?= date('d/m/Y à H:i', strtotime($order['order_date'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">💰 Prix Total</div>
                        <div class="detail-value green"><?= number_format($order['total_price'], 0, ',', ' ') ?> FCFA</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- EMPTY -->
        <div class="empty-state">
            <span class="empty-icon">📭</span>
            <h3>Aucune commande pour le moment</h3>
            <p>Vous n'avez encore passé aucune commande. Découvrez nos articles disponibles !</p>
            <a href="list_articles.php" class="btn-primary">Découvrir nos articles</a>
        </div>
        <?php endif; ?>

        <div class="btn-wrap">
            <a href="accueil.php" class="btn-outline">← Retour à l'accueil</a>
        </div>

    </div>

    <script>
        // Particles
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<45;i++){
                const p=document.createElement('div');
                p.className='particle';
                const g=Math.random()>0.5;
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${5+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*120}px;background:${g?'#00ff88':'#00cfff'};box-shadow:0 0 6px ${g?'#00ff88':'#00cfff'};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();
    </script>
</body>
</html>