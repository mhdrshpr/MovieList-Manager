-- ============================================================
-- MovieManager - Database Installation & Schema Unified Script
-- سامانه مدیریت فیلم و سریال - اسکریپت تجمیعی و یکپارچه پایگاه‌داده
-- Charset: utf8mb4 | Collation: utf8mb4_persian_ci
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+03:30';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- ۱. جدول کاربران (users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='جدول اطلاعات حساب‌های کاربری';

-- ------------------------------------------------------------
-- ۲. جدول گروه‌بندی لیست‌ها (list_groups)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `list_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT '#6366f1',
  `description` text DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL COMMENT 'فایل تصویر بنر گروه',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_list_groups_user` (`user_id`),
  CONSTRAINT `fk_list_groups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='گروه‌بندی لیست‌های کاربران';

-- ------------------------------------------------------------
-- ۳. جدول لیست‌ها (lists)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT '#0891b2',
  `description` text DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL COMMENT 'فایل بنر سفارشی لیست',
  `share_code` varchar(64) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL COMMENT 'شناسه گروه والد',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lists_user` (`user_id`),
  KEY `idx_lists_group` (`group_id`),
  KEY `idx_lists_share_code` (`share_code`),
  CONSTRAINT `fk_lists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lists_group` FOREIGN KEY (`group_id`) REFERENCES `list_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='لیست‌های فیلم و سریال';

-- ------------------------------------------------------------
-- ۴. جدول فیلم‌ها (movies)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `release_year` int(11) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL COMMENT 'ژانرهای اختصاص‌یافته با ویرگول',
  `downloaded` tinyint(1) NOT NULL DEFAULT 0,
  `watched` tinyint(1) NOT NULL DEFAULT 0,
  `watch_date` date DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL COMMENT 'امتیاز ۱ تا ۱۰',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_movies_user` (`user_id`),
  KEY `idx_movies_list` (`list_id`),
  CONSTRAINT `fk_movies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movies_list` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='عناوین فیلم‌ها';

-- ------------------------------------------------------------
-- ۵. جدول سریال‌ها (series)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `genre` varchar(255) DEFAULT NULL COMMENT 'ژانرهای سریال',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_series_user` (`user_id`),
  KEY `idx_series_list` (`list_id`),
  CONSTRAINT `fk_series_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_series_list` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='عناوین سریال‌ها';

-- ------------------------------------------------------------
-- ۶. جدول فصل‌های سریال (seasons)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `season_number` int(11) NOT NULL,
  `episodes` int(11) DEFAULT NULL,
  `downloaded` tinyint(1) NOT NULL DEFAULT 0,
  `watched` tinyint(1) NOT NULL DEFAULT 0,
  `watch_date` date DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL COMMENT 'امتیاز فصل ۱ تا ۱۰',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seasons_series` (`series_id`),
  CONSTRAINT `fk_seasons_series` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='فصل‌های مربوط به سریال‌ها';

-- ------------------------------------------------------------
-- ۷. جدول دیدگاه‌های عناوین (comments)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `media_type` enum('movie','season') NOT NULL,
  `media_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comments_user` (`user_id`),
  KEY `idx_comments_media` (`media_type`,`media_id`),
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='یادداشت‌ها و دیدگاه‌های فیلم و فصل سریال';

