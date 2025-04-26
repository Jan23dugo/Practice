CREATE TABLE IF NOT EXISTS `university_grading_systems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `university_name` varchar(255) NOT NULL,
  `min_percentage` decimal(5,2) DEFAULT NULL,
  `max_percentage` decimal(5,2) DEFAULT NULL,
  `grade_value` varchar(10) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `is_special_grade` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_university_grade` (`university_name`, `grade_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 