<?php
require __DIR__ . '/config.php';
require_login();

$pdo = db();
$userId = current_user_id();
$error = '';
$success = '';

// Récupérer les infos de l'utilisateur
try {
    $stmt = $pdo->prepare('SELECT prenom, nom, email, telephone, seller_id_type, seller_id_number, seller_ine, seller_id_photo, seller_verified FROM visiteur WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die('Utilisateur non trouvé');
    }
} catch (PDOException $e) {
    error_log('User fetch error: ' . $e->getMessage());
    die('Une erreur serveur est survenue.');
}

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Jeton de sécurité invalide';
    } else {
        $idType = trim($_POST['id_type'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');

        if ($idType === '' || $idNumber === '') {
            $error = 'Tous les champs sont obligatoires';
        } elseif (empty($_FILES['id_photo']) || !is_uploaded_file($_FILES['id_photo']['tmp_name'])) {
            $error = 'Veuillez télécharger une photo de votre pièce d\'identité';
        } else {
            $file = $_FILES['id_photo'];
            $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'application/pdf' => '.pdf'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowed[$mime])) {
                $error = 'Type de fichier non supporté (JPG, PNG ou PDF uniquement)';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Fichier trop volumineux (max 5MB)';
            } else {
                $ext = $allowed[$mime];
                $photoFilename = 'kyc_' . $userId . '_' . time() . $ext;
                $uploadDir = __DIR__ . '/uploads/kyc/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $destPath = $uploadDir . $photoFilename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    try {
                        $stmt = $pdo->prepare(
                            'UPDATE visiteur 
                             SET telephone = :telephone,
                                 seller_id_type = :id_type, 
                                 seller_id_number = :id_number,
                                 seller_ine = :ine,
                                 seller_id_photo = :id_photo,
                                 seller_verified = 2 
                             WHERE id = :id'
                        );
                        $stmt->execute([
                            ':telephone' => $phone,
                            ':id_type' => $idType,
                            ':id_number' => $idNumber,
                            ':ine' => $ine,
                            ':id_photo' => $photoFilename,
                            ':id' => $userId,
                        ]);

                        $success = 'Votre candidature a été soumise avec succès ! L\'administrateur examinera vos documents.';
                        $user['telephone'] = $phone;
                        $user['seller_id_type'] = $idType;
                        $user['seller_id_number'] = $idNumber;
                        $user['seller_ine'] = $ine;
                        $user['seller_id_photo'] = $photoFilename;
                        $user['seller_verified'] = 2;
                    } catch (PDOException $e) {
                        error_log('Apply seller update error: ' . $e->getMessage());
                        $error = 'Une erreur serveur est survenue lors de la soumission';
                    }
                } else {
                    $error = 'Erreur lors du téléchargement du fichier';
                }
            }
        }
    }
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devenir Vendeur - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-orange: #ff9500;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --border: rgba(0, 207, 255, 0.16);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
            --glow-orange: 0 0 20px rgba(255, 149, 0, 0.45), 0 0 60px rgba(255, 149, 0, 0.12);
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
            padding: 20px;
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

        .container {
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 40px auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            letter-spacing: 2px;
            margin-bottom: 12px;
            background: linear-gradient(90deg, var(--neon-orange), #ffb703);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: var(--text-soft);
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.25);
            color: var(--neon-green);
        }

        .alert-error {
            background: rgba(255, 68, 102, 0.08);
            border: 1px solid rgba(255, 68, 102, 0.25);
            color: #ff6688;
        }

        .status-box {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-box.pending {
            background: rgba(255, 149, 0, 0.08);
            border: 1px solid rgba(255, 149, 0, 0.2);
        }

        .status-box.verified {
            background: rgba(0, 255, 136, 0.08);
            border: 1px solid rgba(0, 255, 136, 0.2);
        }

        .status-icon {
            font-size: 24px;
        }

        .status-text {
            flex: 1;
        }

        .status-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
        }

        .status-value {
            font-weight: 600;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.68);
            margin-bottom: 10px;
        }

        .form-control {
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(0, 207, 255, 0.14);
            background: rgba(2, 8, 17, 0.8);
            color: #fff;
            font-size: 14px;
            padding: 12px 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: 'Rajdhani', sans-serif;
        }

        .form-control:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 4px rgba(0, 207, 255, 0.12);
        }

        .upload-box {
            padding: 24px;
            border-radius: 10px;
            border: 2px dashed rgba(255, 149, 0, 0.26);
            background: rgba(255, 149, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-box:hover {
            border-color: rgba(255, 149, 0, 0.5);
            background: rgba(255, 149, 0, 0.1);
        }

        .upload-box input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .upload-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .upload-hint {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 8px;
        }

        .file-name {
            font-size: 12px;
            color: var(--neon-orange);
            margin-top: 8px;
        }

        .benefits {
            background: rgba(255, 149, 0, 0.05);
            border: 1px solid rgba(255, 149, 0, 0.15);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .benefits h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1.5px;
            color: var(--neon-orange);
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .benefits ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .benefits li {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .benefits li::before {
            content: '✓';
            color: var(--neon-green);
            font-weight: bold;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--neon-orange), #ffb703);
            color: #1a0f00;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(255, 149, 0, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-orange);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: transparent;
            color: var(--text-soft);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 24px;
        }

        .btn-back:hover {
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 207, 255, 0.2), transparent);
            margin: 24px 0;
        }

        @media (max-width: 600px) {
            .card {
                padding: 24px;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="container">
        <a href="accueil.php" class="btn-back">← Retour à l'accueil</a>

        <div class="header">
            <h1>💼 Devenir Vendeur</h1>
            <p>Rejoignez notre communauté de vendeurs et commencez à monétiser vos articles</p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Statut de candidature -->
            <?php if ($user['seller_verified'] == 2): ?>
                <div class="status-box pending">
                    <div class="status-icon">⏳</div>
                    <div class="status-text">
                        <div class="status-label">Statut</div>
                        <div class="status-value">En attente de vérification</div>
                    </div>
                </div>
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 13px; margin-bottom: 24px;">Votre candidature a été reçue. L'administrateur examinera vos documents dans les 24-48 heures.</p>
            <?php elseif ($user['seller_verified'] == 1): ?>
                <div class="status-box verified">
                    <div class="status-icon">✓</div>
                    <div class="status-text">
                        <div class="status-label">Statut</div>
                        <div class="status-value">Vérifié - Vous êtes vendeur !</div>
                    </div>
                </div>
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 13px; margin-bottom: 24px;">Accédez à votre tableau de bord vendeur pour gérer vos articles et vos ventes.</p>
                <a href="admin/seller_dashboard.php" class="btn-submit" style="background: linear-gradient(135deg, var(--neon-green), #00b86b); color: #001a0d;">📊 Aller au Tableau de Bord</a>
            <?php else: ?>
                <!-- Avantages -->
                <div class="benefits">
                    <h3>🎯 Avantages de devenir vendeur</h3>
                    <ul>
                        <li>Accès à un tableau de bord complet</li>
                        <li>Gestion facile de vos articles</li>
                        <li>Suivi des ventes en temps réel</li>
                        <li>Support prioritaire 24/7</li>
                        <li>Commissions compétitives</li>
                    </ul>
                </div>

                <div class="divider"></div>

                <!-- Formulaire -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control" placeholder="Ex: 06 12 34 56 78" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Numéro INE</label>
                        <input type="text" name="ine" class="form-control" placeholder="Ex: 1234567890" value="<?= htmlspecialchars($user['seller_ine'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type de Document d'Identité</label>
                        <select name="id_type" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="passport">Passeport</option>
                            <option value="carte_identite">Carte d'Identité</option>
                            <option value="permis_conduire">Permis de Conduire</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Numéro du Document</label>
                        <input type="text" name="id_number" class="form-control" placeholder="Ex: AB123456" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Photo/Scan du Document</label>
                        <label class="upload-box">
                            <div class="upload-icon">📄</div>
                            <div class="upload-text">Cliquez pour télécharger ou glissez-déposez</div>
                            <div class="upload-hint">JPG, PNG ou PDF - Max 5MB</div>
                            <input type="file" name="id_photo" accept=".jpg,.jpeg,.png,.pdf" required onchange="updateFileName(this)">
                            <div class="file-name" id="fileName"></div>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">🚀 Soumettre ma Candidature</button>
                </form>

                <p style="color: rgba(255, 255, 255, 0.4); font-size: 12px; text-align: center; margin-top: 20px;">
                    En soumettant ce formulaire, vous acceptez nos conditions de vendeur et notre politique de confidentialité.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = '✓ ' + input.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }

        // Drag and drop
        const uploadBox = document.querySelector('.upload-box');
        if (uploadBox) {
            uploadBox.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadBox.style.borderColor = 'rgba(255, 149, 0, 0.8)';
                uploadBox.style.background = 'rgba(255, 149, 0, 0.15)';
            });

            uploadBox.addEventListener('dragleave', () => {
                uploadBox.style.borderColor = 'rgba(255, 149, 0, 0.26)';
                uploadBox.style.background = 'rgba(255, 149, 0, 0.05)';
            });

            uploadBox.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadBox.style.borderColor = 'rgba(255, 149, 0, 0.26)';
                uploadBox.style.background = 'rgba(255, 149, 0, 0.05)';
                const input = uploadBox.querySelector('input[type="file"]');
                input.files = e.dataTransfer.files;
                updateFileName(input);
            });
        }
    </script>
</body>
</html>
