<?php
require __DIR__ . '/../config.php';
require_seller();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$isSellerOnly = ($_SESSION['role'] ?? null) === 'seller';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel article - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --panel-strong: rgba(3, 14, 28, 0.95);
            --border: rgba(0, 207, 255, 0.16);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Rajdhani', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(0, 255, 136, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(0, 207, 255, 0.12), transparent 28%),
                var(--deep-bg);
            color: #fff;
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 207, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 207, 255, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.55;
            pointer-events: none;
            z-index: 0;
        }

        .layout {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
        }

        .admin-sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 2px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .admin-sidebar ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .admin-sidebar a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.72);
            background: transparent;
            border: 1px solid transparent;
            transition: 0.25s ease;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            color: #fff;
            border-color: rgba(0, 207, 255, 0.14);
            background: rgba(0, 207, 255, 0.08);
            box-shadow: inset 0 0 0 1px rgba(0, 207, 255, 0.06);
        }

        .content {
            padding: 32px;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(300px, 0.8fr);
            gap: 24px;
            align-items: stretch;
            margin-bottom: 28px;
        }

        .hero-card,
        .tips-card,
        .form-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            backdrop-filter: blur(18px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        .hero-card {
            padding: 30px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(0, 255, 136, 0.18);
            background: rgba(0, 255, 136, 0.08);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero-title {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(24px, 3vw, 38px);
            line-height: 1.15;
            letter-spacing: 2px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #fff, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-text {
            color: var(--text-soft);
            font-size: 17px;
            line-height: 1.6;
            max-width: 720px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .stat-box {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.42);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }

        .tips-card {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(160deg, rgba(0, 207, 255, 0.12), rgba(0, 20, 40, 0.92) 55%),
                var(--panel-strong);
        }

        .tips-card h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 1.8px;
            margin-bottom: 18px;
        }

        .tips-list {
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .tips-list li {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(0, 207, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-soft);
            line-height: 1.5;
        }

        .tips-list strong {
            display: block;
            color: #fff;
            margin-bottom: 4px;
        }

        .form-card {
            padding: 28px;
        }

        .form-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }

        .form-head h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            letter-spacing: 2px;
            margin-bottom: 6px;
        }

        .form-head p {
            color: var(--text-soft);
            font-size: 15px;
        }

        .status-pill {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.16);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .admin-form {
            display: grid;
            gap: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: grid;
            gap: 10px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.68);
        }

        .form-control,
        textarea.form-control,
        input[type="file"].form-control {
            width: 100%;
            border-radius: 16px;
            border: 1px solid rgba(0, 207, 255, 0.14);
            background: rgba(2, 8, 17, 0.8);
            color: #fff;
            font-size: 16px;
            padding: 15px 16px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        textarea.form-control {
            min-height: 180px;
            resize: vertical;
            line-height: 1.55;
        }

        .form-control:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 4px rgba(0, 207, 255, 0.12);
            transform: translateY(-1px);
        }

        .helper {
            color: rgba(255, 255, 255, 0.44);
            font-size: 14px;
            line-height: 1.45;
        }

        .upload-box {
            padding: 18px;
            border-radius: 18px;
            border: 1px dashed rgba(0, 207, 255, 0.26);
            background: rgba(255, 255, 255, 0.03);
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .preview-item {
            aspect-ratio: 1 / 1;
            border-radius: 16px;
            border: 1px solid rgba(0, 207, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.34);
            font-size: 13px;
            text-align: center;
            padding: 10px;
            position: relative;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-item.has-image {
            border-color: rgba(0, 255, 136, 0.24);
            background: transparent;
        }
        
        .preview-item.has-image::after {
            content: attr(data-index);
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 4px 12px rgba(0, 255, 136, 0.3);
            z-index: 2;
        }

        .remove-btn {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(255, 68, 102, 0.9);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            z-index: 3;
            transition: all 0.2s ease;
        }

        .remove-btn:hover {
            background: var(--neon-red);
            transform: scale(1.1);
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 8, 17, 0.92);
            backdrop-filter: blur(12px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            margin-bottom: 24px;
        }
        
        .modal-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            letter-spacing: 2px;
            margin-bottom: 8px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .modal-header p {
            color: var(--text-soft);
            font-size: 14px;
        }
        
        .modal-body {
            margin-bottom: 24px;
        }
        
        .confirmation-item {
            padding: 14px 16px;
            border-radius: 12px;
            background: rgba(0, 207, 255, 0.05);
            border: 1px solid rgba(0, 207, 255, 0.12);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .confirmation-item-icon {
            font-size: 18px;
            min-width: 24px;
        }
        
        .confirmation-item-text {
            flex: 1;
        }
        
        .confirmation-item-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
        }
        
        .confirmation-item-value {
            font-size: 14px;
            color: #fff;
            margin-top: 4px;
            font-weight: 500;
        }
        
        .modal-images-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }
        
        .modal-image-thumb {
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            border: 1px solid rgba(0, 207, 255, 0.2);
            overflow: hidden;
            position: relative;
        }
        
        .modal-image-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-image-thumb::after {
            content: attr(data-index);
            position: absolute;
            top: 4px;
            right: 4px;
            background: var(--neon-green);
            color: #001a0d;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 10px;
        }
        
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 20px;
            border-radius: 12px;
            border: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .modal-btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .modal-btn-confirm {
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            box-shadow: var(--glow-green);
            font-weight: 700;
        }
        
        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.6);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 8px;
        }

        .admin-btn-primary,
        .admin-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 50px;
            padding: 0 22px;
            border-radius: 14px;
            border: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.7px;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.25s ease, border-color 0.2s ease;
        }

        .admin-btn-primary {
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            box-shadow: var(--glow-green);
        }

        .admin-btn-secondary {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-btn-primary:hover,
        .admin-btn-secondary:hover {
            transform: translateY(-2px);
        }

        .admin-btn-secondary:hover {
            border-color: rgba(0, 207, 255, 0.24);
            box-shadow: var(--glow-blue);
        }

        .notification-badge {
            background-color: var(--neon-red);
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
            display: none;
        }

        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                border-right: none;
                border-bottom: 1px solid rgba(0, 207, 255, 0.12);
            }

            .hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .content {
                padding: 18px;
            }

            .hero-card,
            .tips-card,
            .form-card {
                border-radius: 20px;
            }

            .hero-card,
            .tips-card,
            .form-card {
                padding: 20px;
            }

            .hero-stats,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-head {
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .admin-btn-primary,
            .admin-btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="content">
            <section class="hero">
                <div class="hero-card">
                    <div class="eyebrow">Publication guidee</div>
                    <h1 class="hero-title">Ajoute un article avec une presentation plus propre et plus vendeuse.</h1>
                    <p class="hero-text">Cette page te permet de preparer un nouvel article pour la boutique admin avec un titre clair, une description lisible, un prix net et une galerie d images bien organisee.</p>

                    <div class="hero-stats">
                        <div class="stat-box">
                            <div class="stat-label">Visuel</div>
                            <div class="stat-value">3+ photos</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Prix</div>
                            <div class="stat-value">FCFA</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Publication</div>
                            <div class="stat-value">1 clic</div>
                        </div>
                    </div>
                </div>

                <aside class="tips-card">
                    <div>
                        <h2>Checklist rapide</h2>
                        <ul class="tips-list">
                            <li><strong>Titre clair</strong>Nom du compte, plateforme ou pack pour aider l utilisateur a comprendre tout de suite.</li>
                            <li><strong>Description utile</strong>Precise le contenu, la qualite du compte et les avantages principaux.</li>
                            <li><strong>Photos propres</strong>La premiere image devient la couverture principale dans la boutique.</li>
                        </ul>
                    </div>
                </aside>
            </section>

            <section class="form-card">
                <div class="form-head">
                    <div>
                        <h2>Nouvel article</h2>
                    <p>Remplis les champs ci-dessous pour <?= $isSellerOnly ? 'soumettre un article a valider' : 'publier un article visible dans la boutique' ?>.</p>
                    </div>
                    <div class="status-pill"><?= $isSellerOnly ? 'Validation admin' : 'Mode creation' ?></div>
                </div>

                <div id="confirmationModal" class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>✓ Confirmer la publication</h2>
                            <p>Vérifiez les informations avant de publier votre article</p>
                        </div>
                        <div class="modal-body">
                            <div class="confirmation-item">
                                <div class="confirmation-item-icon">📝</div>
                                <div class="confirmation-item-text">
                                    <div class="confirmation-item-label">Titre</div>
                                    <div class="confirmation-item-value" id="confirmTitle"></div>
                                </div>
                            </div>
                            <div class="confirmation-item">
                                <div class="confirmation-item-icon">💰</div>
                                <div class="confirmation-item-text">
                                    <div class="confirmation-item-label">Prix</div>
                                    <div class="confirmation-item-value" id="confirmPrice"></div>
                                </div>
                            </div>
                            <div class="confirmation-item">
                                <div class="confirmation-item-icon">📸</div>
                                <div class="confirmation-item-text">
                                    <div class="confirmation-item-label">Photos</div>
                                    <div class="confirmation-item-value" id="confirmImageCount"></div>
                                    <div class="modal-images-preview" id="confirmImages"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeConfirmation()">Annuler</button>
                            <button type="button" class="modal-btn modal-btn-confirm" onclick="submitForm()">Publier l'article</button>
                        </div>
                    </div>
                </div>

                <form action="../save_article.php" method="post" enctype="multipart/form-data" class="admin-form" id="articleForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Titre de l article</label>
                            <input class="form-control" type="text" id="title" name="title" required maxlength="255" placeholder="Ex: Compte eFootball Elite PS5">
                        </div>

                        <div class="form-group">
                            <label for="price">Prix en FCFA</label>
                            <input class="form-control" type="number" id="price" name="price" required step="1" min="0" placeholder="Ex: 5000">
                        </div>

                        <div class="form-group">
                            <label for="platform">Plateforme</label>
                            <input class="form-control" type="text" id="platform" name="platform" maxlength="50" value="Android/iOS" placeholder="Ex: Android, iOS, Android/iOS">
                        </div>

                        <div class="form-group">
                            <label for="product_status">Statut produit</label>
                            <select class="form-control" id="product_status" name="product_status">
                                <option value="available">Disponible</option>
                                <option value="reserved">Reserve</option>
                                <option value="sold">Vendu</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="delivery_time">Delai de livraison</label>
                            <input class="form-control" type="text" id="delivery_time" name="delivery_time" maxlength="100" value="Livraison en moins de 5 minutes" placeholder="Ex: Livraison en moins de 5 minutes">
                        </div>

                        <div class="form-group full">
                            <label for="binding_status">Statut de liaison</label>
                            <input class="form-control" type="text" id="binding_status" name="binding_status" maxlength="255" value="Lie a un email factice - Changeable" placeholder="Ex: Lie a un email factice - Changeable">
                        </div>

                        <div class="form-group full">
                            <label for="content">Description de l article</label>
                            <textarea class="form-control" id="content" name="content" rows="8" required placeholder="Decris le contenu du compte, ses points forts, les joueurs ou avantages inclus..."></textarea>
                            <p class="helper">Une description claire rassure le client et augmente la qualite de presentation du produit.</p>
                        </div>

                        <div class="form-group full">
                            <label for="images">Photos de l article (Max 6)</label>
                            <div class="upload-box">
                                <input class="form-control" type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="display: none;">
                                <button type="button" class="admin-btn-secondary" onclick="document.getElementById('images').click()" style="width: 100%; margin-bottom: 10px;">
                                    <span>📁 Sélectionner des photos</span>
                                </button>
                                <p class="helper">Ajoutez vos photos une par une ou plusieurs à la fois. L'ordre d'affichage respectera votre sélection.</p>
                                <div class="preview-grid" id="previewGrid">
                                    <div class="preview-item">Apercu des images</div>
                                </div>
                                <p class="helper" id="imageCount" style="margin-top: 8px; color: rgba(0, 255, 136, 0.7);"></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= $isSellerOnly ? '../seller/my_articles.php' : 'articles.php' ?>" class="admin-btn-secondary">Annuler</a>
                        <button type="button" class="admin-btn-primary" onclick="showConfirmation()"><?= $isSellerOnly ? 'Soumettre l article' : 'Publier l article' ?></button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
        const imagesInput = document.getElementById('images');
        const previewGrid = document.getElementById('previewGrid');
        const imageCountEl = document.getElementById('imageCount');
        const articleForm = document.getElementById('articleForm');
        const confirmationModal = document.getElementById('confirmationModal');
        
        // Stockage interne des fichiers pour permettre l'ajout successif
        let selectedFiles = [];
        const MAX_IMAGES = 6;

        const MAX_FILE_BYTES = 5 * 1024 * 1024;
        const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        imagesInput.addEventListener('change', function () {
            const newFiles = Array.from(imagesInput.files || []);
            const rejected = [];

            const validFiles = newFiles.filter(function (file) {
                if (!ALLOWED_TYPES.includes(file.type)) {
                    rejected.push(file.name + ' (format non autorise)');
                    return false;
                }
                if (file.size > MAX_FILE_BYTES) {
                    rejected.push(file.name + ' (plus de 5 Mo)');
                    return false;
                }
                return true;
            });

            if (rejected.length > 0) {
                alert('Fichiers ignores :\n- ' + rejected.join('\n- '));
            }

            if (validFiles.length === 0) {
                imagesInput.value = '';
                return;
            }

            if (selectedFiles.length + validFiles.length > MAX_IMAGES) {
                alert(`Vous ne pouvez pas ajouter plus de ${MAX_IMAGES} photos.`);
                imagesInput.value = '';
                return;
            }

            selectedFiles = [...selectedFiles, ...validFiles];
            updatePreview();
            
            // Réinitialiser l'input pour permettre de re-sélectionner le même fichier si besoin
            imagesInput.value = '';
        });

        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
        }

        function updatePreview() {
            previewGrid.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                previewGrid.innerHTML = '<div class="preview-item">Apercu des images</div>';
                imageCountEl.textContent = '';
                return;
            }

            imageCountEl.textContent = selectedFiles.length + ' image' + (selectedFiles.length !== 1 ? 's' : '') + ' sélectionnée' + (selectedFiles.length !== 1 ? 's' : '');
            imageCountEl.style.color = 'rgba(0, 255, 136, 0.7)';

            selectedFiles.forEach(function (file, index) {
                const item = document.createElement('div');
                item.className = 'preview-item has-image';
                item.setAttribute('data-index', (index + 1));

                // Bouton de suppression
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = (e) => {
                    e.stopPropagation();
                    removeImage(index);
                };
                item.appendChild(removeBtn);

                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.alt = 'Apercu image ' + (index + 1);
                    img.src = URL.createObjectURL(file);
                    item.appendChild(img);
                } else {
                    item.textContent = file.name;
                }

                previewGrid.appendChild(item);
            });
        }

        function showConfirmation() {
            const title = document.getElementById('title').value.trim();
            const price = document.getElementById('price').value.trim();

            if (!title) {
                alert('Veuillez entrer un titre');
                return;
            }
            if (!price) {
                alert('Veuillez entrer un prix');
                return;
            }
            if (selectedFiles.length === 0) {
                alert('Veuillez selectionner au moins une photo');
                return;
            }

            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmPrice').textContent = price + ' FCFA';
            document.getElementById('confirmImageCount').textContent = selectedFiles.length + ' photo' + (selectedFiles.length !== 1 ? 's' : '');

            const confirmImages = document.getElementById('confirmImages');
            confirmImages.innerHTML = '';
            selectedFiles.forEach(function (file, index) {
                const thumb = document.createElement('div');
                thumb.className = 'modal-image-thumb';
                thumb.setAttribute('data-index', (index + 1));

                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    thumb.appendChild(img);
                }

                confirmImages.appendChild(thumb);
            });

            confirmationModal.classList.add('active');
        }

        function closeConfirmation() {
            confirmationModal.classList.remove('active');
        }

        function submitForm() {
            if (selectedFiles.length === 0) {
                alert('Veuillez selectionner au moins une photo');
                return;
            }

            articleForm.querySelectorAll('input[data-dynamic-upload]').forEach(function (el) {
                el.remove();
            });
            imagesInput.removeAttribute('name');

            selectedFiles.forEach(function (file) {
                const input = document.createElement('input');
                input.type = 'file';
                input.name = 'images[]';
                input.hidden = true;
                input.setAttribute('data-dynamic-upload', '1');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                articleForm.appendChild(input);
            });

            articleForm.submit();
        }

        confirmationModal.addEventListener('click', function (e) {
            if (e.target === confirmationModal) {
                closeConfirmation();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && confirmationModal.classList.contains('active')) {
                closeConfirmation();
            }
        });
    </script>
</body>
</html>
