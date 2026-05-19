<?php
require __DIR__ . '/../config.php';
ensure_store_schema();
require_seller();

$pdo      = db();
$userId   = current_user_id();
$username = $_SESSION['username'];

try { $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_username` VARCHAR(120) NULL AFTER `seller_note`"); } catch(Throwable $e){}
try { $pdo->exec("ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `author_user_id` INT UNSIGNED NULL AFTER `author_username`"); } catch(Throwable $e){}

$filter = in_array($_GET['filter'] ?? '', ['all','pending','approved','rejected']) ? ($_GET['filter'] ?? 'all') : 'all';
$whereExtra = $filter !== 'all' ? " AND approval_status = " . $pdo->quote($filter) : '';

$stmt = $pdo->prepare("SELECT id, title, price, image, approval_status, seller_note, created_at FROM articles WHERE (author_username = :u OR author_user_id = :uid) $whereExtra ORDER BY created_at DESC");
$stmt->execute([':u' => $username, ':uid' => $userId]);
$articles = $stmt->fetchAll();
$success  = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Mes Articles · Dribbleur Store</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--ng:#00ff88;--nb:#00cfff;--nr:#ff4466;--gold:#ffb703;--bg:#020811;--sw:240px;--nh:70px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--bg);font-family:'Rajdhani',sans-serif;color:#fff;min-height:100vh;overflow-x:hidden;}
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(0,207,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(0,207,255,.03) 1px,transparent 1px);background-size:50px 50px;animation:g 10s linear infinite;z-index:0;pointer-events:none;}
@keyframes g{from{background-position:0 0}to{background-position:50px 50px}}
.layout{display:flex;min-height:100vh;position:relative;z-index:2;}
.sidebar{width:var(--sw);flex-shrink:0;background:rgba(2,8,17,.92);border-right:1px solid rgba(255,183,3,.15);backdrop-filter:blur(20px);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;}
.sl{padding:22px 20px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;flex-direction:column;gap:4px;}
.sl-txt{font-family:'Orbitron',sans-serif;font-weight:900;font-size:13px;letter-spacing:2px;background:linear-gradient(90deg,var(--gold),var(--ng));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.sb{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(255,183,3,.12);border:1px solid rgba(255,183,3,.25);border-radius:6px;color:var(--gold);font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.5px;width:fit-content;}
.sn{flex:1;padding:20px 12px;display:flex;flex-direction:column;gap:4px;}
.nl{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,.22);padding:10px 8px 5px;}
.ni{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,.5);font-size:14px;transition:all .25s;position:relative;}
.ni:hover{color:#fff;background:rgba(255,255,255,.05);}
.ni.active{color:var(--gold);border:1px solid rgba(255,183,3,.2);background:rgba(255,183,3,.06);}
.ni.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2px;background:var(--gold);border-radius:2px;box-shadow:0 0 8px var(--gold);}
.ico{font-size:16px;width:20px;text-align:center;}
.sf{padding:16px 12px;border-top:1px solid rgba(255,255,255,.05);}
.lb{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,68,102,.7);font-size:14px;transition:all .25s;width:100%;}
.lb:hover{color:var(--nr);background:rgba(255,68,102,.08);}
.main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;}
.topbar{height:var(--nh);display:flex;align-items:center;justify-content:space-between;padding:0 30px;background:rgba(2,8,17,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,183,3,.1);position:sticky;top:0;z-index:40;}
.topbar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);opacity:.4;}
.bc{font-family:'Orbitron',sans-serif;font-size:13px;letter-spacing:2px;color:rgba(255,255,255,.4);}
.tu{display:flex;align-items:center;gap:10px;padding:8px 16px;border:1px solid rgba(255,183,3,.2);border-radius:8px;background:rgba(255,183,3,.05);font-size:13px;}
.gd{width:8px;height:8px;border-radius:50%;background:var(--gold);box-shadow:0 0 8px var(--gold);animation:pd 2s ease-in-out infinite;}
@keyframes pd{0%,100%{opacity:1;}50%{opacity:.3;}}
.content{padding:32px 30px 60px;flex:1;}
.ph{font-family:'Orbitron',sans-serif;font-weight:900;font-size:24px;letter-spacing:3px;background:linear-gradient(135deg,#fff,var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
.ps{color:rgba(255,255,255,.32);font-size:14px;margin-bottom:24px;}
.fb{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;}
.fbi{padding:8px 18px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(255,255,255,.45);font-family:'Rajdhani',sans-serif;font-size:13px;text-decoration:none;transition:all .25s;}
.fbi:hover,.fbi.active{background:rgba(255,183,3,.1);border-color:rgba(255,183,3,.3);color:var(--gold);}
.toast{padding:14px 20px;border-radius:10px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.25);color:var(--ng);}
.at{width:100%;border-collapse:collapse;}
.at th{font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:2px;color:rgba(255,255,255,.25);padding:10px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,.06);}
.at td{padding:14px;border-bottom:1px solid rgba(255,255,255,.04);font-size:14px;vertical-align:middle;}
.at tr:hover td{background:rgba(255,255,255,.02);}
.ai{width:52px;height:52px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,255,255,.1);}
.ap{width:52px;height:52px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:20px;}
.abt{font-weight:600;color:#e0f7ff;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.apr{font-family:'Orbitron',sans-serif;font-size:12px;color:var(--gold);}
.apb{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.2px;}
.apb.pending{background:rgba(255,183,3,.1);border:1px solid rgba(255,183,3,.25);color:var(--gold);}
.apb.approved{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.22);color:var(--ng);}
.apb.rejected{background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:var(--nr);}
.note-tip{margin-top:6px;font-size:12px;color:rgba(255,68,102,.75);font-style:italic;max-width:260px;}
.edit-link{display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:8px;border:1px solid rgba(0,207,255,.22);color:#00cfff;text-decoration:none;font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:1.2px;}
.edit-link:hover{background:rgba(0,207,255,.08);}
.empty{text-align:center;padding:80px 20px;color:rgba(255,255,255,.25);}
.empty .ei{font-size:52px;margin-bottom:16px;}
.empty p{font-size:14px;margin-bottom:20px;}
.sl2{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,var(--gold),#e69a00);color:#000;border-radius:10px;font-family:'Orbitron',sans-serif;font-weight:700;font-size:11px;letter-spacing:2px;text-decoration:none;transition:all .3s;}
.sl2:hover{transform:translateY(-2px);box-shadow:0 0 20px rgba(255,183,3,.4);}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}.content{padding:20px 16px 40px;}}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="layout">
    <aside class="sidebar">
        <div class="sl"><div class="sl-txt">Dribbleur Store</div><div class="sb">⭐ VENDEUR</div></div>
        <nav class="sn">
            <div class="nl">Dashboard</div>
            <a href="index.php" class="ni"><span class="ico">📊</span> Vue d'ensemble</a>
            <div class="nl">Mes articles</div>
            <a href="submit_article.php" class="ni"><span class="ico">➕</span> Soumettre un article</a>
            <a href="my_articles.php" class="ni active"><span class="ico">📦</span> Mes articles</a>
            <div class="nl">Site</div>
            <a href="../accueil.php" class="ni"><span class="ico">🏠</span> Voir la boutique</a>
        </nav>
        <div class="sf"><a href="../logout.php" class="lb"><span>🚪</span> Déconnexion</a></div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="bc">Vendeur <span style="color:var(--gold);">/</span> Mes Articles</div>
            <div class="tu"><span class="gd"></span> <?= htmlspecialchars($username) ?></div>
        </div>
        <div class="content">
            <div class="ph">Mes Articles</div>
            <div class="ps">Suivez le statut de validation de chacun de vos articles.</div>

            <?php if ($success): ?>
            <div class="toast">✅ Article soumis ! Il est en attente de validation par l'équipe admin (généralement sous 24h).</div>
            <?php endif; ?>

            <div class="fb">
                <a href="?filter=all"      class="fbi <?= $filter==='all'      ? 'active':'' ?>">Tous (<?= count($articles) ?>)</a>
                <a href="?filter=pending"  class="fbi <?= $filter==='pending'  ? 'active':'' ?>">⏳ En attente</a>
                <a href="?filter=approved" class="fbi <?= $filter==='approved' ? 'active':'' ?>">✅ Approuvés</a>
                <a href="?filter=rejected" class="fbi <?= $filter==='rejected' ? 'active':'' ?>">❌ Refusés</a>
            </div>

            <?php if (empty($articles)): ?>
            <div class="empty"><div class="ei">📦</div><p>Aucun article trouvé.</p><a href="submit_article.php" class="sl2">➕ Soumettre un article</a></div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="at">
                <thead><tr><th>Image</th><th>Titre</th><th>Prix</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($articles as $a):
                    $ap = $a['approval_status'] ?? 'pending';
                    [$icon,$label,$cls] = match($ap){
                        'approved'=>['✅','Approuvé','approved'],
                        'rejected'=>['❌','Refusé','rejected'],
                        default   =>['⏳','En attente','pending'],
                    };
                    $img = $a['image'] ? BASE_URL.'/uploads/articles/'.htmlspecialchars($a['image']) : '';
                ?>
                <tr>
                    <td><?php if($img): ?><img src="<?=$img?>" class="ai" alt=""><?php else: ?><div class="ap">🖼️</div><?php endif; ?></td>
                    <td>
                        <div class="abt"><?= htmlspecialchars($a['title']) ?></div>
                        <?php if($ap==='rejected' && !empty($a['seller_note'])): ?>
                        <div class="note-tip">💬 <?= htmlspecialchars($a['seller_note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><div class="apr"><?= number_format((float)$a['price'],0,',',' ') ?> FCFA</div></td>
                    <td><span class="apb <?= $cls ?>"><?= $icon ?> <?= $label ?></span></td>
                    <td style="color:rgba(255,255,255,.35);font-size:13px;"><?= date('d/m/Y',strtotime($a['created_at'])) ?></td>
                    <td><a class="edit-link" href="../admin/edit_article.php?id=<?= (int) $a['id'] ?>">Modifier</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
