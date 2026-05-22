<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps =
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (isset($_SERVER["SERVER_PORT"]) &&
            (int) $_SERVER["SERVER_PORT"] === 443);

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax",
    ]);

    session_start();
}

// Chemin de base du projet — calculé dynamiquement (fonctionne quel que soit l'emplacement XAMPP)
if (!defined("BASE_URL")) {
    // Remonte d'un niveau si on est dans un sous-dossier /admin/
    $scriptDir = dirname($_SERVER["SCRIPT_NAME"]);
    while (in_array(basename($scriptDir), ["admin", "seller"], true)) {
        $scriptDir = dirname($scriptDir);
    }
    define("BASE_URL", rtrim($scriptDir, "/"));
}

// Configuration BDD
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
const DB_NAME = "efootball";
const STORE_ORDER_NOTIFICATION_EMAIL = "";
const STORE_SENDER_EMAIL = "diagneibeu14@gmail.com";
const STORE_SENDER_NAME = "Dribbleur Store";
const STORE_NAME = "Dribbleur Store";
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    );

    return $pdo;
}

function ensure_store_schema(): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo = db();

    try {
        $pdo->exec(
            "ALTER TABLE `articles`
             ADD COLUMN IF NOT EXISTS `product_status` VARCHAR(20) NOT NULL DEFAULT 'available'
             AFTER `binding_status`",
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `wishlist_items` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `article_id` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_user_article` (`user_id`, `article_id`),
                KEY `idx_article_id` (`article_id`),
                CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `visiteur`(`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_wishlist_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "ALTER TABLE `visiteur`
             ADD COLUMN IF NOT EXISTS `telephone` VARCHAR(20) NULL AFTER `email`,
             ADD COLUMN IF NOT EXISTS `adresse` VARCHAR(255) NULL AFTER `telephone`,
             ADD COLUMN IF NOT EXISTS `ville` VARCHAR(120) NULL AFTER `adresse`",
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "ALTER TABLE `articles`
             ADD COLUMN IF NOT EXISTS `approval_status` VARCHAR(20) NOT NULL DEFAULT 'approved'
             AFTER `product_status`",
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "ALTER TABLE `articles`
             ADD COLUMN IF NOT EXISTS `seller_note` TEXT NULL
             AFTER `approval_status`",
        );
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec(
            "ALTER TABLE `articles`
             ADD COLUMN IF NOT EXISTS `author_username` VARCHAR(120) NULL AFTER `seller_note`,
             ADD COLUMN IF NOT EXISTS `author_user_id` INT UNSIGNED NULL AFTER `author_username`",
        );
    } catch (Throwable $e) {
    }

    // Champs KYC vendeur
    try {
        $pdo->exec(
            "ALTER TABLE `visiteur`
             ADD COLUMN IF NOT EXISTS `seller_id_type`   VARCHAR(30)  NULL AFTER `ville`,
             ADD COLUMN IF NOT EXISTS `seller_id_number` VARCHAR(60)  NULL AFTER `seller_id_type`,
             ADD COLUMN IF NOT EXISTS `seller_ine`       VARCHAR(60)  NULL AFTER `seller_id_number`,
             ADD COLUMN IF NOT EXISTS `seller_id_photo`  VARCHAR(255) NULL AFTER `seller_ine`,
             ADD COLUMN IF NOT EXISTS `seller_verified`  TINYINT(1) NOT NULL DEFAULT 0 AFTER `seller_id_photo`",
        );
    } catch (Throwable $e) {
    }

    $initialized = true;
}

function customer_profile_required_fields(): array
{
    return [
        "prenom" => "Prenom",
        "nom" => "Nom",
        "email" => "Email",
        "telephone" => "Telephone",
        "adresse" => "Adresse",
        "ville" => "Ville",
    ];
}

