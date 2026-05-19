<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
if (!empty($_SESSION['logged']) && in_array($_SESSION['role'] ?? '', ['seller', 'admin'])) {
    redirect('/seller/index.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Vendeur — Inscription · Dribbleur Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--neon-green:#00ff88;--neon-blue:#00cfff;--neon-red:#ff4466;--neon-gold:#ffb703;--deep-bg:#020811;--card-bg:rgba(0,20,40,0.88);--glow-gold:0 0 24px rgba(255,183,3,0.4),0 0 60px rgba(255,183,3,0.1);}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--deep-bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px;overflow-x:hidden;}
        .bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,0.03) 1px,transparent 1px);background-size:50px 50px;animation:gridMove 10s linear infinite;z-index:0;pointer-events:none;}
        @keyframes gridMove{from{background-position:0 0}to{background-position:50px 50px}}
        .orb{position:fixed;border-radius:50%;filter:blur(90px);opacity:0.13;z-index:0;pointer-events:none;}
        .orb-1{width:500px;height:500px;background:#ffb703;top:-200px;right:-150px;}
        .orb-2{width:400px;height:400px;background:#00cfff;bottom:-100px;left:-100px;}
        .card{position:relative;z-index:10;background:var(--card-bg);border:1px solid rgba(255,183,3,0.2);border-radius:24px;padding:44px 40px;width:100%;max-width:620px;backdrop-filter:blur(20px);box-shadow:0 30px 80px rgba(0,0,0,0.6),var(--glow-gold);animation:cardIn 0.7s cubic-bezier(0.16,1,0.3,1);}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--neon-gold),var(--neon-green),transparent);border-radius:24px 24px 0 0;}
        @keyframes cardIn{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;background:rgba(255,183,3,0.1);border:1px solid rgba(255,183,3,0.3);border-radius:20px;color:var(--neon-gold);font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:2px;margin-bottom:14px;}
        .title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:24px;letter-spacing:2px;background:linear-gradient(135deg,#fff,var(--neon-gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
        .subtitle{color:rgba(255,255,255,0.38);font-size:14px;letter-spacing:0.5px;margin-bottom:24px;line-height:1.6;}
        .perks{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;}
        .perk{padding:5px 11px;background:rgba(0,255,136,0.06);border:1px solid rgba(0,255,136,0.15);border-radius:8px;font-size:12px;color:rgba(255,255,255,0.6);}
        .section-label{display:flex;align-items:center;gap:10px;font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:2px;color:var(--neon-gold);margin:22px 0 12px;text-transform:uppercase;}
        .section-label::after{content:'';flex:1;height:1px;background:rgba(255,183,3,0.18);}
        .security-notice{background:rgba(255,183,3,0.07);border:1px solid rgba(255,183,3,0.2);border-radius:10px;padding:14px 16px;font-size:13px;color:rgba(255,255,255,0.6);line-height:1.7;margin-bottom:16px;}
        .security-notice strong{color:var(--neon-gold);}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group.full{grid-column:1/-1;}
        label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,0.35);}
        input,select{padding:12px 14px;background:rgba(0,207,255,0.06);border:1px solid rgba(0,207,255,0.18);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;outline:none;transition:border-color 0.3s,box-shadow 0.3s;}
        input:focus,select:focus{border-color:var(--neon-gold);box-shadow:0 0 14px rgba(255,183,3,0.2);}
        input::placeholder{color:rgba(255,255,255,0.18);}
        select option{background:#0a1628;}
        .pw-wrap{position:relative;}
        .pw-wrap input{width:100%;padding-right:44px;}
        .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.3);font-size:16px;transition:color 0.2s;}
        .toggle-pw:hover{color:var(--neon-gold);}
        .strength-bar{height:3px;border-radius:2px;background:rgba(255,255,255,0.06);margin-top:4px;overflow:hidden;}
        .strength-fill{height:100%;border-radius:2px;transition:width 0.4s,background 0.4s;width:0;}
        .file-wrap{position:relative;cursor:pointer;}
        .file-wrap input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
        .file-box{padding:20px 14px;background:rgba(255,183,3,0.04);border:2px dashed rgba(255,183,3,0.25);border-radius:10px;text-align:center;transition:border-color 0.3s,background 0.3s;}
        .file-wrap:hover .file-box,.file-wrap:focus-within .file-box{border-color:var(--neon-gold);background:rgba(255,183,3,0.08);}
        .file-box p{font-size:13px;color:rgba(255,255,255,0.45);margin-top:6px;}
        .file-box strong{color:var(--neon-gold);}
        .file-preview{display:none;margin-top:10px;}
        .file-preview img{max-width:100%;max-height:160px;border-radius:8px;border:1px solid rgba(255,183,3,0.3);}
        .file-name{font-size:12px;color:var(--neon-green);margin-top:6px;}
        .error-msg{grid-column:1/-1;padding:12px 16px;background:rgba(255,68,102,0.1);border:1px solid rgba(255,68,102,0.3);border-radius:10px;color:var(--neon-red);font-size:13px;display:none;}
        .error-msg.show{display:block;animation:aIn 0.3s ease;}
        @keyframes aIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
        .submit-btn{grid-column:1/-1;margin-top:10px;width:100%;padding:15px;background:linear-gradient(135deg,var(--neon-gold),#e69a00);color:#000;border:none;border-radius:12px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:13px;letter-spacing:2px;cursor:pointer;transition:all 0.3s;box-shadow:0 6px 24px rgba(255,183,3,0.3);}
        .submit-btn:hover{transform:translateY(-3px);box-shadow:var(--glow-gold);}
        .submit-btn:disabled{opacity:0.5;cursor:not-allowed;transform:none;}
        .links{grid-column:1/-1;display:flex;justify-content:center;gap:20px;margin-top:6px;font-size:13px;}
        .links a{color:rgba(255,255,255,0.35);text-decoration:none;transition:color 0.2s;}
        .links a:hover{color:var(--neon-blue);}
        @media(max-width:540px){.card{padding:28px 18px;}.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="orb orb-1"></div><div class="orb orb-2"></div>

<div class="card">
    <div class="badge">⭐ ESPACE VENDEUR</div>
    <div class="title">Devenir Vendeur</div>
    <div class="subtitle">Publiez vos comptes eFootball premium. Une vérification d'identité est requise pour la sécurité de la plateforme.</div>
    <div class="perks">
        <div class="perk">📦 Postez vos articles</div>
        <div class="perk">✅ Validation admin</div>
        <div class="perk">📊 Dashboard dédié</div>
        <div class="perk">🔔 Notifications email</div>
    </div>

    <form id="sellerForm" action="inscription_submit.php" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-grid">
            <div id="errorBox" class="error-msg"></div>

            <div class="section-label full">👤 Informations personnelles</div>

            <div class="form-group">
                <label for="s_prenom">Prénom *</label>
                <input type="text" id="s_prenom" name="prenom" placeholder="Khalil" autocomplete="given-name" required>
            </div>
            <div class="form-group">
                <label for="s_nom">Nom *</label>
                <input type="text" id="s_nom" name="nom" placeholder="Diagne" autocomplete="family-name" required>
            </div>
            <div class="form-group full">
                <label for="s_email">Email *</label>
                <input type="email" id="s_email" name="email" placeholder="vous@exemple.com" autocomplete="email" required>
            </div>
            <div class="form-group full">
                <label for="s_username">Nom d'utilisateur * (commence par une majuscule)</label>
                <input type="text" id="s_username" name="username" placeholder="Ex: KhalilSeller" required>
            </div>
            <div class="form-group">
                <label for="s_telephone">Téléphone *</label>
                <input type="tel" id="s_telephone" name="telephone" placeholder="+221 77 XXX XX XX" required>
            </div>
            <div class="form-group">
                <label for="s_ville">Ville *</label>
                <input type="text" id="s_ville" name="ville" placeholder="Dakar" required>
            </div>
            <div class="form-group full">
                <label for="s_adresse">Adresse *</label>
                <input type="text" id="s_adresse" name="adresse" placeholder="Quartier, rue..." required>
            </div>

            <div class="section-label full">🪪 Vérification d'identité (KYC)</div>

            <div class="security-notice full">
                <strong>🔒 Pourquoi ?</strong> Pour protéger les acheteurs, chaque vendeur doit fournir une pièce d'identité valide.
                Ces données sont <strong>strictement confidentielles</strong> et accessibles uniquement à l'équipe Dribbleur Store.
            </div>

            <div class="form-group">
                <label for="s_id_type">Type de pièce *</label>
                <select id="s_id_type" name="seller_id_type" required>
                    <option value="">— Choisir —</option>
                    <option value="CNI">Carte Nationale d'Identité</option>
                    <option value="Passeport">Passeport</option>
                    <option value="Carte_Sejour">Carte de séjour</option>
                    <option value="Permis">Permis de conduire</option>
                </select>
            </div>
            <div class="form-group">
                <label for="s_id_number">Numéro de la pièce *</label>
                <input type="text" id="s_id_number" name="seller_id_number" placeholder="Ex: 1 234 567 89" required>
            </div>
            <div class="form-group full">
                <label>Photo de la pièce * (recto visible, JPG/PNG/WEBP, max 5 Mo)</label>
                <div class="file-wrap">
                    <input type="file" id="s_id_photo" name="seller_id_photo" accept="image/jpeg,image/png,image/webp" required>
                    <div class="file-box">
                        <div style="font-size:28px;">📷</div>
                        <p><strong>Cliquer pour choisir</strong> ou glisser-déposer</p>
                        <p style="font-size:11px;margin-top:2px;">JPG · PNG · WEBP — max 5 Mo</p>
                    </div>
                </div>
                <div class="file-preview" id="filePreview">
                    <img id="previewImg" src="" alt="Aperçu de votre pièce d'identité">
                    <div class="file-name" id="fileName"></div>
                </div>
            </div>

            <div class="section-label full">🔑 Sécurité</div>

            <div class="form-group full">
                <label for="s_password">Mot de passe * (min. 8 caractères)</label>
                <div class="pw-wrap">
                    <input type="password" id="s_password" name="password" placeholder="••••••••" autocomplete="new-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('s_password',this)">👁</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            </div>
            <div class="form-group full">
                <label for="s_confirm">Confirmer le mot de passe *</label>
                <div class="pw-wrap">
                    <input type="password" id="s_confirm" name="confirm_password" placeholder="••••••••" autocomplete="new-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('s_confirm',this)">👁</button>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">🚀 Soumettre ma demande vendeur</button>
            <div class="links">
                <a href="../index.php">← Retour à l'accueil</a>
                <a href="../inscription.php">Compte client classique</a>
            </div>
        </div>
    </form>
</div>

<script>
function togglePw(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.textContent=i.type==='password'?'👁':'🙈';}
document.getElementById('s_password').addEventListener('input',function(){
    const v=this.value,fill=document.getElementById('strengthFill');
    let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    fill.style.width=(s*25)+'%';fill.style.background=['#ff4466','#ffb703','#00cfff','#00ff88'][s-1]||'transparent';
});
document.getElementById('s_id_photo').addEventListener('change',function(){
    const file=this.files[0],prev=document.getElementById('filePreview'),img=document.getElementById('previewImg'),fn=document.getElementById('fileName'),eb=document.getElementById('errorBox');
    if(file){
        if(file.size>5*1024*1024){eb.textContent='❌ Fichier trop volumineux (max 5 Mo).';eb.className='error-msg show';this.value='';return;}
        const r=new FileReader();r.onload=e=>{img.src=e.target.result;prev.style.display='block';};r.readAsDataURL(file);
        fn.textContent='✓ '+file.name;
    }else{prev.style.display='none';}
});
document.getElementById('sellerForm').addEventListener('submit',function(e){
    const eb=document.getElementById('errorBox');eb.className='error-msg';
    const pw=document.getElementById('s_password').value,cf=document.getElementById('s_confirm').value,
          un=document.getElementById('s_username').value,idT=document.getElementById('s_id_type').value,
          idN=document.getElementById('s_id_number').value.trim(),photo=document.getElementById('s_id_photo').files[0];
    const err=msg=>{e.preventDefault();eb.textContent=msg;eb.className='error-msg show';};
    if(pw!==cf)return err('❌ Les mots de passe ne correspondent pas.');
    if(pw.length<8)return err('❌ Mot de passe trop court (min. 8 caractères).');
    if(!/^[A-Z]/.test(un))return err('❌ Le nom d\'utilisateur doit commencer par une majuscule.');
    if(!idT)return err('❌ Veuillez choisir le type de pièce d\'identité.');
    if(!idN)return err('❌ Veuillez saisir le numéro de votre pièce d\'identité.');
    if(!photo)return err('❌ Veuillez joindre une photo de votre pièce d\'identité.');
    const btn=document.getElementById('submitBtn');btn.textContent='⏳ Envoi...';btn.disabled=true;
});
</script>
</body>
</html>
