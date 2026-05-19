<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dribbleur Store - Inscription</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-purple: #8b5cf6;
            --deep-bg: #020811;
            --card-bg: rgba(0, 20, 40, 0.85);
            --glow-green: 0 0 20px rgba(0,255,136,0.6), 0 0 60px rgba(0,255,136,0.2);
            --glow-blue: 0 0 20px rgba(0,207,255,0.6), 0 0 60px rgba(0,207,255,0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--deep-bg);
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            perspective: 1000px;
            padding: 30px 0;
        }

        /* ── Animated grid background ── */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,207,255,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,207,255,0.06) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 8s linear infinite;
            z-index: 0;
        }
        @keyframes gridMove {
            from { background-position: 0 0; }
            to   { background-position: 50px 50px; }
        }

        /* ── Floating orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: orbFloat linear infinite;
            z-index: 0;
            pointer-events: none;
        }
        .orb-1 { width: 400px; height: 400px; background: #00ff88; top: -100px; left: -100px; animation-duration: 14s; }
        .orb-2 { width: 300px; height: 300px; background: #00cfff; bottom: -80px; right: -80px; animation-duration: 10s; }
        .orb-3 { width: 250px; height: 250px; background: #8b5cf6; top: 50%; left: 60%; animation-duration: 18s; }

        @keyframes orbFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(30px,-20px) scale(1.05); }
            66%      { transform: translate(-20px,30px) scale(0.95); }
        }

        /* ── Particles ── */
        .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: var(--neon-green);
            border-radius: 50%;
            animation: particleFly linear infinite;
            box-shadow: 0 0 6px var(--neon-green);
        }
        @keyframes particleFly {
            from { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            to   { transform: translateY(-100px) translateX(var(--drift)); opacity: 0; }
        }

        /* ── Scanlines ── */
        .scanlines {
            position: fixed; inset: 0;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 2px,
                rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px
            );
            pointer-events: none;
            z-index: 5;
        }

        /* ── Card ── */
        .container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            padding: 40px 35px;
            border-radius: 20px;
            width: 90%;
            max-width: 440px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,207,255,0.2);
            box-shadow:
                0 0 0 1px rgba(0,255,136,0.05),
                0 25px 80px rgba(0,0,0,0.7),
                inset 0 1px 0 rgba(255,255,255,0.07);
            animation: cardEntrance 0.9s cubic-bezier(0.16,1,0.3,1) forwards;
            transition: transform 0.12s ease;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: perspective(1200px) rotateX(15deg) translateY(60px) scale(0.9); }
            to   { opacity: 1; transform: perspective(1200px) rotateX(0deg) translateY(0) scale(1); }
        }

        .container::before, .container::after {
            content: '';
            position: absolute;
            width: 40px; height: 40px;
            border-color: var(--neon-green);
            border-style: solid;
        }
        .container::before { top: -1px; left: -1px; border-width: 2px 0 0 2px; border-radius: 20px 0 0 0; }
        .container::after  { bottom: -1px; right: -1px; border-width: 0 2px 2px 0; border-radius: 0 0 20px 0; }

        /* ── Title ── */
        h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
            margin-bottom: 28px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 8px rgba(0,255,136,0.4));
        }

        /* ── Form ── */
        form { display: flex; flex-direction: column; }

        label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 2px;
            color: var(--neon-blue);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 14px;
        }
        .input-wrap::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            border-radius: 2px;
            transition: width 0.4s ease;
        }
        .input-wrap:focus-within::after { width: 100%; }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0,207,255,0.06);
            border: 1px solid rgba(0,207,255,0.15);
            border-radius: 10px;
            color: #e0f7ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 15px;
            letter-spacing: 1px;
            transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
            outline: none;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--neon-green);
            background: rgba(0,255,136,0.07);
            box-shadow: 0 0 20px rgba(0,255,136,0.2), inset 0 0 10px rgba(0,255,136,0.05);
        }
        input::placeholder { color: rgba(255,255,255,0.25); }

        /* ── Checkbox ── */
        .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding: 12px 15px;
            background: rgba(0,207,255,0.04);
            border: 1px solid rgba(0,207,255,0.12);
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.3s, background 0.3s;
        }
        .checkbox-wrap:hover {
            border-color: var(--neon-green);
            background: rgba(0,255,136,0.05);
        }
        input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--neon-green);
            cursor: pointer;
            flex-shrink: 0;
        }
        .checkbox-wrap span {
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            letter-spacing: 0.5px;
        }

        /* ── Errors ── */
        .error {
            color: #ff4466;
            font-size: 11px;
            letter-spacing: 1px;
            margin-top: -10px;
            margin-bottom: 10px;
            min-height: 16px;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-5px); }
            75%      { transform: translateX(5px); }
        }

        /* ── Buttons ── */
        button {
            position: relative;
            overflow: hidden;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 4px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            font-weight: 700;
            box-shadow: 0 4px 24px rgba(0,255,136,0.3);
            transition: transform 0.2s ease, box-shadow 0.3s ease;
        }
        button::before {
            content: '';
            position: absolute;
            top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: rgba(255,255,255,0.25);
            transform: skewX(-20deg);
            animation: btnShine 3s ease-in-out infinite;
        }
        @keyframes btnShine {
            0%   { left: -60%; opacity: 0; }
            20%  { opacity: 1; }
            50%  { left: 130%; opacity: 0; }
            100% { left: 130%; opacity: 0; }
        }
        button:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--glow-green);
        }
        button:active { transform: scale(0.98); }

        /* ── Progress bar ── */
        .strength-bar-wrap {
            margin-top: -10px;
            margin-bottom: 14px;
        }
        .strength-bar-bg {
            height: 4px;
            background: rgba(255,255,255,0.07);
            border-radius: 4px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 4px;
            transition: width 0.4s ease, background 0.4s ease;
        }
        .strength-label {
            font-size: 10px;
            letter-spacing: 1px;
            margin-top: 4px;
            color: rgba(255,255,255,0.4);
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

    <div class="container" id="card">
        <h1>Inscription eFootball</h1>

        <form id="inscriptionForm" action="inscription_submit.php" method="POST">

            <label for="prenom">Prénom</label>
            <div class="input-wrap">
                <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
            </div>

            <label for="nom">Nom</label>
            <div class="input-wrap">
                <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
            </div>

            <label for="email">Adresse e-mail</label>
            <div class="input-wrap">
                <input type="email" id="email" name="email" placeholder="exemple@mail.com" required>
            </div>

            <label for="telephone">Telephone</label>
            <div class="input-wrap">
                <input type="text" id="telephone" name="telephone" placeholder="77 507 29 36" required>
            </div>

            <label for="adresse">Adresse</label>
            <div class="input-wrap">
                <input type="text" id="adresse" name="adresse" placeholder="Votre adresse complete" required>
            </div>

            <label for="ville">Ville</label>
            <div class="input-wrap">
                <input type="text" id="ville" name="ville" placeholder="Votre ville" required>
            </div>

            <label for="username">Nom d'utilisateur</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username" placeholder="Commence par une majuscule" required pattern="[A-Z].*">
            </div>
            <div id="usernameError" class="error"></div>

            <label for="password">Mot de passe</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="Min. 8 caractères" required>
            </div>
            <div class="strength-bar-wrap">
                <div class="strength-bar-bg"><div class="strength-bar" id="strengthBar"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <label for="confirmPassword">Confirmer le mot de passe</label>
            <div class="input-wrap">
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="••••••••" required>
            </div>
            <div id="passwordError" class="error"></div>

            <label class="checkbox-wrap" style="font-family:inherit;font-size:inherit;color:inherit;letter-spacing:0;text-transform:none;">
                <input type="checkbox" id="terms" name="terms" required>
                <span>J'accepte les termes et conditions d'eFootball.</span>
            </label>

            <button type="submit">S'inscrire</button>

        </form>
    </div>

    <script>
        // Particles
        const pContainer = document.getElementById('particles');
        for (let i = 0; i < 40; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.cssText = `
                left: ${Math.random() * 100}%;
                animation-duration: ${4 + Math.random() * 8}s;
                animation-delay: ${Math.random() * 8}s;
                --drift: ${(Math.random() - 0.5) * 100}px;
                background: ${Math.random() > 0.5 ? '#00ff88' : '#00cfff'};
                box-shadow: 0 0 6px ${Math.random() > 0.5 ? '#00ff88' : '#00cfff'};
                width: ${1 + Math.random() * 2}px;
                height: ${1 + Math.random() * 2}px;
            `;
            pContainer.appendChild(p);
        }

        // 3D tilt
        const card = document.getElementById('card');
        document.addEventListener('mousemove', (e) => {
            const cx = window.innerWidth / 2, cy = window.innerHeight / 2;
            const dx = (e.clientX - cx) / cx;
            const dy = (e.clientY - cy) / cy;
            card.style.transform = `perspective(1200px) rotateY(${dx * 8}deg) rotateX(${-dy * 8}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1200px) rotateX(0) rotateY(0)';
        });

        // Password strength
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/\d/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const levels = [
                { w: '0%',   bg: 'transparent', txt: '' },
                { w: '25%',  bg: '#ff4466',      txt: 'FAIBLE' },
                { w: '50%',  bg: '#ffaa00',      txt: 'MOYEN' },
                { w: '75%',  bg: '#00cfff',      txt: 'BON' },
                { w: '100%', bg: '#00ff88',      txt: 'FORT' },
            ];
            bar.style.width = levels[strength].w;
            bar.style.background = levels[strength].bg;
            label.textContent = levels[strength].txt;
            label.style.color = levels[strength].bg;
        });

        // Form validation
        document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const passwordError = document.getElementById('passwordError');
            const usernameError = document.getElementById('usernameError');

            const usernameRegex = /^[A-Z]/;
            const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;

            if (!usernameRegex.test(username)) {
                e.preventDefault();
                usernameError.textContent = "⚠ Le nom d'utilisateur doit commencer par une majuscule.";
                document.getElementById('username').focus();
                return;
            } else {
                usernameError.textContent = "";
            }

            if (password !== confirmPassword || !passwordRegex.test(password)) {
                e.preventDefault();
                passwordError.textContent = password !== confirmPassword
                    ? "⚠ Les mots de passe ne correspondent pas !"
                    : "⚠ Minimum 8 caractères, une lettre et un chiffre.";
                document.getElementById('confirmPassword').focus();
            } else {
                passwordError.textContent = "";
            }
        });
    </script>
</body>
</html>
