<?php
require __DIR__ . '/config.php';
$wa = 'https://wa.me/221775072936';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; font-family: 'Syne', sans-serif; background: #050810; color: #ddeeff; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .box { max-width: 520px; background: rgba(10,16,32,.95); border: 1px solid rgba(0,212,255,.2); border-radius: 16px; padding: 32px; text-align: center; }
        h1 { font-size: 22px; margin-bottom: 12px; color: #00ff88; }
        p { line-height: 1.7; color: rgba(255,255,255,.7); margin-bottom: 20px; }
        a { display: inline-block; margin: 6px; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; }
        .wa { background: #25d366; color: #fff; }
        .back { border: 1px solid rgba(0,212,255,.3); color: #00d4ff; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Support Dribbleur Store</h1>
        <p>Guide de livraison, FAQ et assistance : contactez-nous sur WhatsApp (reponse rapide, 7j/7).</p>
        <a class="wa" href="<?= htmlspecialchars($wa) ?>" target="_blank" rel="noopener">WhatsApp Support</a>
        <a class="back" href="index.php">Retour a l accueil</a>
        <a class="back" href="conditions.php">Conditions generales</a>
    </div>
</body>
</html>
