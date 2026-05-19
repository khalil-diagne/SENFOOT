<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    redirect('/index.php');
}

if (!isset($_SESSION['user_id_from_db'])) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM visiteur WHERE username = :username');
        $stmt->execute([':username' => $_SESSION['username']]);
        $user_id = $stmt->fetchColumn();
        if ($user_id) { $_SESSION['user_id_from_db'] = $user_id; }
        else { die("Erreur critique : Impossible de vérifier votre identité pour le chat."); }
    } catch (PDOException $e) {
        error_log('Test page identity error: ' . $e->getMessage());
        die('Une erreur serveur est survenue lors de l\'initialisation du chat.');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Client - Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --neon-green:#00ff88;--neon-blue:#00cfff;
            --deep-bg:#020811;--card-bg:rgba(0,20,40,0.88);
            --glow-green:0 0 20px rgba(0,255,136,0.5),0 0 60px rgba(0,255,136,0.15);
            --glow-blue:0 0 20px rgba(0,207,255,0.5),0 0 60px rgba(0,207,255,0.15);
            --nav-h:70px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;height:100vh;overflow:hidden;display:flex;flex-direction:column;}

        /* ── BG ── */
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.04) 1px,transparent 1px);background-size:50px 50px;animation:gridMove 10s linear infinite;z-index:0;pointer-events:none;}
        @keyframes gridMove{from{background-position:0 0;}to{background-position:50px 50px;}}
        .orb{position:fixed;border-radius:50%;filter:blur(90px);opacity:0.18;animation:orbFloat linear infinite;z-index:0;pointer-events:none;}
        .orb-1{width:400px;height:400px;background:#00ff88;top:-120px;left:-120px;animation-duration:16s;}
        .orb-2{width:320px;height:320px;background:#00cfff;bottom:-80px;right:-80px;animation-duration:12s;}
        .orb-3{width:250px;height:250px;background:#8b5cf6;top:45%;left:60%;animation-duration:20s;}
        @keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(30px,-20px) scale(1.05);}66%{transform:translate(-18px,30px) scale(0.95);}}
        .scanlines{position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.02) 2px,rgba(0,0,0,0.02) 4px);pointer-events:none;z-index:1;}
        .particles{position:fixed;inset:0;z-index:0;pointer-events:none;}
        .particle{position:absolute;border-radius:50%;animation:particleFly linear infinite;}
        @keyframes particleFly{from{transform:translateY(100vh) translateX(0);opacity:0;}10%{opacity:1;}90%{opacity:1;}to{transform:translateY(-100px) translateX(var(--drift));opacity:0;}}

        /* ── NAV ── */
        nav{position:relative;z-index:100;height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.92);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,207,255,0.15);box-shadow:0 4px 30px rgba(0,0,0,0.5);flex-shrink:0;}
        nav::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-green),var(--neon-blue),transparent);animation:scanH 4s ease-in-out infinite;}
        @keyframes scanH{0%,100%{opacity:0.4;}50%{opacity:1;}}
        .nav-logo{font-family:'Orbitron',sans-serif;font-weight:900;font-size:17px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;}
        .nav-back{display:flex;align-items:center;gap:6px;padding:8px 18px;background:transparent;border:1px solid rgba(0,207,255,0.25);border-radius:8px;color:#e0f7ff;font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:1.5px;text-decoration:none;transition:all 0.3s;}
        .nav-back:hover{border-color:var(--neon-blue);box-shadow:var(--glow-blue);transform:translateY(-2px);}

        /* ── MAIN ── */
        main{flex:1;display:flex;justify-content:center;align-items:center;padding:20px;position:relative;z-index:2;overflow:hidden;}

        /* ── CHAT CONTAINER ── */
        .chat-wrap{
            width:100%;max-width:860px;height:100%;max-height:calc(100vh - var(--nav-h) - 40px);
            background:var(--card-bg);
            border:1px solid rgba(0,207,255,0.18);
            border-radius:22px;
            backdrop-filter:blur(20px);
            box-shadow:0 20px 60px rgba(0,0,0,0.6),var(--glow-blue);
            display:flex;flex-direction:column;
            overflow:hidden;
            animation:cardReveal 0.8s cubic-bezier(0.16,1,0.3,1);
            position:relative;
        }
        @keyframes cardReveal{from{opacity:0;transform:translateY(30px) scale(0.97);}to{opacity:1;transform:translateY(0) scale(1);}}
        .chat-wrap::before,.chat-wrap::after{content:'';position:absolute;width:40px;height:40px;border-color:var(--neon-green);border-style:solid;z-index:1;pointer-events:none;}
        .chat-wrap::before{top:-1px;left:-1px;border-width:2px 0 0 2px;border-radius:22px 0 0 0;}
        .chat-wrap::after{bottom:-1px;right:-1px;border-width:0 2px 2px 0;border-radius:0 0 22px 0;}

        /* ── CHAT HEADER ── */
        .chat-header{
            padding:20px 28px;
            background:linear-gradient(90deg,rgba(0,207,255,0.08),rgba(0,255,136,0.05),transparent);
            border-bottom:1px solid rgba(0,207,255,0.15);
            display:flex;align-items:center;gap:14px;
            position:relative;flex-shrink:0;
        }
        .chat-header::after{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:120px;height:1px;background:linear-gradient(90deg,transparent,var(--neon-blue),transparent);}
        .support-avatar{
            width:46px;height:46px;border-radius:50%;
            background:linear-gradient(135deg,var(--neon-green),var(--neon-blue));
            display:flex;align-items:center;justify-content:center;font-size:20px;
            box-shadow:var(--glow-green);
            animation:logoPulse 3s ease-in-out infinite;
            flex-shrink:0;
        }
        @keyframes logoPulse{0%,100%{box-shadow:var(--glow-green);}50%{box-shadow:0 0 30px rgba(0,255,136,0.8);}}
        .support-info{}
        .support-name{font-family:'Orbitron',sans-serif;font-size:14px;letter-spacing:2px;color:#e0f7ff;margin-bottom:2px;}
        .support-status{font-size:12px;color:var(--neon-green);letter-spacing:1px;display:flex;align-items:center;gap:5px;}
        .status-dot{width:7px;height:7px;border-radius:50%;background:var(--neon-green);box-shadow:0 0 8px var(--neon-green);animation:dotBlink 2s ease-in-out infinite;}
        @keyframes dotBlink{0%,100%{opacity:1;}50%{opacity:0.4;}}

        /* ── MESSAGES ── */
        .chat-messages{
            flex:1;padding:24px;
            overflow-y:auto;
            display:flex;flex-direction:column;gap:14px;
        }
        .chat-messages::-webkit-scrollbar{width:6px;}
        .chat-messages::-webkit-scrollbar-track{background:rgba(255,255,255,0.03);border-radius:10px;}
        .chat-messages::-webkit-scrollbar-thumb{background:linear-gradient(180deg,var(--neon-green),var(--neon-blue));border-radius:10px;box-shadow:0 0 8px rgba(0,207,255,0.4);}

        .message{
            display:flex;flex-direction:column;max-width:72%;
            padding:13px 17px;border-radius:16px;
            line-height:1.55;word-wrap:break-word;
            animation:msgIn 0.35s cubic-bezier(0.16,1,0.3,1);
            position:relative;
        }
        @keyframes msgIn{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}

        .message-content{font-size:15px;margin-bottom:5px;}
        .message-time{font-size:10px;font-family:'Orbitron',sans-serif;letter-spacing:1px;opacity:0.45;align-self:flex-end;}

        .message.sent{
            background:linear-gradient(135deg,rgba(0,207,255,0.2),rgba(0,100,200,0.25));
            border:1px solid rgba(0,207,255,0.2);
            align-self:flex-end;border-bottom-right-radius:4px;
            box-shadow:0 4px 16px rgba(0,100,200,0.2);
        }
        .message.received{
            background:rgba(255,255,255,0.04);
            border:1px solid rgba(255,255,255,0.07);
            align-self:flex-start;border-bottom-left-radius:4px;
            box-shadow:0 4px 16px rgba(0,0,0,0.2);
        }

        /* Sender labels */
        .msg-sender{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;margin-bottom:5px;}
        .message.sent .msg-sender{color:var(--neon-blue);align-self:flex-end;}
        .message.received .msg-sender{color:var(--neon-green);}

        /* ── TYPING INDICATOR ── */
        .typing-indicator{display:none;align-self:flex-start;padding:12px 18px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:16px;border-bottom-left-radius:4px;gap:4px;}
        .typing-indicator.active{display:flex;align-items:center;}
        .typing-indicator span{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--neon-blue);animation:typingBounce 1.4s infinite;}
        .typing-indicator span:nth-child(2){animation-delay:0.2s;}
        .typing-indicator span:nth-child(3){animation-delay:0.4s;}
        @keyframes typingBounce{0%,60%,100%{transform:translateY(0);opacity:0.5;}30%{transform:translateY(-8px);opacity:1;}}

        /* ── INPUT ── */
        .chat-input{
            display:flex;padding:18px 20px;gap:12px;
            border-top:1px solid rgba(0,207,255,0.12);
            background:rgba(2,8,17,0.7);
            flex-shrink:0;align-items:center;
        }

        .input-wrap-msg{flex:1;position:relative;}
        .input-wrap-msg::after{content:'';position:absolute;bottom:0;left:0;width:0;height:2px;background:linear-gradient(90deg,var(--neon-green),var(--neon-blue));border-radius:2px;transition:width 0.4s;}
        .input-wrap-msg:focus-within::after{width:100%;}

        #message-input{
            width:100%;padding:13px 18px;
            background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.15);border-radius:12px;
            color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;letter-spacing:0.5px;
            outline:none;transition:border-color 0.3s,background 0.3s,box-shadow 0.3s;
        }
        #message-input:focus{border-color:var(--neon-green);background:rgba(0,255,136,0.06);box-shadow:0 0 16px rgba(0,255,136,0.12);}
        #message-input::placeholder{color:rgba(255,255,255,0.22);}

        .send-btn{
            position:relative;overflow:hidden;
            width:48px;height:48px;border-radius:12px;flex-shrink:0;
            background:linear-gradient(135deg,var(--neon-green),#00b86b);
            border:none;cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            box-shadow:0 4px 16px rgba(0,255,136,0.3);
            transition:transform 0.2s,box-shadow 0.3s;
        }
        .send-btn:hover{transform:translateY(-2px) scale(1.06);box-shadow:var(--glow-green);}
        .send-btn:active{transform:scale(0.95);}
        .send-btn svg{color:#001a0d;}

        @media(max-width:600px){
            main{padding:0;}
            .chat-wrap{border-radius:0;max-height:calc(100vh - var(--nav-h));}
            .message{max-width:88%;}
            nav{padding:0 16px;}
        }
    </style>
</head>
<body>

    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>
    <div class="scanlines"></div>

    <!-- NAV -->
    <nav>
        <a href="accueil.php" class="nav-logo">Dribbleur Store</a>
        <a href="accueil.php" class="nav-back">← Accueil</a>
    </nav>

    <main>
        <div class="chat-wrap">

            <!-- Header -->
            <div class="chat-header">
                <div class="support-avatar">💬</div>
                <div class="support-info">
                    <div class="support-name">Support Client</div>
                    <div class="support-status">
                        <span class="status-dot"></span>
                        En ligne · Répond rapidement
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chat-messages">
                <div class="message received">
                    <div class="msg-sender">Support</div>
                    <div class="message-content">Bonjour ! Comment puis-je vous aider aujourd'hui ?</div>
                    <div class="message-time">14:32</div>
                </div>
                <div class="message sent">
                    <div class="msg-sender">Vous</div>
                    <div class="message-content">Bonjour, j'ai une question concernant ma commande.</div>
                    <div class="message-time">14:33</div>
                </div>
                <div class="message received">
                    <div class="msg-sender">Support</div>
                    <div class="message-content">Bien sûr, je consulte votre dossier. Pouvez-vous me donner votre numéro de commande ?</div>
                    <div class="message-time">14:33</div>
                </div>

                <!-- Typing -->
                <div class="typing-indicator" id="typingIndicator">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <!-- Input -->
            <form class="chat-input" id="chat-form">
                <div class="input-wrap-msg">
                    <input type="text" id="message-input" placeholder="Écrivez votre message..." autocomplete="off" required>
                </div>
                <button type="submit" class="send-btn" title="Envoyer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </form>
        </div>
    </main>

    <script>
        /* ── Particles ── */
        (function(){
            const c=document.getElementById('particles');
            for(let i=0;i<35;i++){
                const p=document.createElement('div');p.className='particle';
                const g=Math.random()>0.5;
                p.style.cssText=`left:${Math.random()*100}%;animation-duration:${6+Math.random()*10}s;animation-delay:${Math.random()*10}s;--drift:${(Math.random()-.5)*100}px;background:${g?'#00ff88':'#00cfff'};box-shadow:0 0 6px ${g?'#00ff88':'#00cfff'};width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;`;
                c.appendChild(p);
            }
        })();

        /* ── Chat ── */
        const form       = document.getElementById('chat-form');
        const input      = document.getElementById('message-input');
        const messages   = document.getElementById('chat-messages');
        const typingEl   = document.getElementById('typingIndicator');

        function timeNow(){
            const n=new Date();
            return n.getHours().toString().padStart(2,'0')+':'+n.getMinutes().toString().padStart(2,'0');
        }

        function addMessage(text, type) {
            const div = document.createElement('div');
            div.className = 'message ' + type;
            div.innerHTML = `
                <div class="msg-sender">${type==='sent'?'Vous':'Support'}</div>
                <div class="message-content">${escapeHtml(text)}</div>
                <div class="message-time">${timeNow()}</div>
            `;
            messages.insertBefore(div, typingEl);
            messages.scrollTop = messages.scrollHeight;
        }

        function escapeHtml(t){
            const d=document.createElement('div');
            d.appendChild(document.createTextNode(t));
            return d.innerHTML;
        }

        function showTyping(){
            typingEl.classList.add('active');
            messages.scrollTop = messages.scrollHeight;
        }
        function hideTyping(){ typingEl.classList.remove('active'); }

        form.addEventListener('submit', function(e){
            e.preventDefault();
            const text = input.value.trim();
            if(!text) return;
            addMessage(text, 'sent');
            input.value = '';
            input.focus();
            // Simulate support typing
            setTimeout(showTyping, 600);
            setTimeout(()=>{
                hideTyping();
                // Auto-reply
                const replies = [
                    'Je vérifie cela pour vous immédiatement.',
                    'Merci pour votre message, je reviens vers vous dans un instant.',
                    'Bien noté ! Un conseiller va traiter votre demande.',
                    'Avez-vous d\'autres informations à me communiquer ?',
                    'Je comprends, je transmets votre demande à l\'équipe concernée.',
                ];
                addMessage(replies[Math.floor(Math.random()*replies.length)], 'received');
            }, 2200);
        });

        // Enter key
        input.addEventListener('keydown', function(e){
            if(e.key==='Enter' && !e.shiftKey){ form.requestSubmit(); }
        });

        // Scroll to bottom on load
        messages.scrollTop = messages.scrollHeight;
    </script>
</body>
</html>
