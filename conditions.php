<?php
require __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Conditions, remboursement et livraison - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --deep-bg: #020811;
            --panel: rgba(0, 20, 40, 0.84);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            background:
                radial-gradient(circle at top left, rgba(0,255,136,0.12), transparent 22%),
                radial-gradient(circle at bottom right, rgba(0,207,255,0.14), transparent 26%),
                var(--deep-bg);
            padding: 22px;
        }
        .page {
            max-width: 980px;
            margin: 0 auto;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(20px, 3vw, 30px);
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid rgba(0, 207, 255, 0.22);
            background: rgba(0,20,40,0.7);
            color: #e8fbff;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .hero,
        .section {
            background: var(--panel);
            border: 1px solid rgba(0, 207, 255, 0.14);
            border-radius: 24px;
            backdrop-filter: blur(16px);
            box-shadow: 0 18px 50px rgba(0,0,0,0.24);
        }
        .hero {
            padding: 28px;
            margin-bottom: 18px;
        }
        .hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(24px, 4vw, 42px);
            line-height: 1.15;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }
        .hero p {
            color: rgba(255,255,255,0.72);
            max-width: 760px;
            line-height: 1.65;
            font-size: 16px;
        }
        .grid {
            display: grid;
            gap: 18px;
        }
        .section {
            padding: 24px;
        }
        .section h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 1.8px;
            margin-bottom: 14px;
            color: var(--neon-blue);
        }
        .section p,
        .section li {
            color: rgba(255,255,255,0.74);
            line-height: 1.65;
            font-size: 16px;
        }
        .section ul {
            list-style: none;
            display: grid;
            gap: 10px;
        }
        .section li {
            padding-left: 18px;
            position: relative;
        }
        .section li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--neon-green);
            box-shadow: 0 0 10px rgba(0,255,136,0.55);
        }
        .notice {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255,183,3,0.08);
            border: 1px solid rgba(255,183,3,0.2);
            color: #ffe4a1;
        }
        @media (max-width: 640px) {
            body { padding: 16px; }
            .hero, .section { padding: 18px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div class="brand">Dribbleur Store</div>
            <a class="btn" href="accueil.php">Retour au site</a>
        </div>

        <section class="hero">
            <h1>Conditions, remboursement et livraison</h1>
            <p>Cette page explique clairement comment se passent les achats, les livraisons et les demandes de support sur Dribbleur Store. L objectif est d etre transparent avec chaque client avant la commande.</p>
        </section>

        <div class="grid">
            <section class="section">
                <h2>Conditions generales</h2>
                <ul>
                    <li>Chaque article correspond a un compte ou a une offre numerique presentee avec ses caracteristiques visibles sur la fiche produit.</li>
                    <li>Le client doit verifier la plateforme, le prix, le statut du produit et les informations de livraison avant de commander.</li>
                    <li>Une commande enregistree peut faire passer un article en statut reserve afin d eviter une double vente.</li>
                    <li>Le support client peut demander des informations complementaires avant la livraison pour securiser la transaction.</li>
                </ul>
            </section>

            <section class="section">
                <h2>Livraison</h2>
                <ul>
                    <li>La livraison est generalement rapide et se fait selon le delai affiche sur la fiche produit.</li>
                    <li>En cas de forte demande ou de verification supplementaire, le delai peut etre allonge. Le client est alors informe par message.</li>
                    <li>Les informations de livraison sont transmises apres confirmation de la commande via le canal de contact convenu.</li>
                    <li>Le client doit fournir un numero WhatsApp valide pour faciliter la confirmation et l assistance.</li>
                </ul>
            </section>

            <section class="section">
                <h2>Remboursement</h2>
                <ul>
                    <li>Un remboursement peut etre etudie si le produit livre ne correspond pas a la fiche ou s il existe un probleme majeur confirme a la reception.</li>
                    <li>Aucune demande de remboursement ne sera acceptee en cas de mauvaise utilisation, de modification du compte par le client ou de non-respect des consignes apres livraison.</li>
                    <li>Le client doit signaler rapidement tout probleme au support avec des preuves claires pour permettre une verification serieuse.</li>
                    <li>Selon le cas, une correction, un remplacement ou un remboursement partiel ou complet pourra etre propose.</li>
                </ul>
                <div class="notice">Important: les produits numeriques exigent une verification rapide apres reception. Plus le signalement est tardif, plus il devient difficile de confirmer l origine du probleme.</div>
            </section>

            <section class="section">
                <h2>Support</h2>
                <ul>
                    <li>Le support reste disponible pour accompagner le client avant, pendant et apres la commande.</li>
                    <li>Pour toute demande, il faut preciser le numero de commande, le produit concerne et le probleme rencontre.</li>
                    <li>Le support peut orienter le client sur les bonnes pratiques pour conserver l acces et la securite du compte.</li>
                </ul>
            </section>
        </div>
    </div>
</body>
</html>
