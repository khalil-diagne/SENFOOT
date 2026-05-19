<?php
require __DIR__ . '/config.php';

ensure_store_schema();
$pdo = db();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    redirect('/index.php');
}

$username = $_SESSION['username'] ?? 'Utilisateur';
$error = '';

try {
    $stmt = $pdo->prepare('SELECT prenom, nom, email, telephone, adresse, ville, username, avatar FROM visiteur WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die('Utilisateur introuvable');
    }
} catch (PDOException $e) {
    error_log('Profile edit load error: ' . $e->getMessage());
    die('Une erreur serveur est survenue lors du chargement du profil.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');

    $user['prenom'] = $prenom;
    $user['nom'] = $nom;
    $user['email'] = $email;
    $user['telephone'] = $telephone;
    $user['adresse'] = $adresse;
    $user['ville'] = $ville;

    if ($prenom === '' || $nom === '' || $email === '' || $telephone === '' || $adresse === '' || $ville === '') {
        $error = 'Tous les champs sont requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } elseif (!preg_match('/^\d{9,15}$/', preg_replace('/\D+/', '', $telephone))) {
        $error = 'Telephone invalide';
    } else {
        try {
            $check = $pdo->prepare('SELECT username FROM visiteur WHERE email = :email AND username != :username LIMIT 1');
            $check->execute([':email' => $email, ':username' => $_SESSION['username']]);

            if ($check->fetch()) {
                $error = 'Cet email est deja utilise';
            } else {
                $update = $pdo->prepare(
                    'UPDATE visiteur
                     SET prenom = :prenom, nom = :nom, email = :email, telephone = :telephone, adresse = :adresse, ville = :ville
                     WHERE username = :username'
                );
                $update->execute([
                    ':prenom' => $prenom,
                    ':nom' => $nom,
                    ':email' => $email,
                    ':telephone' => $telephone,
                    ':adresse' => $adresse,
                    ':ville' => $ville,
                    ':username' => $_SESSION['username'],
                ]);

                $_SESSION['prenom'] = $prenom;
                $_SESSION['nom'] = $nom;
                $_SESSION['email'] = $email;

                header('Location: profile.php?updated=1');
                exit();
            }
        } catch (PDOException $e) {
            error_log('Profile edit update error: ' . $e->getMessage());
            $error = 'Une erreur serveur est survenue lors de la mise a jour.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $fileSize = $_FILES['avatar']['size'];

    if (!in_array($fileExt, $allowed, true)) {
        $error = 'Format non autorise. Utilisez JPG ou PNG';
    } elseif ($fileSize > 2097152) {
        $error = 'Le fichier est trop volumineux (max 2MB)';
    } else {
        $newFileName = 'avatar_' . $_SESSION['username'] . '_' . time() . '.' . $fileExt;
        $uploadPath = 'uploads/avatars/' . $newFileName;
        if (!is_dir('uploads/avatars')) {
            mkdir('uploads/avatars', 0777, true);
        }
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
            try {
                $pdo->prepare('UPDATE visiteur SET avatar = :avatar WHERE username = :username')
                    ->execute([':avatar' => $uploadPath, ':username' => $_SESSION['username']]);
                header('Location: profile.php?avatar_updated=1');
                exit();
            } catch (PDOException $e) {
                error_log('Profile avatar save error: ' . $e->getMessage());
                $error = 'Une erreur serveur est survenue lors de la sauvegarde de l\'avatar.';
            }
        } else {
            $error = 'Erreur lors de l\'upload';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Dribbleur Store</title>
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
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}
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
        nav{position:fixed;top:0;left:0;right:0;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,207,255,0.15);z-index:100;box-shadow:0 4px 30px rgba(0,0,0,0.5);}
        nav::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-green),var(--neon-blue),transparent);animation:scanH 4s ease-in-out infinite;}
        @keyframes scanH{0%,100%{opacity:0.4;}50%{opacity:1;}}
        .nav-logo{font-family:'Orbitron',sans-serif;font-weight:900;font-size:17px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;}
        .nav-user{display:flex;align-items:center;gap:8px;padding:8px 16px;border:1px solid rgba(0,207,255,0.2);border-radius:8px;background:rgba(0,207,255,0.05);font-family:'Rajdhani',sans-serif;font-size:14px;letter-spacing:1px;color:#e0f7ff;}
        .container{position:relative;z-index:2;max-width:960px;margin:0 auto;padding:calc(var(--nav-h)+50px) 24px 80px;animation:heroReveal 0.8s cubic-bezier(0.16,1,0.3,1);}
        @keyframes heroReveal{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
        .page-header{text-align:center;margin-bottom:44px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(22px,4vw,38px);letter-spacing:3px;background:linear-gradient(135deg,#fff 0%,var(--neon-green) 50%,var(--neon-blue) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 20px rgba(0,255,136,0.3));margin-bottom:8px;}
        .page-subtitle{color:rgba(255,255,255,0.4);font-size:15px;letter-spacing:2px;}
        .alert{padding:14px 20px;border-radius:12px;margin-bottom:24px;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1px;display:flex;align-items:center;gap:10px;animation:slideDown 0.4s ease;}
        @keyframes slideDown{from{opacity:0;transform:translateY(-12px);}to{opacity:1;transform:translateY(0);}}
        .alert-success{background:rgba(0,255,136,0.08);border:1px solid rgba(0,255,136,0.25);color:var(--neon-green);}
        .alert-error{background:rgba(255,68,102,0.08);border:1px solid rgba(255,68,102,0.25);color:#ff4466;}
        .card{background:var(--card-bg);border:1px solid rgba(0,207,255,0.15);border-radius:20px;padding:36px;backdrop-filter:blur(16px);box-shadow:0 20px 60px rgba(0,0,0,0.4);position:relative;overflow:hidden;}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,255,136,0.3),rgba(0,207,255,0.3),transparent);}
        .profile-grid{display:grid;grid-template-columns:220px 1fr;gap:24px;margin-bottom:24px;}
        .avatar-section{text-align:center;display:flex;flex-direction:column;align-items:center;gap:18px;}
        .avatar-ring{width:160px;height:160px;border-radius:50%;background:conic-gradient(var(--neon-green),var(--neon-blue),var(--neon-green));padding:3px;animation:ringRotate 4s linear infinite;box-shadow:var(--glow-green);}
        @keyframes ringRotate{from{filter:hue-rotate(0deg);}to{filter:hue-rotate(360deg);}}
        .avatar-inner{width:100%;height:100%;border-radius:50%;overflow:hidden;background:var(--deep-bg);}
        .avatar-inner img{width:100%;height:100%;object-fit:cover;}
        .avatar-placeholder{width:100%;height:100%;background:linear-gradient(135deg,rgba(0,255,136,0.2),rgba(0,207,255,0.2));display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-weight:900;font-size:56px;color:var(--neon-green);}
        .avatar-upload-btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(0,207,255,0.07);border:1px solid rgba(0,207,255,0.25);border-radius:8px;color:#e0f7ff;font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;cursor:pointer;transition:all 0.3s;}
        .avatar-upload-btn:hover{border-color:var(--neon-blue);box-shadow:var(--glow-blue);transform:translateY(-2px);}
        .avatar-upload-btn input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;font-size:0;}
        .avatar-hint{font-size:11px;color:rgba(255,255,255,0.3);letter-spacing:0.5px;}
        .username-tag{padding:10px 20px;background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.2);border-radius:8px;font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:var(--neon-green);}
        .form-group{margin-bottom:22px;}
        .form-label{font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:2px;color:var(--neon-blue);text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
        .input-wrap{position:relative;}
        .input-wrap::after{content:'';position:absolute;bottom:0;left:0;width:0;height:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));border-radius:2px;transition:width 0.4s;}
        .input-wrap:focus-within::after{width:100%;}
        input[type="text"],input[type="email"]{width:100%;padding:13px 16px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;letter-spacing:0.5px;transition:border-color 0.3s,background 0.3s,box-shadow 0.3s;outline:none;}
        input[type="text"]:focus,input[type="email"]:focus{border-color:var(--neon-green);background:rgba(0,255,136,0.07);box-shadow:0 0 20px rgba(0,255,136,0.15);}
        input::placeholder{color:rgba(255,255,255,0.22);}
        .btn-row{display:flex;gap:14px;margin-top:28px;}
        .btn-save{position:relative;overflow:hidden;flex:1;padding:14px;background:linear-gradient(135deg,var(--neon-green),#00b86b);color:#001a0d;font-family:'Orbitron',sans-serif;font-weight:700;font-size:12px;letter-spacing:2px;border:none;border-radius:10px;cursor:pointer;box-shadow:0 4px 20px rgba(0,255,136,0.3);transition:transform 0.2s,box-shadow 0.3s;}
        .btn-save::before{content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;background:rgba(255,255,255,0.25);transform:skewX(-20deg);animation:btnShine 3s ease-in-out infinite;}
        @keyframes btnShine{0%{left:-60%;opacity:0;}20%{opacity:1;}50%{left:130%;opacity:0;}100%{left:130%;opacity:0;}}
        .btn-save:hover{transform:translateY(-3px);box-shadow:var(--glow-green);}
        .btn-cancel{padding:14px 24px;background:transparent;border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:rgba(255,255,255,0.5);font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:2px;cursor:pointer;transition:all 0.3s;}
        .btn-cancel:hover{border-color:rgba(255,255,255,0.3);color:#fff;}
        .section-title{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:var(--neon-blue);margin-bottom:20px;}
        .actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;}
        .action-card{display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px 16px;background:rgba(255,255,255,0.03);border:1px solid rgba(0,207,255,0.1);border-radius:16px;text-decoration:none;color:#fff;transition:transform 0.3s,border-color 0.3s,box-shadow 0.3s;position:relative;overflow:hidden;}
        .action-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));opacity:0;transition:opacity 0.3s;}
        .action-card:hover{transform:translateY(-6px);border-color:var(--neon-blue);box-shadow:var(--glow-blue);}
        .action-card:hover::before{opacity:1;}
        .action-card.danger:hover{border-color:#ff4466;box-shadow:0 0 20px rgba(255,68,102,0.3);}
        .action-icon{font-size:28px;}
        .action-title{font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;color:#e0f7ff;text-align:center;}
        .action-desc{font-size:12px;color:rgba(255,255,255,0.35);text-align:center;}
        @media(max-width:700px){.profile-grid{grid-template-columns:1fr;}.avatar-section{padding-bottom:10px;}.btn-row{flex-direction:column;}}
    </style>
</head>
<body>

    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>
    <div class="scanlines"></div>

    <nav>
        <a href="accueil.php" class="nav-logo">Dribbleur Store</a>
        <div class="nav-user"><?= htmlspecialchars($username) ?></div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Mon Profil</h1>
            <p class="page-subtitle">Gerez vos informations personnelles</p>
        </div>

        <?php if (isset($_GET['checkout_required'])): ?>
            <div class="alert alert-error">Completez toutes vos informations personnelles avant de continuer la commande.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Profil mis a jour avec succes.</div>
        <?php endif; ?>
        <?php if (isset($_GET['avatar_updated'])): ?>
            <div class="alert alert-success">Avatar mis a jour avec succes.</div>
        <?php endif; ?>
        <?php if (isset($_GET['applied'])): ?>
            <div class="alert alert-success">Votre candidature a été envoyée avec succès et est en attente de validation.</div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-grid" style="margin-bottom:24px;">
            <div class="card avatar-section">
                <div class="avatar-ring">
                    <div class="avatar-inner">
                        <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?= strtoupper(substr($user['prenom'] ?? 'U', 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <label class="avatar-upload-btn">
                        Changer l'avatar
                        <input type="file" name="avatar" accept="image/jpeg,image/png" onchange="this.form.submit()">
                    </label>
                    <div class="avatar-hint">JPG / PNG - max 2MB</div>
                </form>

                <div class="username-tag">@<?= htmlspecialchars($user['username']) ?></div>
            </div>

            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <div class="form-label">Prenom</div>
                        <div class="input-wrap">
                            <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" placeholder="Votre prenom" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Nom</div>
                        <div class="input-wrap">
                            <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" placeholder="Votre nom" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Email</div>
                        <div class="input-wrap">
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="votre.email@exemple.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Telephone</div>
                        <div class="input-wrap">
                            <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" placeholder="77 507 29 36" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Adresse</div>
                        <div class="input-wrap">
                            <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" placeholder="Votre adresse complete" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Ville</div>
                        <div class="input-wrap">
                            <input type="text" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '') ?>" placeholder="Votre ville" required>
                        </div>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn-cancel" onclick="window.location.href='accueil.php'">Annuler</button>
                        <button type="submit" name="update_profile" class="btn-save">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (($_SESSION['role'] ?? 'user') === 'user'): ?>
        <div class="card" style="margin-bottom:24px; border-color:var(--neon-green);">
            <div class="section-title" style="color:var(--neon-green);">🚀 Devenir Vendeur</div>
            <?php
            $stmtV = $pdo->prepare("SELECT seller_verified FROM visiteur WHERE username = :u");
            $stmtV->execute([':u' => $_SESSION['username']]);
            $sStatus = $stmtV->fetchColumn();
            
            if ($sStatus == 1): ?>
                <div class="alert alert-success">Félicitations ! Votre compte vendeur est déjà activé.</div>
            <?php elseif ($sStatus == 2): ?>
                <div class="alert alert-success" style="background:rgba(0,207,255,0.1); border-color:var(--neon-blue); color:var(--neon-blue);">
                    ⏳ Votre candidature est en cours d'examen par l'administrateur.
                </div>
            <?php else: ?>
                <p style="color:rgba(255,255,255,0.6); font-size:14px; margin-bottom:20px;">
                    Soumettez votre candidature pour commencer à vendre vos articles sur Dribbleur Store.
                </p>
                <form method="POST" action="apply_seller.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="form-group">
                        <div class="form-label">Type de pièce d'identité</div>
                        <div class="input-wrap">
                            <select name="id_type" class="role-select" style="width:100%; padding:12px;" required>
                                <option value="CNI">Carte Nationale d'Identité</option>
                                <option value="Passport">Passeport</option>
                                <option value="Permis">Permis de conduire</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Numéro de la pièce</div>
                        <div class="input-wrap">
                            <input type="text" name="id_number" placeholder="Ex: 1 750 1990 00123" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-label">Photo de la pièce d'identité</div>
                        <div class="input-wrap">
                            <input type="file" name="id_photo" accept="image/jpeg,image/png" required style="padding:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(0,207,255,0.2); border-radius:8px; width:100%; color:#fff;">
                        </div>
                        <div class="avatar-hint">Format JPG/PNG - Max 4MB</div>
                    </div>
                    <button type="submit" class="btn-save" style="width:100%;">Envoyer ma candidature</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="section-title">Actions rapides</div>
            <div class="actions-grid">
                <a href="changer_mot_de_passe.php" class="action-card">
                    <div class="action-icon">Mot de passe</div>
                    <div class="action-desc">Securisez votre compte</div>
                </a>
                <a href="accueil.php" class="action-card">
                    <div class="action-icon">Accueil</div>
                    <div class="action-desc">Retour a l'accueil</div>
                </a>
                <a href="order_history.php" class="action-card">
                    <div class="action-icon">Commandes</div>
                    <div class="action-desc">Voir l'historique</div>
                </a>
                <a href="logout.php" class="action-card danger">
                    <div class="action-icon">Deconnexion</div>
                    <div class="action-desc">Se deconnecter</div>
                </a>
            </div>
        </div>
    </div>

    <script>
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