-- ------------------------------------------------------------
-- ۸. جدول کامنت‌های کلی لیست‌ها (list_comments)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `list_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_list_comments_list` (`list_id`),
  KEY `idx_list_comments_user` (`user_id`),
  CONSTRAINT `fk_list_comments_list` FOREIGN KEY (`list_id`) REFERENCES `lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_list_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='دیدگاه‌های ثبت شده برای کل یک لیست';

-- ------------------------------------------------------------
-- ۹. جدول عناوین مرتبط و خط زمانی داستانی (prequels)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prequels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `media_type` enum('movie','series') NOT NULL COMMENT 'نوع اثر مرجع',
  `media_id` int(11) NOT NULL COMMENT 'شناسه اثر مرجع',
  `relation_type` enum('prequel','sequel','collection') NOT NULL DEFAULT 'prequel' COMMENT 'نوع پیوند داستانی',
  `related_type` enum('movie','series') NOT NULL DEFAULT 'movie' COMMENT 'نوع اثر مرتبط',
  `title` varchar(255) NOT NULL,
  `release_year` int(11) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL COMMENT 'ژانرهای اثر مرتبط',
  `watched` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'وضعیت مشاهده',
  `rating` tinyint(4) DEFAULT NULL COMMENT 'امتیاز ۱ تا ۱۰',
  `comment` text DEFAULT NULL COMMENT 'یادداشت یا دیدگاه',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ترتیب قرارگیری روی خط زمانی مارپیچ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prequels_user` (`user_id`),
  KEY `idx_prequels_media` (`media_type`,`media_id`),
  KEY `idx_prequels_sort` (`sort_order`),
  CONSTRAINT `fk_prequels_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='پیش‌درآمدها، دنباله‌ها و خط داستانی فرانچایزها';

-- ------------------------------------------------------------
-- ۱۰. جدول کتابخانه ژانرها (genres)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'برای ژانرهای استاندارد سراسری مقدار NULL است',
  `name` varchar(100) NOT NULL COMMENT 'عنوان کامل جهت نمایش',
  `name_fa` varchar(100) DEFAULT NULL COMMENT 'نام فارسی',
  `name_en` varchar(100) DEFAULT NULL COMMENT 'نام انگلیسی',
  `slug` varchar(120) DEFAULT NULL COMMENT 'شناسه یکتا لاتین',
  `color` varchar(20) DEFAULT '#0891b2' COMMENT 'رنگ برچسب',
  `is_standard` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'آیا جزو ۳۰ دسته پیش‌فرض سامانه است',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_genre_name` (`user_id`,`name`),
  KEY `idx_genres_user` (`user_id`),
  KEY `idx_genres_slug` (`slug`),
  KEY `idx_genres_standard` (`is_standard`),
  CONSTRAINT `fk_genres_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='کتابخانه ۳۰ ژانر استاندارد و ژانرهای سفارشی کاربران';

-- ------------------------------------------------------------
-- ۱۱. جدول تنظیمات پیش‌فرض کاربران (user_defaults)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_defaults` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `default_list_color` varchar(20) DEFAULT '#0891b2',
  `default_group_color` varchar(20) DEFAULT '#6366f1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_defaults_user` (`user_id`),
  CONSTRAINT `fk_user_defaults_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='تنظیمات رنگ و رفتارهای پیش‌فرض هر کاربر';

-- ------------------------------------------------------------
-- ۱۲. جدول دستاوردها و مدال‌ها (achievements)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `achievement_key` varchar(50) NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_achievement` (`user_id`,`achievement_key`),
  KEY `idx_achievements_user` (`user_id`),
  CONSTRAINT `fk_achievements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='مدال‌ها و دستاوردهای ۳بعدی کسب‌شده کاربران';

-- ------------------------------------------------------------
-- ۱۳. جدول لاگ فعالیت‌ها و امنیت (user_activity_log)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci COMMENT='ثبت رخدادها و امنیت ورود کاربران';

-- ------------------------------------------------------------
-- داده‌های پیش‌فرض: ۳۰ ژانر استاندارد سینمایی (فارسی و انگلیسی)
-- ------------------------------------------------------------
INSERT INTO `genres` (`user_id`, `name`, `name_fa`, `name_en`, `slug`, `color`, `is_standard`) VALUES
(NULL, 'اکشن / Action', 'اکشن', 'Action', 'action', '#ef4444', 1),
(NULL, 'ماجراجویی / Adventure', 'ماجراجویی', 'Adventure', 'adventure', '#f97316', 1),
(NULL, 'انیمیشن / Animation', 'انیمیشن', 'Animation', 'animation', '#eab308', 1),
(NULL, 'زندگی‌نامه / Biography', 'زندگی‌نامه', 'Biography', 'biography', '#84cc16', 1),
(NULL, 'کمدی / Comedy', 'کمدی', 'Comedy', 'comedy', '#10b981', 1),
(NULL, 'جنایی / Crime', 'جنایی', 'Crime', 'crime', '#06b6d4', 1),
(NULL, 'مستند / Documentary', 'مستند', 'Documentary', 'documentary', '#0ea5e9', 1),
(NULL, 'درام / Drama', 'درام', 'Drama', 'drama', '#3b82f6', 1),
(NULL, 'خانوادگی / Family', 'خانوادگی', 'Family', 'family', '#6366f1', 1),
(NULL, 'فانتزی / Fantasy', 'فانتزی', 'Fantasy', 'fantasy', '#8b5cf6', 1),
(NULL, 'فیلم نوآر / Film-Noir', 'فیلم نوآر', 'Film-Noir', 'film-noir', '#475569', 1),
(NULL, 'تاریخی / History', 'تاریخی', 'History', 'history', '#a855f7', 1),
(NULL, 'ترسناک / Horror', 'ترسناک', 'Horror', 'horror', '#dc2626', 1),
(NULL, 'موسیقی / Music', 'موسیقی', 'Music', 'music', '#ec4899', 1),
(NULL, 'موزیکال / Musical', 'موزیکال', 'Musical', 'musical', '#f43f5e', 1),
(NULL, 'معمایی / Mystery', 'معمایی', 'Mystery', 'mystery', '#14b8a6', 1),
(NULL, 'عاشقانه / Romance', 'عاشقانه', 'Romance', 'romance', '#fb7185', 1),
(NULL, 'علمی-تخیلی / Sci-Fi', 'علمی-تخیلی', 'Sci-Fi', 'sci-fi', '#0284c7', 1),
(NULL, 'ورزشی / Sport', 'ورزشی', 'Sport', 'sport', '#16a34a', 1),
(NULL, 'هیجان‌انگیز / Thriller', 'هیجان‌انگیز', 'Thriller', 'thriller', '#b91c1c', 1),
(NULL, 'جنگی / War', 'جنگی', 'War', 'war', '#78716c', 1),
(NULL, 'وسترن / Western', 'وسترن', 'Western', 'western', '#d97706', 1),
(NULL, 'ابرقهرمانی / Superhero', 'ابرقهرمانی', 'Superhero', 'superhero', '#7c3aed', 1),
(NULL, 'روانشناختی / Psychological', 'روانشناختی', 'Psychological', 'psychological', '#6d28d9', 1),
(NULL, 'جاسوسی / Spy', 'جاسوسی', 'Spy', 'spy', '#334155', 1),
(NULL, 'ماوراءالطبیعه / Supernatural', 'ماوراءالطبیعه', 'Supernatural', 'supernatural', '#4338ca', 1),
(NULL, 'پساآخرالزمانی / Post-Apocalyptic', 'پساآخرالزمانی', 'Post-Apocalyptic', 'post-apocalyptic', '#713f12', 1),
(NULL, 'سایبرپانک / Cyberpunk', 'سایبرپانک', 'Cyberpunk', 'cyberpunk', '#059669', 1),
(NULL, 'کوتاه / Short', 'کوتاه', 'Short', 'short', '#64748b', 1),
(NULL, 'مافیایی / Gangster', 'مافیایی', 'Gangster', 'gangster', '#1e293b', 1),
(NULL, 'کمدی موقعیت / Sitcom', 'کمدی موقعیت', 'Sitcom', 'sitcom', '#10b981', 1)
ON DUPLICATE KEY UPDATE 
  `name_fa` = VALUES(`name_fa`), 
  `name_en` = VALUES(`name_en`), 
  `slug` = VALUES(`slug`), 
  `color` = VALUES(`color`), 
  `is_standard` = 1;

SET foreign_key_checks = 1;
