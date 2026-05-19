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
                $oldBasename = basename($oldPhoto);
                @unlink(__DIR__ . '/../uploads/kyc/' . $oldBasename);
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
    "SELECT id, username, prenom, nom, email, telephone, adresse, ville, seller_id_type, seller_id_number, seller_ine, seller_id_photo 
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
    <link rel="stylesheet" href="../style/admin_styles.css">
    <style>
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);
            --sidebar-w:240px;--nav-h:70px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}
        .admin-container{display:flex;min-height:100vh;position:relative;z-index:2;}
        .admin-content{flex:1 1 0;min-width:0;display:flex;flex-direction:column;padding:30px;}
        .admin-header{margin-bottom:30px;}
        .page-title{font-family:'Orbitron',sans-serif;font-size:24px;color:var(--neon-blue);margin-bottom:10px;}
        .page-header{margin-bottom:30px;}
        .page-title{font-family:'Orbitron',sans-serif;font-size:24px;color:var(--neon-blue);margin-bottom:10px;}
        
        .filter-bar{display:flex;gap:10px;margin-bottom:20px;}
        .fbi{padding:10px 20px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);text-decoration:none;transition:0.3s;}
        .fbi.active{border-color:var(--neon-blue);color:var(--neon-blue);background:rgba(0,207,255,0.1);}
        .cnt{background:rgba(0,207,255,0.2);padding:2px 8px;border-radius:10px;font-size:12px;margin-left:5px;}

        .seller-card{background:var(--card-bg);border:1px solid rgba(0,207,255,0.1);border-radius:12px;padding:24px;margin-bottom:20px;display:grid;grid-template-columns:1fr 300px;gap:24px;}
        .seller-info h3{font-family:'Orbitron',sans-serif;font-size:18px;color:#fff;margin-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:5px;}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;}
        .info-item{margin-bottom:8px;}
        .info-label{color:rgba(255,255,255,0.4);font-size:11px;text-transform:uppercase;display:block;font-family:'Orbitron',sans-serif;}
        
        .kyc-section{margin-top:15px;padding:15px;background:rgba(0,255,136,0.05);border:1px solid rgba(0,255,136,0.1);border-radius:10px;}
        .kyc-title{font-family:'Orbitron',sans-serif;font-size:12px;color:var(--neon-green);margin-bottom:10px;display:block;}

        .photo-preview{width:100%;height:200px;border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,0.1);background:#000;cursor:pointer;transition:0.3s;}
        .photo-preview:hover{border-color:var(--neon-blue);transform:scale(1.02);}
        .photo-preview img{width:100%;height:100%;object-fit:contain;}

        .actions{display:flex;gap:10px;margin-top:20px;}
        .btn{padding:12px 24px;border-radius:8px;font-family:'Orbitron',sans-serif;font-size:11px;font-weight:bold;cursor:pointer;border:none;transition:0.3s;flex:1;}
        .btn-approve{background:var(--neon-green);color:#000;}
        .btn-reject{background:rgba(255,68,102,0.1);border:1px solid var(--neon-red);color:var(--neon-red);}
        .btn:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,0,0,0.3);}

        .alert{padding:15px;border-radius:8px;margin-bottom:20px;background:rgba(0,255,136,0.1);border:1px solid var(--neon-green);color:var(--neon-green);}
        
        /* Lightbox simple */
        .lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:1000;justify-content:center;align-items:center;padding:40px;}
        .lightbox.open{display:flex;}
        .lightbox img{max-width:100%;max-height:100%;box-shadow:0 0 50px rgba(0,207,255,0.3);}

        @media(max-width:900px){
            .admin-container{flex-direction:column;}
            .admin-sidebar{display:none;}
            .admin-content{margin-left:0;padding:20px;}
            .seller-card{grid-template-columns:1fr;}
            .seller-card .seller-photo{width:100%;}
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1 class="page-title">Candidatures Vendeurs</h1>
                <p>Gérez les demandes des utilisateurs souhaitant devenir vendeurs.</p>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?filter=pending" class="fbi <?= $filter==='pending'?'active':'' ?>">⏳ En attente <span class="cnt"><?= $cntPending ?></span></a>
                <a href="?filter=approved" class="fbi <?= $filter==='approved'?'active':'' ?>">✅ Approuvés <span class="cnt"><?= $cntApproved ?></span></a>
            </div>

            <?php if (empty($sellers)): ?>
                <div style="text-align:center; padding:50px; color:rgba(255,255,255,0.3);">
                    Aucune candidature trouvée.
                </div>
            <?php else: ?>
                <?php foreach ($sellers as $s): ?>
                    <div class="seller-card">
                        <div class="seller-info">
                            <h3><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> (@<?= htmlspecialchars($s['username']) ?>)</h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <?= htmlspecialchars($s['email']) ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Téléphone</span>
                                    <?= htmlspecialchars($s['telephone'] ?? 'N/A') ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Téléphone</span>
                                    <?= htmlspecialchars($s['telephone'] ?? 'N/A') ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ville</span>
                                    <?= htmlspecialchars($s['ville'] ?? 'N/A') ?>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Adresse</span>
                                    <?= htmlspecialchars($s['adresse'] ?? 'N/A') ?>
                                </div>
                            </div>

                            <div class="kyc-section">
                                <span class="kyc-title">Informations d'identité</span>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Type de pièce</span>
                                        <?= htmlspecialchars($s['seller_id_type']) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Numéro de pièce</span>
                                        <?= htmlspecialchars($s['seller_id_number']) ?>
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
                            <span class="info-label" style="margin-bottom:10px;">Photo de la pièce</span>
                            <?php if ($s['seller_id_photo']):
                                $photoFile = basename($s['seller_id_photo']);
                                $photoUrl = '../uploads/kyc/' . $photoFile;
                                $ext = strtolower(pathinfo($photoFile, PATHINFO_EXTENSION));
                            ?>
                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)): ?>
                                    <div class="photo-preview" onclick="openLightbox('<?= htmlspecialchars($photoUrl) ?>')">
                                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="ID Photo">
                                    </div>
                                <?php elseif ($ext === 'pdf'): ?>
                                    <div class="photo-preview" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px;">
                                        <span style="color:#fff;">Document PDF</span>
                                        <a href="<?= htmlspecialchars($photoUrl) ?>" target="_blank" style="color:var(--neon-blue); text-decoration:underline; font-size:13px;">Ouvrir le document</a>
                                    </div>
                                <?php else: ?>
                                    <div class="photo-preview" style="display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                                        Fichier non reconnu
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="photo-preview" style="display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                                    Aucune photo
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="lightbox" id="lightbox" onclick="this.classList.remove('open')">
        <img id="lightboxImg" src="" alt="">
    </div>

    <script>
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').classList.add('open');
        }
    </script>
</body>
</html>
