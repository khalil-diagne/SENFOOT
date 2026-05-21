<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_admin();

$pdo = db();

// Traitement des actions (Approuver / Refuser)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE visiteur SET role = 'seller', seller_verified = 1 WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $message = "Candidature approuvée. L'utilisateur est maintenant vendeur.";
        } elseif ($action === 'reject') {
            // On peut aussi supprimer la photo si on refuse
            $stmtPhoto = $pdo->prepare("SELECT seller_id_photo FROM visiteur WHERE id = :id");
            $stmtPhoto->execute([':id' => $userId]);
            $oldPhoto = $stmtPhoto->fetchColumn();
            if ($oldPhoto) {
                @unlink(__DIR__ . '/../uploads/kyc/' . $oldPhoto);
            }

            $stmt = $pdo->prepare("UPDATE visiteur SET seller_verified = 0, seller_id_photo = NULL WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $message = "Candidature refusée.";
        }
    }
}

$filter = in_array($_GET['filter'] ?? '', ['pending','approved']) ? $_GET['filter'] : 'pending';
$statusValue = ($filter === 'pending') ? 2 : 1;

$stmt = $pdo->prepare(
    "SELECT id, username, prenom, nom, email, telephone, adresse, ville, seller_id_type, seller_id_number, seller_id_photo 
     FROM visiteur 
     WHERE seller_verified = :s 
     ORDER BY id DESC"
);
$stmt->execute([':s' => $statusValue]);
$sellers = $stmt->fetchAll();

