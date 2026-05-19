<?php
// S'assurer que l'ID utilisateur est en session pour les appels API.
if (!isset($_SESSION['user_id_from_db'])) {
    try {
        require_once __DIR__ . '/../config.php';
        $pdo_sidebar = db();

        $stmt_sidebar = $pdo_sidebar->prepare('SELECT id FROM visiteur WHERE username = :username');
        $stmt_sidebar->execute([':username' => $_SESSION['username'] ?? '']);
        $user_id = $stmt_sidebar->fetchColumn();
        if ($user_id) {
            $_SESSION['user_id_from_db'] = $user_id;
        }
    } catch (PDOException $e) {
    }
}

$sidebarRole = $_SESSION['role'] ?? 'user';
?>

<style>
.notification-badge {
    background-color: #e74c3c;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 8px;
    display: none;
}
</style>

<div class="admin-sidebar">
    <h2><?= $sidebarRole === 'seller' ? 'Espace Vendeur' : 'Admin Dashboard' ?></h2>

    <?php if ($sidebarRole === 'seller'): ?>
        <ul>
            <li><a href="../seller/index.php">Tableau de bord</a></li>
            <li><a href="article_new.php" class="<?= basename($_SERVER['PHP_SELF']) === 'article_new.php' ? 'active' : '' ?>">Vendre un article</a></li>
            <li><a href="../seller/my_articles.php">Mes articles</a></li>
            <li><a href="../accueil.php">Retour au site</a></li>
            <li><a href="../logout.php">Deconnexion</a></li>
        </ul>
    <?php else: ?>
        <ul>
            <li><a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : '' ?>">Tableau de bord</a></li>
            <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">Gestion des utilisateurs</a></li>
            <li><a href="articles.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['articles.php', 'article_new.php'], true) ? 'active' : '' ?>">Gestion des articles</a></li>
            <li>
                <a href="chat.php" class="<?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>">
                    Messagerie
                    <span id="chat-notification-badge" class="notification-badge"></span>
                </a>
            </li>
            <li><a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">Gestion des commandes</a></li>
            <li>
                <a href="pending_articles.php" class="<?= basename($_SERVER['PHP_SELF']) === 'pending_articles.php' ? 'active' : '' ?>">
                    Articles Vendeurs
                    <?php
                    try {
                        $pdo_s = db();
                        $cnt = $pdo_s->query("SELECT COUNT(*) FROM articles WHERE approval_status = 'pending' AND author_username IS NOT NULL")->fetchColumn();
                        if ($cnt > 0) {
                            echo '<span class="notification-badge" style="display:inline-block;">' . (int) $cnt . '</span>';
                        }
                    } catch (Throwable $e) {
                    }
                    ?>
                </a>
            </li>
            <li>
                <a href="pending_sellers.php" class="<?= basename($_SERVER['PHP_SELF']) === 'pending_sellers.php' ? 'active' : '' ?>">
                    Candidatures Vendeurs
                    <?php
                    try {
                        $pdo_s = db();
                        $cntS = $pdo_s->query("SELECT COUNT(*) FROM visiteur WHERE seller_verified = 2")->fetchColumn();
                        if ($cntS > 0) {
                            echo '<span class="notification-badge" style="display:inline-block;">' . (int) $cntS . '</span>';
                        }
                    } catch (Throwable $e) {
                    }
                    ?>
                </a>
            </li>
            <li><a href="../accueil.php">Retour au site</a></li>
            <li><a href="../logout.php">Deconnexion</a></li>
        </ul>
    <?php endif; ?>
</div>

<?php if ($sidebarRole === 'admin'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBadge = document.getElementById('chat-notification-badge');
    if (!chatBadge) return;

    async function checkNewMessages() {
        try {
            const response = await fetch('../chat_api.php?action=check_new');
            const data = await response.json();
            if (data.unread_count > 0) {
                chatBadge.textContent = data.unread_count;
                chatBadge.style.display = 'inline-block';
            } else {
                chatBadge.style.display = 'none';
            }
        } catch (error) {
            console.error('Erreur lors de la verification des messages:', error);
        }
    }

    checkNewMessages();
    setInterval(checkNewMessages, 10000);
});
</script>
<?php endif; ?>
