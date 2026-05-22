<?php
/**
 * Script pour initialiser/mettre à jour les articles avec des données d'exemple
 * Exécutez ce fichier une seule fois pour ajouter les exemples de données
 * Accès: /admin/ uniquement ou pas du tout après
 */
require __DIR__ . '/../config.php';

// Vérifier l'accès administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Accès refusé';
    exit;
}

try {
    $pdo = db();

    // Vérifier et ajouter les colonnes manquantes si elles n'existent pas
    try {
        $pdo->query('SELECT price FROM articles LIMIT 1');
    } catch (PDOException $e) {
        // Les colonnes n'existent pas, les ajouter
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `price` DECIMAL(10, 2) DEFAULT 0 AFTER `title`');
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `platform` VARCHAR(50) DEFAULT "Multi" AFTER `price`');
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `delivery_time` VARCHAR(100) DEFAULT "5 minutes" AFTER `platform`');
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `binding_status` VARCHAR(255) DEFAULT "Lié à un email" AFTER `delivery_time`');
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `gallery_images` JSON DEFAULT NULL AFTER `image`');
        $pdo->query('ALTER TABLE `articles` ADD COLUMN `why_choose_us` JSON DEFAULT NULL AFTER `gallery_images`');
    }

    // Insérer des exemples d'articles
    $articles = [
        [
            'title' => 'PUISSANCE 3181',
            'price' => 29999,
            'platform' => 'Android/iOS',
            'delivery_time' => 'Livraison en moins de 5 minutes',
            'binding_status' => 'Lié à un email factice - Changeable',
            'content' => 'Compte eFootball haut niveau avec 3181 de puissance. Équipe complète, joueurs légendaires inclus et nombreux coins.',
            'image' => 'efootball_1.jpg',
            'gallery_images' => json_encode(['efootball_1.jpg', 'efootball_2.jpg', 'efootball_3.jpg', 'efootball_4.jpg', 'efootball_5.jpg']),
            'why_choose_us' => json_encode(['Joueurs légendaires inclus', 'Coins suffisants pour upgrader', 'Équipe complète et optimisée']),
            'author_username' => 'admin'
        ],
        [
            'title' => 'PUISSANCE 2850',
            'price' => 24999,
            'platform' => 'Android/iOS',
            'delivery_time' => 'Livraison en moins de 5 minutes',
            'binding_status' => 'Lié à un email factice - Changeable',
            'content' => 'Compte eFootball avec 2850 de puissance. Parfait pour les joueurs intermédiaires.',
            'image' => 'efootball_2.jpg',
            'gallery_images' => json_encode(['efootball_2.jpg', 'efootball_1.jpg', 'efootball_3.jpg']),
            'why_choose_us' => json_encode(['Support 24/7', 'Garantie 30 jours', 'Livraison instantanée']),
            'author_username' => 'admin'
        ],
        [
            'title' => 'PUISSANCE 2500',
            'price' => 19999,
            'platform' => 'Android',
            'delivery_time' => 'Livraison en moins de 5 minutes',
            'binding_status' => 'Lié à un email factice - Changeable',
            'content' => 'Compte eFootball parfait pour débuter avec une bonne équipe de base.',
            'image' => 'efootball_3.jpg',
            'gallery_images' => json_encode(['efootball_3.jpg', 'efootball_4.jpg', 'efootball_5.jpg']),
            'why_choose_us' => json_encode(['100% sécurisé', 'Comptes vérifiés', 'Meilleur prix garanti']),
            'author_username' => 'admin'
        ],
        [
            'title' => 'PUISSANCE 3500',
            'price' => 34999,
            'platform' => 'iOS',
            'delivery_time' => 'Livraison en moins de 5 minutes',
            'binding_status' => 'Lié à un email factice - Changeable',
            'content' => 'Compte eFootball elite avec 3500 de puissance. Les meilleures cartes et coins.',
            'image' => 'efootball_4.jpg',
            'gallery_images' => json_encode(['efootball_4.jpg', 'efootball_5.jpg', 'efootball_1.jpg']),
            'why_choose_us' => json_encode(['Équipe de rêve', 'Tous les joueurs top', 'Victoires garanties']),
            'author_username' => 'admin'
        ]
    ];

    foreach ($articles as $art) {
        $stmt = $pdo->prepare('
            INSERT INTO articles (title, price, platform, delivery_time, binding_status, content, image, gallery_images, why_choose_us, author_username)
            VALUES (:title, :price, :platform, :delivery_time, :binding_status, :content, :image, :gallery_images, :why_choose_us, :author_username)
            ON DUPLICATE KEY UPDATE 
                price = :price,
                platform = :platform,
                delivery_time = :delivery_time,
                binding_status = :binding_status
        ');

        $stmt->execute([
            ':title' => $art['title'],
            ':price' => $art['price'],
            ':platform' => $art['platform'],
            ':delivery_time' => $art['delivery_time'],
            ':binding_status' => $art['binding_status'],
            ':content' => $art['content'],
            ':image' => $art['image'],
            ':gallery_images' => $art['gallery_images'],
            ':why_choose_us' => $art['why_choose_us'],
            ':author_username' => $art['author_username']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Articles initialisés avec succès',
        'articles_added' => count($articles)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('Init articles error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur serveur est survenue.'
    ], JSON_UNESCAPED_UNICODE);
}
