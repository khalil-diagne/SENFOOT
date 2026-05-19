<?php
require __DIR__ . '/../config.php';
ensure_store_schema();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/seller/inscription.php'); }
if (empty($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) { die('Jeton CSRF invalide.'); }

$prenom   = trim(htmlspecialchars(strip_tags($_POST['prenom']   ?? '')));
$nom      = trim(htmlspecialchars(strip_tags($_POST['nom']      ?? '')));
$email    = trim(htmlspecialchars(strip_tags($_POST['email']    ?? '')));
$telephone= trim(htmlspecialchars(strip_tags($_POST['telephone']?? '')));
$adresse  = trim(htmlspecialchars(strip_tags($_POST['adresse']  ?? '')));
$ville    = trim(htmlspecialchars(strip_tags($_POST['ville']    ?? '')));
$username = trim(htmlspecialchars(strip_tags($_POST['username'] ?? '')));
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$idType   = trim(htmlspecialchars(strip_tags($_POST['seller_id_type']   ?? '')));
$idNumber = trim(htmlspecialchars(strip_tags($_POST['seller_id_number'] ?? '')));

// Validation
$errors = [];
foreach (['prenom'=>$prenom,'nom'=>$nom,'email'=>$email,'telephone'=>$telephone,
          'adresse'=>$adresse,'ville'=>$ville,'username'=>$username] as $f => $v) {
    if ($v === '') $errors[] = "Le champ «$f» est obligatoire.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';
if (!preg_match('/^[A-Z]/', $username))          $errors[] = 'Le nom d\'utilisateur doit commencer par une majuscule.';
if (strlen($password) < 8)                       $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
if ($password !== $confirm)                       $errors[] = 'Les mots de passe ne correspondent pas.';
if (!preg_match('/^\d{9,15}$/', preg_replace('/\D+/', '', $telephone))) $errors[] = 'Numéro de téléphone invalide.';
if (!in_array($idType, ['CNI','Passeport','Carte_Sejour','Permis']))     $errors[] = 'Type de pièce d\'identité invalide.';
if ($idNumber === '')                             $errors[] = 'Le numéro de pièce d\'identité est obligatoire.';

// Validation photo KYC
$idPhotoPath = null;
if (!isset($_FILES['seller_id_photo']) || $_FILES['seller_id_photo']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'La photo de votre pièce d\'identité est obligatoire.';
} elseif ($_FILES['seller_id_photo']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Erreur lors de l\'upload de la photo (code ' . $_FILES['seller_id_photo']['error'] . ').';
} else {
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['seller_id_photo']['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowedMime[$mime]))                       $errors[] = 'Format de photo invalide (JPG, PNG ou WEBP uniquement).';
    elseif ($_FILES['seller_id_photo']['size'] > 5*1024*1024) $errors[] = 'La photo dépasse 5 Mo.';
    else {
        $destDir = __DIR__ . '/../uploads/kyc';
        if (!is_dir($destDir)) mkdir($destDir, 0750, true);
        $ext      = $allowedMime[$mime];
        $filename = 'kyc_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $destPath = $destDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['seller_id_photo']['tmp_name'], $destPath)) {
            $errors[] = 'Impossible d\'enregistrer la photo. Réessayez.';
        } else {
            $idPhotoPath = $filename;
        }
    }
}

if (!empty($errors)) { die('ERREUR : ' . implode(' ', $errors)); }

$conn = db();
$check = $conn->prepare('SELECT COUNT(*) FROM visiteur WHERE username = :u OR email = :e');
$check->execute([':u' => $username, ':e' => $email]);
if ((int)$check->fetchColumn() > 0) {
    die('ERREUR : Ce nom d\'utilisateur ou cet email existe déjà.');
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare(
        "INSERT INTO visiteur (prenom, nom, email, telephone, adresse, ville, username, password, role,
                               seller_id_type, seller_id_number, seller_id_photo, seller_verified)
         VALUES (:prenom,:nom,:email,:telephone,:adresse,:ville,:username,:password,'seller',
                 :id_type,:id_number,:id_photo,0)"
    );
    $stmt->execute([
        ':prenom'    => $prenom,
        ':nom'       => $nom,
        ':email'     => $email,
        ':telephone' => $telephone,
        ':adresse'   => $adresse,
        ':ville'     => $ville,
        ':username'  => $username,
        ':password'  => $hashed,
        ':id_type'   => $idType,
        ':id_number' => $idNumber,
        ':id_photo'  => $idPhotoPath,
    ]);

    session_regenerate_id(true);
    $_SESSION['username'] = $username;
    $_SESSION['prenom']   = $prenom;
    $_SESSION['nom']      = $nom;
    $_SESSION['email']    = $email;
    $_SESSION['role']     = 'seller';
    $_SESSION['logged']   = true;
    csrf_token();

    redirect('/seller/index.php');
} catch (PDOException $e) {
    error_log('Seller inscription error: ' . $e->getMessage());
    die('Erreur serveur. Veuillez réessayer.');
}
