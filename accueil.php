<?php
require __DIR__ . '/config.php';
ensure_store_schema();

if (!isset($_SESSION['username']) || !isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    redirect('/index.php');
}

if (!isset($_SESSION['user_id_from_db'])) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM visiteur WHERE username = :username');
        $stmt->execute([':username' => $_SESSION['username']]);
        $user_id = $stmt->fetchColumn();
        if ($user_id) $_SESSION['user_id_from_db'] = $user_id;
    } catch (PDOException $e) {}
}

$wishlistIds = [];
$currentUserId = current_user_id();
if ($currentUserId) {
    $wishlistIds = fetch_wishlist_article_ids($currentUserId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dribbleur Store - Comptes Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        /* ══ CSS VARIABLES ══ */
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-purple: #8b5cf6;
            --deep-bg: #020811;
            --card-bg: rgba(0, 20, 40, 0.8);
            --glow-green: 0 0 20px rgba(0,255,136,0.5), 0 0 60px rgba(0,255,136,0.15);
            --glow-blue: 0 0 20px rgba(0,207,255,0.5), 0 0 60px rgba(0,207,255,0.15);
            --nav-h: 70px;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            background: var(--deep-bg);
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            overflow-x: hidden;
            width: 100%;
        }

        /* ── Global bg effects ── */
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,207,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,207,255,0.045) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 10s linear infinite;
            z-index: 0; pointer-events: none;
        }
        @keyframes gridMove {
            from { background-position: 0 0; }
            to   { background-position: 50px 50px; }
        }

        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(90px); opacity: 0.25;
            animation: orbFloat linear infinite;
            z-index: 0; pointer-events: none;
        }
        .orb-1 { width:500px;height:500px;background:#00ff88;top:-150px;left:-150px;animation-duration:16s; }
        .orb-2 { width:400px;height:400px;background:#00cfff;bottom:-100px;right:-100px;animation-duration:12s; }
        .orb-3 { width:300px;height:300px;background:#8b5cf6;top:40%;left:55%;animation-duration:20s; }
        @keyframes orbFloat {
            0%,100%{transform:translate(0,0) scale(1);}
            33%{transform:translate(40px,-30px) scale(1.06);}
            66%{transform:translate(-25px,40px) scale(0.94);}
        }

        .scanlines {
            position: fixed; inset: 0;
            background: repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.025) 2px,rgba(0,0,0,0.025) 4px);
            pointer-events: none; z-index: 1;
        }

        .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .particle {
            position: absolute; border-radius: 50%;
            animation: particleFly linear infinite;
        }
        @keyframes particleFly {
            from{transform:translateY(100vh) translateX(0);opacity:0;}
            10%{opacity:1;} 90%{opacity:1;}
            to{transform:translateY(-100px) translateX(var(--drift));opacity:0;}
        }

        /* ══ NAVBAR ══ */
        nav {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--nav-h);
            display: flex; align-items: center; justify-content: space-between;
            gap: 18px;
            padding: max(10px, var(--safe-top)) calc(max(18px, var(--safe-right))) 0 calc(max(18px, var(--safe-left)));
            background: rgba(2,8,17,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,207,255,0.15);
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5);
        }
        nav::after {
            content: '';
            position: absolute; bottom: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, var(--neon-green), var(--neon-blue), transparent);
            animation: scanH 4s ease-in-out infinite;
        }
        @keyframes scanH {
            0%,100%{opacity:0.4;} 50%{opacity:1;}
        }

        .logo-wrap {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
            min-width: 0;
            flex: 1 1 auto;
        }
        .logo-img {
            width: 42px; height: 42px; border-radius: 50%;
            border: 2px solid var(--neon-green);
            box-shadow: var(--glow-green);
            object-fit: cover;
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%,100%{box-shadow:var(--glow-green);}
            50%{box-shadow:0 0 35px rgba(0,255,136,0.8),0 0 70px rgba(0,255,136,0.3);}
        }
        .logo-text {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900; font-size: 16px; letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; min-width: 0; }
        .nav-actions-desktop { display: flex; flex: 1 1 auto; flex-wrap: wrap; justify-content: flex-end; min-width: 0; }
        .nav-actions-desktop .nav-btn { flex: 0 1 auto; min-width: 0; }

        .nav-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 16px;
            background: transparent;
            border: 1px solid rgba(0,207,255,0.25);
            border-radius: 8px;
            color: #e0f7ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px; letter-spacing: 1px;
            cursor: pointer; text-decoration: none;
            transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .nav-btn::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(0,207,255,0.08), transparent);
            transform: translateX(-100%);
            transition: transform 0.4s ease;
        }
        .nav-btn:hover { border-color: var(--neon-blue); box-shadow: var(--glow-blue); transform: translateY(-2px); }
        .nav-btn:hover::before { transform: translateX(100%); }

        .nav-btn.admin { background: rgba(255,0,60,0.15); border-color: #ff003c; color: #ff6688; }
        .nav-btn.admin:hover { box-shadow: 0 0 20px rgba(255,0,60,0.5); border-color: #ff003c; }

        .nav-toggle,
        .mobile-menu,
        .mobile-menu-backdrop {
            display: none;
        }

        .nav-toggle {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(0,207,255,0.25);
            background: rgba(0,20,40,0.72);
            color: #e0f7ff;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
            flex: 0 0 auto;
        }

        .nav-toggle:hover,
        .nav-toggle:focus-visible {
            border-color: var(--neon-blue);
            box-shadow: var(--glow-blue);
            transform: translateY(-1px);
            outline: none;
        }

        .nav-toggle svg {
            width: 20px;
            height: 20px;
        }

        .mobile-menu-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2,8,17,0.58);
            backdrop-filter: blur(8px);
            z-index: 109;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .mobile-menu {
            position: fixed;
            top: calc(var(--nav-h) + var(--safe-top) + 8px);
            left: calc(max(14px, var(--safe-left)));
            right: calc(max(14px, var(--safe-right)));
            z-index: 110;
            padding: 16px;
            border: 1px solid rgba(0,207,255,0.2);
            border-radius: 18px;
            background: rgba(0,16,32,0.96);
            box-shadow: 0 18px 60px rgba(0,0,0,0.45);
            backdrop-filter: blur(18px);
            transform: translateY(-12px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .mobile-menu-inner {
            display: grid;
            gap: 10px;
        }

        .mobile-nav-btn {
            width: 100%;
            justify-content: space-between;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 15px;
        }

        body.menu-open {
            overflow: hidden;
        }

        body.menu-open .mobile-menu-backdrop {
            opacity: 1;
            pointer-events: auto;
        }

        body.menu-open .mobile-menu {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .cart-count {
            background: var(--neon-green); color: #001a0d;
            border-radius: 50%; width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; font-family: 'Orbitron', sans-serif;
        }

        /* ══ HERO ══ */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: calc(var(--nav-h) + 40px) 30px 80px;
            text-align: center;
            overflow: hidden;
        }

        #canvas3d {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            z-index: 1; opacity: 0.6;
        }

        .hero-content {
            position: relative; z-index: 2;
            max-width: 800px;
            animation: heroReveal 1.2s cubic-bezier(0.16,1,0.3,1) forwards;
        }
        @keyframes heroReveal {
            from{opacity:0;transform:translateY(40px);}
            to{opacity:1;transform:translateY(0);}
        }

        .hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900; font-size: clamp(28px, 5vw, 56px);
            letter-spacing: 3px; line-height: 1.15;
            background: linear-gradient(135deg, #fff 0%, var(--neon-green) 50%, var(--neon-blue) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            filter: drop-shadow(0 0 30px rgba(0,255,136,0.3));
            margin-bottom: 24px;
        }

        .hero-desc {
            font-size: 16px; line-height: 1.7;
            color: rgba(255,255,255,0.72); max-width: 600px;
            margin: 0 auto 32px; letter-spacing: 0.5px;
        }

        .hero-badges {
            display: flex; flex-wrap: wrap; gap: 12px;
            justify-content: center; margin-bottom: 36px;
        }
        .badge {
            padding: 8px 18px;
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 30px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px; letter-spacing: 1.5px;
            color: var(--neon-green);
            background: rgba(0,255,136,0.06);
            backdrop-filter: blur(10px);
            animation: badgePulse 3s ease-in-out infinite;
        }
        .badge:nth-child(2){animation-delay:0.3s;}
        .badge:nth-child(3){animation-delay:0.6s;}
        .badge:nth-child(4){animation-delay:0.9s;}
        @keyframes badgePulse {
            0%,100%{box-shadow:none;}
            50%{box-shadow:0 0 12px rgba(0,255,136,0.25);}
        }

        .cta-button {
            position: relative; overflow: hidden;
            padding: 16px 48px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700; font-size: 14px; letter-spacing: 2px;
            border: none; border-radius: 50px; cursor: pointer;
            box-shadow: 0 8px 32px rgba(0,255,136,0.35);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .cta-button::before {
            content: '';
            position: absolute; top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: rgba(255,255,255,0.3);
            transform: skewX(-20deg);
            animation: btnShine 3s ease-in-out infinite;
        }
        @keyframes btnShine {
            0%{left:-60%;opacity:0;} 20%{opacity:1;} 50%{left:130%;opacity:0;} 100%{left:130%;opacity:0;}
        }
        .cta-button:hover { transform: translateY(-4px) scale(1.03); box-shadow: var(--glow-green); }
        .cta-button:active { transform: scale(0.97); }

        /* ══ SECTIONS ══ */
        section { position: relative; z-index: 2; padding: 80px 30px; }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900; font-size: clamp(20px,3vw,32px);
            letter-spacing: 3px; text-align: center; margin-bottom: 50px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            filter: drop-shadow(0 0 12px rgba(0,255,136,0.3));
        }

        /* ── Articles ── */
        .articles-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr));
            gap: 24px;
        }

        .article-card {
            background: var(--card-bg);
            border: 1px solid rgba(0,207,255,0.15);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(16px);
            transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
            animation: cardFadeIn 0.6s ease both;
            position: relative;
        }
        .article-card:hover {
            transform: translateY(-8px) scale(1.01);
            border-color: var(--neon-green);
            box-shadow: var(--glow-green);
        }
        @keyframes cardFadeIn {
            from{opacity:0;transform:translateY(20px);}
            to{opacity:1;transform:translateY(0);}
        }
        .article-card img {
            width:100%; height:160px; object-fit:cover; display:block;
        }
        .article-card-top {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            z-index: 2;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border-radius: 999px;
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }
        .status-badge.status-available { color: var(--neon-green); background: rgba(0,255,136,0.12); border-color: rgba(0,255,136,0.25); }
        .status-badge.status-reserved { color: #ffb703; background: rgba(255,183,3,0.14); border-color: rgba(255,183,3,0.24); }
        .status-badge.status-sold { color: #ff5d73; background: rgba(255,93,115,0.16); border-color: rgba(255,93,115,0.28); }
        .favorite-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(2,8,17,0.72);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .favorite-btn.is-active { color: #ff5d73; border-color: rgba(255,93,115,0.4); background: rgba(255,93,115,0.14); }
        .article-card-body { padding: 18px; }
        .article-card-body h3 {
            font-family: 'Orbitron', sans-serif; font-size: 13px;
            letter-spacing: 1px; color: var(--neon-blue); margin-bottom: 10px;
        }
        .article-card-body p { color: rgba(255,255,255,0.65); font-size: 14px; line-height: 1.6; }
        .article-status-copy { color: rgba(255,255,255,0.72); font-size: 13px; margin-top: 6px; }
        .article-link {
            display: inline-block; margin-top: 12px;
            color: var(--neon-green); font-family: 'Orbitron', sans-serif;
            font-size: 11px; letter-spacing: 1.5px; text-decoration: none;
            transition: text-shadow 0.3s;
        }
        .article-link:hover { text-shadow: 0 0 10px var(--neon-green); }

        /* ── Features ── */
        .features-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            gap: 20px;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid rgba(0,207,255,0.12);
            border-radius: 16px; padding: 30px 24px;
            text-align: center;
            backdrop-filter: blur(16px);
            transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
            position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-green), transparent);
            opacity: 0; transition: opacity 0.3s;
        }
        .feature-card:hover { transform: translateY(-8px); border-color: var(--neon-blue); box-shadow: var(--glow-blue); }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon { font-size: 36px; margin-bottom: 14px; }
        .feature-card h3 {
            font-family: 'Orbitron', sans-serif; font-size: 12px;
            letter-spacing: 2px; color: var(--neon-blue); margin-bottom: 10px;
        }
        .feature-card p { color: rgba(255,255,255,0.6); font-size: 14px; line-height: 1.6; }

        /* ── Testimonials ── */
        .testimonials-grid {
            max-width: 1000px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
            gap: 20px;
        }

        .testimonial-card {
            background: var(--card-bg);
            border: 1px solid rgba(0,255,136,0.12);
            border-radius: 16px; padding: 28px;
            backdrop-filter: blur(16px);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .testimonial-card:hover { transform: translateY(-6px); box-shadow: var(--glow-green); }

        .stars { color: var(--neon-green); font-size: 18px; margin-bottom: 14px; letter-spacing: 3px; }
        .testimonial-text { color: rgba(255,255,255,0.72); font-size: 14px; line-height: 1.7; margin-bottom: 18px; }

        .testimonial-author { display: flex; align-items: center; gap: 12px; }
        .author-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg, var(--neon-green), var(--neon-blue));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Orbitron', sans-serif; font-weight: 900;
            color: #001a0d; font-size: 16px; flex-shrink: 0;
        }
        .author-name { font-weight: 700; font-size: 15px; }
        .author-date { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 2px; }

        /* ── Divider ── */
        .neon-divider {
            width: 100%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--neon-green), var(--neon-blue), transparent);
            opacity: 0.3; margin: 0;
        }

        /* ══ FOOTER ══ */
        footer {
            position: relative; z-index: 2;
            background: rgba(0,8,20,0.9);
            border-top: 1px solid rgba(0,207,255,0.1);
            padding: 60px 30px 30px;
            backdrop-filter: blur(20px);
        }

        .footer-content {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
            gap: 40px; margin-bottom: 40px;
        }

        .footer-section h3 {
            font-family: 'Orbitron', sans-serif; font-size: 12px;
            letter-spacing: 2px; color: var(--neon-blue);
            margin-bottom: 18px; padding-bottom: 8px;
            border-bottom: 1px solid rgba(0,207,255,0.2);
        }
        .footer-section p { color: rgba(255,255,255,0.55); font-size: 14px; line-height: 1.7; }
        .footer-section ul { list-style: none; }
        .footer-section ul li { margin-bottom: 10px; }
        .footer-section ul li a {
            color: rgba(255,255,255,0.55); text-decoration: none;
            font-size: 14px; transition: color 0.3s;
        }
        .footer-section ul li a:hover { color: var(--neon-green); }

        .footer-bottom {
            text-align: center; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.3); font-size: 13px; letter-spacing: 1px;
        }

        /* ══ CART MODAL ══ */
        .modal {
            display: none; position: fixed; inset: 0;
            background: rgba(2,8,17,0.85); backdrop-filter: blur(10px);
            z-index: 200; justify-content: center; align-items: center;
        }
        .modal.open { display: flex; }

        .modal-content {
            background: rgba(0,20,40,0.95);
            border: 1px solid rgba(0,207,255,0.2);
            border-radius: 20px; padding: 40px;
            max-width: 500px; width: 90%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.7), var(--glow-blue);
            animation: modalIn 0.4s cubic-bezier(0.16,1,0.3,1);
            position: relative;
        }
        @keyframes modalIn {
            from{opacity:0;transform:scale(0.88) translateY(20px);}
            to{opacity:1;transform:scale(1) translateY(0);}
        }
        .modal-content::before, .modal-content::after {
            content:''; position:absolute; width:35px; height:35px;
            border-color:var(--neon-green); border-style:solid;
        }
        .modal-content::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:20px 0 0 0;}
        .modal-content::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 20px 0;}

        .modal-content h2 {
            font-family: 'Orbitron', sans-serif; font-size: 18px; letter-spacing: 2px;
            color: var(--neon-blue); margin-bottom: 24px;
        }

        .close-modal {
            position: absolute; top: 16px; right: 20px;
            font-size: 24px; cursor: pointer; color: rgba(255,255,255,0.5);
            transition: color 0.2s;
        }
        .close-modal:hover { color: #ff4466; }

        /* ── Scroll reveal ── */
        .reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 980px) {
            nav {
                padding-right: calc(max(16px, var(--safe-right)));
                padding-left: calc(max(16px, var(--safe-left)));
            }

            .nav-actions-desktop {
                display: none;
            }

            .nav-toggle,
            .mobile-menu,
            .mobile-menu-backdrop {
                display: flex;
            }

            .mobile-menu {
                display: block;
            }

            .hero {
                padding: calc(var(--nav-h) + var(--safe-top) + 30px) 22px 56px;
                min-height: 100svh;
            }

            section,
            footer {
                padding-left: 22px;
                padding-right: 22px;
            }

            .articles-grid,
            .features-grid,
            .testimonials-grid,
            .footer-content {
                gap: 18px;
            }
        }

        @media (max-width: 680px) {
            .logo-img {
                width: 38px;
                height: 38px;
            }

            .logo-text {
                font-size: 14px;
                letter-spacing: 1.2px;
            }

            .hero h1 {
                letter-spacing: 1.5px;
            }

            .hero-desc {
                font-size: 15px;
            }

            .cta-button {
                width: 100%;
                max-width: 320px;
                padding: 15px 24px;
            }

            section {
                padding-top: 64px;
                padding-bottom: 64px;
            }

            .section-title {
                margin-bottom: 32px;
                letter-spacing: 2px;
            }

            .article-card-body,
            .feature-card,
            .testimonial-card,
            .modal-content {
                padding-left: 18px;
                padding-right: 18px;
            }

            .modal-content {
                width: min(92vw, 500px);
            }
        }

        @media (max-width: 480px) {
            nav {
                gap: 12px;
                padding-right: calc(max(12px, var(--safe-right)));
                padding-left: calc(max(12px, var(--safe-left)));
            }

            .logo-text {
                max-width: 160px;
            }

            .hero {
                padding-left: 16px;
                padding-right: 16px;
            }

            section,
            footer {
                padding-left: 16px;
                padding-right: 16px;
            }

            .hero-badges {
                gap: 10px;
            }

            .badge {
                width: 100%;
                text-align: center;
            }

            .articles-grid,
            .features-grid,
            .testimonials-grid,
            .footer-content {
                grid-template-columns: 1fr;
            }

            .mobile-menu {
                left: calc(max(10px, var(--safe-left)));
                right: calc(max(10px, var(--safe-right)));
                padding: 14px;
            }
        }
    </style>
    <link rel="stylesheet" href="style/article_lightbox.css">
