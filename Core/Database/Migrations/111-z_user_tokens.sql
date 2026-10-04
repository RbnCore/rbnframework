-- ---------------------------------------------------------------------------
-- 111-z_user_tokens.sql
--
-- FW sürüm numarası: 0.9.0 — Adım 5 (bkz. .github/UPGRADING.md, "0.9.0 — Adım 5")
--
-- Amaç: tek kullanımlık, süreli e-posta doğrulama / parola sıfırlama token
-- kasası. Ham token veritabanında SAKLANMAZ; yalnızca sha256 özeti tutulur.
--
-- YAYIN SIRASI: bu SQL, kod dağıtımından ÖNCE uygulanır. Tablo yoksa token
-- üretimi çalışmaz ve e-posta doğrulama / parola sıfırlama akışı hata verir.
--
-- Kapsam   : yalnız proje şemaları (`z_` ön ekli kullanıcı tablolarını içeren
--            veritabanları). `asw_*` tablolarına, ortak (`cm_*`) şemaya ve master
--            (`developers`, `projects`, `licences`, `ip_blocks`) şemalarına
--            dokunulmaz.
-- Uygulama : HER proje veritabanına ayrı ayrı. Eklemelidir (CREATE TABLE IF
--            NOT EXISTS), tekrar çalıştırılabilir, mevcut tabloyu/satırı
--            DEĞİŞTİRMEZ.
-- Motor    : InnoDB, utf8mb4 / utf8mb4_unicode_ci — projelerin diğer `z_*`
--            tablolarıyla aynı.
--
-- Doğrulama (uyguladıktan sonra, her proje DB'sinde):
--   SELECT TABLE_NAME FROM information_schema.TABLES
--    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'z_user_tokens';
--   → 1 satır
--
-- Geri alma: tabloyu düşürmek gerekmez (yalnızca geçici token satırları vardır):
--   DROP TABLE IF EXISTS z_user_tokens;
--   DİKKAT: önce KODU geri alın; aksi halde dağıtılmış kod token üretemez.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `z_user_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT NOT NULL COMMENT 'z_users.id',
  `purpose`    ENUM('email_verification','password_recovery') COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` CHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sha256(ham token) — ham token saklanmaz',
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL COMMENT 'doldu = tek kullanımlık kilidi / iptal',
  `ip`         VARCHAR(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_token_hash` (`token_hash`),
  KEY `idx_user_purpose_pending` (`user_id`,`purpose`,`used_at`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
