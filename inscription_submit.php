<?php
require __DIR__ . '/config.php';
$conn = db();
ensure_store_schema();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['prenom']) ||
        empty($_POST['nom']) ||
        empty($_POST['email']) ||
        empty($_POST['telephone']) ||
        empty($_POST['adresse']) ||
        empty($_POST['ville']) ||
        empty($_POST['username']) ||
        empty($_POST['password']) ||
        empty($_POST['confirm_password'])
    ) {
        die('ERREUR: Tous les champs sont obligatoires');
    }

    $prenom = htmlspecialchars(strip_tags($_POST['prenom']));
    $nom = htmlspecialchars(strip_tags($_POST['nom']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $telephone = htmlspecialchars(strip_tags($_POST['telephone']));
    $adresse = htmlspecialchars(strip_tags($_POST['adresse']));
    $ville = htmlspecialchars(strip_tags($_POST['ville']));
    $username = htmlspecialchars(strip_tags($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!preg_match('/^[A-Z]/', $username)) {
        die('ERREUR: Le nom d\'utilisateur doit commencer par une majuscule.');
    }

    if ($password !== $confirm_password) {
        die('ERREUR: LES MOTS DE PASSES NE SONT PAS CORRESPONDANTS');
    }

    if (!preg_match('/^\d{9,15}$/', preg_replace('/\D+/', '', $telephone))) {
        die('ERREUR: Le numero de telephone est invalide.');
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO visiteur (prenom, nom, email, telephone, adresse, ville, username, password, role) VALUES (:prenom, :nom, :email, :telephone, :adresse, :ville, :username, :password, 'user')";

        $checkSql = "SELECT COUNT(*) FROM visiteur WHERE username = :username OR email = :email";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        $exists = (int) $checkStmt->fetchColumn();

        if ($exists > 0) {
            die('ERREUR: Le nom d\'utilisateur ou l\'email existe deja. Choisissez un autre username ou utilisez un autre email.');
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':adresse', $adresse);
        $stmt->bindParam(':ville', $ville);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->execute();

        session_regenerate_id(true);
        $_SESSION['username'] = $username;
        $_SESSION['prenom'] = $prenom;
        $_SESSION['nom'] = $nom;
        $_SESSION['email'] = $email;
        $_SESSION['telephone'] = $telephone;
        $_SESSION['adresse'] = $adresse;
        $_SESSION['ville'] = $ville;
        $_SESSION['role'] = 'user';
        $_SESSION['logged'] = true;
        csrf_token();

        redirect('/accueil.php');
    } catch (PDOException $e) {
        error_log('Erreur SQL inscription: ' . $e->getMessage());
        die('Une erreur serveur est survenue. Veuillez reessayer plus tard.');
    }
}
?>
