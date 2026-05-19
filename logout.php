<?php
require __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Jeton CSRF invalide');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 4200,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: ' . BASE_URL . '/index.php?status=logged_out');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la deconnexion</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #020811;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .card {
            width: min(92vw, 420px);
            padding: 24px;
            border-radius: 16px;
            background: rgba(0, 20, 40, 0.92);
            border: 1px solid rgba(0, 207, 255, 0.18);
            text-align: center;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        button,
        a {
            min-width: 140px;
            min-height: 44px;
            border-radius: 10px;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font: inherit;
        }
        button {
            background: #00ff88;
            color: #001a0d;
        }
        a {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Confirmer la deconnexion</h1>
        <p>Pour terminer la session en toute securite, confirme la deconnexion.</p>
        <div class="actions">
            <a href="<?= htmlspecialchars(BASE_URL . '/accueil.php') ?>">Annuler</a>
            <form method="post" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <button type="submit">Se deconnecter</button>
            </form>
        </div>
    </div>
</body>
</html>