// Comptes badges
$cntPending = $pdo->query("SELECT COUNT(*) FROM visiteur WHERE seller_verified = 2")->fetchColumn();
$cntApproved = $pdo->query("SELECT COUNT(*) FROM visiteur WHERE seller_verified = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Candidatures Vendeurs · Admin</title>
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
            --sidebar-w: 260px;
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
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
            min-height: 100vh;
        }

        /* Sidebar Styles (from article_new.php for consistency) */
        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
            height: 100vh;
            position: sticky;
            top: 0;
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

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-desc {
            color: var(--text-soft);
            font-size: 16px;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .fbi {
            padding: 12px 20px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-soft);
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fbi.active {
            border-color: var(--neon-blue);
            color: #fff;
            background: rgba(0, 207, 255, 0.1);
            box-shadow: var(--glow-blue);
        }

        .cnt {
            background: rgba(0, 207, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
        }

        .seller-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 32px;
            backdrop-filter: blur(18px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
            animation: cardIn 0.5s ease both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .seller-info h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            color: #fff;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-label {
            color: rgba(255, 255, 255, 0.4);
            font-size: 10px;
            text-transform: uppercase;
            display: block;
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .info-value {
            font-size: 15px;
            color: #fff;
            font-weight: 500;
        }
        
        .kyc-section {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 255, 136, 0.03);
            border: 1px solid rgba(0, 255, 136, 0.1);
            border-radius: 18px;
        }

        .kyc-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            color: var(--neon-green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .photo-preview {
            width: 100%;
            aspect-ratio: 3/2;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #000;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .photo-preview:hover {
            border-color: var(--neon-blue);
            transform: scale(1.02);
            box-shadow: var(--glow-blue);
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .photo-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 207, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: 0.3s;
        }

        .photo-preview:hover .photo-overlay {
            opacity: 1;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 14px 24px;
            border-radius: 14px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: 0.3s;
            flex: 1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            box-shadow: var(--glow-green);
        }

        .btn-reject {
            background: rgba(255, 68, 102, 0.1);
            border: 1px solid var(--neon-red);
            color: var(--neon-red);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-approve:hover {
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.6);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.2);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 8, 17, 0.95);
            backdrop-filter: blur(15px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 40px;
            cursor: zoom-out;
        }

        .lightbox.open {
            display: flex;
        }

        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .lightbox img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 12px;
            box-shadow: 0 0 50px rgba(0, 207, 255, 0.3);
            border: 1px solid rgba(0, 207, 255, 0.2);
        }

        .lightbox-info {
            margin-top: 20px;
            background: var(--panel-strong);
            padding: 15px 25px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            color: var(--neon-blue);
        }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; }
            .admin-sidebar { display: none; }
        }

        @media (max-width: 900px) {
            .seller-card { grid-template-columns: 1fr; }
            .content { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">Candidatures Vendeurs</h1>
                <p class="page-desc">Gérez les demandes des utilisateurs souhaitant devenir vendeurs sur la plateforme.</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert">✓ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?filter=pending" class="fbi <?= $filter==='pending'?'active':'' ?>">
                    ⏳ En attente <span class="cnt"><?= $cntPending ?></span>
                </a>
                <a href="?filter=approved" class="fbi <?= $filter==='approved'?'active':'' ?>">
                    ✅ Approuvés <span class="cnt"><?= $cntApproved ?></span>
                </a>
            </div>

            <?php if (empty($sellers)): ?>
                <div style="text-align:center; padding:80px; background:var(--panel-bg); border-radius:24px; border:1px dashed var(--border);">
                    <div style="font-size:40px; margin-bottom:15px; opacity:0.3;">📂</div>
                    <p style="color:var(--text-soft); font-family:'Orbitron',sans-serif; font-size:14px; letter-spacing:1px;">Aucune candidature trouvée dans cette section.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sellers as $s): ?>
                    <div class="seller-card">
                        <div class="seller-info">
                            <h3><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> <span style="color:var(--neon-blue); font-size:14px; margin-left:10px;">@<?= htmlspecialchars($s['username']) ?></span></h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <div class="info-value"><?= htmlspecialchars($s['email']) ?></div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Téléphone</span>
                                    <div class="info-value"><?= htmlspecialchars($s['telephone'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ville</span>
                                    <div class="info-value"><?= htmlspecialchars($s['ville'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Adresse</span>
                                    <div class="info-value"><?= htmlspecialchars($s['adresse'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="kyc-section">
                                <span class="kyc-title">🛡️ Informations d'identité</span>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Type de pièce</span>
                                        <div class="info-value"><?= htmlspecialchars($s['seller_id_type']) ?></div>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Numéro de pièce</span>
                                        <div class="info-value"><?= htmlspecialchars($s['seller_id_number']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($filter === 'pending'): ?>
                            <div class="actions">
                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve">Approuver le vendeur</button>
                                </form>
                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject">Refuser</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="seller-photo">
                            <span class="info-label" style="margin-bottom:12px;">Document d'identité</span>
                            <?php if ($s['seller_id_photo']): ?>
                                <div class="photo-preview" onclick="openLightbox('../uploads/kyc/<?= htmlspecialchars($s['seller_id_photo']) ?>', '<?= htmlspecialchars($s['seller_id_type']) ?> - <?= htmlspecialchars($s['seller_id_number']) ?>')">
                                    <img src="../uploads/kyc/<?= htmlspecialchars($s['seller_id_photo']) ?>" alt="ID Photo">
                                    <div class="photo-overlay">
                                        <span style="font-family:'Orbitron',sans-serif; font-size:12px; color:#fff;">🔍 Agrandir</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="photo-preview" style="display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.1); border-style:dashed;">
                                    <div style="text-align:center;">
                                        <div style="font-size:24px; margin-bottom:8px;">🚫</div>
                                        <div style="font-size:10px; font-family:'Orbitron',sans-serif;">Aucune photo</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <div class="lightbox" id="lightbox" onclick="this.classList.remove('open')">
        <div class="lightbox-content">
            <img id="lightboxImg" src="" alt="">
            <div class="lightbox-info" id="lightboxInfo"></div>
        </div>
    </div>

    <script>
        function openLightbox(src, info) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxInfo').textContent = info;
            document.getElementById('lightbox').classList.add('open');
        }
        
        // Fermer avec Echap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('lightbox').classList.remove('open');
            }
        });
    </script>
</body>
</html>
