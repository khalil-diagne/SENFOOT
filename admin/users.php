<?php
require __DIR__ . '/../config.php';
require_admin();
$pdo = db();

$message = ''; $error = '';

// Get current admin ID
$stmtAdmin = $pdo->prepare('SELECT id FROM visiteur WHERE username = :username');
$stmtAdmin->execute([':username' => $_SESSION['username']]);
$_SESSION['user_id_from_db'] = $stmtAdmin->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Jeton de sécurité invalide.';
    } else {
        $userId = $_POST['user_id'] ?? null;

        if (isset($_POST['action']) && $_POST['action'] === 'delete' && $userId) {
            if ($userId == ($_SESSION['user_id_from_db'] ?? null)) {
                $error = 'Vous ne pouvez pas supprimer votre propre compte.';
            } else {
                $pdo->prepare('DELETE FROM visiteur WHERE id = :id')->execute([':id' => $userId]);
                $message = 'Utilisateur supprimé avec succès.';
            }
        }

        if (isset($_POST['action']) && $_POST['action'] === 'change_role' && $userId) {
            $newRole = $_POST['new_role'] ?? 'user';
            if ($userId == ($_SESSION['user_id_from_db'] ?? null)) {
                $error = 'Vous ne pouvez pas modifier votre propre rôle.';
            } elseif ($newRole === 'admin' || $newRole === 'user' || $newRole === 'seller') {
                $pdo->prepare('UPDATE visiteur SET role = :role WHERE id = :id')->execute([':role'=>$newRole,':id'=>$userId]);
                $message = 'Rôle mis à jour avec succès.';
            }
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$users = $pdo->query('SELECT id, username, prenom, nom, email, role FROM visiteur ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$totalAdmins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$totalUsers  = count(array_filter($users, fn($u) => $u['role'] === 'user'));
$totalSellers = count(array_filter($users, fn($u) => $u['role'] === 'seller'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Admin · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --panel-strong: rgba(3, 14, 28, 0.95);
            --border: rgba(0, 207, 255, 0.16);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
            --sidebar-w: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Rajdhani', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(0, 255, 136, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(0, 207, 255, 0.12), transparent 28%),
                var(--deep-bg);
            color: #fff;
            overflow-x: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 207, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 207, 255, 0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.55;
            pointer-events: none;
            z-index: 0;
        }

        .layout {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .admin-sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 2px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }

        .admin-sidebar ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .admin-sidebar a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.72);
            background: transparent;
            border: 1px solid transparent;
            transition: 0.25s ease;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            color: #fff;
            border-color: rgba(0, 207, 255, 0.14);
            background: rgba(0, 207, 255, 0.08);
            box-shadow: inset 0 0 0 1px rgba(0, 207, 255, 0.06);
        }

        .content {
            padding: 32px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, var(--neon-red), #ff8844);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-desc {
            color: var(--text-soft);
            font-size: 16px;
        }

        .mini-stats {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .mini-stat {
            padding: 16px 24px;
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.3s;
        }

        .mini-stat:hover {
            transform: translateY(-4px);
            border-color: var(--neon-blue);
        }

        .mini-stat-val {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 24px;
            color: var(--neon-blue);
        }

        .mini-stat-label {
            font-size: 12px;
            letter-spacing: 1px;
            color: var(--text-soft);
            text-transform: uppercase;
            font-family: 'Orbitron', sans-serif;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: alertIn 0.4s ease;
        }

        @keyframes alertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.2); color: var(--neon-green); }
        .alert-error { background: rgba(255, 68, 102, 0.08); border: 1px solid rgba(255, 68, 102, 0.2); color: var(--neon-red); }

        .table-wrap {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            backdrop-filter: blur(18px);
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 2px;
            color: rgba(255, 255, 255, 0.4);
            padding: 18px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-transform: uppercase;
        }

        tbody td {
            padding: 16px 20px;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            transition: background 0.2s;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-av {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 700;
            background: rgba(0, 207, 255, 0.1);
            border: 1px solid rgba(0, 207, 255, 0.2);
            color: var(--neon-blue);
        }

        .user-av.admin-av {
            background: rgba(255, 68, 102, 0.1);
            border-color: rgba(255, 68, 102, 0.3);
            color: var(--neon-red);
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .role-pill.admin { background: rgba(255, 68, 102, 0.1); border: 1px solid var(--neon-red); color: var(--neon-red); }
        .role-pill.user { background: rgba(0, 207, 255, 0.1); border: 1px solid var(--neon-blue); color: var(--neon-blue); }
        .role-pill.seller { background: rgba(0, 255, 136, 0.1); border: 1px solid var(--neon-green); color: var(--neon-green); }

        .role-select {
            padding: 8px 12px;
            background: rgba(2, 8, 17, 0.8);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .role-select:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 10px rgba(0, 207, 255, 0.2);
        }

        .btn-delete {
            padding: 8px 16px;
            border-radius: 10px;
            background: rgba(255, 68, 102, 0.1);
            border: 1px solid rgba(255, 68, 102, 0.3);
            color: var(--neon-red);
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
        }

        .btn-delete:hover {
            background: var(--neon-red);
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 68, 102, 0.4);
        }

        .me-badge {
            padding: 4px 10px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            border-radius: 8px;
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            text-transform: uppercase;
        }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; }
            .admin-sidebar { display: none; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">Gestion des Utilisateurs</h1>
                <p class="page-desc">Visualisez et gérez les comptes utilisateurs, modifiez les rôles ou supprimez des accès.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-stat-val"><?= count($users) ?></div>
                    <div class="mini-stat-label">Total</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-val" style="color:var(--neon-red);"><?= $totalAdmins ?></div>
                    <div class="mini-stat-label">Admins</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-val" style="color:var(--neon-green);"><?= $totalSellers ?></div>
                    <div class="mini-stat-label">Vendeurs</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-val" style="color:var(--neon-blue);"><?= $totalUsers ?></div>
                    <div class="mini-stat-label">Clients</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle Actuel</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php $isMe = ($u['id'] == $_SESSION['user_id_from_db']); ?>
                            <tr>
                                <td style="font-family:'Orbitron',sans-serif; font-size:12px; opacity:0.5;">#<?= $u['id'] ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-av <?= $u['role'] === 'admin' ? 'admin-av' : '' ?>">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                                            <div style="font-size:12px; opacity:0.6;">@<?= htmlspecialchars($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="opacity:0.7;"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="role-pill <?= $u['role'] ?>">
                                        <?= $u['role'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isMe): ?>
                                        <span class="me-badge">C'est vous</span>
                                    <?php else: ?>
                                        <div style="display:flex; gap:10px; align-items:center;">
                                            <form method="POST" style="display:flex; gap:8px;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="action" value="change_role">
                                                <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                    <option value="user" <?= $u['role']==='user'?'selected':'' ?>>Client</option>
                                                    <option value="seller" <?= $u['role']==='seller'?'selected':'' ?>>Vendeur</option>
                                                    <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                                </select>
                                            </form>
                                            
                                            <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur définitivement ?');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn-delete">Supprimer</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
