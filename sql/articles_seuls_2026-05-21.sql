-- Export articles uniquement — base efootball (2026-05-21)
-- Importer après avoir créé la table `articles` (voir export complet).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `articles`;

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `price`, `platform`, `delivery_time`, `binding_status`, `product_status`, `approval_status`, `seller_note`, `image`, `gallery_images`, `why_choose_us`, `author_username`, `author_user_id`, `created_at`, `updated_at`) VALUES
(3,'PUISSANCE 3160','puissance-3160','COMPTE AVEC DES JOUEURS EPICS TEL QUE MESSI 2015, LAUTARO 2021 EPIC , NEDVED 103     ',15000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'6c57d36bddb77ded.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:54:13','2026-05-03 19:53:38'),
(4,'PUISSANCE 3220','puissance-3220','COMPTE AVEC DES JOUEURS EPICS TEL QUE SALAH BLITZ CURLER , MESSI 2015',55000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'d15d49d8c5af18fc.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:55:55','2026-05-03 18:25:08'),
(5,'PUISSANCE 3178','puissance-3178','COMPTE AVEC DES JOUEURS EPICS TEL QUE GULLIT 105 , KAHN 106 , CR7 GOAL POACHER',28000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'f24f12c4efe44be6.jpg',NULL,NULL,'khalil11',NULL,'2025-10-19 13:58:55','2026-05-09 11:23:30'),
(6,'PUISSANCE 3197','puissance-3197','COMPTE AVEC DES EPIC TEL QUE MBAPPE BLITZ CURLER , BALE 2018',45000.00,'Multi','5 minutes','Lié à un email','reserved','approved',NULL,'b04c56310c1fb86c.jpg','[\"b04c56310c1fb86c.jpg\",\"dbdd576ed37b8c73.jpg\"]',NULL,'khalil11',NULL,'2025-10-19 14:13:36','2026-05-01 00:15:18');

SET FOREIGN_KEY_CHECKS = 1;
