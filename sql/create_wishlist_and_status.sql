-- Ajoute le statut produit aux articles
ALTER TABLE `articles`
ADD COLUMN IF NOT EXISTS `product_status` VARCHAR(20) NOT NULL DEFAULT 'available' AFTER `binding_status`;

-- Cree la table de liste d envies
CREATE TABLE IF NOT EXISTS `wishlist_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_article` (`user_id`, `article_id`),
  KEY `idx_article_id` (`article_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `visiteur`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_article` FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
