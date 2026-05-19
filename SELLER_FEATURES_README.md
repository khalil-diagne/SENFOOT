# Fonctionnalités Vendeur - Guide d'Utilisation

## Vue d'ensemble

Ce document décrit les nouvelles fonctionnalités vendeur intégrées à la plateforme Dribbleur Store.

## Fichiers Créés

### 1. **seller_dashboard.php** (`/admin/`)
Tableau de bord principal du vendeur affichant :
- **Statistiques en temps réel** :
  - Nombre total d'articles
  - Articles disponibles, réservés et vendus
  - Nombre de commandes
  - Chiffre d'affaires total
- **Liste des articles récents** avec statut et approbation
- **Actions rapides** : créer un nouvel article

**Accès** : `/admin/seller_dashboard.php`

### 2. **seller_products.php** (`/admin/`)
Gestion complète des produits du vendeur :
- **Affichage en grille** de tous les articles du vendeur
- **Informations par article** :
  - Image de couverture
  - Titre et prix
  - Statut (disponible, réservé, vendu)
  - Statut d'approbation (approuvé, en attente)
- **Actions** :
  - ✏️ Éditer un article
  - 🗑️ Supprimer un article
- **Bouton d'ajout** : créer un nouvel article

**Accès** : `/admin/seller_products.php`

### 3. **seller_orders.php** (`/admin/`)
Suivi des commandes concernant les articles du vendeur :
- **Statistiques** :
  - Nombre total de commandes
  - Nombre d'articles vendus
  - Chiffre d'affaires généré
- **Tableau des commandes** avec :
  - Numéro de commande
  - Informations du client (nom, email)
  - Nombre d'articles et quantité
  - Montant total
  - Date de la commande
  - Lien vers les détails

**Accès** : `/admin/seller_orders.php`

### 4. **seller_profile.php** (`/admin/`)
Profil vendeur avec vérification KYC :

#### Section Informations Personnelles
- Prénom, Nom, Email
- Téléphone, Adresse, Ville
- Pseudo (non modifiable)
- Bouton de sauvegarde

#### Section Vérification d'Identité (KYC)
- **Statut de vérification** :
  - ✓ Vérifié (si approuvé par admin)
  - ⏳ En attente (si soumis)
  - 📋 Non vérifié (si aucun document)
- **Formulaire de soumission** :
  - Type de document (passeport, carte d'identité, permis, autre)
  - Numéro du document
  - Upload de fichier (JPG, PNG, PDF - max 5MB)

**Accès** : `/admin/seller_profile.php`

## Architecture et Intégration

### Structure de Navigation
Tous les fichiers vendeur incluent une **barre latérale de navigation** cohérente :
```
VENDEUR
├── 📊 Tableau de Bord
├── 📦 Mes Produits
├── 📋 Mes Commandes
├── 👤 Mon Profil
└── 🔙 Retour Profil
```

### Sécurité
- **Authentification** : Fonction `require_seller()` vérifie que l'utilisateur est vendeur ou admin
- **Autorisation** : Les vendeurs ne voient que leurs propres données
- **Validation** : Tous les formulaires valident les données côté serveur

### Base de Données
Les fonctionnalités utilisent les colonnes existantes :
- `articles.author_user_id` : ID du vendeur
- `articles.binding_status` : Statut du produit
- `articles.approval_status` : Statut d'approbation
- `visiteur.seller_id_type` : Type de document KYC
- `visiteur.seller_id_number` : Numéro du document
- `visiteur.seller_id_photo` : Photo/scan du document
- `visiteur.seller_verified` : Statut de vérification

## Flux Utilisateur

### Pour un Vendeur Nouvellement Inscrit

1. **Compléter le profil** → `/admin/seller_profile.php`
   - Remplir les informations personnelles
   - Soumettre les documents KYC
   
2. **Créer des articles** → `/admin/seller_products.php` → "Nouvel Article"
   - Utilise le formulaire existant `article_new.php`
   
3. **Suivre les ventes** → `/admin/seller_orders.php`
   - Consulter les commandes de ses articles
   
4. **Gérer l'inventaire** → `/admin/seller_products.php`
   - Éditer ou supprimer des articles

### Pour un Administrateur

- Approuver/rejeter les articles : `/admin/articles.php` (existant)
- Vérifier les documents KYC : `/admin/pending_sellers.php` (existant)
- Consulter les commandes : `/admin/orders.php` (existant)

## Styles et Thème

Tous les fichiers utilisent le **thème Neon** existant :
- Couleurs : Vert néon (#00ff88), Bleu néon (#00cfff), Rouge néon (#ff4466)
- Polices : Orbitron (titres), Rajdhani (corps)
- Effets : Gradients, glows, animations fluides
- Responsive : Adaptation mobile complète

## Améliorations Futures Possibles

1. **Statistiques avancées** : Graphiques de ventes, tendances
2. **Notifications** : Alertes pour nouvelles commandes
3. **Évaluations** : Système de notation des vendeurs
4. **Paiements** : Intégration de système de paiement vendeur
5. **Modération** : Système d'appels pour articles rejetés
6. **Analytique** : Suivi détaillé des performances

## Dépannage

### Erreur "Accès refusé"
- Vérifier que l'utilisateur a le rôle 'seller' ou 'admin'
- Vérifier la session utilisateur

### Articles ne s'affichent pas
- Vérifier que `author_user_id` est correctement défini
- Vérifier les permissions de base de données

### KYC non visible
- S'assurer que les colonnes KYC existent (voir `config.php` - `ensure_store_schema()`)
- Vérifier les permissions de dossier `/uploads/kyc/`

## Support

Pour toute question ou problème, consultez :
- `config.php` : Configuration générale et fonctions utilitaires
- `admin/article_new.php` : Formulaire de création d'article
- `admin/admin.php` : Tableau de bord administrateur
