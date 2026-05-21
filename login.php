<?php
require __DIR__ . '/config.php';
$conn = db();

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        redirect('/connexion.php?error=empty');
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $sql = 'SELECT id, prenom, nom, email, username, password, role FROM visiteur WHERE username = :username LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && isset($result['password']) && password_verify($password, $result['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id_from_db'] = $result['id']; // Important pour les pages admin
        $_SESSION['username'] = $result['username'];
        $_SESSION['prenom'] = $result['prenom'];
        $_SESSION['nom'] = $result['nom'];
        $_SESSION['email'] = $result['email'];
        $_SESSION['role'] = $result['role']; // <-- C'est la ligne la plus importante !
        $_SESSION['logged'] = true;
        csrf_token();

        redirect('/accueil.php');
    } else {
        redirect('/connexion.php?error=login');
    }

} else {
    redirect('/connexion.php');
}

?>
