<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_seller();
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre un article · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;--neon-gold:#ffb703;--deep-bg:#020811;--card-bg:rgba(0,20,40,0.82);--sidebar-w:240px;--nav-h:70px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.03) 1px,transparent 1px);background-size:50px 50px;animation:g 10s linear infinite;z-index:0;pointer-events:none;}
        @keyframes g{from{background-position:0 0}to{background-position:50px 50px}}
        .layout{display:flex;min-height:100vh;position:relative;z-index:2;}
        .sidebar{width:var(--sidebar-w);flex-shrink:0;background:rgba(2,8,17,0.92);border-right:1px solid rgba(255,183,3,0.15);backdrop-filter:blur(20px);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;}
        .sidebar-logo{padding:22px 20px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;flex-direction:column;gap:4px;}
        .sidebar-logo-text{font-family:'Orbitron',sans-serif;font-weight:900;font-size:13px;letter-spacing:2px;background:linear-gradient(90deg,var(--neon-gold),var(--neon-green));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .seller-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(255,183,3,0.12);border:1px solid rgba(255,183,3,0.25);border-radius:6px;color:var(--neon-gold);font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;width:fit-content;}
        .sidebar-nav{flex:1;padding:20px 12px;display:flex;flex-direction:column;gap:4px;}
        .nav-label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.22);padding:10px 8px 5px;}
        .nav-item{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,0.5);font-size:14px;transition:all 0.25s;position:relative;}
        .nav-item:hover{color:#fff;background:rgba(255,255,255,0.05);}
        .nav-item.active{color:var(--neon-gold);border:1px solid rgba(255,183,3,0.2);background:rgba(255,183,3,0.06);}
        .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2px;background:var(--neon-gold);border-radius:2px;box-shadow:0 0 8px var(--neon-gold);}
        .nav-icon{font-size:16px;width:20px;text-align:center;}
        .sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.05);}
        .logout-btn{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,68,102,0.7);font-size:14px;transition:all 0.25s;width:100%;}
        .logout-btn:hover{color:var(--neon-red);background:rgba(255,68,102,0.08);}
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
        .topbar{height:var(--nav-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,0.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,183,3,0.1);position:sticky;top:0;z-index:40;}
        .topbar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--neon-gold),transparent);opacity:0.4;}
        .breadcrumb{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:rgba(255,255,255,0.4);}
        .topbar-user{display:flex;align-items:center;gap:10px;padding:8px 16px;border:1px solid rgba(255,183,3,0.2);border-radius:8px;background:rgba(255,183,3,0.05);font-size:13px;}
        .gold-dot{width:8px;height:8px;border-radius:50%;background:var(--neon-gold);box-shadow:0 0 8px var(--neon-gold);animation:p 2s ease-in-out infinite;}
        @keyframes p{0%,100%{opacity:1;}50%{opacity:0.3;}}
        .content{padding:32px 30px 60px;flex:1;}
        .page-heading{font-family:'Orbitron',sans-serif;font-weight:900;font-size:24px;letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--neon-gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
        .page-sub{color:rgba(255,255,255,0.32);font-size:14px;letter-spacing:0.5px;margin-bottom:28px;}
        .notice{padding:14px 18px;background:rgba(255,183,3,0.08);border:1px solid rgba(255,183,3,0.22);border-radius:10px;font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:24px;line-height:1.7;}
        .notice strong{color:var(--neon-gold);}
        .form-card{background:rgba(0,20,40,0.82);border:1px solid rgba(0,207,255,0.12);border-radius:16px;padding:28px;backdrop-filter:blur(14px);}
        .form-card::before{content:'';display:block;height:2px;background:linear-gradient(90deg,transparent,var(--neon-gold),transparent);margin:-28px -28px 24px;border-radius:16px 16px 0 0;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .form-group{display:flex;flex-direction:column;gap:7px;}
        .form-group.full{grid-column:1/-1;}
        label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.35);}
        input[type=text],input[type=number],select,textarea{padding:12px 14px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.18);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;outline:none;transition:border-color 0.3s,box-shadow 0.3s;}
        input:focus,select:focus,textarea:focus{border-color:var(--neon-gold);box-shadow:0 0 14px rgba(255,183,3,0.15);}
        input::placeholder,textarea::placeholder{color:rgba(255,255,255,0.18);}
        textarea{resize:vertical;min-height:130px;}
        select option{background:#0a1628;}
        .file-zone{padding:24px;background:rgba(255,183,3,0.04);border:2px dashed rgba(255,183,3,0.2);border-radius:10px;text-align:center;transition:border-color 0.3s,background 0.3s;cursor:pointer;position:relative;}
        .file-zone:hover,.file-zone:focus-within{border-color:var(--neon-gold);background:rgba(255,183,3,0.08);}
        .file-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
        .file-zone p{font-size:13px;color:rgba(255,255,255,0.4);margin-top:8px;}
        .file-zone strong{color:var(--neon-gold);}
        .preview-grid{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
        .preview-thumb{width:80px;height:80px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,183,3,0.3);}
        .submit-btn{grid-column:1/-1;margin-top:8px;width:100%;padding:15px;background:linear-gradient(135deg,var(--neon-gold),#e69a00);color:#000;border:none;border-radius:12px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:13px;letter-spacing:2px;cursor:pointer;transition:all 0.3s;box-shadow:0 6px 20px rgba(255,183,3,0.3);}
        .submit-btn:hover{transform:translateY(-2px);box-shadow:0 0 24px rgba(255,183,3,0.4);}
        @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:20px 16px 40px;}.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-text">Dribbleur Store</div>
            <div class="seller-badge">⭐ VENDEUR</div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Dashboard</div>
            <a href="index.php" class="nav-item"><span class="nav-icon">📊</span> Vue d'ensemble</a>
            <div class="nav-label">Mes articles</div>
            <a href="submit_article.php" class="nav-item active"><span class="nav-icon">➕</span> Soumettre un article</a>
            <a href="my_articles.php" class="nav-item"><span class="nav-icon">📦</span> Mes articles</a>
            <div class="nav-label">Site</div>
            <a href="../accueil.php" class="nav-item"><span class="nav-icon">🏠</span> Voir la boutique</a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn"><span>🚪</span> Déconnexion</a>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="breadcrumb">Vendeur <span style="color:var(--neon-gold);">/</span> Soumettre un article</div>
            <div class="topbar-user"><span class="gold-dot"></span> <?= htmlspecialchars($username) ?></div>
        </div>
        <div class="content">
            <div class="page-heading">Soumettre un Article</div>
            <div class="page-sub">Votre article sera examiné par l'équipe avant publication sur la boutique.</div>

            <div class="notice">
                <strong>📋 Processus de validation :</strong> Une fois soumis, votre article sera examiné par un admin (généralement sous 24h).
                Si approuvé ✅, il sera visible sur la boutique. Si refusé ❌, vous recevrez un email expliquant le motif.
            </div>

            <form action="../save_article.php" method="POST" enctype="multipart/form-data" id="articleForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-card">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="a_title">Titre de l'article *</label>
                            <input type="text" id="a_title" name="title" placeholder="Ex: Compte eFootball 2025 — OVR 120 — PS5" required>
                        </div>
                        <div class="form-group full">
                            <label for="a_content">Description détaillée *</label>
                            <textarea id="a_content" name="content" placeholder="Décrivez en détail le compte : joueurs, équipements, coins, niveau..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="a_price">Prix (FCFA) *</label>
                            <input type="number" id="a_price" name="price" placeholder="Ex: 15000" min="0" step="500" required>
                        </div>
                        <div class="form-group">
                            <label for="a_platform">Plateforme</label>
                            <select id="a_platform" name="platform">
                                <option value="Multi">Multi (PS4/PS5/Mobile)</option>
                                <option value="PS5">PS5</option>
                                <option value="PS4">PS4</option>
                                <option value="Mobile">Mobile</option>
                                <option value="PC">PC</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="a_delivery">Délai de livraison</label>
                            <select id="a_delivery" name="delivery_time">
                                <option value="Livraison immédiate">Livraison immédiate</option>
                                <option value="Livraison sous 24h">Livraison sous 24h</option>
                                <option value="Livraison a confirmer">À confirmer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="a_binding">Statut de liaison</label>
                            <select id="a_binding" name="binding_status">
                                <option value="Lie a un email">Lié à un email</option>
                                <option value="Non lie">Non lié</option>
                                <option value="Lie a un numero">Lié à un numéro</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Photos du compte * (min. 1, max 5, JPG/PNG, max 2 Mo chacune)</label>
                            <div class="file-zone">
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif" multiple id="photoInput">
                                <div style="font-size:28px;">🖼️</div>
                                <p><strong>Cliquer pour ajouter des photos</strong> ou glisser-déposer</p>
                                <p style="font-size:11px;margin-top:4px;">Captures d'écran du compte — max 2 Mo chacune</p>
                            </div>
                            <div class="preview-grid" id="previewGrid"></div>
                        </div>
                        <button type="submit" class="submit-btn">🚀 Soumettre pour validation</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('photoInput').addEventListener('change', function(){
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    Array.from(this.files).slice(0,5).forEach(file => {
        const r = new FileReader();
        r.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result; img.className = 'preview-thumb';
            grid.appendChild(img);
        };
        r.readAsDataURL(file);
    });
});
</script>
</body>
</html>
