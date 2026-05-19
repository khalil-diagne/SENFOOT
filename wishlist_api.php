<?php
require __DIR__ . '/config.php';
ensure_store_schema();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = current_user_id();

if (!$userId) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Connecte-toi pour gerer ta liste d envies.',
    ]);
    exit();
}

if ($method === 'GET') {
    echo json_encode([
        'success' => true,
        'favorites' => fetch_wishlist_article_ids($userId),
    ]);
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
$articleId = isset($payload['article_id']) ? (int) $payload['article_id'] : 0;

if ($articleId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Article invalide.']);
    exit();
}

try {
    $pdo = db();

    $stmtArticle = $pdo->prepare('SELECT id, title FROM articles WHERE id = :id LIMIT 1');
    $stmtArticle->execute([':id' => $articleId]);
    $article = $stmtArticle->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
        exit();
    }

    $stmtExisting = $pdo->prepare('SELECT id FROM wishlist_items WHERE user_id = :user_id AND article_id = :article_id LIMIT 1');
    $stmtExisting->execute([
        ':user_id' => $userId,
        ':article_id' => $articleId,
    ]);
    $existingId = $stmtExisting->fetchColumn();

    if ($existingId) {
        $stmtDelete = $pdo->prepare('DELETE FROM wishlist_items WHERE id = :id');
        $stmtDelete->execute([':id' => (int) $existingId]);

        echo json_encode([
            'success' => true,
            'is_favorite' => false,
            'message' => 'Article retire de ta liste d envies.',
        ]);
        exit();
    }

    $stmtInsert = $pdo->prepare('INSERT INTO wishlist_items (user_id, article_id) VALUES (:user_id, :article_id)');
    $stmtInsert->execute([
        ':user_id' => $userId,
        ':article_id' => $articleId,
    ]);

    echo json_encode([
        'success' => true,
        'is_favorite' => true,
        'message' => 'Article ajoute a ta liste d envies.',
    ]);
} catch (Throwable $e) {
    error_log('Wishlist API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
