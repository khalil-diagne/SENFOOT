<?php
require __DIR__ . '/config.php';
ensure_store_schema();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username']) || !isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

$userId = current_user_id();
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit();
}

$profile = fetch_user_profile_by_id($userId);
if (!$profile) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Profil introuvable.']);
    exit();
}

echo json_encode([
    'success' => true,
    'profile' => [
        'id' => (int) $profile['id'],
        'prenom' => $profile['prenom'] ?? '',
        'nom' => $profile['nom'] ?? '',
        'email' => $profile['email'] ?? '',
        'telephone' => $profile['telephone'] ?? '',
        'adresse' => $profile['adresse'] ?? '',
        'ville' => $profile['ville'] ?? '',
        'username' => $profile['username'] ?? '',
    ]
]);
