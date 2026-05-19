<?php
require __DIR__ . '/config.php';
$pdo = db();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    redirect('/index.php');
}

$username = $_SESSION['username'] ?? 'Utilisateur';
$error = '';
csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Jeton de securite invalide';
    } else {
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($old) || empty($new) || empty($confirm)) {
        $error = 'Tous les champs sont requis';
    } elseif ($new !== $confirm) {
        $error = 'Les nouveaux mots de passe ne correspondent pas';
    } elseif (strlen($new) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($old === $new) {
        $error = 'Le nouveau mot de passe doit être différent de l\'ancien';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT password FROM visiteur WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $_SESSION['username']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error = 'Utilisateur introuvable';
            } elseif (!isset($row['password']) || !password_verify($old, $row['password'])) {
                $error = 'Ancien mot de passe incorrect';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE visiteur SET password = :pw WHERE username = :username')
                    ->execute([':pw' => $hash, ':username' => $_SESSION['username']]);
                header('Location: changer_mot_de_passe.php?pw_changed=1');
                exit();
            }
        } catch (PDOException $e) {
            error_log('Password change error: ' . $e->getMessage());
            $error = 'Une erreur serveur est survenue. Veuillez reessayer.';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);
            --glow-green:0 0 20px rgba(0,255,136,0.5),0 0 60px rgba(0,255,136,0.15);
            --glow-blue:0 0 20px rgba(0,207,255,0.5),0 0 60px rgba(0,207,255,0.15);
            --nav-h:70px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}

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

        /* ── NAV ── */
        nav{position:fixed;top:0;left:0;right:0;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,207,255,0.15);z-index:100;box-shadow:0 4px 30px rgba(0,0,0,0.5);}
        nav::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-green),var(--neon-blue),transparent);animation:scanH 4s ease-in-out infinite;}
        @keyframes scanH{0%,100%{opacity:0.4;}50%{opacity:1;}}
        .nav-logo{font-family:'Orbitron',sans-serif;font-weight:900;font-size:17px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;}
        .nav-user{display:flex;align-items:center;gap:8px;padding:8px 16px;border:1px solid rgba(0,207,255,0.2);border-radius:8px;background:rgba(0,207,255,0.05);font-family:'Rajdhani',sans-serif;font-size:14px;letter-spacing:1px;color:#e0f7ff;}

        /* ── LAYOUT ── */
        .container{position:relative;z-index:2;max-width:560px;margin:0 auto;padding:calc(var(--nav-h)+50px) 24px 80px;animation:heroReveal 0.9s cubic-bezier(0.16,1,0.3,1);}
        @keyframes heroReveal{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}

        /* ── HEADER ── */
        .page-header{text-align:center;margin-bottom:36px;}
        .page-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:clamp(18px,3.5vw,32px);letter-spacing:3px;background:linear-gradient(135deg,#fff 0%,var(--neon-green) 50%,var(--neon-blue) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 0 18px rgba(0,255,136,0.3));margin-bottom:8px;}
        .page-subtitle{color:rgba(255,255,255,0.4);font-size:14px;letter-spacing:2px;}

        /* ── CARD ── */
        .form-card{background:var(--card-bg);border:1px solid rgba(0,207,255,0.18);border-radius:22px;padding:40px 36px;backdrop-filter:blur(20px);box-shadow:0 25px 70px rgba(0,0,0,0.5);position:relative;overflow:hidden;}
        .form-card::before,.form-card::after{content:'';position:absolute;width:40px;height:40px;border-color:var(--neon-green);border-style:solid;}
        .form-card::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:22px 0 0 0;}
        .form-card::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 22px 0;}

        /* ── LOCK ICON ── */
        .lock-icon{
            width:72px;height:72px;margin:0 auto 24px;border-radius:50%;
            background:linear-gradient(135deg,rgba(0,255,136,0.12),rgba(0,207,255,0.08));
            border:1px solid rgba(0,255,136,0.25);
            display:flex;align-items:center;justify-content:center;
            font-size:28px;
            box-shadow:var(--glow-green);
            animation:logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse{0%,100%{box-shadow:var(--glow-green);}50%{box-shadow:0 0 35px rgba(0,255,136,0.7),0 0 70px rgba(0,255,136,0.3);}}

        /* ── ALERTS ── */
        .alert{padding:13px 18px;border-radius:10px;margin-bottom:24px;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1px;display:flex;align-items:center;gap:10px;animation:slideDown 0.4s ease;}
        @keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
        .alert-success{background:rgba(0,255,136,0.07);border:1px solid rgba(0,255,136,0.22);color:var(--neon-green);}
        .alert-error{background:rgba(255,68,102,0.07);border:1px solid rgba(255,68,102,0.22);color:#ff4466;animation:slideDown 0.4s ease, shake 0.4s ease;}
        @keyframes shake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(5px);}}

        /* ── FORM ── */
        .form-group{margin-bottom:22px;}
        .form-label{font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:2px;color:var(--neon-blue);text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;}

        .input-wrap{position:relative;}
        .input-wrap::after{content:'';position:absolute;bottom:0;left:0;width:0;height:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));border-radius:2px;transition:width 0.4s;}
        .input-wrap:focus-within::after{width:100%;}

        .eye-wrap{position:relative;}
        .eye-wrap input{padding-right:46px;}
        .eye-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.35);font-size:16px;transition:color 0.2s;padding:0;}
        .eye-toggle:hover{color:var(--neon-blue);}

        input[type="password"],input[type="text"]{
            width:100%;padding:13px 16px;
            background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:10px;
            color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;letter-spacing:0.5px;
            transition:border-color 0.3s,background 0.3s,box-shadow 0.3s;outline:none;
        }
        input:focus{border-color:var(--neon-green);background:rgba(0,255,136,0.07);box-shadow:0 0 20px rgba(0,255,136,0.15);}
        input.input-error{border-color:#ff4466;background:rgba(255,68,102,0.05);}
        input.input-ok{border-color:var(--neon-green);}
        input::placeholder{color:rgba(255,255,255,0.22);}

        /* ── STRENGTH ── */
        .strength-wrap{margin-top:8px;}
        .strength-bg{height:3px;background:rgba(255,255,255,0.07);border-radius:4px;overflow:hidden;}
        .strength-bar{height:100%;width:0;border-radius:4px;transition:width 0.4s,background 0.4s;}
        .strength-label{font-size:10px;letter-spacing:1px;margin-top:4px;color:rgba(255,255,255,0.35);}

        /* ── HINT ── */
        .hint{font-size:11px;color:rgba(255,255,255,0.3);letter-spacing:0.5px;margin-top:6px;display:flex;align-items:center;gap:6px;}

        /* ── MATCH indicator ── */
        .match-indicator{font-size:11px;letter-spacing:1px;margin-top:6px;min-height:16px;}

        /* ── BUTTONS ── */
        .btn-row{display:flex;gap:12px;margin-top:28px;}

        .btn-save{
            position:relative;overflow:hidden;flex:2;
            padding:14px;
            background:linear-gradient(135deg,var(--neon-green),#00b86b);
            color:#001a0d;font-family:'Orbitron',sans-serif;font-weight:700;font-size:12px;letter-spacing:2px;
            border:none;border-radius:10px;cursor:pointer;
            box-shadow:0 4px 20px rgba(0,255,136,0.3);
            transition:transform 0.2s,box-shadow 0.3s;
        }
        .btn-save::before{content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;background:rgba(255,255,255,0.25);transform:skewX(-20deg);animation:btnShine 3s ease-in-out infinite;}
        @keyframes btnShine{0%{left:-60%;opacity:0;}20%{opacity:1;}50%{left:130%;opacity:0;}100%{left:130%;opacity:0;}}
        .btn-save:hover{transform:translateY(-3px);box-shadow:var(--glow-green);}
        .btn-save:active{transform:scale(0.97);}

        .btn-cancel{
            flex:1;padding:14px;
            background:transparent;border:1px solid rgba(255,255,255,0.1);border-radius:10px;
            color:rgba(255,255,255,0.45);font-family:'Orbitron',sans-serif;font-size:12px;letter-spacing:2px;
            cursor:pointer;transition:all 0.3s;
        }
        .btn-cancel:hover{border-color:rgba(255,255,255,0.28);color:#fff;}

        /* ── BACK LINK ── */
        .back-link{text-align:center;margin-top:24px;}
        .back-link a{color:rgba(0,207,255,0.7);font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.3s;}
        .back-link a:hover{color:var(--neon-green);transform:translateX(-4px);}

        @media(max-width:500px){.form-card{padding:32px 20px;}.btn-row{flex-direction:column;}}
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

        <div class="page-header">
            <h1 class="page-title">Changer le mot de passe</h1>
            <p class="page-subtitle">Sécurisez votre compte avec un nouveau mot de passe</p>
        </div>

        <div class="form-card">

            <div class="lock-icon">🔐</div>

            <?php if (isset($_GET['pw_changed'])): ?>
                <div class="alert alert-success">✓ Mot de passe modifié avec succès !</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="pwForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                <!-- Ancien mot de passe -->
                <div class="form-group">
                    <div class="form-label">🔑 Ancien mot de passe</div>
                    <div class="input-wrap eye-wrap">
                        <input type="password" id="old_password" name="old_password" placeholder="Votre mot de passe actuel" required autocomplete="current-password">
                        <button type="button" class="eye-toggle" onclick="toggleEye('old_password',this)">👁</button>
                    </div>
                </div>

                <!-- Nouveau mot de passe -->
                <div class="form-group">
                    <div class="form-label">🆕 Nouveau mot de passe</div>
                    <div class="input-wrap eye-wrap">
                        <input type="password" id="new_password" name="new_password" placeholder="Min. 8 caractères" required minlength="8" autocomplete="new-password" oninput="checkStrength(this.value);checkMatch();">
                        <button type="button" class="eye-toggle" onclick="toggleEye('new_password',this)">👁</button>
                    </div>
                    <div class="strength-wrap">
                        <div class="strength-bg"><div class="strength-bar" id="strengthBar"></div></div>
                        <div class="strength-label" id="strengthLabel"></div>
                    </div>
                    <div class="hint">ℹ Minimum 8 caractères, une lettre et un chiffre recommandés</div>
                </div>

                <!-- Confirmer -->
                <div class="form-group">
                    <div class="form-label">✔ Confirmer le nouveau mot de passe</div>
                    <div class="input-wrap eye-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Retapez votre nouveau mot de passe" required minlength="8" autocomplete="new-password" oninput="checkMatch();">
                        <button type="button" class="eye-toggle" onclick="toggleEye('confirm_password',this)">👁</button>
                    </div>
                    <div class="match-indicator" id="matchIndicator"></div>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn-cancel" onclick="window.location.href='profile.php'">Annuler</button>
                    <button type="submit" class="btn-save">Mettre à jour</button>
                </div>
            </form>

            <div class="back-link">
                <a href="profile.php">← Retour au profil</a>
            </div>
        </div>
    </div>

    <script>
        /* ── Particles ── */
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<40;i++){
                const p=document.createElement('div');p.className='particle';
                const g=Math.random()>0.5;
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${5+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*120}px;background:${g?'#00ff88':'#00cfff'};box-shadow:0 0 6px ${g?'#00ff88':'#00cfff'};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();

        /* ── Eye toggle ── */
        function toggleEye(id, btn) {
            const input = document.getElementById(id);
            if(input.type==='password'){ input.type='text'; btn.textContent='🙈'; }
            else { input.type='password'; btn.textContent='👁'; }
        }

        /* ── Password strength ── */
        function checkStrength(val) {
            const bar=document.getElementById('strengthBar');
            const label=document.getElementById('strengthLabel');
            let s=0;
            if(val.length>=8) s++;
            if(/[A-Z]/.test(val)) s++;
            if(/\d/.test(val)) s++;
            if(/[^A-Za-z0-9]/.test(val)) s++;
            const levels=[
                {w:'0%',bg:'transparent',txt:''},
                {w:'25%',bg:'#ff4466',txt:'FAIBLE'},
                {w:'50%',bg:'#ffaa00',txt:'MOYEN'},
                {w:'75%',bg:'#00cfff',txt:'BON'},
                {w:'100%',bg:'#00ff88',txt:'FORT'},
            ];
            bar.style.width=levels[s].w;
            bar.style.background=levels[s].bg;
            label.textContent=levels[s].txt;
            label.style.color=levels[s].bg;
        }

        /* ── Match check ── */
        function checkMatch() {
            const np=document.getElementById('new_password').value;
            const cp=document.getElementById('confirm_password').value;
            const el=document.getElementById('matchIndicator');
            const input=document.getElementById('confirm_password');
            if(!cp){ el.textContent=''; input.classList.remove('input-error','input-ok'); return; }
            if(np===cp){
                el.textContent='✓ Les mots de passe correspondent';
                el.style.color='#00ff88';
                input.classList.remove('input-error'); input.classList.add('input-ok');
            } else {
                el.textContent='✗ Les mots de passe ne correspondent pas';
                el.style.color='#ff4466';
                input.classList.remove('input-ok'); input.classList.add('input-error');
            }
        }

        /* ── Client-side validation ── */
        document.getElementById('pwForm').addEventListener('submit',function(e){
            const old=document.getElementById('old_password').value;
            const np=document.getElementById('new_password').value;
            const cp=document.getElementById('confirm_password').value;
            if(!old||!np||!cp){ e.preventDefault(); return; }
            if(np.length<8){ e.preventDefault(); document.getElementById('new_password').classList.add('input-error'); return; }
            if(np!==cp){ e.preventDefault(); document.getElementById('confirm_password').classList.add('input-error'); return; }
            if(old===np){ e.preventDefault(); return; }
        });
    </script>
</body>
</html>