function customer_profile_missing_fields(array $profile): array
{
    $missing = [];
    foreach (customer_profile_required_fields() as $field => $label) {
        $value = trim((string) ($profile[$field] ?? ""));
        // On est plus souple sur la validation pour éviter les blocages intempestifs
        if ($value === "" || strlen($value) < 2) {
            $missing[$field] = $label;
        }
    }

    // Validation email
    if (($profile["email"] ?? "") !== "" && !filter_var((string) $profile["email"], FILTER_VALIDATE_EMAIL)) {
        $missing["email"] = "Email valide";
    }

    // Validation téléphone (on accepte si au moins 8 chiffres pour être plus large)
    $telephone = preg_replace('/\D+/', '', (string) ($profile["telephone"] ?? ""));
    if ($telephone === "" || strlen($telephone) < 8) {
        $missing["telephone"] = "Telephone valide";
    }

    return $missing;
}

function fetch_user_profile_by_id(int $userId): ?array
{
    ensure_store_schema();

    try {
        $stmt = db()->prepare(
            "SELECT id, prenom, nom, email, telephone, adresse, ville, username
             FROM visiteur
             WHERE id = :id
             LIMIT 1",
        );
        $stmt->execute([":id" => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        return $profile ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function current_user_profile(): ?array
{
    $userId = current_user_id();
    if (!$userId) {
        return null;
    }

    return fetch_user_profile_by_id($userId);
}

function send_order_notification_emails(array $payload): void
{
    $merchantEmail = trim(STORE_ORDER_NOTIFICATION_EMAIL);
    $customerEmail = trim((string) ($payload["customer_email"] ?? ""));
    $recipients = array_values(array_unique(array_filter([$merchantEmail, $customerEmail], static function ($email) {
        return $email !== "" && filter_var($email, FILTER_VALIDATE_EMAIL);
    })));

    if ($recipients === []) {
        return;
    }

    $subject = STORE_NAME . " - Nouvelle commande #" . ((int) ($payload["order_id"] ?? 0));
    $lines = [
        "Une commande a ete enregistree sur " . STORE_NAME . ".",
        "",
        "Commande : #" . ((int) ($payload["order_id"] ?? 0)),
        "Client : " . trim((string) ($payload["customer_name"] ?? "")),
        "Email : " . $customerEmail,
        "Telephone profil : " . trim((string) ($payload["profile_phone"] ?? "")),
        "Telephone WhatsApp : " . trim((string) ($payload["payer_phone"] ?? "")),
        "Adresse : " . trim((string) ($payload["address"] ?? "")),
        "Ville : " . trim((string) ($payload["city"] ?? "")),
        "Total : " . trim((string) ($payload["formatted_total"] ?? "")),
        "",
        "Articles :",
        trim((string) ($payload["articles_text"] ?? "")),
    ];

    $body = implode("\r\n", $lines);
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
    ];

    if ($customerEmail !== "" && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $headers[] = "Reply-To: " . $customerEmail;
    }

    foreach ($recipients as $recipient) {
        $sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            error_log("Order email send failed for " . $recipient . " on order #" . ((int) ($payload["order_id"] ?? 0)));
        }
    }
}

function redirect(string $to): never
{
    // Préfixe le chemin de base si le chemin commence par '/'
    $url = str_starts_with($to, "/") ? BASE_URL . $to : $to;
    header("Location: " . $url);
    exit();
}

function require_login(): void
{
    if (empty($_SESSION["logged"]) || $_SESSION["logged"] !== true) {
        redirect("/connexion.php");
    }
}

function current_user_id(): ?int
{
    if (!empty($_SESSION["user_id_from_db"])) {
        return (int) $_SESSION["user_id_from_db"];
    }

    if (empty($_SESSION["username"])) {
        return null;
    }

    try {
        $stmt = db()->prepare("SELECT id FROM visiteur WHERE username = :username LIMIT 1");
        $stmt->execute([":username" => $_SESSION["username"]]);
        $userId = $stmt->fetchColumn();
        if ($userId !== false) {
            $_SESSION["user_id_from_db"] = (int) $userId;
            return (int) $userId;
        }
    } catch (Throwable $e) {
    }

    return null;
}

function article_status_meta(?string $status): array
{
    $normalized = match (strtolower(trim((string) $status))) {
        "reserved", "reserve", "reserver", "réservé", "réserve" => "reserved",
        "sold", "vendu", "vendue" => "sold",
        default => "available",
    };

    return match ($normalized) {
        "reserved" => [
            "value" => "reserved",
            "label" => "Reserve",
            "class" => "status-reserved",
            "color" => "#ffb703",
        ],
        "sold" => [
            "value" => "sold",
            "label" => "Vendu",
            "class" => "status-sold",
            "color" => "#ff5d73",
        ],
        default => [
            "value" => "available",
            "label" => "Disponible",
            "class" => "status-available",
            "color" => "#00ff88",
        ],
    };
}

function fetch_wishlist_article_ids(int $userId): array
{
    ensure_store_schema();

    try {
        $stmt = db()->prepare("SELECT article_id FROM wishlist_items WHERE user_id = :user_id");
        $stmt->execute([":user_id" => $userId]);
        return array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    } catch (Throwable $e) {
        return [];
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? null) !== 'admin') {
        redirect('/index.php');
    }
}

function require_seller(): void
{
    require_login();
    $role = $_SESSION['role'] ?? null;
    if ($role !== 'seller' && $role !== 'admin') {
        redirect('/index.php');
    }
}

function send_article_rejection_email(string $sellerEmail, string $sellerName, string $articleTitle, string $note): bool
{
    if ($sellerEmail === '' || !filter_var($sellerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $safeTitle = htmlspecialchars($articleTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeNote  = htmlspecialchars($note, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeName  = htmlspecialchars($sellerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $subject = 'Votre article a été refusé - ' . STORE_NAME;
    $body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body{font-family:Arial,sans-serif;background:#020811;color:#e0f7ff;margin:0;padding:0;}
        .wrap{max-width:580px;margin:0 auto;background:rgba(0,20,40,0.95);border:1px solid rgba(255,68,102,0.3);border-radius:12px;overflow:hidden;}
        .hd{background:linear-gradient(135deg,#ff4466,#cc0022);padding:28px 24px;text-align:center;color:#fff;}
        .hd h1{margin:0;font-size:22px;letter-spacing:2px;}
        .bd{padding:32px 28px;}
        .note{background:rgba(255,68,102,0.1);border-left:3px solid #ff4466;border-radius:6px;padding:16px;margin:20px 0;font-size:14px;line-height:1.7;}
        .ft{padding:20px;text-align:center;font-size:12px;color:rgba(255,255,255,0.4);border-top:1px solid rgba(255,255,255,0.05);}
    </style>
</head>
<body>
<div class="wrap">
  <div class="hd"><h1>❌ Article Refusé</h1></div>
  <div class="bd">
    <p>Bonjour <strong>{$safeName}</strong>,</p>
    <p>Nous avons examiné votre article <strong>« {$safeTitle} »</strong> et nous ne pouvons malheureusement pas le publier pour le moment.</p>
    <div class="note"><strong style="color:#ff4466;">Motif du refus :</strong><br><br>{$safeNote}</div>
    <p>Vous pouvez soumettre un nouvel article en tenant compte de ces remarques.</p>
    <p style="margin-top:24px;">Cordialement,<br><strong style="color:#00ff88;">L'équipe Dribbleur Store</strong></p>
  </div>
  <div class="ft">© 2025 Dribbleur Store. Tous droits réservés.</div>
</div>
</body>
</html>
HTML;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= sprintf("From: %s <%s>\r\n", STORE_NAME, STORE_SENDER_EMAIL);
    $headers .= sprintf("Reply-To: %s\r\n", STORE_SENDER_EMAIL);

    return mail($sellerEmail, $subject, $body, $headers);
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verify_csrf_token(?string $token): bool
{
    $sessionToken = $_SESSION["csrf_token"] ?? "";

    return is_string($token) &&
        is_string($sessionToken) &&
        $sessionToken !== "" &&
        hash_equals($sessionToken, $token);
}

function require_post(): void
{
    if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
        http_response_code(405);
        exit("Methode non autorisee");
    }
}

/** Dossier uploads/articles (chemin absolu, créé si besoin). */
function articles_upload_dir(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "articles";

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException(
            "Impossible de creer le dossier uploads/articles. Verifiez les droits d ecriture sur le serveur.",
        );
    }

    if (!is_writable($dir)) {
        @chmod($dir, 0775);
    }

    if (!is_writable($dir)) {
        throw new RuntimeException(
            "Le dossier uploads/articles n est pas accessible en ecriture. Ajustez les permissions (chmod 775) ou contactez l hebergeur.",
        );
    }

    return $dir;
}

function upload_error_label(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE => "Fichier trop volumineux (limite PHP upload_max_filesize).",
        UPLOAD_ERR_FORM_SIZE => "Fichier trop volumineux (limite du formulaire).",
        UPLOAD_ERR_PARTIAL => "Transfert interrompu, reessayez.",
        UPLOAD_ERR_NO_FILE => "Aucun fichier recu.",
        UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant sur le serveur.",
        UPLOAD_ERR_CANT_WRITE => "Impossible d ecrire le fichier sur le disque.",
        UPLOAD_ERR_EXTENSION => "Extension bloquee par la configuration PHP.",
        default => "Erreur upload (code " . $code . ").",
    };
}

function detect_upload_image_mime(string $tmpPath): ?string
{
    $mime = null;

    if (function_exists("finfo_open")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $tmpPath) ?: null;
            finfo_close($finfo);
        }
    }

    if ($mime === null && function_exists("mime_content_type")) {
        $mime = mime_content_type($tmpPath) ?: null;
    }

    return is_string($mime) ? strtolower($mime) : null;
}

/**
 * Enregistre les images article. Continue si une image echoue ; remonte les erreurs regroupees.
 *
 * @return array{stored: string[], errors: string[]}
 */
function store_article_uploaded_images(array $files, ?string $destDir = null, int $maxBytes = 5242880): array
{
    $destDir = $destDir ?? articles_upload_dir();
    $destDir = rtrim($destDir, "/\\");

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp",
    ];

    $stored = [];
    $errors = [];

    if (!isset($files["error"]) || !is_array($files["error"])) {
        return ["stored" => $stored, "errors" => $errors];
    }

    foreach ($files["error"] as $index => $error) {
        $error = (int) $error;
        $label = (string) ($files["name"][$index] ?? ("Image " . ((int) $index + 1)));

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = $label . " : " . upload_error_label($error);
            continue;
        }

        $tmpName = (string) ($files["tmp_name"][$index] ?? "");
        $size = (int) ($files["size"][$index] ?? 0);

        if ($tmpName === "" || !is_uploaded_file($tmpName)) {
            $errors[] = $label . " : fichier invalide ou deja traite.";
            continue;
        }

        $mime = detect_upload_image_mime($tmpName);
        if ($mime === null || !isset($allowed[$mime])) {
            $errors[] =
                $label .
                " : format non autorise (JPG, PNG, GIF ou WEBP uniquement).";
            continue;
        }

        if ($size <= 0) {
            $errors[] = $label . " : fichier vide.";
            continue;
        }

        if ($size > $maxBytes) {
            $maxMo = round($maxBytes / 1048576, 1);
            $errors[] = $label . " : trop volumineux (max " . $maxMo . " Mo).";
            continue;
        }

        $fileName = bin2hex(random_bytes(8)) . "." . $allowed[$mime];
        $destPath = $destDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destPath)) {
            $detail = is_writable($destDir)
                ? "verifiez l espace disque et les droits du dossier uploads/articles."
                : "le dossier uploads/articles n est pas inscriptible.";
            $errors[] = $label . " : impossible de deplacer le fichier (" . $detail . ")";
            error_log(
                "move_uploaded_file failed: " .
                    $tmpName .
                    " -> " .
                    $destPath .
                    " | writable=" .
                    (is_writable($destDir) ? "yes" : "no"),
            );
            continue;
        }

        $stored[] = $fileName;
    }

    return ["stored" => $stored, "errors" => $errors];
}
