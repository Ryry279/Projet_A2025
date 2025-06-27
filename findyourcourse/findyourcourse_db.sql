-- findyourcourse_db.sql
-- Database: findyourcourse_db
-- ------------------------------------------------------
-- Server version: (Your MySQL/MariaDB Server Version)

-- Create database if it doesn't exist and set it as the current database
CREATE DATABASE IF NOT EXISTS `findyourcourse_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `findyourcourse_db`;

-- Set names for correct character encoding
SET NAMES utf8mb4;

--
-- Table structure for table `users`
-- Stores user information, including roles for regular students, premium students, and administrators.
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('student', 'admin', 'premium_student') NOT NULL DEFAULT 'student' COMMENT 'User role determines access level',
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `categories`
-- Organizes courses into different categories like "ERP Fundamentals" or "Salesforce".
--
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `courses`
-- Details about each course, including title, description, content type, link to premium status, and category.
--
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `content_type` ENUM('video', 'text', 'interactive', 'mixed') NOT NULL DEFAULT 'text' COMMENT 'Type of main content (video, text, etc.)',
  `content_body` LONGTEXT DEFAULT NULL COMMENT 'Main textual content of the course, if applicable (e.g., for text or mixed types)',
  `content_url` VARCHAR(255) DEFAULT NULL COMMENT 'URL to video or main interactive content resource',
  `thumbnail_url` VARCHAR(255) DEFAULT 'assets/images/default_thumbnail.jpg' COMMENT 'Path to course thumbnail image',
  `category_id` INT DEFAULT NULL,
  `duration_minutes` INT DEFAULT NULL COMMENT 'Approximate duration of the course in minutes (e.g., >20 min)',
  `is_premium` BOOLEAN DEFAULT FALSE COMMENT 'True if the course requires a premium subscription',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `enrollments`
-- Tracks which users are enrolled in which courses and their progress.
--
DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `progress` INT DEFAULT 0 COMMENT 'Progress percentage (0-100)',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `user_course_enrollment` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `favorites`
-- Allows users to mark courses as their favorites for easy access.
--
DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `favorited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `user_course_favorite` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `quizzes`
-- Defines quizzes, typically associated with a course. Each course has at most one quiz due to UNIQUE constraint.
--
DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL UNIQUE COMMENT 'Each course can have one main quiz',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `questions`
-- Stores individual questions for each quiz.
--
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `type` ENUM('multiple_choice', 'single_choice') DEFAULT 'single_choice' COMMENT 'Type of question determines answer format',
  `points` INT DEFAULT 1 COMMENT 'Points awarded for a correct answer to this question',
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `answers`
-- Stores possible answers for each question, indicating which one is correct.
--
DROP TABLE IF EXISTS `answers`;
CREATE TABLE `answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `answer_text` TEXT NOT NULL,
  `is_correct` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `quiz_attempts`
-- Records users attempts at quizzes, including their scores.
--
DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE `quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `quiz_id` INT NOT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL COMMENT 'Score achieved as a percentage, e.g., 85.50',
  `total_questions_attempted` INT DEFAULT NULL,
  `correct_answers` INT DEFAULT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `newsletter_subscriptions`
-- Stores email addresses of users who subscribed to the newsletter.
--
DROP TABLE IF EXISTS `newsletter_subscriptions`;
CREATE TABLE `newsletter_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `password_resets`
-- Stores tokens for password reset requests.
--
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Securely generated random token',
  `expires_at` INT NOT NULL COMMENT 'Unix timestamp for token expiry',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `users`
--
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`) VALUES
('admin', 'admin@findyourcourse.com', '$2y$10$N2qoX.qMWdeDecNMPKySBuL6E13yvA0LzM2NcsnCMfAcTJjnsH/uS', 'Admin', 'User', 'admin'), -- Password: AdminPassword123!
('teststudent', 'student@example.com', '$2y$10$E.A1gP3xWjL0s7.R8E2Pz.H.R.T5z.1g9.0pQ.O.I.N.G.Pass', 'Test', 'Student', 'student'), -- Example student password
('premiumuser', 'premium@example.com', '$2y$10$E.A1gP3xWjL0s7.R8E2Pz.H.R.T5z.1g9.0pQ.O.I.N.G.Pass', 'Premium', 'User', 'premium_student'); -- Example premium password

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'ERP Fundamentals', 'Learn the basics of Enterprise Resource Planning systems.'),
(2, 'Salesforce', 'Courses focused on Salesforce CRM.'),
(3, 'Web Development', 'Beginner courses on web technologies.'),
(4, 'Project Management', 'Introduction to project management methodologies.');

