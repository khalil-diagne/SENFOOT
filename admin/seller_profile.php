<?php
require __DIR__ . '/../config.php';
require_seller();

$pdo = db();
$userId = current_user_id();

try {
    $stmt = $pdo->prepare("
        SELECT id, prenom, nom, email, telephone, adresse, ville, username,
               seller_id_type, seller_id_number, seller_id_photo, seller_verified
        FROM visiteur
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        die('Profil introuvable');
    }
} catch (PDOException $e) {
    error_log('Seller profile load error: ' . $e->getMessage());
    die('Une erreur serveur est survenue.');
}

$error = '';
$success = '';

// Traiter la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');

    if ($prenom === '' || $nom === '' || $email === '' || $telephone === '' || $adresse === '' || $ville === '') {
        $error = 'Tous les champs sont requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } else {
        try {
            $check = $pdo->prepare('SELECT id FROM visiteur WHERE email = :email AND id != :id LIMIT 1');
            $check->execute([':email' => $email, ':id' => $userId]);

            if ($check->fetch()) {
                $error = 'Cet email est déjà utilisé';
            } else {
                $update = $pdo->prepare(
                    'UPDATE visiteur
                     SET prenom = :prenom, nom = :nom, email = :email, telephone = :telephone, adresse = :adresse, ville = :ville
                     WHERE id = :id'
                );
                $update->execute([
                    ':prenom' => $prenom,
                    ':nom' => $nom,
                    ':email' => $email,
                    ':telephone' => $telephone,
                    ':adresse' => $adresse,
                    ':ville' => $ville,
                    ':id' => $userId,
                ]);

                $success = 'Profil mis à jour avec succès';
                $profile['prenom'] = $prenom;
                $profile['nom'] = $nom;
                $profile['email'] = $email;
                $profile['telephone'] = $telephone;
                $profile['adresse'] = $adresse;
                $profile['ville'] = $ville;
            }
        } catch (PDOException $e) {
            error_log('Seller profile update error: ' . $e->getMessage());
            $error = 'Une erreur serveur est survenue lors de la mise à jour.';
        }
    }
}

