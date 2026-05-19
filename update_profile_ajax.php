<?php
require __DIR__ . '/config.php';
ensure_store_schema();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

$json = file_get_contents('php://input');
$payload = json_decode($json, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}

$userId = current_user_id();
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit();
}

try {
    $pdo = db();

    // Récupérer le profil actuel
    $stmt = $pdo->prepare('SELECT * FROM visiteur WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Profil introuvable.']);
        exit();
    }

    // Fusionner avec les données existantes
    $prenom = trim((string) ($payload['prenom'] ?? $profile['prenom'] ?? ''));
    $nom = trim((string) ($payload['nom'] ?? $profile['nom'] ?? ''));
    $email = trim((string) ($payload['email'] ?? $profile['email'] ?? ''));
    $telephone = preg_replace('/\D+/', '', (string) ($payload['telephone'] ?? $profile['telephone'] ?? ''));
    $adresse = trim((string) ($payload['adresse'] ?? $profile['adresse'] ?? ''));
    $ville = trim((string) ($payload['ville'] ?? $profile['ville'] ?? ''));

    // Valider les données
    $errors = [];

    if ($prenom === '') {
        $errors['prenom'] = 'Le prénom est requis.';
    }
    if ($nom === '') {
        $errors['nom'] = 'Le nom est requis.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Un email valide est requis.';
    }
    if ($telephone === '' || strlen($telephone) < 9) {
        $errors['telephone'] = 'Un numéro de téléphone valide est requis.';
    }
    if ($adresse === '') {
        $errors['adresse'] = 'L\'adresse est requise.';
    }
    if ($ville === '') {
        $errors['ville'] = 'La ville est requise.';
    }

    // Vérifier l'unicité de l'email
    if ($email !== ($profile['email'] ?? '')) {
        $stmtCheck = $pdo->prepare('SELECT id FROM visiteur WHERE email = :email AND id != :id LIMIT 1');
        $stmtCheck->execute([':email' => $email, ':id' => $userId]);
        if ($stmtCheck->fetch()) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit();
    }

    // Mettre à jour le profil
    $stmt = $pdo->prepare(
        'UPDATE visiteur SET prenom = :prenom, nom = :nom, email = :email, telephone = :telephone, adresse = :adresse, ville = :ville WHERE id = :id'
    );
    $stmt->execute([
        ':prenom' => $prenom,
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone,
        ':adresse' => $adresse,
        ':ville' => $ville,
        ':id' => $userId,
    ]);

    // Mettre à jour la session
    $_SESSION['prenom'] = $prenom;
    $_SESSION['nom'] = $nom;
    $_SESSION['email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Profil mis à jour avec succès.',
        'profile' => [
            'id' => $userId,
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse,
            'ville' => $ville,
        ]
    ]);
} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du profil.']);
}
