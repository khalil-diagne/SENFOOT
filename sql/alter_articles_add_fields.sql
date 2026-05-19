-- Ajouter les champs manquants à la table articles (si ils n'existent pas déjà)
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `price` DECIMAL(10, 2) DEFAULT 0 AFTER `title`;
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `platform` VARCHAR(50) DEFAULT 'Multi' AFTER `price`;
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `delivery_time` VARCHAR(100) DEFAULT '5 minutes' AFTER `platform`;
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `binding_status` VARCHAR(255) DEFAULT 'Lié à un email' AFTER `delivery_time`;
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `gallery_images` JSON DEFAULT NULL AFTER `image`;
ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `why_choose_us` JSON DEFAULT NULL AFTER `gallery_images`;

-- Exemple de données
INSERT INTO `articles` (title, price, platform, delivery_time, binding_status, content, image, gallery_images, why_choose_us, author_username) 
VALUES (
  'PUISSANCE 3181',
  29999.00,
  'Android/iOS',
  'Livraison en moins de 5 minutes',
  'Lié à un email factice - Changeable',
  'Compte eFootball haut niveau avec 3181 de puissance',
  'efootball1.jpg',
  '["efootball1.jpg", "efootball2.jpg", "efootball3.jpg", "efootball4.jpg", "efootball5.jpg", "efootball6.jpg"]',
  '["Joueurs légendaires inclus", "Coins suffisants pour upgrader", "Équipe complète et optimisée"]',
  'admin'
) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
