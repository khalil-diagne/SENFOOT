# Guide d'Installation Rapide - Lightbox Article

## ✅ Installation en 5 minutes

### 1. Ajouter les fichiers nécessaires

Les fichiers suivants ont déjà été créés dans votre projet:

```
/style/article_lightbox.css      ← Styles de la lightbox
/style/article_lightbox.js       ← Logique JavaScript
/article_api.php                 ← API pour récupérer les articles
/admin/init_articles.php         ← Script pour initialiser les données
/demo_lightbox.php               ← Page de démo (optionnel)
```

### 2. Modifier la base de données

**Option A: Automatiquement (recommandé)**
1. Connectez-vous en tant qu'admin
2. Allez sur `/admin/init_articles.php`
3. Les colonnes seront créées automatiquement et les données d'exemple seront ajoutées

**Option B: Manuellement**
Exécutez ce script SQL:
```sql
ALTER TABLE `articles` ADD COLUMN `price` DECIMAL(10, 2) DEFAULT 0 AFTER `title`;
ALTER TABLE `articles` ADD COLUMN `platform` VARCHAR(50) DEFAULT 'Multi' AFTER `price`;
ALTER TABLE `articles` ADD COLUMN `delivery_time` VARCHAR(100) DEFAULT '5 minutes' AFTER `platform`;
ALTER TABLE `articles` ADD COLUMN `binding_status` VARCHAR(255) DEFAULT 'Lié à un email' AFTER `delivery_time`;
ALTER TABLE `articles` ADD COLUMN `gallery_images` JSON DEFAULT NULL AFTER `image`;
ALTER TABLE `articles` ADD COLUMN `why_choose_us` JSON DEFAULT NULL AFTER `gallery_images`;
```

### 3. Ajouter les imports dans vos pages

**Dans le `<head>`:**
```html
<link rel="stylesheet" href="/style/article_lightbox.css">
```

**Avant la fermeture du `</body>`:**
```html
<script src="/style/article_lightbox.js"></script>
```

✅ **C'est fait!** Les fichiers accueil.php et list_articles.php sont déjà mis à jour.

### 4. Rendre les cartes cliquables

Modifiez vos cartes d'articles pour appeler la lightbox:

```html
<!-- Version simple -->
<div class="article-card" onclick="articleLightbox.open(<?= $article['id'] ?>)">
  <img src="uploads/articles/<?= $article['image'] ?>" alt="">
  <h3><?= $article['title'] ?></h3>
  <p><?= number_format($article['price'], 0, ',', ' ') ?> FCFA</p>
</div>
```

### 5. Ajouter des articles avec données complètes

Lors de l'ajout d'un article en base de données:

```php
$stmt = $pdo->prepare('
  INSERT INTO articles (
    title, price, platform, delivery_time, binding_status,
    content, image, gallery_images, why_choose_us, author_username
  ) VALUES (:title, :price, :platform, :delivery_time, :binding_status,
            :content, :image, :gallery_images, :why_choose_us, :author_username)
');

$stmt->execute([
  ':title' => 'PUISSANCE 3181',
  ':price' => 29999,
  ':platform' => 'Android/iOS',
  ':delivery_time' => 'Livraison en moins de 5 minutes',
  ':binding_status' => 'Lié à un email factice - Changeable',
  ':content' => 'Description de l\'article...',
  ':image' => 'image.jpg',  // Fichier dans /uploads/articles/
  ':gallery_images' => json_encode([
    'image.jpg',
    'image2.jpg',
    'image3.jpg'
  ]),
  ':why_choose_us' => json_encode([
    'Joueurs légendaires inclus',
    'Coins suffisants',
    'Équipe optimisée'
  ]),
  ':author_username' => 'admin'
]);
```

---

## 🧪 Tester la démo

Rendez-vous sur: `http://localhost/demo_lightbox.php`

Cliquez sur une carte pour voir la lightbox en action avec des données d'exemple.

---

## 📋 Éléments de la lightbox

### Galerie photos
- ✅ Affiche jusqu'à 6 images
- ✅ Navigation avec boutons ← et →
- ✅ Indicateur de position (n/total)
- ✅ Boucle automatique (après la dernière image, retour à la première)
- ✅ Animations fluides

### Détails de l'article
- ✅ Titre avec gradient cyan
- ✅ Prix formaté en FCFA avec glow vert
- ✅ Plateforme (Android/iOS)
- ✅ Délai de livraison
- ✅ Binding Status
- ✅ Section "Pourquoi nous choisir" (3 points)
- ✅ Description complète de l'article
- ✅ Bouton "Acheter maintenant"

### Interactions
- ✅ Clic sur l'image ou la carte = ouvre la lightbox
- ✅ Clic en dehors = ferme la lightbox
- ✅ Bouton ✕ en haut à droite = ferme
- ✅ Touche Escape = ferme
- ✅ Flèches clavier = navigue les images
- ✅ Bouton d'achat = ajoute au panier + notification

---

## 🎨 Customization

### Changer les couleurs

Modifiez le CSS (article_lightbox.css):

```css
/* Couleur principale (vert) */
.gallery-btn, .info-label, .lightbox-buy-btn {
    color: #00ff88;  /* Changer cette couleur */
}

/* Couleur secondaire (cyan) */
.lightbox-title, .info-value {
    color: #00ffcc;  /* Changer cette couleur */
}
```

### Adapter la taille de la modal

```css
.article-lightbox {
    max-width: 1000px;  /* Changer la largeur */
    max-height: 90vh;    /* Changer la hauteur */
}
```

### Ajouter des animations personnalisées

Consultez le fichier CSS pour les animations existantes (@keyframes)

---

## 🐛 Troubleshooting

### La lightbox ne s'ouvre pas
```
❌ Problème: Console montre "articleLightbox is not defined"
✅ Solution: Vérifiez que article_lightbox.js est chargé avant la fermeture du body
```

### Les images ne s'affichent pas
```
❌ Problème: Images manquantes dans la galerie
✅ Solution: Vérifiez que les fichiers existent dans /uploads/articles/
          et que le chemin dans la BD est correct
```

### Le prix ne s'affiche pas
```
❌ Problème: Prix affiche "---"
✅ Solution: Vérifiez que la colonne price existe en BD
          et que la valeur est un DECIMAL(10,2)
```

### Le panier ne se met pas à jour
```
❌ Problème: Rien ne se passe au clic "Acheter"
✅ Solution: Vérifiez que localStorage est activé dans le navigateur
          et que la console n'affiche pas d'erreurs
```

---

## 📞 Support

Pour toute question, consultez:
- `LIGHTBOX_DOCUMENTATION.md` - Documentation complète
- `demo_lightbox.php` - Exemple d'implémentation
- Console du navigateur (F12) - Erreurs et logs

---

## ✨ Prochaines étapes

1. **Ajouter vos images** dans `/uploads/articles/`
2. **Créer vos articles** avec les détails complets
3. **Tester le panier** et l'intégration au checkout
4. **Customiser le design** selon votre charte graphique

---

**Bonne utilisation! 🚀**
