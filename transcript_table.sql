-- SQL Script to create transcript_subjects table
-- This table stores data extracted from Transcript of Records

CREATE TABLE IF NOT EXISTS `transcript_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `units` varchar(10) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- You might also want to create a students table if it doesn't exist already
-- This is just a sample structure, modify according to your needs

CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_student_number` (`student_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add a foreign key constraint if both tables exist
ALTER TABLE `transcript_subjects`
ADD CONSTRAINT `fk_transcript_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE; 