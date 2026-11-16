-- =======================================================
-- WHATJOBS.COM RECREATION DATABASE SCHEMA
-- Database: whatjobs_clone
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB / phpMyAdmin / XAMPP
-- =======================================================

CREATE DATABASE IF NOT EXISTS `whatjobs_clone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `whatjobs_clone`;

-- Disable foreign key checks during creation
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- Table: admins
-- -------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL DEFAULT 'Administrator',
    `role` ENUM('superadmin', 'admin', 'moderator') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: users (Job Seekers)
-- -------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(25) NULL,
    `current_job_title` VARCHAR(100) NULL,
    `location` VARCHAR(100) NULL,
    `resume_path` VARCHAR(255) NULL,
    `bio` TEXT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: companies
-- -------------------------------------------------------
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `logo_url` VARCHAR(255) NULL,
    `website` VARCHAR(255) NULL,
    `location` VARCHAR(100) NULL,
    `industry` VARCHAR(100) NULL,
    `company_size` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_companies_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: categories
-- -------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'work_outline',
    `job_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: job_types
-- -------------------------------------------------------
DROP TABLE IF EXISTS `job_types`;
CREATE TABLE `job_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: jobs
-- -------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `job_type_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL,
    `location` VARCHAR(100) NOT NULL,
    `zip_code` VARCHAR(20) NULL,
    `salary_min` DECIMAL(10, 2) NULL,
    `salary_max` DECIMAL(10, 2) NULL,
    `salary_type` ENUM('year', 'month', 'hour') NOT NULL DEFAULT 'year',
    `is_salary_negotiable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_remote` TINYINT(1) NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `experience_level` ENUM('Entry Level', 'Mid Level', 'Senior Level', 'Lead / Director') NOT NULL DEFAULT 'Mid Level',
    `description` LONGTEXT NOT NULL,
    `responsibilities` TEXT NULL,
    `requirements` TEXT NULL,
    `benefits` TEXT NULL,
    `status` ENUM('active', 'paused', 'closed') NOT NULL DEFAULT 'active',
    `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_jobs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jobs_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_jobs_job_type` FOREIGN KEY (`job_type_id`) REFERENCES `job_types` (`id`) ON DELETE RESTRICT,
    INDEX `idx_jobs_company` (`company_id`),
    INDEX `idx_jobs_category` (`category_id`),
    INDEX `idx_jobs_type` (`job_type_id`),
    INDEX `idx_jobs_status` (`status`),
    INDEX `idx_jobs_remote` (`is_remote`),
    INDEX `idx_jobs_featured` (`is_featured`),
    INDEX `idx_jobs_location` (`location`),
    INDEX `idx_jobs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: applications
-- -------------------------------------------------------
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `applicant_name` VARCHAR(100) NOT NULL,
    `applicant_email` VARCHAR(100) NOT NULL,
    `applicant_phone` VARCHAR(25) NOT NULL,
    `cover_letter` TEXT NULL,
    `resume_path` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'reviewing', 'shortlisted', 'rejected', 'accepted') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_app_job` (`job_id`),
    INDEX `idx_app_user` (`user_id`),
    INDEX `idx_app_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: saved_jobs
-- -------------------------------------------------------
DROP TABLE IF EXISTS `saved_jobs`;
CREATE TABLE `saved_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_job` (`user_id`, `job_id`),
    CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saved_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
    INDEX `idx_saved_user` (`user_id`),
    INDEX `idx_saved_job` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- SEED DATA
-- =======================================================

-- Admins: default login admin / admin123
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@whatjobs.com', '$2y$10$E6CtN3i8KziodY91tAwnZeC7rYyYYPRcSnweEuftK5aXtMdf1wYxS', 'System Administrator', 'superadmin');

-- Users: default login seeker@example.com / password123
INSERT INTO `users` (`full_name`, `email`, `password`, `phone`, `current_job_title`, `location`, `resume_path`, `bio`, `status`) VALUES
('Alex Johnson', 'seeker@example.com', '$2y$10$oZvwL8ZckbC8jD6lsMWbLO1npBOrB4K6ZFuj/vtJpGyGSqBiFNsd2', '+1 (555) 234-5678', 'Senior Full-Stack Developer', 'New York, NY', 'uploads/resumes/sample-alex-resume.pdf', 'Passionate web software engineer with 6+ years of experience in modern PHP, JavaScript, and cloud architectures.', 'active'),
('Sarah Miller', 'sarah.miller@example.com', '$2y$10$oZvwL8ZckbC8jD6lsMWbLO1npBOrB4K6ZFuj/vtJpGyGSqBiFNsd2', '+1 (555) 345-6789', 'Registered Nurse', 'Austin, TX', NULL, 'Dedicated Healthcare professional with BSN and 4 years of ICU and emergency clinical care experience.', 'active');

-- Companies
INSERT INTO `companies` (`name`, `slug`, `logo_url`, `website`, `location`, `industry`, `company_size`, `description`) VALUES
('Oracle', 'oracle', 'https://images.usnlx.com/companylogos/oracle.gif', 'https://www.oracle.com', 'Austin, TX', 'Technology & Cloud', '10,000+ employees', 'Oracle Corporation is an American multinational computer technology corporation headquartered in Austin, Texas.'),
('Google', 'google', 'https://www.google.com/favicon.ico', 'https://careers.google.com', 'Mountain View, CA', 'Internet & AI', '10,000+ employees', 'Google LLC specializes in internet-related services and products including search, cloud computing, and AI.'),
('Microsoft', 'microsoft', 'https://c.s-microsoft.com/favicon.ico', 'https://careers.microsoft.com', 'Redmond, WA', 'Software & Hardware', '10,000+ employees', 'Microsoft enables digital transformation for the era of an intelligent cloud and an intelligent edge.'),
('Amazon', 'amazon', 'https://www.amazon.com/favicon.ico', 'https://www.amazon.jobs', 'Seattle, WA', 'E-commerce & Cloud', '10,000+ employees', 'Amazon strives to be Earth’s most customer-centric company, leading e-commerce, cloud computing, and logistics.'),
('Deloitte', 'deloitte', 'https://www.deloitte.com/favicon.ico', 'https://www2.deloitte.com', 'New York, NY', 'Consulting & Accounting', '10,000+ employees', 'Deloitte provides industry-leading audit and assurance, tax, consulting, and risk advisory services.'),
('Kaiser Permanente', 'kaiser-permanente', 'https://healthy.kaiserpermanente.org/favicon.ico', 'https://www.kaiserpermanentejobs.org', 'Oakland, CA', 'Healthcare', '10,000+ employees', 'One of America’s leading health care providers and not-for-profit health plans serving 12.5 million members.'),
('Spotify', 'spotify', 'https://open.spotifycdn.com/cdn/images/favicon.0f31d2ea.ico', 'https://www.lifeatspotify.com', 'New York, NY', 'Music & Streaming', '5,000-10,000 employees', 'Spotify unlocked the world of music by allowing listeners to access millions of tracks from creators globally.'),
('Tesla', 'tesla', 'https://www.tesla.com/themes/custom/tesla_frontend/assets/favicons/favicon.ico', 'https://www.tesla.com/careers', 'Austin, TX', 'Automotive & Clean Energy', '10,000+ employees', 'Tesla is accelerating the world’s transition to sustainable energy with electric cars, solar, and clean energy.');

-- Job Types
INSERT INTO `job_types` (`name`, `slug`) VALUES
('Full-Time', 'full-time'),
('Part-Time', 'part-time'),
('Contract', 'contract'),
('Remote', 'remote'),
('Internship', 'internship');

-- Categories
INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('Technology & IT', 'technology-it', 'computer'),
('Healthcare & Nursing', 'healthcare-nursing', 'medical_services'),
('Finance & Accounting', 'finance-accounting', 'attach_money'),
('Sales & Marketing', 'sales-marketing', 'trending_up'),
('Customer Service', 'customer-service', 'support_agent'),
('Human Resources', 'human-resources', 'people'),
('Engineering', 'engineering', 'precision_manufacturing'),
('Design & Creative', 'design-creative', 'palette');

-- Jobs
INSERT INTO `jobs` (`company_id`, `category_id`, `job_type_id`, `title`, `slug`, `location`, `zip_code`, `salary_min`, `salary_max`, `salary_type`, `is_salary_negotiable`, `is_remote`, `is_featured`, `experience_level`, `description`, `responsibilities`, `requirements`, `benefits`, `status`, `views_count`, `created_at`) VALUES
(1, 1, 1, 'Software Developer 5 - Cloud Infrastructure', 'software-developer-5-cloud-infrastructure', 'Dover, DE', '19904', 125000.00, 185000.00, 'year', 0, 1, 1, 'Senior Level', 
'Join the high-impact team building Oracle Cloud Infrastructure\'s state-of-the-art distributed observability platform, powering mission-critical cloud telemetry across global regions. You will design, develop, and operate hyperscale services handling millions of operations per second.',
'Design, implement, and maintain distributed backend microservices in Java, Go, and C++.\nArchitect fault-tolerant systems with 99.999% reliability targets.\nCollaborate with cross-functional architecture and security teams.\nMentor junior and mid-level software engineers on system design principles.',
'Bachelor\'s or Master\'s degree in Computer Science, Software Engineering, or equivalent experience.\n7+ years of professional backend software development.\nStrong expertise with distributed systems, cloud computing, and microservice architectures.\nProficiency with relational and NoSQL databases.',
'Comprehensive healthcare, dental, and vision insurance\n401(k) matching up to 6%\nGenerous stock equity compensation\nUnlimited Paid Time Off (PTO)\nRemote work flexibility stipend', 'active', 412, NOW() - INTERVAL 1 HOUR),

(2, 1, 1, 'Senior Full Stack Engineer', 'senior-full-stack-engineer', 'Mountain View, CA', '94043', 145000.00, 210000.00, 'year', 0, 0, 1, 'Senior Level',
'Google is looking for a versatile Senior Full Stack Engineer to lead front-to-back development on our next-generation developer tooling ecosystem. You will bridge intuitive user interfaces with resilient distributed infrastructure.',
'Build responsive web interfaces utilizing modern HTML5, CSS3, and JavaScript standards.\nDesign robust RESTful and gRPC APIs powering real-time workflows.\nOptimize client-side performance, accessibility, and cross-browser resilience.\nParticipate in peer code reviews and architectural RFC discussions.',
'5+ years building and scaling complex web applications.\nSolid understanding of browser internals, DOM manipulation, and asynchronous programming.\nProficiency in backend languages (PHP, Go, Python, or Java).\nExperience with CI/CD deployment pipelines.',
'World-class health and wellness coverage\nFree gourmet on-site dining and snacks\nCompetitive bonus and stock options\nAnnual education reimbursement and conference budget', 'active', 680, NOW() - INTERVAL 4 HOUR),

(3, 1, 4, 'Cloud Solutions Architect', 'cloud-solutions-architect', 'Remote, USA', '00000', 130000.00, 175000.00, 'year', 0, 1, 1, 'Senior Level',
'As a Cloud Solutions Architect at Microsoft, you will partner with enterprise clients to architect modern hybrid and multi-cloud solutions on Azure, modernizing legacy systems and accelerating digital transformation.',
'Serve as the technical authority on Azure cloud architecture and migration roadmaps.\nConduct technical deep-dives and proof-of-concept demonstrations.\nEnsure enterprise security, compliance, and governance benchmarks are met.\nDeliver technical whitepapers and best-practice blueprints.',
'Demonstrated track record in cloud architecture and enterprise systems integration.\nCertified Azure Solutions Architect Expert or equivalent AWS certification.\nExceptional client communication and presentation skills.\nStrong foundation in network security and infrastructure-as-code.',
'100% remote flexibility across the US\nHealth, dental, vision, and mental health support\n401(k) with immediate vesting\nHome office equipment stipend ($1,500)', 'active', 320, NOW() - INTERVAL 8 HOUR),

(4, 5, 2, 'Customer Support Specialist', 'customer-support-specialist', 'Seattle, WA', '98101', 22.00, 28.50, 'hour', 0, 1, 0, 'Entry Level',
'Amazon is seeking empathetic, problem-solving Customer Support Specialists to deliver world-class service to millions of customers. This role offers flexible part-time and remote shifts.',
'Handle inbound customer inquiries via chat, email, and phone with courtesy and efficiency.\nTroubleshoot delivery, account, and order concerns using internal diagnostic tools.\nMaintain high first-contact resolution rates and customer satisfaction scores.\nDocument feedback to help product teams improve customer journeys.',
'Excellent verbal and written English communication skills.\nPrevious customer service or helpdesk experience preferred.\nHigh-speed reliable internet connection and quiet dedicated workspace.\nAbility to adapt to rotating shifts and weekend hours.',
'Competitive hourly wages with shift differentials\nEmployee merchandise discounts\nFlexible scheduling options\nCareer advancement opportunities within 6 months', 'active', 215, NOW() - INTERVAL 12 HOUR),

(6, 2, 1, 'Registered Nurse (Emergency Department)', 'registered-nurse-emergency-department', 'Austin, TX', '78701', 85000.00, 115000.00, 'year', 0, 0, 1, 'Mid Level',
'Kaiser Permanente is hiring passionate Registered Nurses to join our state-of-the-art Emergency Department. Work alongside dedicated medical professionals providing urgent, high-quality patient care in a supportive environment.',
'Triage and deliver clinical nursing care to patients with acute and critical conditions.\nAdminister medications, IV therapies, and emergency medical treatments safely.\nCollaborate closely with attending physicians and emergency medical technicians.\nEducate patients and families on post-discharge care protocols.',
'Active, unencumbered RN license in the State of Texas (or compact license).\nMinimum 2 years of emergency department or acute care nursing experience.\nBLS, ACLS, and PALS certifications required.\nStrong clinical critical-thinking and rapid decision-making skills.',
'Sign-on bonus up to $15,000 for qualifying candidates\nPremium healthcare coverage with low copays\nDefined pension plan and 401(k)\nTuition reimbursement for advanced nursing degrees', 'active', 540, NOW() - INTERVAL 1 DAY),

(5, 3, 1, 'Senior Financial Analyst', 'senior-financial-analyst', 'New York, NY', '10001', 110000.00, 140000.00, 'year', 0, 0, 0, 'Mid Level',
'Deloitte\'s Financial Advisory team is seeking a Senior Financial Analyst to deliver strategic financial modeling, valuation, and forecasting for top Fortune 500 corporate clients.',
'Develop dynamic three-statement financial models and scenario forecasts.\nAnalyze historical financial statements, variances, and KPI performance metrics.\nPrepare executive-level slide decks and reporting packages for C-suite stakeholders.\nSupport mergers & acquisitions due diligence and capital budgeting initiatives.',
'Bachelor\'s degree in Finance, Accounting, Economics, or related quantitative field.\n3-5 years in corporate finance, investment banking, or management consulting.\nAdvanced mastery of Microsoft Excel, financial modeling, and data visualization.\nCFA or CPA progress is a strong advantage.',
'Competitive base salary plus performance bonus\nComprehensive medical, dental, and life insurance\nGenerous paternity/maternity parental leave\nProfessional development sponsorship', 'active', 190, NOW() - INTERVAL 1 DAY),

(7, 8, 1, 'UI/UX Product Designer', 'ui-ux-product-designer', 'New York, NY', '10012', 115000.00, 155000.00, 'year', 0, 1, 1, 'Mid Level',
'Spotify is looking for a creative UI/UX Product Designer to shape the future of music listening experiences. You will design engaging features across web, mobile, and connected devices for over 500 million active music lovers.',
'Design intuitive user journeys, wireframes, high-fidelity prototypes, and design systems.\nConduct user research, usability testing, and analyze behavioral telemetry.\nCollaborate closely with product managers and frontend engineering teams.\nAdvocate for accessible, inclusive, and visually delightful design standards.',
'4+ years of UI/UX design experience for consumer-facing digital applications.\nCompelling portfolio demonstrating user-centered problem-solving and visual craft.\nDeep familiarity with modern typography, layout, spacing, and interaction patterns.\nFluency in Figma, prototyping tools, and web design constraints.',
'Flexible working options (All The World Is Your Workplace program)\nSix months paid parental leave\nExtensive wellness days and mental wellbeing programs\nFree Spotify Premium for you and family', 'active', 890, NOW() - INTERVAL 2 DAYS),

(8, 7, 1, 'Mechanical Systems Engineer', 'mechanical-systems-engineer', 'Austin, TX', '78725', 105000.00, 145000.00, 'year', 0, 0, 0, 'Mid Level',
'Tesla is seeking a driven Mechanical Systems Engineer to design, validate, and manufacture next-generation thermal and structural components for our vehicle and energy platforms at Gigafactory Texas.',
'Lead mechanical packaging and 3D CAD modeling for high-voltage energy systems.\nPerform finite element analysis (FEA) and computational fluid dynamics (CFD).\nPrototype, assemble, and rigorously test physical test articles.\nRoot-cause manufacturing anomalies and implement design-for-manufacturability improvements.',
'BS or MS in Mechanical, Aerospace, or Mechatronics Engineering.\n3+ years experience with 3D CAD (CATIA, SolidWorks, or NX).\nHands-on prototyping, machining, and structural test validation experience.\nHigh passion for sustainable transport and first-principles thinking.',
'Generous Tesla stock awards with annual refreshers\nComprehensive medical and dental coverage\nDiscounts on Tesla vehicles and accessories\nRelocation assistance to Austin, TX', 'active', 430, NOW() - INTERVAL 2 DAYS),

(1, 4, 1, 'Product Marketing Manager', 'product-marketing-manager', 'San Francisco, CA', '94105', 120000.00, 160000.00, 'year', 0, 1, 0, 'Mid Level',
'Lead end-to-end go-to-market strategies for enterprise cloud software. Position features, craft high-converting messaging, and launch multi-channel campaigns driving developer adoption.',
'Craft compelling value propositions, sales enablement collateral, and pitch decks.\nCollaborate with product and sales to define pricing and packaging strategies.\nExecute webinar, conference, and digital campaign activations.\nMeasure campaign pipeline impact and ROI.',
'4+ years B2B SaaS product marketing experience.\nProven track record of launching tech products successfully.\nExcellent copywriting and storytelling skills.\nAnalytical mindset with hubspot/salesforce familiarity.',
'Competitive salary and quarterly incentives\n401(k) matching\nComprehensive healthcare\nRemote working allowance', 'active', 160, NOW() - INTERVAL 3 DAYS),

(2, 6, 1, 'Human Resources Specialist', 'human-resources-specialist', 'Chicago, IL', '60601', 75000.00, 95000.00, 'year', 0, 0, 0, 'Mid Level',
'Join Google\'s People Operations team supporting talent onboarding, employee relations, and policy compliance across Midwest office hubs.',
'Facilitate employee onboarding, orientation, and training programs.\nAddress employee relations inquiries with impartiality and care.\nPartner with recruitment to streamline candidate interview cycles.\nMaintain HRIS records and ensure compliance with employment regulations.',
'3+ years in human resources or people operations.\nSolid knowledge of state and federal employment laws.\nStrong interpersonal empathy and conflict resolution skills.\nSHRM-CP or PHR certification is a plus.',
'Health and dental plans\nGenerous paid time off\nParental leave benefits\n401k match', 'active', 280, NOW() - INTERVAL 4 DAYS);

-- Update category job counts
UPDATE `categories` c SET `job_count` = (SELECT COUNT(*) FROM `jobs` j WHERE j.category_id = c.id AND j.status = 'active');

-- Sample Application
INSERT INTO `applications` (`job_id`, `user_id`, `applicant_name`, `applicant_email`, `applicant_phone`, `cover_letter`, `resume_path`, `status`) VALUES
(1, 1, 'Alex Johnson', 'seeker@example.com', '+1 (555) 234-5678', 'Dear Oracle Hiring Team,\n\nI am writing to express my strong enthusiasm for the Software Developer 5 position. With over six years developing cloud-native platforms, I bring deep hands-on expertise in distributed microservices and reliability engineering.\n\nThank you for your time and consideration.\n\nSincerely,\nAlex Johnson', 'uploads/resumes/sample-alex-resume.pdf', 'reviewing');

-- Sample Saved Job
INSERT INTO `saved_jobs` (`user_id`, `job_id`) VALUES
(1, 2),
(1, 3);

