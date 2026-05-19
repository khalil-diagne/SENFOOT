<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_admin();

header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

$action    = $data['action']     ?? '';
$articleId = (int)($data['article_id'] ?? 0);
$note      = trim((string)($data['note'] ?? ''));

if (!in_array($action, ['approve', 'reject'], true) || $articleId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action ou article invalide.']);
    exit;
}

if ($action === 'reject' && $note === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Le motif du refus est obligatoire.']);
    exit;
}

$pdo = db();

// Récupérer l'article et les infos vendeur
$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.author_username, v.email, v.prenom, v.nom
     FROM articles a
     LEFT JOIN visiteur v ON v.username = a.author_username
     WHERE a.id = :id LIMIT 1"
);
$stmt->execute([':id' => $articleId]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
    exit;
}

if ($action === 'approve') {
    $pdo->prepare("UPDATE articles SET approval_status = 'approved', seller_note = NULL WHERE id = :id")
        ->execute([':id' => $articleId]);

    echo json_encode(['success' => true, 'message' => 'Article approuvé et publié sur la boutique.']);

} elseif ($action === 'reject') {
    $pdo->prepare("UPDATE articles SET approval_status = 'rejected', seller_note = :note WHERE id = :id")
        ->execute([':note' => $note, ':id' => $articleId]);

    // Envoyer l'email de rejet au vendeur
    $sellerEmail = $article['email'] ?? '';
    $sellerName  = trim(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?: ($article['author_username'] ?? 'Vendeur');

    $emailSent = false;
    if ($sellerEmail !== '') {
        $emailSent = send_article_rejection_email(
            $sellerEmail,
            $sellerName,
            $article['title'],
            $note
        );
    }

    echo json_encode([
        'success'    => true,
        'message'    => 'Article refusé.' . ($emailSent ? ' Email envoyé au vendeur.' : ' (Email non envoyé — vérifiez la config SMTP.)'),
        'email_sent' => $emailSent,
    ]);
}
