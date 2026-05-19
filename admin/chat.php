<?php
require __DIR__ . '/../config.php';
require_admin();

if (!isset($_SESSION['user_id_from_db'])) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM visiteur WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $_SESSION['username']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['id'])) {
            $_SESSION['user_id_from_db'] = (int) $row['id'];
        } else {
            die("Impossible de trouver l'ID de l'administrateur.");
        }
    } catch (PDOException $e) {
        error_log('Admin chat bootstrap error: ' . $e->getMessage());
        die('Une erreur serveur est survenue lors du chargement de la messagerie.');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-blue: #00cfff;
            --neon-red: #ff4466;
            --deep-bg: #020811;
            --panel-bg: rgba(0, 20, 40, 0.82);
            --panel-strong: rgba(3, 14, 28, 0.95);
            --border: rgba(0, 207, 255, 0.15);
            --text-soft: rgba(255, 255, 255, 0.58);
            --glow-green: 0 0 20px rgba(0, 255, 136, 0.45), 0 0 60px rgba(0, 255, 136, 0.12);
            --glow-blue: 0 0 20px rgba(0, 207, 255, 0.45), 0 0 60px rgba(0, 207, 255, 0.12);
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
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            background: rgba(2, 8, 17, 0.92);
            border-right: 1px solid rgba(0, 207, 255, 0.12);
            backdrop-filter: blur(18px);
            padding: 24px 16px;
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
            padding: 28px;
            display: grid;
            gap: 22px;
            min-width: 0;
        }

        .hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 26px 28px;
            border-radius: 24px;
            border: 1px solid var(--border);
            background: linear-gradient(160deg, rgba(0, 207, 255, 0.1), rgba(0, 20, 40, 0.92) 55%);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(18px);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(0, 255, 136, 0.18);
            background: rgba(0, 255, 136, 0.08);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(24px, 3vw, 38px);
            line-height: 1.15;
            letter-spacing: 2px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #fff, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            max-width: 760px;
            color: var(--text-soft);
            font-size: 17px;
            line-height: 1.6;
        }

        .hero-meta {
            display: grid;
            gap: 10px;
            min-width: 220px;
        }

        .meta-box {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.04);
        }

        .meta-box strong {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            letter-spacing: 1.4px;
            color: rgba(255, 255, 255, 0.42);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .meta-box span {
            font-size: 15px;
            color: #fff;
        }

        .chat-shell {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 18px;
            min-height: calc(100vh - 210px);
        }

        .panel {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(18px);
            min-width: 0;
        }

        .sidebar-panel {
            padding: 18px;
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            gap: 16px;
        }

        .panel-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .search-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
        }

        .search-row input,
        .composer textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid rgba(0, 207, 255, 0.14);
            background: rgba(2, 8, 17, 0.78);
            color: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .search-row input {
            padding: 14px 16px;
            font-size: 15px;
        }

        .search-row input:focus,
        .composer textarea:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 4px rgba(0, 207, 255, 0.12);
            transform: translateY(-1px);
        }

        .search-btn,
        .send-btn {
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .search-btn {
            padding: 0 16px;
            min-width: 56px;
            background: rgba(0, 207, 255, 0.12);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 207, 255, 0.18);
        }

        .search-btn:hover,
        .send-btn:hover {
            transform: translateY(-1px);
        }

        .conversations {
            overflow: auto;
            display: grid;
            gap: 10px;
            padding-right: 4px;
        }

        .conversation-item {
            padding: 14px 15px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            transition: 0.22s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .conversation-item:hover,
        .conversation-item.active {
            border-color: rgba(0, 207, 255, 0.2);
            background: rgba(0, 207, 255, 0.08);
            box-shadow: inset 0 0 0 1px rgba(0, 207, 255, 0.06);
        }

        .conversation-user {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 1px;
            color: #001a0d;
            background: linear-gradient(135deg, var(--neon-green), var(--neon-blue));
            flex-shrink: 0;
        }

        .conversation-name {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-sub {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.42);
        }

        .unread-badge,
        .notification-badge {
            background-color: var(--neon-red);
            color: white;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            font-weight: bold;
            min-width: 24px;
            text-align: center;
        }

        .notification-badge {
            display: none;
        }

        .chat-panel {
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            min-height: 0;
            overflow: hidden;
        }

        .chat-header {
            padding: 22px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .chat-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            letter-spacing: 1.4px;
        }

        .chat-header p {
            color: rgba(255, 255, 255, 0.46);
            font-size: 13px;
            margin-top: 4px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--neon-green);
            box-shadow: 0 0 12px var(--neon-green);
            flex-shrink: 0;
        }

        .empty-chat,
        .empty-conversations {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: rgba(255, 255, 255, 0.38);
            padding: 32px;
            line-height: 1.55;
        }

        .messages {
            overflow: auto;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.02), transparent 24%),
                rgba(2, 8, 17, 0.26);
        }

        .message-row {
            display: flex;
        }

        .message-row.sent {
            justify-content: flex-end;
        }

        .message-row.received {
            justify-content: flex-start;
        }

        .message {
            max-width: min(75%, 640px);
            padding: 14px 16px 12px;
            border-radius: 20px;
            line-height: 1.5;
            word-break: break-word;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .message.sent {
            background: linear-gradient(135deg, rgba(0, 207, 255, 0.9), rgba(0, 255, 136, 0.75));
            color: #001a0d;
            border-bottom-right-radius: 6px;
        }

        .message.received {
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-bottom-left-radius: 6px;
        }

        .message-time {
            display: block;
            margin-top: 7px;
            font-size: 11px;
            opacity: 0.75;
            text-align: right;
        }

        .composer {
            padding: 18px 20px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .composer textarea {
            resize: none;
            min-height: 62px;
            max-height: 180px;
            padding: 16px;
            font-family: inherit;
            font-size: 16px;
            line-height: 1.45;
        }

        .send-btn {
            min-height: 58px;
            padding: 0 22px;
            background: linear-gradient(135deg, var(--neon-green), #00b86b);
            color: #001a0d;
            box-shadow: var(--glow-green);
            letter-spacing: 1.3px;
            font-size: 11px;
        }

        .send-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .notif {
            position: fixed;
            right: 20px;
            top: 20px;
            min-width: 260px;
            max-width: min(92vw, 420px);
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(0, 255, 136, 0.25);
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.18), rgba(0, 207, 255, 0.16));
            color: #f1ffff;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.32);
            backdrop-filter: blur(14px);
            z-index: 3000;
            animation: notifIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .notif[data-type="error"] {
            border-color: rgba(255, 80, 110, 0.45);
            background: linear-gradient(135deg, rgba(255, 70, 100, 0.2), rgba(255, 120, 120, 0.15));
        }

        .notif[data-type="warning"] {
            border-color: rgba(255, 186, 0, 0.4);
            background: linear-gradient(135deg, rgba(255, 186, 0, 0.18), rgba(255, 120, 0, 0.15));
        }

        .notif-title {
            display: block;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .notif-text {
            font-size: 13px;
            line-height: 1.45;
        }

        @keyframes notifIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1150px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                border-right: none;
                border-bottom: 1px solid rgba(0, 207, 255, 0.12);
            }

            .hero {
                flex-direction: column;
            }
        }

        @media (max-width: 900px) {
            .content {
                padding: 18px;
            }

            .chat-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .sidebar-panel {
                max-height: 340px;
            }

            .chat-panel {
                min-height: 65vh;
            }
        }

        @media (max-width: 620px) {
            .hero,
            .sidebar-panel,
            .chat-panel {
                border-radius: 20px;
            }

            .hero {
                padding: 20px;
            }

            .chat-header,
            .messages,
            .composer,
            .sidebar-panel {
                padding-left: 16px;
                padding-right: 16px;
            }

            .search-row,
            .composer {
                grid-template-columns: 1fr;
            }

            .send-btn,
            .search-btn {
                width: 100%;
            }

            .message {
                max-width: 88%;
            }

            .notif {
                left: 14px;
                right: 14px;
                top: auto;
                bottom: 14px;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="content">
            <section class="hero">
                <div>
                    <div class="eyebrow">Support admin en direct</div>
                    <h1>Messagerie client repensee pour suivre les conversations plus facilement.</h1>
                    <p>Retrouve rapidement un utilisateur, lis les messages dans un espace plus propre et reponds sans perdre le fil des echanges.</p>
                </div>

                <div class="hero-meta">
                    <div class="meta-box">
                        <strong>Actualisation</strong>
                        <span>Toutes les 4 secondes sur la conversation active</span>
                    </div>
                    <div class="meta-box">
                        <strong>Recherche</strong>
                        <span>Filtre instantane par nom d utilisateur</span>
                    </div>
                </div>
            </section>

            <section class="chat-shell">
                <aside class="panel sidebar-panel">
                    <div class="panel-title">Conversations</div>

                    <div class="search-row">
                        <input type="text" id="user-search-input" placeholder="Rechercher un utilisateur...">
                        <button id="user-search-button" class="search-btn" type="button">Chercher</button>
                    </div>

                    <div class="conversations" id="conversations-list"></div>
                </aside>

                <section class="panel chat-panel">
                    <div class="chat-header">
                        <div>
                            <h2 id="chat-title">Aucune conversation selectionnee</h2>
                            <p id="chat-subtitle">Choisis un utilisateur dans la colonne de gauche pour demarrer.</p>
                        </div>
                        <span class="status-dot" aria-hidden="true"></span>
                    </div>

                    <div class="empty-chat" id="no-conversation-selected">
                        <div>
                            <p>Selectionne une conversation pour afficher l historique et envoyer une reponse.</p>
                        </div>
                    </div>

                    <div class="messages" id="chat-messages" style="display:none;"></div>

                    <form class="composer" id="chat-form" style="display:none;">
                        <input type="hidden" id="receiver-id" name="receiver_id" value="">
                        <textarea id="message-input" placeholder="Ecris ta reponse ici..." autocomplete="off"></textarea>
                        <button type="submit" class="send-btn">Envoyer</button>
                    </form>
                </section>
            </section>
        </main>
    </div>

    <script>
    (function () {
        const conversationsList = document.getElementById('conversations-list');
        const noConversation = document.getElementById('no-conversation-selected');
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const receiverIdInput = document.getElementById('receiver-id');
        const userSearchInput = document.getElementById('user-search-input');
        const userSearchButton = document.getElementById('user-search-button');
        const chatTitle = document.getElementById('chat-title');
        const chatSubtitle = document.getElementById('chat-subtitle');
        const currentUserId = <?php echo (int) ($_SESSION['user_id_from_db'] ?? 0); ?>;

        let selectedUserId = null;
        let refreshInterval = null;

        function notify(message, type = 'success', title = 'Information') {
            const notification = document.createElement('div');
            notification.className = 'notif';
            notification.dataset.type = type;
            notification.innerHTML = '<span class="notif-title"></span><span class="notif-text"></span>';
            notification.querySelector('.notif-title').textContent = title;
            notification.querySelector('.notif-text').textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.transition = 'opacity 0.35s';
                notification.style.opacity = '0';
            }, 2400);
            setTimeout(() => notification.remove(), 2800);
        }

        function initials(name) {
            const clean = (name || 'U').trim();
            return clean.slice(0, 2).toUpperCase();
        }

        function escapeHtml(value) {
            return (value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderConversations(users) {
            const search = (userSearchInput.value || '').trim().toLowerCase();
            const filtered = search
                ? users.filter(user => (user.username || '').toLowerCase().includes(search))
                : users;

            if (!filtered.length) {
                conversationsList.innerHTML = '<div class="empty-conversations">Aucun utilisateur ne correspond a la recherche.</div>';
                return;
            }

            conversationsList.innerHTML = filtered.map(user => {
                const unread = Number(user.unread_count || 0);
                return `
                    <button class="conversation-item${selectedUserId === Number(user.id) ? ' active' : ''}" type="button" data-user-id="${Number(user.id)}" data-username="${escapeHtml(user.username || 'Utilisateur')}">
                        <div class="conversation-user">
                            <span class="avatar">${initials(user.username)}</span>
                            <div>
                                <div class="conversation-name">${escapeHtml(user.username || 'Utilisateur')}</div>
                                <div class="conversation-sub">${unread > 0 ? unread + ' nouveau(x) message(s)' : 'Conversation disponible'}</div>
                            </div>
                        </div>
                        ${unread > 0 ? '<span class="unread-badge">' + unread + '</span>' : ''}
                    </button>
                `;
            }).join('');

            conversationsList.querySelectorAll('.conversation-item').forEach(element => {
                element.addEventListener('click', function () {
                    selectedUserId = Number(element.dataset.userId);
                    const username = element.dataset.username || 'Utilisateur';

                    receiverIdInput.value = String(selectedUserId);
                    chatTitle.textContent = 'Conversation avec ' + username;
                    chatSubtitle.textContent = 'Les messages se rafraichissent automatiquement.';
                    noConversation.style.display = 'none';
                    chatMessages.style.display = 'flex';
                    chatForm.style.display = 'grid';

                    renderConversations(users);
                    loadMessages();

                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }
                    refreshInterval = setInterval(loadMessages, 4000);
                });
            });
        }

        function loadConversations() {
            fetch('../chat_api.php?action=get_conversations')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        renderConversations(data);
                    }
                })
                .catch(error => {
                    console.error(error);
                    notify('Impossible de charger la liste des conversations.', 'error', 'Chargement echoue');
                });
        }

        function loadMessages() {
            if (!selectedUserId) {
                return;
            }

            fetch('../chat_api.php?action=fetch&user_id=' + selectedUserId)
                .then(response => response.json())
                .then(messages => {
                    if (!Array.isArray(messages)) {
                        return;
                    }

                    chatMessages.innerHTML = messages.map(message => {
                        const isSent = Number(message.sender_id) === currentUserId;
                        const time = message.timestamp
                            ? new Date(message.timestamp).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                            : '';
                        return `
                            <div class="message-row ${isSent ? 'sent' : 'received'}">
                                <div class="message ${isSent ? 'sent' : 'received'}">
                                    <div>${escapeHtml(message.message || '')}</div>
                                    <span class="message-time">${time}</span>
                                </div>
                            </div>
                        `;
                    }).join('');

                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error(error);
                    notify('Impossible de charger les messages de cette conversation.', 'error', 'Chargement echoue');
                });
        }

        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = (messageInput.value || '').trim();

            if (!selectedUserId) {
                notify('Choisis d abord une conversation.', 'warning', 'Conversation requise');
                return;
            }

            if (!message) {
                notify('Le message ne peut pas etre vide.', 'warning', 'Message vide');
                return;
            }

            const formData = new FormData();
            formData.append('message', message);
            formData.append('receiver_id', selectedUserId);

            const submitButton = chatForm.querySelector('.send-btn');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Envoi...';

            fetch('../chat_api.php?action=send', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages();
                        loadConversations();
                        notify('Le message a bien ete envoye.', 'success', 'Message envoye');
                    } else {
                        notify(data.message || 'Erreur pendant l envoi du message.', 'error', 'Envoi echoue');
                    }
                })
                .catch(error => {
                    console.error(error);
                    notify('Erreur reseau pendant l envoi du message.', 'error', 'Envoi echoue');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
        });

        messageInput.addEventListener('input', function () {
            messageInput.style.height = 'auto';
            messageInput.style.height = Math.min(messageInput.scrollHeight, 180) + 'px';
        });

        userSearchInput.addEventListener('input', loadConversations);
        userSearchButton.addEventListener('click', loadConversations);

        loadConversations();
        setInterval(loadConversations, 15000);
    })();
    </script>
</body>
</html>
