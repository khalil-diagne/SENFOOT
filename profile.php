<?php
require __DIR__ . '/config.php';

function getAvatarPath($avatarFromDB) {
    if (empty($avatarFromDB)) return null;
    $cleanFilename = basename($avatarFromDB);
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png)$/i', $cleanFilename)) return null;
    $avatarPath = __DIR__ . '/uploads/avatars/' . $cleanFilename;
    if (file_exists($avatarPath) && is_file($avatarPath)) {
        $realPath  = realpath($avatarPath);
        $uploadDir = realpath(__DIR__ . '/uploads/avatars/');
        if ($realPath && $uploadDir && strpos($realPath, $uploadDir) === 0)
            return 'uploads/avatars/' . $cleanFilename;
    }
    return null;
}

$pdo = db();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    redirect('/index.php');
}

$requested = $_SESSION['username'];
$stmt = $pdo->prepare('SELECT prenom, nom, email, username, avatar FROM visiteur WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $requested]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$notFound = !$user;

$avatarPath = !$notFound ? getAvatarPath($user['avatar'] ?? '') : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil - <?= !$notFound ? htmlspecialchars($user['username']) : 'Introuvable' ?> · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);
            --glow-green:0 0 20px rgba(0,255,136,0.5),0 0 60px rgba(0,255,136,0.15);
            --glow-blue:0 0 20px rgba(0,207,255,0.5),0 0 60px rgba(0,207,255,0.15);
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;display:flex;align-items:center;justify-content:center;padding:30px 20px;}

        /* ── BG ── */
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.045) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.045) 1px,transparent 1px);background-size:50px 50px;animation:gridMove 10s linear infinite;z-index:0;pointer-events:none;}
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

        /* ── CARD ── */
        .profile-card{
            position:relative;z-index:2;
            width:100%;max-width:700px;
            background:var(--card-bg);
            border:1px solid rgba(0,207,255,0.18);
            border-radius:24px;
            padding:48px 40px 40px;
            backdrop-filter:blur(20px);
            box-shadow:0 30px 80px rgba(0,0,0,0.55),var(--glow-blue);
            animation:cardReveal 0.9s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes cardReveal{from{opacity:0;transform:translateY(40px) scale(0.96);}to{opacity:1;transform:translateY(0) scale(1);}}

        .profile-card::before,.profile-card::after{content:'';position:absolute;width:45px;height:45px;border-color:var(--neon-green);border-style:solid;}
        .profile-card::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:24px 0 0 0;}
        .profile-card::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 24px 0;}

        /* ── NOT FOUND ── */
        .notfound{text-align:center;padding:20px;}
        .notfound-icon{font-size:64px;margin-bottom:16px;opacity:0.5;display:block;}
        .notfound h2{font-family:'Orbitron',sans-serif;font-size:18px;letter-spacing:3px;color:#ff4466;margin-bottom:12px;}
        .notfound p{color:rgba(255,255,255,0.45);font-size:15px;margin-bottom:28px;}

        /* ── PROFILE HEADER ── */
        .profile-header{display:flex;align-items:center;gap:32px;margin-bottom:32px;}

        .avatar-ring{
            flex-shrink:0;width:140px;height:140px;border-radius:50%;
            background:conic-gradient(var(--neon-green),var(--neon-blue),var(--neon-green));
            padding:3px;animation:ringRotate 4s linear infinite;
            box-shadow:var(--glow-green);
        }
        @keyframes ringRotate{from{filter:hue-rotate(0deg);}to{filter:hue-rotate(360deg);}}
        .avatar-inner{width:100%;height:100%;border-radius:50%;overflow:hidden;background:var(--deep-bg);}
        .avatar-inner img{width:100%;height:100%;object-fit:cover;}
        .avatar-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(0,255,136,0.18),rgba(0,207,255,0.12));font-family:'Orbitron',sans-serif;font-weight:900;font-size:52px;color:var(--neon-green);}

        .profile-info{flex:1;}

        .profile-name{
            font-family:'Orbitron',sans-serif;font-weight:900;
            font-size:clamp(18px,3vw,28px);letter-spacing:2px;
            background:linear-gradient(90deg,#fff,var(--neon-green),var(--neon-blue));
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
            filter:drop-shadow(0 0 12px rgba(0,255,136,0.25));
            margin-bottom:16px;
        }

        .profile-meta{
            display:flex;align-items:center;gap:10px;
            padding:10px 16px;margin-bottom:10px;
            background:rgba(0,207,255,0.05);
            border:1px solid rgba(0,207,255,0.12);border-radius:10px;
            font-size:14px;letter-spacing:0.5px;color:rgba(255,255,255,0.7);
            transition:border-color 0.3s,background 0.3s;
        }
        .profile-meta:hover{border-color:rgba(0,207,255,0.25);background:rgba(0,207,255,0.08);}
        .meta-label{font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;color:var(--neon-blue);min-width:90px;}

        /* ── ALERTS ── */
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:20px;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1px;display:flex;align-items:center;gap:10px;animation:fadeDown 0.4s ease;}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
        .alert-success{background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.22);color:var(--neon-green);}

        /* ── DIVIDER ── */
        .divider{width:100%;height:1px;background:linear-gradient(90deg,transparent,rgba(0,207,255,0.2),transparent);margin:28px 0;}

        /* ── ACTIONS ── */
        .actions-grid{display:flex;gap:12px;flex-wrap:wrap;}

        .btn{
            position:relative;overflow:hidden;
            display:inline-flex;align-items:center;gap:8px;
            padding:12px 24px;border-radius:10px;
            font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1.5px;
            border:none;cursor:pointer;text-decoration:none;
            transition:transform 0.25s,box-shadow 0.25s;
            flex:1;min-width:140px;justify-content:center;
        }
        .btn::before{content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;background:rgba(255,255,255,0.2);transform:skewX(-20deg);animation:btnShine 4s ease-in-out infinite;}
        @keyframes btnShine{0%{left:-60%;opacity:0;}20%{opacity:1;}50%{left:130%;opacity:0;}100%{left:130%;opacity:0;}}

        .btn-edit{background:linear-gradient(135deg,var(--neon-blue),#0080aa);color:#001520;font-weight:700;box-shadow:0 4px 20px rgba(0,207,255,0.25);}
        .btn-edit:hover{transform:translateY(-3px);box-shadow:var(--glow-blue);}

        .btn-pw{background:linear-gradient(135deg,var(--neon-green),#00b86b);color:#001a0d;font-weight:700;box-shadow:0 4px 20px rgba(0,255,136,0.25);}
        .btn-pw:hover{transform:translateY(-3px);box-shadow:var(--glow-green);}

        .btn-logout{background:rgba(255,68,102,0.1);border:1px solid rgba(255,68,102,0.25);color:#ff6688;font-weight:600;}
        .btn-logout:hover{background:rgba(255,68,102,0.2);border-color:#ff4466;box-shadow:0 0 20px rgba(255,68,102,0.3);transform:translateY(-2px);}

        .btn-home{background:transparent;border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.55);font-weight:400;}
        .btn-home:hover{border-color:rgba(255,255,255,0.3);color:#fff;transform:translateY(-2px);}

        .btn-seller{background:linear-gradient(135deg,#ff9500,#ffb703) !important;color:#1a0f00 !important;font-weight:700 !important;box-shadow:0 4px 20px rgba(255,149,0,0.25) !important;}
        .btn-seller:hover{transform:translateY(-3px) !important;box-shadow:0 0 20px rgba(255,149,0,0.5) !important;}

        @media(max-width:580px){
            .profile-header{flex-direction:column;text-align:center;}
            .profile-card{padding:36px 24px 32px;}
            .actions-grid{flex-direction:column;}
            .btn{min-width:unset;}
            .avatar-ring{width:120px;height:120px;}
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

    <?php if ($notFound): ?>
    <div class="profile-card">
        <div class="notfound">
            <span class="notfound-icon">🔍</span>
            <h2>Profil introuvable</h2>
            <p>Le profil demandé n'existe pas ou a été supprimé.</p>
            <a class="btn btn-home" href="accueil.php">← Retour à l'accueil</a>
        </div>
    </div>

    <?php else: ?>
    <div class="profile-card">

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">✓ Profil mis à jour avec succès</div>
        <?php endif; ?>
        <?php if (isset($_GET['avatar_updated'])): ?>
            <div class="alert alert-success">✓ Avatar mis à jour avec succès</div>
        <?php endif; ?>
        <?php if (isset($_GET['pw_changed'])): ?>
            <div class="alert alert-success">✓ Mot de passe mis à jour</div>
        <?php endif; ?>

        <!-- Header -->
        <div class="profile-header">
            <div class="avatar-ring">
                <div class="avatar-inner">
                    <?php if ($avatarPath): ?>
                        <img src="<?= htmlspecialchars($avatarPath) ?>"
                             alt="Avatar"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="avatar-placeholder" style="display:none;">
                            <?= strtoupper(substr($user['prenom'] ?? $user['username'], 0, 1)) ?>
                        </div>
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($user['prenom'] ?? $user['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>

                <div class="profile-meta">
                    <span class="meta-label">👤 Pseudo</span>
                    @<?= htmlspecialchars($user['username']) ?>
                </div>
                <div class="profile-meta">
                    <span class="meta-label">📧 Email</span>
                    <?= htmlspecialchars($user['email']) ?>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Actions -->
        <div class="actions-grid">
            <a class="btn btn-edit" href="profile_edit.php">✏️ Éditer</a>
            <a class="btn btn-pw" href="changer_mot_de_passe.php">🔐 Mot de passe</a>
            <a class="btn btn-logout" href="logout.php">🚪 Déconnexion</a>
            <a class="btn btn-home" href="accueil.php">🏠 Accueil</a>
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller'): ?>
                <a class="btn btn-seller" href="apply_seller.php" style="background:linear-gradient(135deg,#ff9500,#ffb703);color:#1a0f00;font-weight:700;box-shadow:0 4px 20px rgba(255,149,0,0.25);">💼 Devenir Vendeur</a>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>

    <script>
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<40;i++){
                const p=document.createElement('div');
                p.className='particle';
                const g=Math.random()>0.5;
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${5+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*120}px;background:${g?'#00ff88':'#00cfff'};box-shadow:0 0 6px ${g?'#00ff88':'#00cfff'};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();

        <?php if (!empty($user['avatar'])): ?>
        console.log('Avatar DB:', '<?= addslashes($user['avatar']) ?>');
        console.log('Avatar Path:', '<?= addslashes($avatarPath ?? 'null') ?>');
        <?php endif; ?>
    </script>
</body>
</html>