--
-- Dumping data for table `courses`
--
INSERT INTO `courses` (`id`, `title`, `description`, `content_type`, `category_id`, `duration_minutes`, `is_premium`, `thumbnail_url`, `content_body`) VALUES
(1, 'Introduction to ERP Systems', 'Definition, purpose, overview of editors, functions, benchmark of Enterprise Resource Planning.', 'text', 1, 35, FALSE, 'assets/images/erp_intro.jpg', 'This course covers the fundamental concepts of ERP systems. We will explore what ERP stands for, its historical evolution, the key benefits it brings to organizations, and common modules found in most ERP software. You will also get an overview of major ERP vendors and how to choose the right system for a business. This foundational knowledge is essential for anyone looking to understand modern business operations or pursue a career in IT consulting or business analysis focusing on enterprise systems.'),
(2, 'Salesforce: Platform Overview', 'An introduction to the Salesforce platform, its core clouds (Sales Cloud, Service Cloud, Marketing Cloud), and basic navigation within the Lightning Experience.', 'video', 2, 40, FALSE, 'assets/images/salesforce_overview.jpg', NULL),
(3, 'Salesforce: Practical Demonstration', 'A hands-on demonstration of Salesforce features including lead management, opportunity tracking, account and contact management, and basic report creation.', 'video', 2, 60, TRUE, 'assets/images/salesforce_demo.jpg', NULL),
(4, 'HTML & CSS for Beginners', 'Learn the building blocks of web pages with HTML5 and CSS3. This course covers tags, elements, attributes, styling, layout techniques like Flexbox and Grid, and responsive design principles.', 'mixed', 3, 90, FALSE, 'https://placehold.co/400x225/f1c40f/784212?text=HTML+CSS', 'Start your web development journey here! HTML provides the structure, and CSS makes it look great. We will build several small projects together.'),
(5, 'Introduction to Agile Methodology', 'Understand the principles and practices of Agile project management, including Scrum and Kanban frameworks. Learn about user stories, sprints, daily stand-ups, and retrospectives.', 'text', 4, 45, FALSE, 'https://placehold.co/400x225/8e44ad/ecf0f1?text=Agile+Intro', 'Agile is a popular approach for managing complex projects in a flexible and iterative manner. This course introduces its core values and common practices.'),
(6, 'Advanced Salesforce Configuration', 'Deep dive into Salesforce customization: custom objects, fields, relationships, validation rules, Process Builder, Flow basics, and an introduction to Apex triggers.', 'video', 2, 120, TRUE, 'https://placehold.co/400x225/1abc9c/2c3e50?text=Salesforce+Advanced', NULL),
(7, 'Understanding SAP ERP', 'An overview of SAP S/4HANA, its key modules (FI, CO, SD, MM, PP), architecture, and the business benefits it provides to large enterprises.', 'text', 1, 50, FALSE, 'https://placehold.co/400x225/3498db/ecf0f1?text=SAP+ERP', 'SAP is a leading ERP provider globally. This course provides a high-level understanding of its flagship S/4HANA product and its role in digital transformation.'),
(8, 'JavaScript Fundamentals', 'Learn the basics of JavaScript programming for interactive websites: variables, data types, operators, control flow, functions, DOM manipulation, and event handling.', 'interactive', 3, 75, FALSE, 'https://placehold.co/400x225/e74c3c/ecf0f1?text=JavaScript+Basics', 'JavaScript is essential for modern web development. This course includes interactive coding exercises to help you grasp the fundamentals quickly.'),
(9, 'Managing Projects with Scrum', 'A detailed look at the Scrum framework within Agile: roles (Product Owner, Scrum Master, Development Team), events (Sprint Planning, Daily Scrum, Sprint Review, Sprint Retrospective), and artifacts (Product Backlog, Sprint Backlog, Increment).', 'mixed', 4, 60, TRUE, 'https://placehold.co/400x225/2ecc71/2c3e50?text=Scrum+Framework', 'Scrum is a lightweight yet powerful framework for developing, delivering, and sustaining complex products. This course explores its components in detail.'),
(10, 'Microsoft Dynamics 365 Overview', 'Introduction to Microsoft''s suite of ERP and CRM applications (Dynamics 365 Finance, Supply Chain Management, Sales, Customer Service). Understand its capabilities and target audience.', 'video', 1, 40, FALSE, 'https://placehold.co/400x225/9b59b6/ecf0f1?text=Dynamics+365', NULL);

--
-- Dumping data for table `quizzes`
--
INSERT INTO `quizzes` (`id`, `course_id`, `title`, `description`) VALUES
(1, 1, 'ERP Fundamentals Quiz', 'Test your knowledge on the basics of ERP systems covered in the "Introduction to ERP Systems" course.');

--
-- Dumping data for table `questions`
--
INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `type`, `points`) VALUES
(1, 1, 'What does ERP stand for?', 'single_choice', 1),
(2, 1, 'Which of the following are common ERP modules? (Select all that apply)', 'multiple_choice', 2),
(3, 1, 'True or False: ERP systems are only suitable for large manufacturing companies.', 'single_choice', 1);

--
-- Dumping data for table `answers`
--

-- Answers for Question 1 (ERP Acronym, quiz_id 1, question_id 1)
INSERT INTO `answers` (`id`, `question_id`, `answer_text`, `is_correct`) VALUES
(1, 1, 'Enterprise Resource Planning', TRUE),
(2, 1, 'Enhanced Reporting Protocol', FALSE),
(3, 1, 'Employee Roster Program', FALSE),
(4, 1, 'External Requisition Process', FALSE);

-- Answers for Question 2 (ERP Modules, quiz_id 1, question_id 2)
INSERT INTO `answers` (`id`, `question_id`, `answer_text`, `is_correct`) VALUES
(5, 2, 'Finance and Accounting', TRUE),
(6, 2, 'Human Resources (HR)', TRUE),
(7, 2, 'Supply Chain Management (SCM)', TRUE),
(8, 2, 'Graphic Design Suite', FALSE),
(9, 2, 'Customer Relationship Management (CRM)', TRUE);

-- Answers for Question 3 (ERP Suitability, quiz_id 1, question_id 3)
INSERT INTO `answers` (`id`, `question_id`, `answer_text`, `is_correct`) VALUES
(10, 3, 'True', FALSE),
(11, 3, 'False', TRUE);

-- You can add more sample data for other tables (enrollments, favorites, quiz_attempts, newsletter_subscriptions) if needed.
-- Création de la table pour les paramètres généraux du site
CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `setting_value` TEXT DEFAULT NULL,
  `setting_description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion du paramètre pour le prix de l'abonnement
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
('premium_subscription_price', '9.99', 'Price of the premium subscription in USD');

COMMIT;