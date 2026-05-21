<?php
require_once __DIR__ . '/config.php';

try {
    ensure_store_schema();
} catch (Throwable $e) {
}

$isLogged = !empty($_SESSION['logged']) && $_SESSION['logged'] === true;
$currentUserId = current_user_id();
$wishlistIds = $currentUserId ? fetch_wishlist_article_ids($currentUserId) : [];
$featuredArticles = [];

try {
    $stmt = db()->query("SELECT id, title, slug, content, image, price, platform, delivery_time, binding_status, product_status, gallery_images, why_choose_us, created_at FROM articles WHERE (approval_status = 'approved' OR approval_status IS NULL OR author_username IS NULL) ORDER BY created_at DESC LIMIT 6");
    $featuredArticles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $featuredArticles = [];
}

$fallbackArticles = [
    ['id' => null, 'image' => '', 'title' => 'PUISSANCE 3181 - MOBILE', 'content' => 'Compte elite avec Messi, Neymar, Mbappe. Equipe prete a gagner.', 'price' => '29999', 'platform' => 'Android/iOS', 'delivery_time' => '5 min', 'binding_status' => 'Verifie', 'product_status' => 'available'],
    ['id' => null, 'image' => '', 'title' => 'PUISSANCE 3500 - ULTIME', 'content' => 'Le meilleur compte du catalogue avec CR7, Pele et Maradona.', 'price' => '34999', 'platform' => 'Android/iOS', 'delivery_time' => '5 min', 'binding_status' => 'Verifie', 'product_status' => 'available'],
    ['id' => null, 'image' => '', 'title' => 'PUISSANCE 2850 - PRO', 'content' => 'Parfait pour les joueurs intermediaires avec une equipe equilibree.', 'price' => '24999', 'platform' => 'Android/iOS', 'delivery_time' => '10 min', 'binding_status' => 'Verifie', 'product_status' => 'reserved'],
    ['id' => null, 'image' => '', 'title' => 'PUISSANCE 2500 - STARTER', 'content' => 'Ideal pour debuter avec une equipe solide des le premier match.', 'price' => '19999', 'platform' => 'Android/iOS', 'delivery_time' => '5 min', 'binding_status' => 'Verifie', 'product_status' => 'available'],
];

if ($featuredArticles === []) {
    $featuredArticles = $fallbackArticles;
}

$heroArticle = $featuredArticles[0] ?? $fallbackArticles[0];
$heroArticleId = isset($heroArticle['id']) ? (int) $heroArticle['id'] : 0;
$heroStatusMeta = article_status_meta($heroArticle['product_status'] ?? null);
$heroImageUrl = landing_article_image_url($heroArticle['image'] ?? '');
$siteLogo = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSPaMEBxEFKZDyKze1PbLLKgv-PZ2BJQkJd1Q&s';

function landing_article_image_url(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $image) || strpos($image, '/') === 0) {
        return $image;
    }

    return 'uploads/articles/' . rawurlencode($image);
}