// Traiter l'upload de document KYC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['kyc_document'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $fileExt = strtolower(pathinfo($_FILES['kyc_document']['name'], PATHINFO_EXTENSION));
    $fileSize = $_FILES['kyc_document']['size'];

    if (!in_array($fileExt, $allowed, true)) {
        $error = 'Format non autorisé. Utilisez JPG, PNG ou PDF';
    } elseif ($fileSize > 5242880) {
        $error = 'Le fichier est trop volumineux (max 5MB)';
    } else {
        $newFileName = 'kyc_' . $userId . '_' . time() . '.' . $fileExt;
        $uploadPath = '../uploads/kyc/' . $newFileName;
        if (!is_dir('../uploads/kyc')) {
            mkdir('../uploads/kyc', 0777, true);
        }
        if (move_uploaded_file($_FILES['kyc_document']['tmp_name'], $uploadPath)) {
            try {
                $idType = trim($_POST['id_type'] ?? '');
                $idNumber = trim($_POST['id_number'] ?? '');
                $photoFilename = basename($newFileName);

                $pdo->prepare('UPDATE visiteur SET seller_id_type = :type, seller_id_number = :number, seller_id_photo = :photo WHERE id = :id')
                    ->execute([
                        ':type' => $idType,
                        ':number' => $idNumber,
                        ':photo' => $photoFilename,
                        ':id' => $userId
                    ]);

                $success = 'Document KYC uploadé. En attente de vérification par l\'administrateur.';
                $profile['seller_id_type'] = $idType;
                $profile['seller_id_number'] = $idNumber;
                $profile['seller_id_photo'] = $photoFilename;
            } catch (PDOException $e) {
                error_log('KYC upload error: ' . $e->getMessage());
                $error = 'Une erreur serveur est survenue lors de la sauvegarde.';
            }
        } else {
            $error = 'Erreur lors de l\'upload';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Vendeur - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
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

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            letter-spacing: 2px;
            margin-bottom: 32px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            color: var(--neon-red);
        }

        .form-card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            backdrop-filter: blur(18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            margin-bottom: 24px;
        }

        .form-card h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            letter-spacing: 1.5px;
            margin-bottom: 24px;
            color: #fff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }

        .form-group {
            display: grid;
            gap: 10px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.68);
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
        }

        .form-control:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 4px rgba(0, 207, 255, 0.12);
        }

        .form-control:disabled {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow-green);
        }

        .kyc-status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 10px;
            background: rgba(0, 207, 255, 0.08);
            border: 1px solid rgba(0, 207, 255, 0.2);
            margin-bottom: 20px;
        }

        .kyc-status.verified {
            background: rgba(0, 255, 136, 0.08);
            border-color: rgba(0, 255, 136, 0.2);
        }

        .kyc-status.verified .status-icon {
            color: var(--neon-green);
        }

        .kyc-status.pending .status-icon {
            color: var(--neon-blue);
        }

        .status-icon {
            font-size: 20px;
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

        .upload-box {
            padding: 18px;
            border-radius: 10px;
            border: 2px dashed rgba(0, 207, 255, 0.26);
            background: rgba(0, 207, 255, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .upload-box:hover {
            border-color: rgba(0, 207, 255, 0.5);
            background: rgba(0, 207, 255, 0.1);
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

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                display: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <aside class="admin-sidebar">
            <h2>VENDEUR</h2>
            <ul>
                <li><a href="seller_dashboard.php">📊 Tableau de Bord</a></li>
                <li><a href="seller_products.php">📦 Mes Produits</a></li>
                <li><a href="seller_orders.php">📋 Mes Commandes</a></li>
                <li><a href="seller_profile.php" class="active">👤 Mon Profil</a></li>
                <li><a href="../profile.php">🔙 Retour Profil</a></li>
            </ul>
        </aside>

        <main class="content">
            <h1 class="page-title">Mon Profil Vendeur</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Informations Personnelles -->
            <div class="form-card">
                <h2>Informations Personnelles</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($profile['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($profile['nom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($profile['telephone'] ?? '') ?>" required>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($profile['adresse'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ville</label>
                            <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($profile['ville'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pseudo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($profile['username'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit">💾 Enregistrer les modifications</button>
                </form>
            </div>

            <!-- Vérification KYC -->
            <div class="form-card">
                <h2>Vérification d'Identité (KYC)</h2>

                <?php if ($profile['seller_verified']): ?>
                    <div class="kyc-status verified">
                        <div class="status-icon">✓</div>
                        <div class="status-text">
                            <div class="status-label">Statut de Vérification</div>
                            <div class="status-value">Vérifié</div>
                        </div>
                    </div>
                <?php elseif ($profile['seller_id_photo']): ?>
                    <div class="kyc-status pending">
                        <div class="status-icon">⏳</div>
                        <div class="status-text">
                            <div class="status-label">Statut de Vérification</div>
                            <div class="status-value">En attente de vérification</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="kyc-status pending">
                        <div class="status-icon">📋</div>
                        <div class="status-text">
                            <div class="status-label">Statut de Vérification</div>
                            <div class="status-value">Non vérifié - Veuillez soumettre vos documents</div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Type de Document</label>
                            <select name="id_type" class="form-control" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="passport" <?= ($profile['seller_id_type'] === 'passport' ? 'selected' : '') ?>>Passeport</option>
                                <option value="carte_identite" <?= ($profile['seller_id_type'] === 'carte_identite' ? 'selected' : '') ?>>Carte d'Identité</option>
                                <option value="permis_conduire" <?= ($profile['seller_id_type'] === 'permis_conduire' ? 'selected' : '') ?>>Permis de Conduire</option>
                                <option value="autre" <?= ($profile['seller_id_type'] === 'autre' ? 'selected' : '') ?>>Autre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Numéro du Document</label>
                            <input type="text" name="id_number" class="form-control" value="<?= htmlspecialchars($profile['seller_id_number'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Photo/Scan du Document</label>
                        <label class="upload-box">
                            <div class="upload-icon">📄</div>
                            <div class="upload-text">Cliquez pour télécharger ou glissez-déposez</div>
                            <input type="file" name="kyc_document" accept=".jpg,.jpeg,.png,.pdf" required>
                        </label>
                        <small style="color: rgba(255, 255, 255, 0.5); margin-top: 8px; display: block;">Formats acceptés: JPG, PNG, PDF (max 5MB)</small>
                    </div>

                    <button type="submit" class="btn-submit">📤 Soumettre pour Vérification</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
