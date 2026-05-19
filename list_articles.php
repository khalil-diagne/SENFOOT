<?php
require __DIR__ . '/config.php';
ensure_store_schema();

$wishlistIds = [];
$currentUserId = current_user_id();
$currentUserProfile = $currentUserId ? current_user_profile() : null;
$profileMissingFields = $currentUserProfile ? customer_profile_missing_fields($currentUserProfile) : customer_profile_required_fields();
$isCheckoutProfileComplete = $currentUserId && $profileMissingFields === [];
if ($currentUserId) {
    $wishlistIds = fetch_wishlist_article_ids($currentUserId);
}

try {
    $pdo = db();
    $stmt = $pdo->query("SELECT id, title, slug, price, image, platform, delivery_time, binding_status, product_status, gallery_images, why_choose_us, content, author_username, created_at FROM articles WHERE (approval_status = 'approved' OR approval_status IS NULL OR author_username IS NULL) ORDER BY created_at DESC LIMIT 50");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('List articles error: ' . $e->getMessage());
    die('Une erreur serveur est survenue lors du chargement des articles.');
}

$platforms = array_values(array_unique(array_filter(array_map(static function ($row) {
    return trim((string) ($row['platform'] ?? ''));
}, $rows))));
natcasesort($platforms);
$platforms = array_values($platforms);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Dribbleur Store - Articles</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/article_lightbox.css">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --deep-bg: #020811;
            --card-bg: rgba(0, 20, 40, 0.8);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.5), 0 0 60px rgba(0, 255, 136, 0.15);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.5), 0 0 60px rgba(0, 207, 255, 0.15);
            --nav-h: 70px;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--deep-bg);
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 207, 255, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 207, 255, 0.045) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 10s linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes gridMove {
            from { background-position: 0 0; }
            to { background-position: 50px 50px; }
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.22;
            animation: orbFloat linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        .orb-1 {
            width: 450px;
            height: 450px;
            background: #00ff88;
            top: -130px;
            left: -130px;
            animation-duration: 16s;
        }

        .orb-2 {
            width: 350px;
            height: 350px;
            background: #00cfff;
            bottom: -80px;
            right: -80px;
            animation-duration: 12s;
        }

        .orb-3 {
            width: 280px;
            height: 280px;
            background: #8b5cf6;
            top: 40%;
            left: 58%;
            animation-duration: 20s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(35px, -25px) scale(1.06); }
            66% { transform: translate(-20px, 35px) scale(0.94); }
        }

        .scanlines {
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 0, 0, 0.025) 2px, rgba(0, 0, 0, 0.025) 4px);
            pointer-events: none;
            z-index: 1;
        }

        .particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            animation: particleFly linear infinite;
        }

        @keyframes particleFly {
            from { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            to { transform: translateY(-100px) translateX(var(--drift)); opacity: 0; }
        }

        nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            min-height: var(--nav-h);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: max(10px, var(--safe-top)) calc(max(18px, var(--safe-right))) 10px calc(max(18px, var(--safe-left)));
            background: rgba(2, 8, 17, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 207, 255, 0.15);
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }

        nav::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--neon-green), var(--neon-blue), transparent);
            animation: scanH 4s ease-in-out infinite;
        }

        @keyframes scanH {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        .nav-logo {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 17px;
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 18px;
            background: transparent;
            border: 1px solid rgba(0, 207, 255, 0.25);
            border-radius: 8px;
            color: #e0f7ff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px;
            letter-spacing: 1px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .nav-btn:hover {
            border-color: var(--neon-blue);
            box-shadow: var(--glow-blue);
            transform: translateY(-2px);
        }

        .cart-count {
            background: var(--neon-green);
            color: #001a0d;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
        }

        .page-wrap {
            position: relative;
            z-index: 2;
            padding: calc(var(--nav-h) + 50px) 30px 80px;
            max-width: 1280px;
            margin: 0 auto;
        }

        html {
            scroll-padding-top: calc(var(--nav-h) + 20px);
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: clamp(22px, 4vw, 40px);
            letter-spacing: 4px;
            text-align: center;
            margin-bottom: 12px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 12px rgba(0, 255, 136, 0.3));
        }

        .page-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.45);
            font-size: 15px;
            letter-spacing: 2px;
            margin-bottom: 38px;
        }

        .success-msg {
            text-align: center;
            padding: 12px 20px;
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.25);
            border-radius: 10px;
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1px;
            margin-bottom: 30px;
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1.8fr) repeat(3, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 18px;
            padding: 18px;
            background: rgba(0, 20, 40, 0.7);
            border: 1px solid rgba(0, 207, 255, 0.15);
            border-radius: 18px;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .toolbar-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toolbar-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.4px;
            color: rgba(255, 255, 255, 0.62);
            text-transform: uppercase;
        }

        .toolbar-input,
        .toolbar-select,
        .cart-phone-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(0, 207, 255, 0.22);
            background: rgba(2, 8, 17, 0.88);
            color: #e9fbff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
        }

        .toolbar-input::placeholder,
        .cart-phone-input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .toolbar-input:focus,
        .toolbar-select:focus,
        .cart-phone-input:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 3px rgba(0, 207, 255, 0.12);
            transform: translateY(-1px);
        }

        .results-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.72);
        }

        .results-count {
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .results-hint {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.42);
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 24px;
            margin-bottom: 28px;
        }

        .article-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 207, 255, 0.15);
            border-radius: 18px;
            overflow: hidden;
            backdrop-filter: blur(16px);
            transition: transform 0.35s, border-color 0.35s, box-shadow 0.35s;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(24px);
            animation: cardIn 0.6s ease forwards;
            position: relative;
        }

        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .article-card:hover {
            transform: translateY(-10px) scale(1.015);
            border-color: var(--neon-green);
            box-shadow: var(--glow-green);
        }

        .article-card:hover::before {
            opacity: 1;
        }

        @keyframes cardIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .article-img-wrap {
            overflow: hidden;
            position: relative;
        }

        .article-top-actions {
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

        .status-badge.status-available {
            color: var(--neon-green);
            background: rgba(0, 255, 136, 0.12);
            border-color: rgba(0, 255, 136, 0.24);
        }

        .status-badge.status-reserved {
            color: #ffb703;
            background: rgba(255, 183, 3, 0.14);
            border-color: rgba(255, 183, 3, 0.24);
        }

        .status-badge.status-sold {
            color: #ff5d73;
            background: rgba(255, 93, 115, 0.16);
            border-color: rgba(255, 93, 115, 0.28);
        }

        .favorite-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(2, 8, 17, 0.72);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .favorite-btn:hover {
            transform: scale(1.05);
            border-color: #ff5d73;
            box-shadow: 0 0 18px rgba(255, 93, 115, 0.28);
        }

        .favorite-btn.is-active {
            color: #ff5d73;
            border-color: rgba(255, 93, 115, 0.46);
            background: rgba(255, 93, 115, 0.14);
        }

        .article-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .article-card:hover .article-image {
            transform: scale(1.04);
        }

        .no-img-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, rgba(0, 207, 255, 0.08), rgba(0, 255, 136, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            opacity: 0.4;
        }

        .article-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 8px;
        }

        .article-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 1.5px;
            line-height: 1.5;
            color: #e8f4ff;
        }

        .article-meta {
            font-size: 12px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.38);
        }

        .article-stock-copy {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.72);
        }

        .article-price {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 20px;
            color: var(--neon-green);
            text-shadow: 0 0 12px rgba(0, 255, 136, 0.4);
            margin-top: 4px;
        }

        .btn-buy,
        .cta-button {
            position: relative;
            overflow: hidden;
            border: none;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
        }

        .btn-buy {
            margin-top: auto;
            padding: 12px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 2px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.25);
        }

        .btn-buy::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 40%;
            height: 200%;
            background: rgba(255, 255, 255, 0.25);
            transform: skewX(-20deg);
            animation: btnShine 3s ease-in-out infinite;
        }

        @keyframes btnShine {
            0% { left: -60%; opacity: 0; }
            20% { opacity: 1; }
            50% { left: 130%; opacity: 0; }
            100% { left: 130%; opacity: 0; }
        }

        .btn-buy:hover,
        .cta-button:hover {
            transform: translateY(-3px);
        }

        .btn-buy:hover {
            box-shadow: var(--glow-green);
        }

        .btn-buy.is-disabled {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.44);
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-buy:active,
        .cta-button:active {
            transform: scale(0.97);
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 56px 24px;
            border: 1px dashed rgba(0, 207, 255, 0.22);
            border-radius: 18px;
            color: rgba(255, 255, 255, 0.6);
            background: rgba(0, 20, 40, 0.45);
            margin-bottom: 40px;
        }

        .no-results.open {
            display: block;
        }

        .no-results strong {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 15px;
            letter-spacing: 1.4px;
            color: var(--neon-blue);
            margin-bottom: 10px;
        }

        .back-wrap {
            text-align: center;
            margin-top: 40px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 36px;
            background: transparent;
            border: 1px solid rgba(0, 207, 255, 0.25);
            border-radius: 50px;
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 2px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-back:hover {
            border-color: var(--neon-blue);
            box-shadow: var(--glow-blue);
            transform: translateY(-3px);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.35);
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 2px;
        }

        .empty-state span {
            font-size: 48px;
            display: block;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 8, 17, 0.88);
            backdrop-filter: blur(10px);
            z-index: 200;
            justify-content: center;
            align-items: center;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background: rgba(0, 20, 40, 0.96);
            border: 1px solid rgba(0, 207, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7), var(--glow-blue);
            animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.88) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-content::before,
        .modal-content::after {
            content: '';
            position: absolute;
            width: 35px;
            height: 35px;
            border-color: var(--neon-green);
            border-style: solid;
        }

        .modal-content::before {
            top: -1px;
            left: -1px;
            border-width: 2px 0 0 2px;
            border-radius: 20px 0 0 0;
        }

        .modal-content::after {
            bottom: -1px;
            right: -1px;
            border-width: 0 2px 2px 0;
            border-radius: 0 0 20px 0;
        }

        .modal-content h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            letter-spacing: 2px;
            color: var(--neon-blue);
            margin-bottom: 24px;
        }

        .close-modal {
            position: absolute;
            top: 16px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.5);
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: #ff4466;
        }

        .cart-label {
            display: block;
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .cart-item-title {
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .cart-item-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            color: var(--neon-green);
            font-weight: 700;
            white-space: nowrap;
        }

        .btn-remove {
            padding: 5px 10px;
            border-radius: 6px;
            background: rgba(255, 70, 100, 0.15);
            color: #ff4466;
            border: 1px solid rgba(255, 70, 100, 0.3);
            font-size: 11px;
            cursor: pointer;
            margin-top: 6px;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #ff4466;
            color: #fff;
        }

        .cta-button {
            width: 100%;
            margin-top: 24px;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 2px;
            border-radius: 50px;
            box-shadow: 0 6px 28px rgba(0, 255, 136, 0.3);
        }

        .cta-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .notif {
            position: fixed;
            right: 20px;
            top: 20px;
            min-width: 260px;
            max-width: min(92vw, 420px);
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(0, 255, 136, 0.25);
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.18), rgba(0, 207, 255, 0.16));
            color: #f1ffff;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(14px);
            z-index: 3000;
            animation: notifIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .notif[data-type="warning"] {
            border-color: rgba(255, 186, 0, 0.4);
            background: linear-gradient(135deg, rgba(255, 186, 0, 0.18), rgba(255, 120, 0, 0.15));
        }

        .notif[data-type="error"] {
            border-color: rgba(255, 80, 110, 0.45);
            background: linear-gradient(135deg, rgba(255, 70, 100, 0.2), rgba(255, 120, 120, 0.15));
        }

        .notif-title {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .notif-text {
            display: block;
            line-height: 1.45;
            font-size: 13px;
        }

        @keyframes notifIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            nav {
                padding-right: calc(max(18px, var(--safe-right)));
                padding-left: calc(max(18px, var(--safe-left)));
            }

            .page-wrap {
                padding: calc(var(--nav-h) + 36px) 18px 64px;
            }

            .toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            nav {
                height: auto;
                min-height: var(--nav-h);
                padding: calc(max(14px, var(--safe-top))) calc(max(16px, var(--safe-right))) 14px calc(max(16px, var(--safe-left)));
                flex-wrap: wrap;
            }

            .nav-logo {
                font-size: 15px;
                letter-spacing: 1.5px;
            }

            .nav-btn {
                width: 100%;
            }

            .page-wrap {
                padding-top: 124px;
            }

            .page-subtitle {
                margin-bottom: 28px;
                font-size: 13px;
                letter-spacing: 1.4px;
            }

            .toolbar {
                grid-template-columns: 1fr;
                padding: 14px;
                gap: 12px;
            }

            .results-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .articles-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .article-image,
            .no-img-placeholder {
                height: 210px;
            }

            .modal {
                align-items: flex-end;
            }

            .modal-content {
                width: 100%;
                max-width: none;
                max-height: 85vh;
                overflow-y: auto;
                border-radius: 22px 22px 0 0;
                padding: 24px 18px 20px;
            }

            .cta-button {
                padding: 15px 18px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            nav {
                padding-right: calc(max(12px, var(--safe-right)));
                padding-left: calc(max(12px, var(--safe-left)));
            }

            .nav-logo {
                font-size: 14px;
                letter-spacing: 1.2px;
            }

            .page-wrap {
                padding-top: calc(var(--nav-h) + 90px);
                padding-left: 14px;
                padding-right: 14px;
            }

            nav {
                justify-content: center;
                gap: 10px;
            }

            .page-title {
                letter-spacing: 2px;
            }

            .article-body {
                padding: 16px;
            }

            .article-title {
                font-size: 12px;
                letter-spacing: 1px;
            }

            .article-price {
                font-size: 18px;
            }

            .notif {
                left: 14px;
                right: 14px;
                top: auto;
                bottom: 14px;
                min-width: 0;
            }
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

    <nav>
        <a href="accueil.php" class="nav-logo">Dribbleur Store</a>
        <a class="nav-btn" href="wishlist.php">Favoris</a>
        <a class="nav-btn" href="conditions.php">Conditions</a>
        <button class="nav-btn" type="button" onclick="openCart()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L4 2H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Panier
            <span class="cart-count" id="cartCount">0</span>
        </button>
    </nav>

    <div class="page-wrap">
        <h2 class="page-title">Nos Articles</h2>
        <p class="page-subtitle">Selectionne ton compte eFootball premium</p>

        <?php if (isset($_GET['created'])): ?>
            <div class="success-msg">Article cree avec succes.</div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <span>📦</span>
                Aucun article publie pour le moment.
            </div>
        <?php else: ?>
            <section class="toolbar" aria-label="Recherche et filtres">
                <div class="toolbar-field">
                    <label class="toolbar-label" for="searchInput">Recherche</label>
                    <input class="toolbar-input" id="searchInput" type="search" placeholder="Rechercher un article par titre">
                </div>
                <div class="toolbar-field">
                    <label class="toolbar-label" for="platformFilter">Plateforme</label>
                    <select class="toolbar-select" id="platformFilter">
                        <option value="">Toutes les plateformes</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?= htmlspecialchars($platform) ?>"><?= htmlspecialchars($platform) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="toolbar-field">
                    <label class="toolbar-label" for="priceFilter">Prix</label>
                    <select class="toolbar-select" id="priceFilter">
                        <option value="">Tous les prix</option>
                        <option value="0-10000">Moins de 10 000 FCFA</option>
                        <option value="10000-30000">10 000 a 30 000 FCFA</option>
                        <option value="30000-999999999">Plus de 30 000 FCFA</option>
                    </select>
                </div>
                <div class="toolbar-field">
                    <label class="toolbar-label" for="statusFilter">Statut</label>
                    <select class="toolbar-select" id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="available">Disponible</option>
                        <option value="reserved">Reserve</option>
                        <option value="sold">Vendu</option>
                    </select>
                </div>
                <div class="toolbar-field">
                    <label class="toolbar-label" for="sortSelect">Trier par</label>
                    <select class="toolbar-select" id="sortSelect">
                        <option value="recent">Plus recents</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix decroissant</option>
                        <option value="title-asc">Titre A-Z</option>
                    </select>
                </div>
            </section>

            <div class="results-bar">
                <div class="results-count" id="resultsCount"></div>
                <div class="results-hint">Filtre par titre, prix, plateforme et statut sans recharger la page.</div>
            </div>

            <div class="articles-grid" id="articlesGrid">
                <?php foreach ($rows as $i => $r): ?>
                    <?php $statusMeta = article_status_meta($r['product_status'] ?? null); ?>
                    <article
                        class="article-card"
                        data-title="<?= htmlspecialchars(mb_strtolower((string) $r['title'])) ?>"
                        data-platform="<?= htmlspecialchars(mb_strtolower((string) ($r['platform'] ?? ''))) ?>"
                        data-price="<?= htmlspecialchars((string) ((float) ($r['price'] ?? 0))) ?>"
                        data-status="<?= htmlspecialchars($statusMeta['value']) ?>"
                        data-created="<?= htmlspecialchars((string) strtotime((string) ($r['created_at'] ?? 'now'))) ?>"
                        style="animation-delay:<?= $i * 0.07 ?>s; cursor: pointer;"
                        onclick="articleLightbox.open(<?= htmlspecialchars(json_encode($r['id'])) ?>)">
                        <div class="article-img-wrap" style="cursor: pointer;">
                            <div class="article-top-actions">
                                <span class="status-badge <?= htmlspecialchars($statusMeta['class']) ?>"><?= htmlspecialchars($statusMeta['label']) ?></span>
                                <button class="favorite-btn<?= in_array((int) $r['id'], $wishlistIds, true) ? ' is-active' : '' ?>" type="button" data-article-id="<?= (int) $r['id'] ?>" onclick="event.stopPropagation(); toggleWishlist(<?= (int) $r['id'] ?>, this)" aria-label="Ajouter aux favoris">♥</button>
                            </div>
                            <?php if (!empty($r['image'])): ?>
                                <img class="article-image" src="uploads/articles/<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
                            <?php else: ?>
                                <div class="no-img-placeholder">⚽</div>
                            <?php endif; ?>
                        </div>
                        <div class="article-body">
                            <h3 class="article-title"><?= htmlspecialchars($r['title']) ?></h3>
                            <div class="article-meta">Publie par BEST DRIBBLEUR SN</div>
                            <div class="article-stock-copy">Statut actuel: <?= htmlspecialchars($statusMeta['label']) ?></div>
                            <div class="article-price"><?= htmlspecialchars(number_format((float) $r['price'], 0, ',', ' ')) ?> FCFA</div>
                            <button class="btn-buy<?= $statusMeta['value'] !== 'available' ? ' is-disabled' : '' ?>" type="button" onclick="event.stopPropagation(); articleLightbox.open(<?= htmlspecialchars(json_encode($r['id'])) ?>)">
                                <?= $statusMeta['value'] === 'available' ? 'Acheter maintenant' : 'Voir le detail' ?>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="no-results" id="noResults">
                <strong>Aucun article ne correspond aux filtres</strong>
                Essaie une autre plateforme, un autre prix ou efface la recherche.
            </div>
        <?php endif; ?>

        <div class="back-wrap">
            <a href="accueil.php" class="btn-back">Retour a l accueil</a>
        </div>
    </div>

    <div class="modal" id="cartModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeCart()">&times;</span>
            <h2>Votre Panier</h2>
            <div id="cartItems"></div>
            <?php if ($isCheckoutProfileComplete): ?>
                <label class="cart-label" for="payerPhoneNumber">Votre numero WhatsApp (9 chiffres, ex: 775072936)</label>
                <input class="cart-phone-input" type="tel" id="payerPhoneNumber" placeholder="77 507 29 36" maxlength="12" inputmode="numeric">
                <button class="cta-button" type="button" onclick="checkout()">Enregistrer la commande et contacter par WhatsApp</button>
            <?php else: ?>
                <div class="cart-label" style="margin-top:18px;line-height:1.6;">
                    Completez d abord votre profil client avant toute commande.
                    Champs manquants : <?= htmlspecialchars(implode(', ', array_values($profileMissingFields))) ?>
                </div>
                <a class="cta-button" href="profile_edit.php?checkout_required=1" style="display:inline-flex;justify-content:center;align-items:center;text-decoration:none;margin-top:16px;">Completer mes informations personnelles</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('particles');
            for (let i = 0; i < 45; i += 1) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                const green = Math.random() > 0.5;
                particle.style.cssText = `left:${Math.random() * 100}%;animation-duration:${5 + Math.random() * 10}s;animation-delay:${Math.random() * 10}s;--drift:${(Math.random() - 0.5) * 120}px;background:${green ? '#00ff88' : '#00cfff'};box-shadow:0 0 6px ${green ? '#00ff88' : '#00cfff'};width:${1 + Math.random() * 2}px;height:${1 + Math.random() * 2}px;`;
                container.appendChild(particle);
            }
        })();

        let cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');

        function syncCart() {
            cart = JSON.parse(localStorage.getItem('efootball_cart') || '[]');
            return cart;
        }

        function updateCart() {
            localStorage.setItem('efootball_cart', JSON.stringify(cart));
            const count = document.getElementById('cartCount');
            if (count) {
                count.textContent = cart.length;
            }
        }

        function notify(message, type = 'success', title = 'Information') {
            const notification = document.createElement('div');
            notification.className = 'notif';
            notification.dataset.type = type;
            notification.innerHTML = '<span class="notif-title"></span><span class="notif-text"></span>';
            notification.querySelector('.notif-title').textContent = title;
            notification.querySelector('.notif-text').textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.transition = 'opacity 0.35s';
                notification.style.opacity = '0';
            }, 2400);
            setTimeout(() => notification.remove(), 2800);
        }

        function showNotification(message) {
            notify(message, 'success', 'Information');
        }

        function toggleWishlist(articleId, button) {
            fetch('wishlist_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        notify(data.message || 'Impossible de modifier les favoris.', 'warning', 'Favoris');
                        return;
                    }

                    if (button) {
                        button.classList.toggle('is-active', !!data.is_favorite);
                    }

                    notify(data.message || 'Liste d envies mise a jour.', 'success', 'Favoris');
                })
                .catch(() => {
                    notify('Une erreur technique est survenue.', 'error', 'Favoris');
                });
        }

        function formatPrice(price) {
            return Number(price || 0).toLocaleString('fr-FR') + ' FCFA';
        }

        function openCart() {
            syncCart();
            const modal = document.getElementById('cartModal');
            const cartItems = document.getElementById('cartItems');
            if (!modal || !cartItems) {
                return;
            }

            if (cart.length === 0) {
                cartItems.innerHTML = '<p style="text-align:center;padding:40px;color:rgba(255,255,255,0.35);font-family:Orbitron,sans-serif;font-size:12px;letter-spacing:2px;">Votre panier est vide</p>';
            } else {
                cartItems.innerHTML = cart.map((item, index) => `
                    <div class="cart-item">
                        <div>
                            <div class="cart-item-title">${item.title}</div>
                            <button class="btn-remove" type="button" onclick="removeFromCart(${index})">Supprimer</button>
                        </div>
                        <div class="cart-item-price">${formatPrice(item.price)}</div>
                    </div>
                `).join('');
            }

            modal.classList.add('open');
        }

        function closeCart() {
            document.getElementById('cartModal').classList.remove('open');
        }

        function removeFromCart(index) {
            syncCart();
            cart.splice(index, 1);
            updateCart();
            openCart();
            notify('L article a ete retire du panier.', 'warning', 'Panier mis a jour');
        }

        function checkout() {
            syncCart();
            if (cart.length === 0) {
                notify('Ajoute au moins un article avant de commander.', 'warning', 'Panier vide');
                return;
            }

            const rawPhone = (document.getElementById('payerPhoneNumber').value || '').replace(/\s/g, '');
            if (!/^\d{9}$/.test(rawPhone)) {
                notify('Entre un numero WhatsApp senegalais valide sur 9 chiffres. Exemple: 775072936.', 'warning', 'Numero invalide');
                return;
            }

            const button = document.querySelector('#cartModal .cta-button');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Enregistrement...';

            const payload = [...cart, { payerPhoneNumber: rawPhone }];
            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cart = [];
                        updateCart();
                        closeCart();
                        if (data.whatsappUrl) {
                            window.open(data.whatsappUrl, '_blank');
                        }
                        notify(data.message || 'Commande enregistree. Consulte WhatsApp pour confirmer.', 'success', 'Commande envoyee');
                        setTimeout(() => {
                            window.location.href = 'order_history.php';
                        }, 1200);
                        return;
                    }

                    if (data.redirect_url) {
                        notify(data.message || 'Complete ton profil avant de commander.', 'warning', 'Profil incomplet');
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                        return;
                    }

                    notify(data.message || 'Une erreur est survenue pendant l enregistrement de la commande.', 'error', 'Commande echouee');
                    button.disabled = false;
                    button.textContent = originalText;
                })
                .catch(error => {
                    console.error(error);
                    notify('Une erreur technique est survenue. Reessaie dans un instant.', 'error', 'Erreur technique');
                    button.disabled = false;
                    button.textContent = originalText;
                });
        }

        function applyFilters() {
            const search = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
            const platform = (document.getElementById('platformFilter')?.value || '').trim().toLowerCase();
            const priceRange = document.getElementById('priceFilter')?.value || '';
            const status = document.getElementById('statusFilter')?.value || '';
            const sort = document.getElementById('sortSelect')?.value || 'recent';
            const grid = document.getElementById('articlesGrid');
            if (!grid) {
                return;
            }

            const cards = Array.from(grid.querySelectorAll('.article-card'));
            const [minPrice, maxPrice] = priceRange ? priceRange.split('-').map(Number) : [null, null];

            const visibleCards = cards.filter(card => {
                const title = card.dataset.title || '';
                const cardPlatform = card.dataset.platform || '';
                const price = Number(card.dataset.price || 0);
                const cardStatus = card.dataset.status || '';
                const matchesSearch = !search || title.includes(search);
                const matchesPlatform = !platform || cardPlatform === platform;
                const matchesPrice = !priceRange || (price >= minPrice && price <= maxPrice);
                const matchesStatus = !status || cardStatus === status;
                const visible = matchesSearch && matchesPlatform && matchesPrice && matchesStatus;
                card.style.display = visible ? '' : 'none';
                return visible;
            });

            visibleCards
                .sort((a, b) => {
                    if (sort === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
                    if (sort === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
                    if (sort === 'title-asc') return (a.dataset.title || '').localeCompare(b.dataset.title || '', 'fr');
                    return Number(b.dataset.created) - Number(a.dataset.created);
                })
                .forEach(card => grid.appendChild(card));

            const resultsCount = document.getElementById('resultsCount');
            const noResults = document.getElementById('noResults');
            if (resultsCount) {
                resultsCount.textContent = `${visibleCards.length} article(s) affiches`;
            }
            if (noResults) {
                noResults.classList.toggle('open', visibleCards.length === 0);
            }
        }

        document.getElementById('cartModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeCart();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            updateCart();
            ['searchInput', 'platformFilter', 'priceFilter', 'statusFilter', 'sortSelect'].forEach(id => {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }
                element.addEventListener(id === 'searchInput' ? 'input' : 'change', applyFilters);
            });
            applyFilters();
        });
    </script>
    <script src="style/article_lightbox.js"></script>
</body>
</html>