</head>
<body>

    <!-- Global bg -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>
    <div class="scanlines"></div>

    <!-- ══ NAV ══ -->
    <nav>
        <a href="accueil.php" class="logo-wrap">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9SBs4aa8Qgupeysy-THcIR8-bRBQHiw1ITQ&s" alt="Dribbleur Store" class="logo-img">
            <span class="logo-text">Dribbleur Store</span>
        </a>

        <div class="nav-actions nav-actions-desktop">
            <a href="test.php" class="nav-btn">Contact</a>
            <a href="wishlist.php" class="nav-btn">Favoris</a>
            <a href="conditions.php" class="nav-btn">Conditions</a>

            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller'): ?>
                <a href="apply_seller.php" class="nav-btn" style="border-color:#ff9500; color:#ffb703;">💼 Devenir Vendeur</a>
            <?php endif; ?>

            <button class="nav-btn" onclick="openCart()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12L4 2H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Panier
                <span class="cart-count" id="cartCount">0</span>
            </button>

            <a href="order_history.php" class="nav-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3m6-3c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Historique
            </a>

            <a href="profile.php" class="nav-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.4"/><path d="M4 20c0-3.3 2.7-6 6-6h4c3.3 0 6 2.7 6 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Profil
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin/admin.php" class="nav-btn admin">⚙️ Admin</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller'): ?>
                <a href="admin/seller_dashboard.php" class="nav-btn" style="border-color:var(--neon-green); color:var(--neon-green);">📊 Tableau Vendeur</a>
                <a href="admin/article_new.php" class="nav-btn" style="border-color:var(--neon-green); color:var(--neon-green);">➕ Vendre un article</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobileMenu" onclick="toggleMobileMenu()">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </nav>

    <div class="mobile-menu-backdrop" onclick="closeMobileMenu()"></div>
    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
        <div class="mobile-menu-inner">
            <a href="test.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()">Contact</a>
            <a href="wishlist.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()">Favoris</a>
            <a href="conditions.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()">Conditions</a>
            <button class="nav-btn mobile-nav-btn" type="button" onclick="closeMobileMenu(); openCart();">
                <span>Panier</span>
                <span class="cart-count" id="cartCountMobile">0</span>
            </button>
            <a href="order_history.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()">Historique</a>
            <a href="profile.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()">Profil</a>
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'seller'): ?>
                <a href="apply_seller.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()" style="border-color:#ff9500; color:#ffb703;">💼 Devenir Vendeur</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin/admin.php" class="nav-btn mobile-nav-btn admin" onclick="closeMobileMenu()">Admin</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller'): ?>
                <a href="admin/seller_dashboard.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()" style="border-color:var(--neon-green); color:var(--neon-green);">📊 Tableau Vendeur</a>
                <a href="admin/article_new.php" class="nav-btn mobile-nav-btn" onclick="closeMobileMenu()" style="border-color:var(--neon-green); color:var(--neon-green);">➕ Vendre un article</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ HERO ══ -->
    <?php if (isset($_GET['article_submitted'])): ?>
        <div style="position:fixed; top:90px; left:50%; transform:translateX(-50%); z-index:1000; width:90%; max-width:600px; padding:15px; border-radius:12px; background:rgba(0,255,136,0.15); border:1px solid var(--neon-green); color:var(--neon-green); font-family:'Orbitron',sans-serif; font-size:12px; text-align:center; backdrop-filter:blur(10px); box-shadow:0 10px 30px rgba(0,0,0,0.5);">
            ✅ Votre article a été soumis avec succès et est en attente de validation par l'administrateur.
        </div>
    <?php endif; ?>
    <section class="hero" id="home">
        <canvas id="canvas3d"></canvas>
        <div class="hero-content">
            <h1>Comptes eFootball<br>Premium</h1>
            <p class="hero-desc">Fatigué de passer des heures à construire votre équipe de rêve ? Notre mission est de vous donner un accès instantané à des comptes eFootball exceptionnels, chargés de joueurs légendaires et de pièces, pour que vous puissiez dominer le terrain sans attendre. C'est rapide, sécurisé et garanti ✓</p>
            <div class="hero-badges">
                <div class="badge">✓ Livraison Immédiate</div>
                <div class="badge">✓ 100% Sécurisé</div>
                <div class="badge">✓ Garantie 30 Jours</div>
                <div class="badge">✓ Support 24/7</div>
            </div>
            <a href="list_articles.php" class="cta-button" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                Voir les Comptes
            </a>
        </div>
    </section>

    <div class="neon-divider"></div>

    <!-- ══ ARTICLES ══ -->
    <?php
    try {
        $pdo = db();
        $stmt = $pdo->query("SELECT id, title, slug, content, image, price, platform, delivery_time, binding_status, product_status, gallery_images, why_choose_us, created_at FROM articles WHERE (approval_status = 'approved' OR approval_status IS NULL OR author_username IS NULL) ORDER BY created_at DESC LIMIT 6");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $recent = []; }
    ?>

    <section id="articles">
        <h2 class="section-title reveal">Nos Articles</h2>
        <div class="articles-grid">
            <?php if (empty($recent)): ?>
                <p style="color:rgba(255,255,255,0.4);text-align:center;grid-column:1/-1;letter-spacing:1px;">Aucun article pour le moment.</p>
            <?php else: ?>
                <?php foreach($recent as $i => $art): ?>
                    <?php $statusMeta = article_status_meta($art['product_status'] ?? null); ?>
                    <div class="article-card reveal" style="animation-delay:<?= $i * 0.1 ?>s; cursor: pointer;" onclick="articleLightbox.open(<?= htmlspecialchars(json_encode($art['id'])) ?>)">
                        <div class="article-card-top">
                            <span class="status-badge <?= htmlspecialchars($statusMeta['class']) ?>"><?= htmlspecialchars($statusMeta['label']) ?></span>
                            <button class="favorite-btn<?= in_array((int) $art['id'], $wishlistIds, true) ? ' is-active' : '' ?>" type="button" data-article-id="<?= (int) $art['id'] ?>" onclick="event.stopPropagation(); toggleWishlist(<?= (int) $art['id'] ?>, this)" aria-label="Ajouter aux favoris">♥</button>
                        </div>
                        <?php if (!empty($art['image'])): ?>
                            <img src="uploads/articles/<?= htmlspecialchars($art['image']) ?>" alt="" style="cursor: pointer;">
                        <?php endif; ?>
                        <div class="article-card-body">
                            <h3><?= htmlspecialchars($art['title']) ?></h3>
                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags($art['content']), 0, 140, '...')) ?></p>
                            <div class="article-status-copy">Statut actuel: <?= htmlspecialchars($statusMeta['label']) ?></div>
                            <a href="javascript:void(0);" class="article-link" onclick="event.stopPropagation();">Voir tous les articles →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="neon-divider"></div>

    <!-- ══ FEATURES ══ -->
    <section id="garanties">
        <h2 class="section-title reveal">Pourquoi Nous Choisir ?</h2>
        <div class="features-grid">
            <?php
            $features = [
                ['⚡','Livraison Immédiate','Recevez votre compte en moins de 5 minutes après l\'achat'],
                ['🔒','100% Sécurisé','Tous nos comptes sont vérifiés et sécurisés'],
                ['💎','Qualité Premium','Comptes avec les meilleurs joueurs et coins'],
                ['🛡️','Garantie 30 Jours','Remboursement complet si problème'],
                ['💬','Support 24/7','Notre équipe disponible à tout moment'],
                ['💳','Paiement Sécurisé','Plusieurs méthodes de paiement acceptées'],
            ];
            foreach($features as $i => $f): ?>
                <div class="feature-card reveal" style="animation-delay:<?= $i * 0.08 ?>s">
                    <div class="feature-icon"><?= $f[0] ?></div>
                    <h3><?= $f[1] ?></h3>
                    <p><?= $f[2] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="neon-divider"></div>

    <!-- ══ TESTIMONIALS ══ -->
    <section id="avis">
        <h2 class="section-title reveal">Avis Clients</h2>
        <div class="testimonials-grid">
            <?php
            $testimonials = [
                ['M','Mohamed','Il y a 2 jours','"Excellent service ! J\'ai reçu mon compte en 3 minutes avec tous les joueurs promis. Incroyable !"'],
                ['A','Ahmed','Il y a 1 semaine','"Meilleur site pour acheter des comptes eFootball. Prix corrects et service rapide. Je recommande !"'],
                ['Y','Youssef','Il y a 3 jours','"Super fiable, j\'ai acheté 3 comptes et tous fonctionnent parfaitement. Support très réactif !"'],
            ];
            foreach($testimonials as $i => $t): ?>
                <div class="testimonial-card reveal" style="animation-delay:<?= $i * 0.12 ?>s">
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text"><?= $t[3] ?></p>
                    <div class="testimonial-author">
                        <div class="author-avatar"><?= $t[0] ?></div>
                        <div>
                            <div class="author-name"><?= $t[1] ?></div>
                            <div class="author-date"><?= $t[2] ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ══ FOOTER ══ -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>eFootball Store</h3>
                <p>La meilleure boutique pour acheter des comptes eFootball premium avec garantie et support.</p>
            </div>
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul>
                    <li><a href="#comptes">Comptes</a></li>
                    <li><a href="#garanties">Garanties</a></li>
                    <li><a href="#avis">Avis Clients</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Guide d'achat</a></li>
                    <li><a href="#">Conditions</a></li>
                    <li><a href="#">Politique de remboursement</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li>📧 diagneibeu10@gmail.com</li>
                    <li>💬 Discord: BEST DRIBBLEUR SN</li>
                    <li>📱 WhatsApp: +221 77 507 29 36</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2025 eFootball Store. Tous droits réservés.
        </div>
    </footer>

    <!-- ══ CART MODAL ══ -->
    <div class="modal" id="cartModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeCart()">&times;</span>
            <h2>🛒 Votre Panier</h2>
            <div id="cartItems"></div>
            
            <!-- PROFILE FORM SECTION -->
            <div id="profileFormSection" style="display:none;margin-top:24px;padding-top:20px;border-top:1px solid rgba(0,207,255,0.2);">
                <div style="background:rgba(0,207,255,0.08);padding:14px;border-radius:8px;margin-bottom:16px;font-size:13px;color:rgba(255,255,255,0.8);">
                    ℹ️ Veuillez compléter vos informations personnelles avant de commander.
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Prénom</label>
                        <input type="text" id="formPrenom" placeholder="Votre prénom" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Nom</label>
                        <input type="text" id="formNom" placeholder="Votre nom" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                    </div>
                </div>
                
                <div style="margin-bottom:12px;">
                    <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Email</label>
                    <input type="email" id="formEmail" placeholder="votre.email@exemple.com" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom:12px;">
                    <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Adresse</label>
                    <input type="text" id="formAdresse" placeholder="Votre adresse" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom:12px;">
                    <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Ville</label>
                    <input type="text" id="formVille" placeholder="Votre ville" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom:16px;">
                    <label style="display:block;color:rgba(255,255,255,0.7);font-size:12px;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;">Téléphone (9 chiffres)</label>
                    <input type="tel" id="formTelephone" placeholder="77 507 29 36" maxlength="9" inputmode="numeric" style="width:100%;padding:10px;border-radius:6px;border:1px solid rgba(0,207,255,0.3);background:rgba(0,0,0,0.3);color:#fff;font-size:14px;box-sizing:border-box;">
                </div>
            </div>
            
            <!-- WHATSAPP PHONE SECTION -->
            <label style="display:block;margin-top:16px;color:rgba(255,255,255,0.9);font-family:'Rajdhani',sans-serif;font-size:14px;">Votre numéro WhatsApp (9 chiffres)</label>
            <input type="tel" id="payerPhoneNumber" placeholder="77 507 29 36" maxlength="9" inputmode="numeric"
                style="width:100%;padding:12px;margin-top:8px;border-radius:8px;border:1px solid rgba(0,207,255,0.4);background:rgba(0,0,0,0.3);color:#fff;font-size:16px;box-sizing:border-box;">
            <button class="cta-button" style="width:100%;margin-top:20px;" onclick="checkout()">
                Confirmer ma commande
            </button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="style/profile_form.js"></script>
    <script>
    /* ── Particles ── */
    (function(){
        const c = document.getElementById('particles');
        for(let i=0;i<50;i++){
            const p = document.createElement('div');
            p.className = 'particle';
            const green = Math.random()>0.5;
            p.style.cssText=`
                left:${Math.random()*100}%;
                animation-duration:${5+Math.random()*10}s;
                animation-delay:${Math.random()*10}s;
                --drift:${(Math.random()-0.5)*120}px;
                background:${green?'#00ff88':'#00cfff'};
                box-shadow:0 0 6px ${green?'#00ff88':'#00cfff'};
                width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;
            `;
            c.appendChild(p);
        }
    })();

    /* ── Three.js canvas ── */
    (function(){
        const canvas = document.getElementById('canvas3d');
        if(!canvas||!window.THREE) return;
        const renderer = new THREE.WebGLRenderer({canvas, alpha:true, antialias:true});
        renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, canvas.offsetWidth/canvas.offsetHeight, 0.1, 1000);
        camera.position.z = 5;

        // Floating spheres / stars
        const geometry = new THREE.BufferGeometry();
        const count = 1200;
        const pos = new Float32Array(count*3);
        for(let i=0;i<count*3;i++) pos[i]=(Math.random()-0.5)*20;
        geometry.setAttribute('position', new THREE.BufferAttribute(pos,3));
        const mat = new THREE.PointsMaterial({color:0x00ff88, size:0.04, transparent:true, opacity:0.7});
        const points = new THREE.Points(geometry,mat);
        scene.add(points);

        // Wireframe sphere
        const sGeo = new THREE.SphereGeometry(2, 20, 20);
        const sMat = new THREE.MeshBasicMaterial({color:0x00cfff, wireframe:true, transparent:true, opacity:0.12});
        const sphere = new THREE.Mesh(sGeo, sMat);
        scene.add(sphere);

        function resize(){
            const w=canvas.offsetWidth, h=canvas.offsetHeight;
            renderer.setSize(w,h,false);
            camera.aspect=w/h; camera.updateProjectionMatrix();
        }
        resize(); window.addEventListener('resize',resize);

        let t=0;
        (function animate(){
            requestAnimationFrame(animate);
            t+=0.004;
            points.rotation.y=t*0.3; points.rotation.x=t*0.1;
            sphere.rotation.y=t*0.5; sphere.rotation.x=t*0.2;
            renderer.render(scene,camera);
        })();
    })();

    /* ── Scroll reveal ── */
    const observer = new IntersectionObserver((entries)=>{
        entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
    },{threshold:0.12});
    document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));

    function toggleWishlist(articleId, button) {
        fetch('wishlist_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ article_id: articleId })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                if (typeof window.notify === 'function') {
                    window.notify(data.message || 'Impossible de modifier les favoris.', 'warning', 'Favoris');
                }
                return;
            }

            if (button) {
                button.classList.toggle('is-active', !!data.is_favorite);
            }

            if (typeof window.notify === 'function') {
                window.notify(data.message || 'Liste d envies mise a jour.', 'success', 'Favoris');
            }
        })
        .catch(() => {
            if (typeof window.notify === 'function') {
                window.notify('Une erreur technique est survenue.', 'error', 'Favoris');
            }
        });
    }

    // Close modal on backdrop click
    document.getElementById('cartModal').addEventListener('click',function(e){
        if(e.target===this) document.getElementById('cartModal').classList.remove('open');
    });

    const mobileMenu = document.getElementById('mobileMenu');
    const navToggle = document.querySelector('.nav-toggle');

    function syncCartBadge() {
        const mainBadge = document.getElementById('cartCount');
        const mobileBadge = document.getElementById('cartCountMobile');
        if (mainBadge && mobileBadge) {
            mobileBadge.textContent = mainBadge.textContent;
        }
    }

    function closeMobileMenu() {
        document.body.classList.remove('menu-open');
        if (mobileMenu) mobileMenu.setAttribute('aria-hidden', 'true');
        if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
    }

    function openMobileMenu() {
        document.body.classList.add('menu-open');
        if (mobileMenu) mobileMenu.setAttribute('aria-hidden', 'false');
        if (navToggle) navToggle.setAttribute('aria-expanded', 'true');
        syncCartBadge();
    }

    function toggleMobileMenu() {
        if (document.body.classList.contains('menu-open')) {
            closeMobileMenu();
            return;
        }
        openMobileMenu();
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            closeMobileMenu();
        }
    });

    const cartCountNode = document.getElementById('cartCount');
    if (cartCountNode && window.MutationObserver) {
        syncCartBadge();
        new MutationObserver(syncCartBadge).observe(cartCountNode, { childList: true, characterData: true, subtree: true });
    }
    </script>
    <script src="style/article_lightbox.js"></script>
    <script src="style/cart.js"></script>
</body>
</html>
