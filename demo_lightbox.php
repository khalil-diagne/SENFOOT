<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Article Lightbox</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --deep-bg: #020811;
            --card-bg: rgba(0, 20, 40, 0.8);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            background: var(--deep-bg);
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 207, 255, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 207, 255, 0.045) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            letter-spacing: 2px;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 40px;
            font-size: 14px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .demo-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 207, 255, 0.15);
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .demo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 207, 255, 0.1), transparent);
            transition: left 0.5s;
            z-index: 1;
        }

        .demo-card:hover::before {
            left: 100%;
        }

        .demo-card:hover {
            transform: translateY(-8px);
            border-color: var(--neon-green);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.5);
        }

        .demo-img {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(0, 207, 255, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            overflow: hidden;
        }

        .demo-body {
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .demo-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 1px;
            color: var(--neon-blue);
            margin-bottom: 8px;
        }

        .demo-price {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: var(--neon-green);
            margin-bottom: 12px;
        }

        .demo-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .btn-demo {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-demo:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 20px rgba(0, 255, 136, 0.3);
        }

        .info-box {
            background: rgba(0, 207, 255, 0.1);
            border: 1px solid rgba(0, 207, 255, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-box h3 {
            color: var(--neon-green);
            margin-bottom: 10px;
            font-size: 16px;
        }

        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
            color: #ffdd00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .feature-list {
            list-style: none;
            padding-left: 0;
        }

        .feature-list li {
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }

        .feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--neon-green);
            font-weight: 900;
        }
    </style>
    <link rel="stylesheet" href="style/article_lightbox.css">