function landing_price($price): string
{
    return number_format((float) $price, 0, ',', ' ');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dribbleur Store - Comptes eFootball Premium Senegal</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Syne:wght@400;600;700;800&family=Syne+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($isLogged): ?>
        <link rel="stylesheet" href="style/article_lightbox.css">
    <?php endif; ?>
    <style>
        :root {
            --black: #050810;
            --surface: #0a1020;
            --surface2: #0f1a2e;
            --green: #00ff88;
            --cyan: #00d4ff;
            --gold: #ffc836;
            --red: #ff3d6b;
            --senegal-green: #00853f;
            --senegal-yellow: #fdef42;
            --senegal-red: #e31b23;
            --green-dim: rgba(0,255,136,.12);
            --cyan-dim: rgba(0,212,255,.12);
            --gold-dim: rgba(255,200,54,.1);
            --glow-g: 0 0 30px rgba(0,255,136,.4), 0 0 80px rgba(0,255,136,.12);
            --glow-c: 0 0 30px rgba(0,212,255,.4), 0 0 80px rgba(0,212,255,.12);
            --nav-h: 74px;
            --transition: all .3s cubic-bezier(.4,0,.2,1);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html {
            scroll-behavior: smooth;
            overflow-y: auto;
        }
        body {
            background: var(--black);
            font-family: 'Syne', sans-serif;
            color: #ddeeff;
            overflow-x: hidden;
            overflow-y: auto;
            min-height: 100vh;
            line-height: 1.6;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
            opacity: .45;
        }

        .grid-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(0,212,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,212,255,.035) 1px, transparent 1px);
            background-size: 48px 48px;
            animation: gridDrift 20s linear infinite;
        }
        @keyframes gridDrift { to { background-position: 48px 48px; } }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            animation: orbDrift linear infinite;
        }
        .orb-1 { width: 560px; height: 560px; background: radial-gradient(circle, rgba(0,255,136,.18), transparent); top: -200px; left: -200px; animation-duration: 20s; }
        .orb-2 { width: 480px; height: 480px; background: radial-gradient(circle, rgba(0,212,255,.15), transparent); bottom: -150px; right: -150px; animation-duration: 16s; animation-delay: -8s; }
        .orb-3 { width: 360px; height: 360px; background: radial-gradient(circle, rgba(255,200,54,.1), transparent); top: 40%; left: 55%; animation-duration: 24s; animation-delay: -4s; }
        @keyframes orbDrift {
            0%,100% { transform: translate(0,0) scale(1); }
            33% { transform: translate(30px,-20px) scale(1.04); }
            66% { transform: translate(-20px,30px) scale(.97); }
        }

        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--nav-h);
            z-index: 100;
            display: flex;
            align-items: center;
            padding: 0 48px;
            background: rgba(5,8,16,.86);
            backdrop-filter: blur(24px) saturate(1.5);
            border-bottom: 1px solid rgba(0,212,255,.15);
            box-shadow: 0 4px 30px rgba(0,0,0,.3);
        }
        nav::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--green), var(--cyan), var(--gold), transparent);
            background-size: 400% 100%;
            animation: navLine 4s ease-in-out infinite;
        }
        @keyframes navLine {
            0%,100% { background-position: 0 0; opacity: .4; }
            50% { background-position: 100% 0; opacity: 1; }
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            margin-right: auto;
            min-width: 0;
        }
        .nav-logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--senegal-green), var(--senegal-yellow), var(--senegal-red));
            display: grid;
            place-items: center;
            font-size: 20px;
            box-shadow: var(--glow-g);
            position: relative;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .nav-logo-icon::after {
            content: "";
            position: absolute;
            inset: 2px;
            background: var(--surface);
            border-radius: 10px;
        }
        .nav-logo-icon span { position: relative; z-index: 2; }
        .nav-logo-icon img {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            border-radius: 10px;
            object-fit: cover;
        }
        .nav-logo-text {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 17px;
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--green), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            white-space: nowrap;
        }
        .senegal-flag { display: inline-flex; gap: 2px; }
        .senegal-flag span { width: 12px; height: 8px; border-radius: 2px; }
        .senegal-flag .green { background: var(--senegal-green); }
        .senegal-flag .yellow { background: var(--senegal-yellow); }
        .senegal-flag .red { background: var(--senegal-red); }

        .nav-links, .nav-auth {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }
        .nav-links a, .auth-link {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: rgba(255,255,255,.68);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .4px;
            transition: var(--transition);
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--green);
            background: rgba(0,255,136,.08);
        }
        .nav-auth { margin-left: 18px; }
        .auth-link {
            border: 1px solid rgba(0,212,255,.28);
            color: var(--cyan);
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .auth-link:hover {
            border-color: var(--cyan);
            background: rgba(0,212,255,.1);
            box-shadow: var(--glow-c);
            transform: translateY(-1px);
        }
        .auth-link.primary {
            background: linear-gradient(135deg, var(--green), #00b86b);
            color: #001a0d;
            border: none;
            font-family: 'Orbitron', monospace;
            font-size: 11px;
            letter-spacing: 1.5px;
            box-shadow: var(--glow-g);
        }
        .auth-link.primary:hover {
            background: linear-gradient(135deg, #27ff9d, #00c777);
            box-shadow: 0 0 50px rgba(0,255,136,.6);
        }
        .cart-count {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--green);
            color: #001a0d;
            font-family: 'Orbitron', monospace;
            font-size: 10px;
            font-weight: 900;
        }
        .nav-mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--cyan);
            font-size: 24px;
            cursor: pointer;
            margin-left: 14px;
        }

        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: calc(var(--nav-h) + 58px) 48px 80px;
            overflow: hidden;
            z-index: 2;
        }
        .field-bg {
            position: absolute;
            right: -80px;
            top: 50%;
            transform: translateY(-50%);
            width: 700px;
            height: 700px;
            opacity: .06;
            animation: fieldRotate 40s linear infinite;
        }
        @keyframes fieldRotate { to { transform: translateY(-50%) rotate(360deg); } }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 700px;
            animation: heroReveal .9s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes heroReveal {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 999px;
            border: 1px solid rgba(0,255,136,.25);
            background: rgba(0,255,136,.06);
            font-family: 'Syne Mono', monospace;
            font-size: 11px;
            letter-spacing: 2px;
            color: var(--green);
            margin-bottom: 28px;
        }
        .eyebrow-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: dotBlink 2s ease-in-out infinite;
        }
        @keyframes dotBlink { 0%,100% { opacity: 1; } 50% { opacity: .2; } }
        .hero h1 {
            font-family: 'Orbitron', monospace;
            font-size: clamp(36px, 5.5vw, 68px);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 28px;
        }
        .hero h1 span { display: block; }
        .hero .line-2 {
            background: linear-gradient(90deg, var(--green), var(--cyan) 60%, var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 24px rgba(0,255,136,.35));
        }
        .hero .senegal {
            display: inline;
            background: linear-gradient(90deg, var(--senegal-green), var(--senegal-yellow), var(--senegal-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-desc {
            font-size: 17px;
            line-height: 1.75;
            color: rgba(255,255,255,.7);
            max-width: 560px;
            margin-bottom: 40px;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .btn-primary, .btn-secondary {
            border-radius: 12px;
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .btn-primary {
            position: relative;
            overflow: hidden;
            padding: 16px 34px;
            background: linear-gradient(135deg, var(--green), #00b86b);
            color: #001a0d;
            font-size: 12px;
            box-shadow: 0 8px 32px rgba(0,255,136,.35);
        }
        .btn-primary::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -70%;
            width: 45%;
            height: 200%;
            background: rgba(255,255,255,.28);
            transform: skewX(-20deg);
            animation: shimmer 3s ease-in-out infinite;
        }
        @keyframes shimmer {
            0% { left: -70%; opacity: 0; }
            20% { opacity: 1; }
            50%,100% { left: 140%; opacity: 0; }
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: var(--glow-g); }
        .btn-secondary {
            padding: 16px 28px;
            background: transparent;
            border: 1px solid rgba(0,212,255,.25);
            color: var(--cyan);
            font-size: 11px;
        }
        .btn-secondary:hover {
            background: rgba(0,212,255,.08);
            border-color: var(--cyan);
            box-shadow: var(--glow-c);
            transform: translateY(-2px);
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            border: none;
            box-shadow: 0 8px 32px rgba(37,211,102,.35);
        }
        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 56px;
            flex-wrap: wrap;
        }
        .hero-stat-val {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 28px;
            line-height: 1;
            background: linear-gradient(135deg, #fff, var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-stat-label {
            font-size: 12px;
            color: rgba(255,255,255,.45);
            letter-spacing: 1px;
            margin-top: 5px;
        }
        .hero-card {
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            width: 340px;
            z-index: 3;
            animation: cardFloat 6s ease-in-out infinite;
        }
        @keyframes cardFloat {
            0%,100% { transform: translateY(-50%) translateX(0); }
            50% { transform: translateY(calc(-50% - 12px)) translateX(4px); }
        }
        .product-card {
            background: linear-gradient(145deg, rgba(10,16,32,.98), rgba(15,26,46,.95));
            border: 1px solid rgba(0,212,255,.2);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 40px 80px rgba(0,0,0,.7), 0 0 60px rgba(0,212,255,.08), inset 0 1px 0 rgba(255,255,255,.06);
            position: relative;
            overflow: hidden;
        }
        .product-card::before {
            content: "";
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            height: 3px;
            background: linear-gradient(90deg, var(--senegal-green), var(--senegal-yellow), var(--senegal-red));
        }
        .product-card-badge, .feature-tag, .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            font-family: 'Syne Mono', monospace;
            font-size: 10px;
        }
        .product-card-badge {
            padding: 4px 12px;
            background: rgba(0,255,136,.1);
            border: 1px solid rgba(0,255,136,.22);
            color: var(--green);
            margin-bottom: 16px;
            letter-spacing: 1.4px;
        }
        .product-card-img {
            width: 100%;
            height: 160px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(0,255,136,.08), rgba(0,212,255,.12));
            display: grid;
            place-items: center;
            font-size: 72px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,.06);
            overflow: hidden;
        }
        .product-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .product-card-title {
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1.5px;
            color: var(--cyan);
            margin-bottom: 8px;
        }
        .product-card-desc { font-size: 13px; color: rgba(255,255,255,.55); margin-bottom: 18px; }
        .product-card-features, .payment-methods { display: flex; gap: 8px; flex-wrap: wrap; }
        .product-card-features { margin-bottom: 20px; }
        .feature-tag {
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(0,212,255,.1);
            border: 1px solid rgba(0,212,255,.2);
            color: var(--cyan);
        }
        .product-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .product-card-price {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 22px;
            color: var(--green);
            text-shadow: 0 0 16px rgba(0,255,136,.4);
        }
        .product-card-price small { font-size: 12px; color: rgba(255,255,255,.5); font-weight: 400; }
        .product-card-btn {
            padding: 10px 18px;
            border-radius: 10px;
            background: rgba(0,255,136,.1);
            border: 1px solid rgba(0,255,136,.22);
            color: var(--green);
            font-family: 'Orbitron', monospace;
            font-size: 10px;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: var(--transition);
        }
        .product-card-btn:hover { background: var(--green); color: #001a0d; }
        .payment-methods { margin-top: 16px; }
        .payment-badge {
            padding: 6px 10px;
            border-radius: 8px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
        }
        .payment-badge.orange { color: #ff7900; border-color: #ff7900; }
        .payment-badge.wave { color: #1db954; border-color: #1db954; }
        .payment-badge.freemoney { color: #00a8e1; border-color: #00a8e1; }

        .ticker-wrap {
            position: relative;
            z-index: 2;
            overflow: hidden;
            background: rgba(0,255,136,.04);
            border-top: 1px solid rgba(0,255,136,.1);
            border-bottom: 1px solid rgba(0,255,136,.1);
            padding: 14px 0;
        }
        .ticker {
            display: flex;
            gap: 64px;
            white-space: nowrap;
            animation: tickerScroll 30s linear infinite;
        }
        .ticker-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Orbitron', monospace;
            font-size: 11px;
            letter-spacing: 1.4px;
            color: rgba(255,255,255,.48);
            flex-shrink: 0;
        }
        .ticker-item .dot, .ticker-item strong { color: var(--green); }
        @keyframes tickerScroll { to { transform: translateX(-50%); } }

        section {
            position: relative;
            z-index: 2;
            padding: 96px 48px;
        }
        .section-inner { max-width: 1300px; margin: 0 auto; }
        .section-label {
            font-family: 'Syne Mono', monospace;
            font-size: 11px;
            letter-spacing: 3px;
            color: var(--green);
            text-transform: uppercase;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-label::before { content: ""; width: 32px; height: 1px; background: var(--green); }
        .section-title {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: clamp(28px, 3.5vw, 46px);
            line-height: 1.15;
            margin-bottom: 16px;
        }
        .accent {
            background: linear-gradient(90deg, var(--green), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .section-sub {
            font-size: 16px;
            color: rgba(255,255,255,.55);
            max-width: 540px;
            line-height: 1.7;
        }
        .articles-section, .testi-section { background: var(--surface); }
        .articles-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 52px;
            flex-wrap: wrap;
        }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-family: 'Syne Mono', monospace;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.62);
        }
        .filter-tab.active { background: rgba(0,255,136,.1); border-color: var(--green); color: var(--green); }
        .articles-grid, .features-grid, .testi-grid {
            display: grid;
            gap: 22px;
        }
        .articles-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .features-grid { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); margin-top: 52px; }
        .testi-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); margin-top: 52px; }

        .article-card, .feature-card, .testi-card {
            border-radius: 20px;
            transition: var(--transition);
            animation: fadeUp .5s ease both;
            position: relative;
            overflow: hidden;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .article-card {
            background: var(--surface2);
            border: 1px solid rgba(0,212,255,.1);
            cursor: pointer;
        }
        .article-card:hover, .feature-card:hover, .testi-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0,255,136,.32);
            box-shadow: var(--glow-g);
        }
        .article-card-img {
            height: 190px;
            background: linear-gradient(135deg, rgba(0,255,136,.06), rgba(0,212,255,.08));
            display: grid;
            place-items: center;
            font-size: 56px;
            overflow: hidden;
        }
        .article-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .article-card-top {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            justify-content: space-between;
            z-index: 2;
        }
        .status-pill {
            padding: 6px 12px;
            border-radius: 999px;
            font-family: 'Syne Mono', monospace;
            font-size: 9px;
            letter-spacing: 1.2px;
            backdrop-filter: blur(8px);
        }
        .available { color: var(--green); background: rgba(0,255,136,.12); border: 1px solid rgba(0,255,136,.24); }
        .reserved { color: var(--gold); background: rgba(255,200,54,.12); border: 1px solid rgba(255,200,54,.24); }
        .sold { color: var(--red); background: rgba(255,61,107,.12); border: 1px solid rgba(255,61,107,.24); }
        .fav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.18);
            background: rgba(5,8,16,.7);
            color: #fff;
            display: grid;
            place-items: center;
            cursor: pointer;
        }
        .fav-btn.active, .favorite-btn.is-active { color: var(--red); border-color: rgba(255,61,107,.45); background: rgba(255,61,107,.13); }
        .article-card-body { padding: 20px; }
        .article-card-title {
            font-family: 'Orbitron', monospace;
            font-size: 13px;
            letter-spacing: 1.2px;
            color: var(--cyan);
            margin-bottom: 10px;
        }
        .article-card-desc { font-size: 13px; color: rgba(255,255,255,.55); line-height: 1.55; margin-bottom: 16px; }
        .article-card-features { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .feature-mini {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            background: rgba(0,212,255,.1);
            color: var(--cyan);
            font-family: 'Syne Mono', monospace;
        }
        .article-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .article-price {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 19px;
            color: var(--green);
            text-shadow: 0 0 14px rgba(0,255,136,.35);
        }
        .article-price small { font-size: 11px; color: rgba(255,255,255,.4); font-weight: 400; }
        .btn-voir {
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(0,255,136,.08);
            border: 1px solid rgba(0,255,136,.2);
            color: var(--green);
            font-family: 'Orbitron', monospace;
            font-size: 9px;
            letter-spacing: 1.5px;
            text-decoration: none;
        }
        .feature-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,.06);
            padding: 32px 26px;
        }
        .feature-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 26px;
            margin-bottom: 20px;
            background: var(--green-dim);
        }
        .feature-card:nth-child(2n) .feature-icon-wrap { background: var(--cyan-dim); }
        .feature-card:nth-child(3n) .feature-icon-wrap { background: var(--gold-dim); }
        .feature-title {
            font-family: 'Orbitron', monospace;
            font-size: 13px;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
            color: var(--green);
        }
        .feature-card:nth-child(2n) .feature-title { color: var(--cyan); }
        .feature-card:nth-child(3n) .feature-title { color: var(--gold); }
        .feature-desc { font-size: 14px; color: rgba(255,255,255,.55); line-height: 1.65; }
        .testi-card {
            background: var(--surface2);
            border: 1px solid rgba(0,255,136,.1);
            padding: 30px;
        }
        .stars { color: var(--gold); font-size: 16px; letter-spacing: 2px; margin-bottom: 16px; }
        .testi-text { font-size: 15px; color: rgba(255,255,255,.68); line-height: 1.75; margin-bottom: 22px; font-style: italic; }
        .testi-author { display: flex; align-items: center; gap: 12px; }
        .testi-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--senegal-green), var(--senegal-yellow), var(--senegal-red));
            display: grid;
            place-items: center;
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            color: #001a0d;
        }
        .testi-name { font-weight: 700; font-size: 15px; }
        .testi-date { font-size: 12px; color: rgba(255,255,255,.35); margin-top: 3px; }
        .testi-location { font-size: 11px; color: var(--cyan); }

        .cta-section {
            text-align: center;
            overflow: hidden;
            background: linear-gradient(180deg, var(--surface), var(--black));
        }
        .cta-content { position: relative; z-index: 2; max-width: 720px; margin: 0 auto; }
        .cta-content h2 {
            font-family: 'Orbitron', monospace;
            font-size: clamp(28px, 4vw, 50px);
            line-height: 1.15;
            margin-bottom: 20px;
        }
        .cta-content p { font-size: 17px; color: rgba(255,255,255,.58); margin-bottom: 36px; }
        .cta-actions { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }

        footer {
            position: relative;
            z-index: 2;
            background: rgba(5,8,16,.95);
            border-top: 1px solid rgba(0,212,255,.1);
            padding: 64px 48px 34px;
        }
        .footer-grid {
            max-width: 1300px;
            margin: 0 auto 44px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 42px;
        }
        .footer-brand p { font-size: 14px; color: rgba(255,255,255,.45); line-height: 1.7; max-width: 300px; margin-top: 16px; }
        .footer-social { display: flex; gap: 12px; margin-top: 20px; }
        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,.05);
            display: grid;
            place-items: center;
            color: var(--cyan);
            border: 1px solid rgba(255,255,255,.1);
            text-decoration: none;
        }
        .footer-col h4 {
            font-family: 'Orbitron', monospace;
            font-size: 11px;
            letter-spacing: 2px;
            color: rgba(255,255,255,.38);
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .footer-col a {
            text-decoration: none;
            color: rgba(255,255,255,.52);
            font-size: 14px;
            transition: var(--transition);
        }
        .footer-col a:hover { color: var(--green); padding-left: 4px; }
        .footer-bottom {
            max-width: 1300px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: rgba(255,255,255,.28);
        }
        .modal {
            position: fixed;
            inset: 0;
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(2, 8, 17, .74);
            backdrop-filter: blur(14px);
            pointer-events: none;
            visibility: hidden;
        }
        .modal.open {
            display: flex;
            pointer-events: auto;
            visibility: visible;
        }
        .modal-content {
            width: min(560px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: linear-gradient(145deg, rgba(10,16,32,.98), rgba(15,26,46,.98));
            border: 1px solid rgba(0,212,255,.24);
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 32px 90px rgba(0,0,0,.68);
            position: relative;
        }
        .modal-content h2 {
            font-family: 'Orbitron', monospace;
            color: var(--cyan);
            font-size: 18px;
            margin-bottom: 18px;
        }
        .close-modal {
            position: absolute;
            top: 14px;
            right: 18px;
            color: rgba(255,255,255,.7);
            font-size: 28px;
            cursor: pointer;
        }
        .modal input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(0,212,255,.35);
            background: rgba(0,0,0,.28);
            color: #fff;
            font-size: 15px;
        }

        .reveal {
            opacity: 1;
            transform: translateY(0);
            transition: opacity .7s ease, transform .7s ease;
        }
        html.js .reveal:not(.visible) {
            opacity: 0;
            transform: translateY(28px);
        }
        html.js .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1100px) {
            .hero-card { display: none; }
            .hero { justify-content: center; text-align: center; }
            .hero-desc { margin-left: auto; margin-right: auto; }
            .hero-stats, .hero-actions { justify-content: center; }
        }
        @media (max-width: 950px) {
            nav { padding: 0 24px; }
            .nav-links, .nav-auth {
                display: none;
                position: absolute;
                top: var(--nav-h);
                left: 0;
                right: 0;
                background: rgba(5,8,16,.98);
                flex-direction: column;
                align-items: stretch;
                padding: 20px 24px;
                border-bottom: 1px solid rgba(0,212,255,.1);
            }
            .nav-links.active, .nav-auth.active { display: flex; }
            .nav-links { gap: 4px; padding-bottom: 10px; }
            .nav-auth { top: calc(var(--nav-h) + 198px); padding-top: 10px; }
            .nav-links a, .auth-link { width: 100%; justify-content: center; text-align: center; }
            .nav-mobile-toggle { display: block; }
            section { padding: 72px 24px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 620px) {
            .hero { padding-left: 24px; padding-right: 24px; }
            .hero h1 { font-size: 35px; }
            .hero-actions { flex-direction: column; align-items: stretch; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
            .hero-stats { gap: 24px; justify-content: flex-start; text-align: left; }
            .articles-header { align-items: flex-start; }
            .filter-tabs { width: 100%; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 8px; }
            .filter-tab { flex: 0 0 auto; }
            .footer-grid { grid-template-columns: 1fr; }
            footer { padding: 48px 24px 28px; }
            .nav-logo-text { font-size: 14px; letter-spacing: 1px; }
            .senegal-flag { display: none; }
        }
    </style>
</head>
<body>
    <div class="grid-bg"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <nav>
        <a href="index.php" class="nav-logo">
            <div class="nav-logo-icon"><img src="<?= htmlspecialchars($siteLogo) ?>" alt="Dribbleur Store"></div>
            <span class="nav-logo-text">Dribbleur Store</span>
            <div class="senegal-flag"><span class="green"></span><span class="yellow"></span><span class="red"></span></div>
        </a>

        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Ouvrir le menu">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="#comptes" class="active">Comptes</a></li>
            <li><a href="#garanties">Garanties</a></li>
            <li><a href="#avis">Avis</a></li>
            <li><a href="conditions.php">Conditions</a></li>
            <li><a href="support.php">Support</a></li>
        </ul>

        <div class="nav-auth" id="navAuth">
            <?php if ($isLogged): ?>
                <a href="wishlist.php" class="auth-link"><i class="fas fa-heart"></i> Favoris</a>
                <button type="button" class="auth-link" onclick="openCart()" style="background:transparent;cursor:pointer;"><i class="fas fa-shopping-cart"></i> Panier <span class="cart-count" id="cartCount">0</span></button>
                <a href="order_history.php" class="auth-link"><i class="fas fa-receipt"></i> Commandes</a>
                <a href="profile.php" class="auth-link"><i class="fas fa-user"></i> Profil</a>
                <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                    <a href="admin/admin.php" class="auth-link"><i class="fas fa-gear"></i> Admin</a>
                <?php endif; ?>
                <?php if (in_array(($_SESSION['role'] ?? null), ['seller', 'admin'], true)): ?>
                    <a href="admin/seller_dashboard.php" class="auth-link"><i class="fas fa-chart-line"></i> Vendeur</a>
                <?php endif; ?>
                <a href="logout.php" class="auth-link primary"><i class="fas fa-right-from-bracket"></i> Sortir</a>
            <?php else: ?>
                <a href="connexion.php" class="auth-link"><i class="fas fa-right-to-bracket"></i> Se connecter</a>
                <a href="inscription.php" class="auth-link primary"><i class="fas fa-user-plus"></i> S'inscrire</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <svg class="field-bg" viewBox="0 0 500 500" fill="none" stroke="rgba(0,212,255,1)" stroke-width="1.5">
            <rect x="50" y="50" width="400" height="400" rx="8"/>
            <line x1="50" y1="250" x2="450" y2="250"/>
            <circle cx="250" cy="250" r="60"/>
            <circle cx="250" cy="250" r="5" fill="rgba(0,212,255,.5)"/>
            <rect x="50" y="160" width="80" height="180"/>
            <rect x="370" y="160" width="80" height="180"/>
            <rect x="50" y="190" width="40" height="120"/>
            <rect x="410" y="190" width="40" height="120"/>
        </svg>

        <div class="hero-content">
            <div class="hero-eyebrow"><span class="eyebrow-dot"></span> Boutique eFootball #1 au Sénégal</div>
            <h1>
                <span>Domine le terrain</span>
                <span class="line-2">Mobile <span class="senegal">Sénégal</span></span>
            </h1>
            <p class="hero-desc">
                Comptes eFootball Mobile premium vérifiés : légendaires, coins max et livraison en moins de <strong>5 minutes</strong>.
                Paiement Orange Money, Wave, Free Money et support WhatsApp 24/7.
            </p>
            <div class="hero-actions">
                <a href="<?= $isLogged ? 'list_articles.php' : 'inscription.php' ?>" class="btn-primary"><i class="fas fa-gamepad"></i> Découvrir les comptes</a>
                <a href="https://wa.me/221775072936" class="btn-secondary btn-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp Direct</a>
            </div>
            <div class="hero-stats">
                <div>
                    <div class="hero-stat-val" id="counter1">0</div>
                    <div class="hero-stat-label">Comptes livrés</div>
                </div>
                <div>
                    <div class="hero-stat-val" id="counter2">0%</div>
                    <div class="hero-stat-label">Satisfaction</div>
                </div>
                <div>
                    <div class="hero-stat-val" id="counter3">0 min</div>
                    <div class="hero-stat-label">Livraison moyenne</div>
                </div>
            </div>
        </div>

        <div class="hero-card">
            <div class="product-card">
                <div class="product-card-badge">⚡ <?= htmlspecialchars(mb_strtoupper($heroStatusMeta['label'])) ?> - <?= htmlspecialchars((string) ($heroArticle['platform'] ?: 'MOBILE')) ?></div>
                <div class="product-card-img">
                    <?php if ($heroImageUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($heroImageUrl) ?>" alt="<?= htmlspecialchars((string) ($heroArticle['title'] ?? 'Compte eFootball')) ?>">
                    <?php else: ?>
                        ⚽
                    <?php endif; ?>
                </div>
                <div class="product-card-title"><?= htmlspecialchars((string) ($heroArticle['title'] ?? 'Compte eFootball Mobile')) ?></div>
                <div class="product-card-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($heroArticle['content'] ?? 'Compte premium vérifié et prêt à jouer.')), 0, 92, '...')) ?></div>
                <div class="product-card-features">
                    <span class="feature-tag">📱 <?= htmlspecialchars((string) ($heroArticle['platform'] ?: 'Mobile')) ?></span>
                    <span class="feature-tag">🔐 <?= htmlspecialchars((string) ($heroArticle['binding_status'] ?: 'Vérifié')) ?></span>
                    <span class="feature-tag">⚡ <?= htmlspecialchars((string) ($heroArticle['delivery_time'] ?: 'Rapide')) ?></span>
                </div>
                <div class="product-card-footer">
                    <div class="product-card-price"><?= htmlspecialchars(landing_price($heroArticle['price'] ?? 0)) ?> <small>FCFA</small></div>
                    <button class="product-card-btn" onclick="<?= $isLogged && $heroArticleId > 0 ? "articleLightbox.open({$heroArticleId})" : "window.location='inscription.php'" ?>">Acheter</button>
                </div>
                <div class="payment-methods">
                    <span class="payment-badge orange"><i class="fas fa-mobile-alt"></i> Orange Money</span>
                    <span class="payment-badge wave"><i class="fas fa-bolt"></i> Wave</span>
                    <span class="payment-badge freemoney"><i class="fas fa-wallet"></i> Free Money</span>
                </div>
            </div>
        </div>
    </section>

    <div class="ticker-wrap">
        <div class="ticker">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <span class="ticker-item"><span class="dot">●</span> <strong>+500</strong> comptes livrés au Sénégal</span>
                <span class="ticker-item"><span class="dot">●</span> Livraison <strong>&lt; 5 minutes</strong> garantie</span>
                <span class="ticker-item"><span class="dot">●</span> Satisfaction <strong>100%</strong> clients</span>
                <span class="ticker-item"><span class="dot">●</span> Support <strong>WhatsApp</strong> 24/7</span>
                <span class="ticker-item"><span class="dot">●</span> Paiement <strong>Orange Money / Wave</strong></span>
                <span class="ticker-item"><span class="dot">●</span> Compatible <strong>Android & iOS</strong></span>
            <?php endfor; ?>
        </div>
    </div>

    <section class="articles-section" id="comptes">
        <div class="section-inner">
            <div class="articles-header reveal">
                <div>
                    <div class="section-label">Catalogue Mobile</div>
                    <h2 class="section-title">Nos <span class="accent">meilleurs</span> comptes eFootball</h2>
                    <p class="section-sub">Chaque compte est testé sur Android et iOS avant mise en vente. Les clients inscrits peuvent accéder au catalogue complet.</p>
                </div>
                <div class="filter-tabs">
                    <span class="filter-tab active">Tous</span>
                    <span class="filter-tab">Android</span>
                    <span class="filter-tab">iOS</span>
                    <span class="filter-tab">Légendaires</span>
                </div>
            </div>

            <div class="articles-grid">
                <?php foreach ($featuredArticles as $article): ?>
                    <?php
                    $articleId = isset($article['id']) ? (int) $article['id'] : 0;
                    $statusMeta = article_status_meta($article['product_status'] ?? null);
                    $imageUrl = landing_article_image_url($article['image'] ?? '');
                    $cardLink = $isLogged ? 'list_articles.php' : 'inscription.php';
                    $clickAction = ($isLogged && $articleId > 0) ? "articleLightbox.open({$articleId})" : "window.location='{$cardLink}'";
                    ?>
                    <article class="article-card reveal" onclick="<?= htmlspecialchars($clickAction, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="article-card-top">
                            <span class="status-pill <?= htmlspecialchars($statusMeta['value']) ?>"><?= htmlspecialchars($statusMeta['label']) ?></span>
                            <?php if ($isLogged && $articleId > 0): ?>
                                <button class="fav-btn favorite-btn<?= in_array($articleId, $wishlistIds, true) ? ' active is-active' : '' ?>" type="button" data-article-id="<?= $articleId ?>" onclick="event.stopPropagation(); toggleWishlist(<?= $articleId ?>, this)" aria-label="Ajouter aux favoris"><i class="<?= in_array($articleId, $wishlistIds, true) ? 'fas' : 'far' ?> fa-heart"></i></button>
                            <?php else: ?>
                                <button class="fav-btn" type="button" onclick="event.stopPropagation(); window.location='inscription.php'" aria-label="Ajouter aux favoris"><i class="far fa-heart"></i></button>
                            <?php endif; ?>
                        </div>
                        <div class="article-card-img">
                            <?php if ($imageUrl !== ''): ?>
                                <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($article['title'] ?? 'Compte eFootball') ?>">
                            <?php else: ?>
                                ⚽
                            <?php endif; ?>
                        </div>
                        <div class="article-card-body">
                            <div class="article-card-title"><?= htmlspecialchars((string) ($article['title'] ?? 'Compte eFootball Mobile')) ?></div>
                            <div class="article-card-features">
                                <span class="feature-mini">📱 <?= htmlspecialchars((string) ($article['platform'] ?: 'Android/iOS')) ?></span>
                                <span class="feature-mini">🔐 <?= htmlspecialchars((string) ($article['binding_status'] ?: 'Vérifié')) ?></span>
                                <span class="feature-mini">⚡ <?= htmlspecialchars((string) ($article['delivery_time'] ?: 'Rapide')) ?></span>
                            </div>
                            <p class="article-card-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($article['content'] ?? 'Compte premium vérifié et prêt à jouer.')), 0, 130, '...')) ?></p>
                            <div class="article-card-footer">
                                <div class="article-price"><?= htmlspecialchars(landing_price($article['price'] ?? 0)) ?> <small>FCFA</small></div>
                                <a href="<?= $isLogged ? 'javascript:void(0)' : 'inscription.php' ?>" class="btn-voir" onclick="<?= $isLogged && $articleId > 0 ? "event.stopPropagation(); articleLightbox.open({$articleId});" : '' ?>">Voir →</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="garanties">
        <div class="section-inner">
            <div class="reveal" style="text-align:center;">
                <div class="section-label" style="justify-content:center;">Pourquoi nous choisir</div>
                <h2 class="section-title">Ce qui nous rend <span class="accent">différents</span></h2>
            </div>
            <div class="features-grid">
                <div class="feature-card reveal"><div class="feature-icon-wrap">⚡</div><div class="feature-title">Livraison Immédiate</div><p class="feature-desc">Recevez les identifiants en moins de 5 minutes après confirmation du paiement.</p></div>
                <div class="feature-card reveal"><div class="feature-icon-wrap">🔒</div><div class="feature-title">100% Sécurisé</div><p class="feature-desc">Comptes vérifiés manuellement sur Android et iOS avant publication.</p></div>
                <div class="feature-card reveal"><div class="feature-icon-wrap">👑</div><div class="feature-title">Qualité Premium</div><p class="feature-desc">Joueurs légendaires, coins disponibles et équipes déjà optimisées.</p></div>
                <div class="feature-card reveal"><div class="feature-icon-wrap">🛡️</div><div class="feature-title">Garantie 30 Jours</div><p class="feature-desc">Remboursement ou assistance en cas de problème avec un compte acheté.</p></div>
                <div class="feature-card reveal"><div class="feature-icon-wrap">💬</div><div class="feature-title">Support WhatsApp</div><p class="feature-desc">Assistance rapide 7j/7 en français et en wolof pour les clients sénégalais.</p></div>
                <div class="feature-card reveal"><div class="feature-icon-wrap">💳</div><div class="feature-title">Paiement Local</div><p class="feature-desc">Orange Money, Wave, Free Money et autres moyens locaux acceptés.</p></div>
            </div>
        </div>
    </section>

    <section class="testi-section" id="avis">
        <div class="section-inner">
            <div class="reveal">
                <div class="section-label">Avis clients</div>
                <h2 class="section-title">Ils font confiance à <span class="accent">Dribbleur Store</span></h2>
            </div>
            <div class="testi-grid">
                <div class="testi-card reveal"><div class="stars">★★★★★</div><p class="testi-text">"J'ai reçu mon compte en 3 minutes. Le compte PUISSANCE 3181 est encore mieux que décrit."</p><div class="testi-author"><div class="testi-avatar">M</div><div><div class="testi-name">Mohamed K.</div><div class="testi-date">Il y a 2 jours</div><div class="testi-location">Dakar, Sénégal</div></div></div></div>
                <div class="testi-card reveal"><div class="stars">★★★★★</div><p class="testi-text">"Meilleur site pour acheter des comptes eFootball au Sénégal. Le support WhatsApp répond très vite."</p><div class="testi-author"><div class="testi-avatar">A</div><div><div class="testi-name">Abdoulaye D.</div><div class="testi-date">Il y a 1 semaine</div><div class="testi-location">Saint-Louis, Sénégal</div></div></div></div>
                <div class="testi-card reveal"><div class="stars">★★★★★</div><p class="testi-text">"Super fiable. Le compte correspond exactement aux photos et la livraison est rapide."</p><div class="testi-author"><div class="testi-avatar">Y</div><div><div class="testi-name">Youssou B.</div><div class="testi-date">Il y a 3 jours</div><div class="testi-location">Thiès, Sénégal</div></div></div></div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content reveal">
            <h2>Prêt à <span class="accent">dominer</span> eFootball Mobile ?</h2>
            <p>Rejoins les joueurs sénégalais qui ont choisi Dribbleur Store. Ton compte premium Android/iOS t'attend.</p>
            <div class="cta-actions">
                <a href="<?= $isLogged ? 'list_articles.php' : 'inscription.php' ?>" class="btn-primary"><i class="fas fa-user-plus"></i> <?= $isLogged ? 'Voir tous les comptes' : "S'inscrire maintenant" ?></a>
                <a href="connexion.php" class="btn-secondary"><i class="fas fa-right-to-bracket"></i> Se connecter</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="index.php" class="nav-logo">
                    <div class="nav-logo-icon"><img src="<?= htmlspecialchars($siteLogo) ?>" alt="Dribbleur Store"></div>
                    <span class="nav-logo-text">Dribbleur Store</span>
                </a>
                <p>La référence sénégalaise pour acheter des comptes eFootball Mobile premium avec livraison rapide, sécurité garantie et support local.</p>
                <div class="footer-social">
                    <a href="https://wa.me/221775072936" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="mailto:diagneibeu10@gmail.com" title="Email"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Boutique</h4>
                <ul>
                    <li><a href="<?= $isLogged ? 'list_articles.php' : 'inscription.php' ?>">Tous les comptes</a></li>
                    <li><a href="inscription.php">Créer un compte</a></li>
                    <li><a href="connexion.php">Connexion client</a></li>
                    <li><a href="seller/inscription.php">Devenir vendeur</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Informations</h4>
                <ul>
                    <li><a href="conditions.php">Conditions générales</a></li>
                    <li><a href="conditions.php">Remboursement</a></li>
                    <li><a href="support.php">Guide de livraison</a></li>
                    <li><a href="support.php">FAQ Mobile</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><a href="support.php">Live Support</a></li>
                    <li><a href="https://wa.me/221775072936">WhatsApp 24/7</a></li>
                    <li><a href="mailto:diagneibeu10@gmail.com">Email</a></li>
                    <li><a href="#">Dakar, Sénégal</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2025 Dribbleur Store - Tous droits réservés.</span>
            <span style="color:rgba(0,255,136,.48);">Conçu à Dakar pour les gamers sénégalais</span>
        </div>
    </footer>

    <?php if ($isLogged): ?>
        <div class="modal" id="cartModal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeCart()">&times;</span>
                <h2>Votre Panier</h2>
                <div id="cartItems"></div>

                <div id="profileFormSection" style="display:none;margin-top:24px;padding-top:20px;border-top:1px solid rgba(0,212,255,.2);">
                    <div style="background:rgba(0,212,255,.08);padding:14px;border-radius:8px;margin-bottom:16px;font-size:13px;color:rgba(255,255,255,.8);">
                        Veuillez compléter vos informations personnelles avant de commander.
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label for="formPrenom" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Prénom</label>
                            <input type="text" id="formPrenom" placeholder="Votre prénom">
                        </div>
                        <div>
                            <label for="formNom" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Nom</label>
                            <input type="text" id="formNom" placeholder="Votre nom">
                        </div>
                    </div>

                    <label for="formEmail" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Email</label>
                    <input type="email" id="formEmail" placeholder="votre.email@exemple.com" style="margin-bottom:12px;">

                    <label for="formAdresse" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Adresse</label>
                    <input type="text" id="formAdresse" placeholder="Votre adresse" style="margin-bottom:12px;">

                    <label for="formVille" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Ville</label>
                    <input type="text" id="formVille" placeholder="Votre ville" style="margin-bottom:12px;">

                    <label for="formTelephone" style="display:block;color:rgba(255,255,255,.72);font-size:12px;margin-bottom:6px;">Téléphone</label>
                    <input type="tel" id="formTelephone" placeholder="77 507 29 36" maxlength="9" inputmode="numeric">
                </div>

                <label for="payerPhoneNumber" style="display:block;margin-top:16px;color:rgba(255,255,255,.9);font-size:14px;">Votre numéro WhatsApp (9 chiffres)</label>
                <input type="tel" id="payerPhoneNumber" placeholder="77 507 29 36" maxlength="9" inputmode="numeric" style="margin-top:8px;">
                <button class="btn-primary cta-button" style="width:100%;justify-content:center;margin-top:20px;border:none;cursor:pointer;" onclick="checkout()">
                    Confirmer ma commande
                </button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.documentElement.classList.add('js');

        function animateCounter(el, target, suffix, duration) {
            let start = 0;
            const step = target / (duration / 16);
            const timer = setInterval(() => {
                start += step;
                if (start >= target) {
                    start = target;
                    clearInterval(timer);
                }
                el.textContent = Math.round(start) + suffix;
            }, 16);
        }

        setTimeout(() => {
            animateCounter(document.getElementById('counter1'), 527, '+', 1800);
            animateCounter(document.getElementById('counter2'), 100, '%', 1400);
            animateCounter(document.getElementById('counter3'), 5, ' min', 1200);
        }, 500);

        const revealElements = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

            revealElements.forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    el.classList.add('visible');
                }
                observer.observe(el);
            });
        } else {
            revealElements.forEach(el => el.classList.add('visible'));
        }

        document.querySelectorAll('.fav-btn:not([data-article-id])').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.classList.toggle('active');
                const icon = btn.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
            });
        });

        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.filter-tab').forEach(item => item.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        const mobileToggle = document.getElementById('mobileToggle');
        const navLinks = document.getElementById('navLinks');
        const navAuth = document.getElementById('navAuth');

        if (mobileToggle && navLinks && navAuth) {
            mobileToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                navAuth.classList.toggle('active');
                const icon = mobileToggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                }
            });

            document.querySelectorAll('.nav-links a, .nav-auth a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    navAuth.classList.remove('active');
                    const icon = mobileToggle.querySelector('i');
                    if (icon) {
                        icon.classList.add('fa-bars');
                        icon.classList.remove('fa-times');
                    }
                });
            });
        }

        <?php if ($isLogged): ?>
        function toggleWishlist(articleId, button) {
            fetch('wishlist_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showNotification(data.message || 'Impossible de modifier les favoris.', 'warning', 'Favoris');
                        return;
                    }

                    if (button) {
                        button.classList.toggle('active', !!data.is_favorite);
                        button.classList.toggle('is-active', !!data.is_favorite);
                        const icon = button.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fas', !!data.is_favorite);
                            icon.classList.toggle('far', !data.is_favorite);
                        }
                    }

                    showNotification(data.message || 'Liste d envies mise a jour.', 'success', 'Favoris');
                })
                .catch(() => showNotification('Une erreur technique est survenue.', 'error', 'Favoris'));
        }

        const cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.addEventListener('click', event => {
                if (event.target === cartModal) closeCart();
            });
        }
        <?php endif; ?>
    </script>
    <?php if ($isLogged): ?>
        <script src="style/profile_form.js"></script>
        <script src="style/article_lightbox.js"></script>
        <script src="style/cart.js"></script>
    <?php endif; ?>
</body>
</html>
