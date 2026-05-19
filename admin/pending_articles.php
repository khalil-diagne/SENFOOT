<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_admin();

$pdo = db();

try { $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_username` VARCHAR(120) NULL AFTER `seller_note`"); } catch(Throwable $e){}
try { $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_user_id` INT UNSIGNED NULL AFTER `author_username`"); } catch(Throwable $e){}

$filter = in_array($_GET['filter'] ?? '', ['pending','approved','rejected']) ? $_GET['filter'] : 'pending';

$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.price, a.image, a.approval_status, a.seller_note,
            a.author_username, a.created_at,
            v.email as seller_email, v.prenom, v.nom
     FROM articles a
     LEFT JOIN visiteur v ON v.username = a.author_username
     WHERE a.author_username IS NOT NULL AND a.approval_status = :s
     ORDER BY a.created_at DESC"
);
$stmt->execute([':s' => $filter]);
$articles = $stmt->fetchAll();

// Comptes badges
$counts = $pdo->query(
    "SELECT approval_status, COUNT(*) as cnt FROM articles WHERE author_username IS NOT NULL GROUP BY approval_status"
)->fetchAll();
$cntMap = [];
foreach ($counts as $c) $cntMap[$c['approval_status']] = (int)$c['cnt'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Articles en attente · Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style/admin_styles.css">
<style>
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;}
.fbi{padding:9px 20px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(255,255,255,.45);font-family:'Rajdhani',sans-serif;font-size:14px;text-decoration:none;transition:all .25s;display:flex;align-items:center;gap:7px;}
.fbi:hover,.fbi.active{border-color:rgba(0,207,255,.35);color:#00cfff;background:rgba(0,207,255,.07);}
.cnt{background:rgba(0,207,255,.2);color:#00cfff;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700;}
.cnt.gold{background:rgba(255,183,3,.2);color:#ffb703;}
.cnt.red{background:rgba(255,68,102,.2);color:#ff4466;}
.cnt.green{background:rgba(0,255,136,.2);color:#00ff88;}
.art-row{background:rgba(0,20,40,.6);border:1px solid rgba(0,207,255,.1);border-radius:12px;padding:20px;margin-bottom:16px;display:flex;gap:20px;align-items:flex-start;transition:border-color .25s;flex-wrap:wrap;min-width:0;}
.art-row:hover{border-color:rgba(0,207,255,.25);}
.art-img{width:80px;height:80px;border-radius:10px;object-fit:cover;border:1px solid rgba(255,255,255,.1);flex-shrink:0;}
.art-img-ph{width:80px;height:80px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;}
.art-info{flex:1;min-width:0;overflow-wrap:anywhere;}
.art-title{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:1px;color:#e0f7ff;margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.art-meta{color:rgba(255,255,255,.35);font-size:13px;margin-bottom:8px;word-break:break-word;}
.art-meta strong{color:rgba(255,255,255,.6);}
.art-price{font-family:'Orbitron',sans-serif;font-size:12px;color:#ffb703;margin-bottom:10px;}
.art-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.btn-approve{padding:9px 20px;border:none;border-radius:8px;background:linear-gradient(135deg,#00ff88,#00b86b);color:#001a0d;font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;font-weight:700;cursor:pointer;transition:all .3s;}
.btn-approve:hover{box-shadow:0 0 16px rgba(0,255,136,.4);transform:translateY(-1px);}
.btn-reject{padding:9px 20px;border:1px solid rgba(255,68,102,.3);border-radius:8px;background:rgba(255,68,102,.08);color:#ff4466;font-family:'Orbitron',sans-serif;font-size:10px;letter-spacing:1.5px;font-weight:700;cursor:pointer;transition:all .3s;}
.btn-reject:hover{background:rgba(255,68,102,.15);border-color:rgba(255,68,102,.5);}
.apb{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.2px;}
.apb.approved{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.22);color:#00ff88;}
.apb.rejected{background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:#ff4466;}
.note-shown{color:rgba(255,68,102,.7);font-size:13px;font-style:italic;margin-top:4px;}
.empty{text-align:center;padding:80px 20px;color:rgba(255,255,255,.25);}
.empty .ei{font-size:52px;margin-bottom:14px;}
.admin-main{min-width:0;}
@media (max-width: 900px) {
    .art-row{flex-wrap:wrap;}
    .art-img,
    .art-img-ph{width:100%;height:auto;}
    .art-actions{width:100%;justify-content:flex-start;}
    .art-title,
    .art-meta{white-space:normal;}
    .art-info{min-width:0;}
}
/* Modal rejet */
.overlay{display:none;position:fixed;inset:0;background:rgba(2,8,17,.8);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;}
.overlay.open{display:flex;}
.modal{background:rgba(0,20,40,.97);border:1px solid rgba(255,68,102,.2);border-radius:16px;padding:36px;max-width:480px;width:90%;animation:mi .35s cubic-bezier(.16,1,.3,1);}
@keyframes mi{from{opacity:0;transform:scale(.9) translateY(16px)}to{opacity:1;transform:none}}
.modal h3{font-family:'Orbitron',sans-serif;font-size:15px;letter-spacing:2px;color:#ff4466;margin-bottom:18px;}
.modal label{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,.35);display:block;margin-bottom:7px;}
.modal textarea{width:100%;padding:12px 14px;background:rgba(255,68,102,.05);border:1px solid rgba(255,68,102,.2);border-radius:10px;color:#e0f7ff;font-family:'Rajdhani',sans-serif;font-size:15px;outline:none;resize:vertical;min-height:110px;transition:border-color .3s;}
.modal textarea:focus{border-color:#ff4466;}
.modal-actions{display:flex;gap:10px;margin-top:18px;justify-content:flex-end;}
.btn-cancel{padding:10px 20px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:transparent;color:rgba(255,255,255,.5);font-family:'Rajdhani',sans-serif;font-size:14px;cursor:pointer;transition:all .25s;}
.btn-cancel:hover{border-color:rgba(255,255,255,.25);color:#fff;}
</style>
</head>
<body>
<div class="admin-container">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Articles Vendeurs</h1>
            <p>Examinez et approuvez ou refusez les articles soumis par les vendeurs.</p>
        </div>

        <div class="filter-bar">
            <a href="?filter=pending"  class="fbi <?= $filter==='pending'  ? 'active':'' ?>">⏳ En attente <span class="cnt gold"><?= $cntMap['pending']  ?? 0 ?></span></a>
            <a href="?filter=approved" class="fbi <?= $filter==='approved' ? 'active':'' ?>">✅ Approuvés  <span class="cnt green"><?= $cntMap['approved'] ?? 0 ?></span></a>
            <a href="?filter=rejected" class="fbi <?= $filter==='rejected' ? 'active':'' ?>">❌ Refusés   <span class="cnt red"><?= $cntMap['rejected'] ?? 0 ?></span></a>
        </div>

        <?php if (empty($articles)): ?>
        <div class="empty"><div class="ei"><?= $filter==='pending' ? '🎉' : '📦' ?></div><p>Aucun article <?= $filter==='pending' ? 'en attente' : ($filter==='approved' ? 'approuvé' : 'refusé') ?>.</p></div>
        <?php else: ?>
        <?php foreach ($articles as $a):
            $img = $a['image'] ? BASE_URL.'/uploads/articles/'.htmlspecialchars($a['image']) : '';
            $sellerName = trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?: ($a['author_username'] ?? 'Vendeur inconnu');
        ?>
        <div class="art-row" id="row-<?= $a['id'] ?>">
            <?php if($img): ?><img src="<?=$img?>" class="art-img" alt=""><?php else: ?><div class="art-img-ph">🖼️</div><?php endif; ?>
            <div class="art-info">
                <div class="art-title"><?= htmlspecialchars($a['title']) ?></div>
                <div class="art-meta">
                    Vendeur : <strong><?= htmlspecialchars($sellerName) ?></strong>
                    (<?= htmlspecialchars($a['author_username'] ?? '') ?>)
                    — Email : <strong><?= htmlspecialchars($a['seller_email'] ?? 'N/A') ?></strong>
                    — Soumis le <?= date('d/m/Y à H:i', strtotime($a['created_at'])) ?>
                </div>
                <div class="art-price"><?= number_format((float)$a['price'], 0, ',', ' ') ?> FCFA</div>

                <?php if ($filter === 'pending'): ?>
                <div class="art-actions">
                    <button class="btn-approve" onclick="doApprove(<?= $a['id'] ?>, this)">✅ Approuver</button>
                    <button class="btn-reject"  onclick="openRejectModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['seller_email'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($a['title']), ENT_QUOTES) ?>')">❌ Refuser</button>
                </div>
                <?php else: ?>
                <span class="apb <?= $a['approval_status'] ?>">
                    <?= $a['approval_status']==='approved' ? '✅ Approuvé' : '❌ Refusé' ?>
                </span>
                <?php if (!empty($a['seller_note'])): ?>
                    <div class="note-shown">Motif : <?= htmlspecialchars($a['seller_note']) ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal rejet -->
<div class="overlay" id="rejectOverlay">
    <div class="modal">
        <h3>❌ Motif du refus</h3>
        <input type="hidden" id="rejectId">
        <input type="hidden" id="rejectEmail">
        <input type="hidden" id="rejectTitle">
        <label for="rejectNote">Message envoyé au vendeur par email *</label>
        <textarea id="rejectNote" placeholder="Ex: Les photos ne sont pas suffisamment claires. Veuillez soumettre des captures plus lisibles..."></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeRejectModal()">Annuler</button>
            <button class="btn-reject" onclick="doReject()">Envoyer le refus</button>
        </div>
    </div>
</div>

<script>
function openRejectModal(id, email, title) {
    document.getElementById('rejectId').value    = id;
    document.getElementById('rejectEmail').value = email;
    document.getElementById('rejectTitle').value = title;
    document.getElementById('rejectNote').value  = '';
    document.getElementById('rejectOverlay').classList.add('open');
}
function closeRejectModal() {
    document.getElementById('rejectOverlay').classList.remove('open');
}

async function doApprove(id, btn) {
    btn.disabled = true; btn.textContent = '⏳ Traitement…';
    try {
        const r = await fetch('approve_article.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'approve', article_id: id})
        });
        const d = await r.json();
        if (d.success) {
            const row = document.getElementById('row-' + id);
            row.style.opacity = '0';
            row.style.transition = 'opacity .4s';
            setTimeout(() => row.remove(), 400);
        } else { alert(d.message || 'Erreur'); btn.disabled = false; btn.textContent = '✅ Approuver'; }
    } catch(e) { alert('Erreur réseau'); btn.disabled = false; btn.textContent = '✅ Approuver'; }
}

async function doReject() {
    const id    = document.getElementById('rejectId').value;
    const note  = document.getElementById('rejectNote').value.trim();
    if (!note) { document.getElementById('rejectNote').focus(); return; }

    try {
        const r = await fetch('approve_article.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'reject', article_id: parseInt(id), note: note})
        });
        const d = await r.json();
        if (d.success) {
            closeRejectModal();
            const row = document.getElementById('row-' + id);
            row.style.opacity = '0'; row.style.transition = 'opacity .4s';
            setTimeout(() => row.remove(), 400);
        } else { alert(d.message || 'Erreur'); }
    } catch(e) { alert('Erreur réseau'); }
}

document.getElementById('rejectOverlay').addEventListener('click', function(e){
    if (e.target === this) closeRejectModal();
});
</script>
</body>
</html>