</head>
<body>
    <div class="bg-grid"></div>

    <div class="container">
        <h1>🎮 Démo - Article Lightbox</h1>
        <p class="subtitle">Cliquez sur une carte pour voir la lightbox en action</p>

        <div class="info-box">
            <h3>ℹ️ À propos de cette démo</h3>
            <p>Cette page démontre la fonctionnalité de lightbox article. Cliquez sur les cartes ci-dessous pour voir la modal s'ouvrir avec les détails complets d'un article.</p>
            <p style="margin-top: 10px;">Note: Les images de la galerie sont des placeholders. En production, utilisez vos vraies images stockées dans <code>/uploads/articles/</code></p>
        </div>

        <h2 style="font-family: 'Orbitron'; font-size: 18px; letter-spacing: 1px; margin-bottom: 20px; color: var(--neon-blue);">
            Articles eFootball
        </h2>

        <div class="demo-grid">
            <!-- Article 1 -->
            <div class="demo-card" onclick="articleLightbox.open(1)">
                <div class="demo-img">⚽</div>
                <div class="demo-body">
                    <div class="demo-title">PUISSANCE 3181</div>
                    <div class="demo-price">29 999 FCFA</div>
                    <p class="demo-desc">Compte elite avec joueurs légendaires et coins suffisants.</p>
                    <button class="btn-demo" onclick="event.stopPropagation(); articleLightbox.open(1)">Voir détails</button>
                </div>
            </div>

            <!-- Article 2 -->
            <div class="demo-card" onclick="articleLightbox.open(2)">
                <div class="demo-img">⚽</div>
                <div class="demo-body">
                    <div class="demo-title">PUISSANCE 2850</div>
                    <div class="demo-price">24 999 FCFA</div>
                    <p class="demo-desc">Compte intermédiaire parfait pour progresser rapidement.</p>
                    <button class="btn-demo" onclick="event.stopPropagation(); articleLightbox.open(2)">Voir détails</button>
                </div>
            </div>

            <!-- Article 3 -->
            <div class="demo-card" onclick="articleLightbox.open(3)">
                <div class="demo-img">⚽</div>
                <div class="demo-body">
                    <div class="demo-title">PUISSANCE 2500</div>
                    <div class="demo-price">19 999 FCFA</div>
                    <p class="demo-desc">Compte pour débuter avec une bonne équipe de base.</p>
                    <button class="btn-demo" onclick="event.stopPropagation(); articleLightbox.open(3)">Voir détails</button>
                </div>
            </div>

            <!-- Article 4 -->
            <div class="demo-card" onclick="articleLightbox.open(4)">
                <div class="demo-img">⚽</div>
                <div class="demo-body">
                    <div class="demo-title">PUISSANCE 3500</div>
                    <div class="demo-price">34 999 FCFA</div>
                    <p class="demo-desc">Compte ultimate avec équipe de rêve garantie.</p>
                    <button class="btn-demo" onclick="event.stopPropagation(); articleLightbox.open(4)">Voir détails</button>
                </div>
            </div>
        </div>

        <div class="info-box">
            <h3>✨ Caractéristiques principales</h3>
            <ul class="feature-list">
                <li>Galerie photo défilante (5-6 images)</li>
                <li>Navigation avec boutons ← →</li>
                <li>Indicateur de position (ex: 2/5)</li>
                <li>Détails complets: titre, prix, plateforme, délai, binding</li>
                <li>Section "Pourquoi nous choisir" avec 3 points clés</li>
                <li>Animations fluides (fade-in + scale)</li>
                <li>Entièrement responsive (mobile + desktop)</li>
                <li>Gestion clavier (Escape, flèches)</li>
                <li>Ajout au panier directement</li>
                <li>Design cohérent avec le site (dark mode, vert cyan)</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>⌨️ Contrôles clavier</h3>
            <ul class="feature-list">
                <li><strong>Escape</strong> - Fermer la modal</li>
                <li><strong>Flèche Gauche</strong> - Image précédente</li>
                <li><strong>Flèche Droite</strong> - Image suivante</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>📚 Documentation</h3>
            <p>Consultez le fichier <code>LIGHTBOX_DOCUMENTATION.md</code> pour:</p>
            <ul class="feature-list">
                <li>Intégration dans vos pages</li>
                <li>Structure de la base de données</li>
                <li>Format des données JSON</li>
                <li>API JavaScript</li>
                <li>Exemples de code</li>
                <li>Troubleshooting</li>
            </ul>
        </div>
    </div>

    <script src="style/article_lightbox.js"></script>

    <script>
        // Mock articles pour la démo
        // En production, ces données viendront de l'API article_api.php
        
        // Override la méthode open pour utiliser des données locales en démo
        const originalOpen = articleLightbox.open.bind(articleLightbox);
        
        articleLightbox.open = function(articleId) {
            // Données de démonstration
            const demoArticles = {
                1: {
                    id: 1,
                    title: 'PUISSANCE 3181',
                    price: 29999,
                    platform: 'Android/iOS',
                    delivery_time: 'Livraison en moins de 5 minutes',
                    binding_status: 'Lié à un email factice - Changeable',
                    content: 'Compte eFootball de haut niveau avec 3181 de puissance. Équipe complète, joueurs légendaires inclus et de nombreuses pièces pour upgrader votre équipe.',
                    image: 'placeholder.jpg',
                    gallery_images: ['placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg'],
                    why_choose_us: ['Joueurs légendaires inclus', 'Coins suffisants pour upgrader', 'Équipe complète et optimisée']
                },
                2: {
                    id: 2,
                    title: 'PUISSANCE 2850',
                    price: 24999,
                    platform: 'Android/iOS',
                    delivery_time: 'Livraison en moins de 5 minutes',
                    binding_status: 'Lié à un email factice - Changeable',
                    content: 'Compte eFootball avec 2850 de puissance. Parfait pour les joueurs intermédiaires qui veulent une équipe solide sans casser la tirelire.',
                    image: 'placeholder.jpg',
                    gallery_images: ['placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg'],
                    why_choose_us: ['Support 24/7 réactif', 'Garantie 30 jours complète', 'Livraison instantanée garantie']
                },
                3: {
                    id: 3,
                    title: 'PUISSANCE 2500',
                    price: 19999,
                    platform: 'Android',
                    delivery_time: 'Livraison en moins de 5 minutes',
                    binding_status: 'Lié à un email factice - Changeable',
                    content: 'Compte eFootball parfait pour débuter avec une bonne équipe de base. Idéal pour les nouveaux joueurs qui veulent commencer fort.',
                    image: 'placeholder.jpg',
                    gallery_images: ['placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg'],
                    why_choose_us: ['100% sécurisé et certifié', 'Comptes vérifiés deux fois', 'Meilleur prix du marché']
                },
                4: {
                    id: 4,
                    title: 'PUISSANCE 3500',
                    price: 34999,
                    platform: 'iOS',
                    delivery_time: 'Livraison en moins de 5 minutes',
                    binding_status: 'Lié à un email factice - Changeable',
                    content: 'Compte eFootball elite avec 3500 de puissance. Les meilleures cartes et coins pour dominer le terrain dès le premier match.',
                    image: 'placeholder.jpg',
                    gallery_images: ['placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg', 'placeholder.jpg'],
                    why_choose_us: ['Équipe de rêve complète', 'Tous les joueurs top disponibles', 'Victoires garanties dès le départ']
                }
            };

            // Utiliser les données locales si disponibles, sinon utiliser l'API
            const article = demoArticles[articleId];
            
            if (article) {
                this.isLoading = true;
                this.currentArticleId = articleId;
                this.currentGalleryIndex = 0;
                this.modal.classList.add('open');
                this.displayArticle(article);
                this.isLoading = false;
            } else {
                originalOpen(articleId);
            }
        };
    </script>
</body>
</html>
