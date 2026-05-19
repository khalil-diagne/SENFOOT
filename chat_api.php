<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json');

// Sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true || !isset($_SESSION['user_id_from_db'])) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit();
}

try {
    $pdo = db();
    // Créer la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        sender_id INT UNSIGNED NOT NULL,
        receiver_id INT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_receiver_read (receiver_id, is_read),
        KEY idx_sender_receiver (sender_id, receiver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données.']);
    exit();
}

$action = $_REQUEST['action'] ?? null;
$current_user_id = $_SESSION['user_id_from_db'];
$is_admin = ($_SESSION['role'] === 'admin');

switch ($action) {
    case 'fetch':
        // Si un admin ouvre une conversation, on marque les messages comme lus
        if ($is_admin && isset($_GET['user_id'])) {
            $stmt_mark_read = $pdo->prepare(
                "UPDATE chat_messages SET is_read = 1 WHERE sender_id = :sender_id AND receiver_id = :receiver_id AND is_read = 0"
            );
            $stmt_mark_read->execute([
                ':sender_id' => $_GET['user_id'],
                ':receiver_id' => $current_user_id
            ]);
        }

        if ($is_admin) {
            $other_user_id = $_GET['user_id'] ?? 0;
            if (!$other_user_id) {
                echo json_encode([]); // L'admin doit sélectionner un utilisateur
                exit();
            }
            $admin_id_for_query = $current_user_id;
            $user_id_for_query = $other_user_id;
        } else {
            // Pour un utilisateur normal, on cherche le premier admin (ou un admin spécifique si la logique évolue)
            $stmt_admin = $pdo->prepare("SELECT id FROM visiteur WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            $stmt_admin->execute();
            $admin_id_for_query = $stmt_admin->fetchColumn();
            $user_id_for_query = $current_user_id;
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM chat_messages 
             WHERE (sender_id = :user_id AND receiver_id = :admin_id) 
                OR (sender_id = :admin_id AND receiver_id = :user_id)
             ORDER BY timestamp ASC"
        );
        $stmt->execute([':user_id' => $user_id_for_query, ':admin_id' => $admin_id_for_query]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sécuriser les messages avant de les envoyer au client
        foreach ($messages as $key => $message) {
            $messages[$key]['message'] = htmlspecialchars($message['message']);
        }
        echo json_encode($messages);
        break;

    case 'send':
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide.']);
            exit();
        }

        if (!is_numeric($current_user_id) || $current_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide pour l\'envoi.']);
            exit();
        }

        if ($is_admin) {
            $receiver_id = $_POST['receiver_id'] ?? 0;
            if (!$receiver_id) { // Un admin doit spécifier un destinataire
                echo json_encode(['success' => false, 'message' => 'Destinataire non spécifié.']);
                exit();
            }
        } else {
            // L'utilisateur envoie au premier admin trouvé
            $stmt_admin = $pdo->prepare("SELECT id FROM visiteur WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            $stmt_admin->execute();
            $receiver_id = $stmt_admin->fetchColumn();
            if (!$receiver_id) { // Si aucun admin n'est trouvé dans le système
                echo json_encode(['success' => false, 'message' => 'Aucun administrateur disponible pour recevoir le message.']);
                exit();
            }
        }
        
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)"
            );
            $stmt->execute([
                ':sender_id' => $current_user_id,
                ':receiver_id' => $receiver_id,
                ':message' => $message // On stocke le message brut
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("Chat API Send Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message.']);
        }
        break;

    case 'get_conversations':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
            exit();
        }
        // On utilise l'ID de l'admin connecté pour compter ses messages non lus
        $admin_id_for_query = $current_user_id;

        $stmt = $pdo->prepare(
            "SELECT v.id, v.username, 
                    (SELECT COUNT(*) FROM chat_messages WHERE sender_id = v.id AND receiver_id = :admin_id AND is_read = 0) as unread_count
             FROM visiteur v
             WHERE v.id != :admin_id
             ORDER BY unread_count DESC, v.username ASC"
        );
        $stmt->execute([':admin_id' => $admin_id_for_query]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'check_new':
        if (!$is_admin) {
            echo json_encode(['unread_count' => 0]);
            exit();
        }
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM chat_messages WHERE receiver_id = :admin_id AND is_read = 0");
        $stmt->execute([':admin_id' => $current_user_id]);
        $unread_count = $stmt->fetchColumn();
        echo json_encode(['unread_count' => (int)$unread_count]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non valide.']);
        break;
}
?>