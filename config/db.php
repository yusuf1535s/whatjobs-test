<?php
/**
 * WhatJobs Clone - Database Connection (PDO)
 * Standard MySQL PDO for XAMPP / Apache / PhpMyAdmin
 * With graceful SQLite fallback for testing environments without active MySQL daemon
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Try connecting to MySQL
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$driver = 'mysql';
            return self::$instance;
        } catch (PDOException $e) {
            // If MySQL is not running or database is not yet created, fall back to SQLite for instant local testing
            error_log("MySQL connection notice: " . $e->getMessage() . ". Falling back to local test database.");
            return self::initFallbackSQLite();
        }
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function initFallbackSQLite(): PDO {
        $targetDir = is_writable(__DIR__) ? __DIR__ : sys_get_temp_dir();
        $sqlitePath = $targetDir . '/whatjobs_local.sqlite';

        // In read-only serverless environments (e.g. Vercel), copy existing DB to writable temp dir
        $bundledSqlite = __DIR__ . '/whatjobs_local.sqlite';
        if ($targetDir !== __DIR__ && !file_exists($sqlitePath) && file_exists($bundledSqlite)) {
            @copy($bundledSqlite, $sqlitePath);
        }

        $isNew = !file_exists($sqlitePath);

        $pdo = new PDO("sqlite:" . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($isNew) {
            self::seedFallbackDatabase($pdo);
        }

        self::$instance = $pdo;
        self::$driver = 'sqlite';
        return self::$instance;
    }

    private static function seedFallbackDatabase(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL DEFAULT 'Administrator',
                role TEXT NOT NULL DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                phone TEXT NULL,
                current_job_title TEXT NULL,
                location TEXT NULL,
                resume_path TEXT NULL,
                bio TEXT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS companies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE,
                logo_url TEXT NULL,
                website TEXT NULL,
                location TEXT NULL,
                industry TEXT NULL,
                company_size TEXT NULL,
                description TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE,
                icon TEXT NOT NULL DEFAULT 'work_outline',
                job_count INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS job_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE
            );

            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                job_type_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                location TEXT NOT NULL,
                zip_code TEXT NULL,
                salary_min REAL NULL,
                salary_max REAL NULL,
                salary_type TEXT NOT NULL DEFAULT 'year',
                is_salary_negotiable INTEGER NOT NULL DEFAULT 0,
                is_remote INTEGER NOT NULL DEFAULT 0,
                is_featured INTEGER NOT NULL DEFAULT 0,
                experience_level TEXT NOT NULL DEFAULT 'Mid Level',
                description TEXT NOT NULL,
                responsibilities TEXT NULL,
                requirements TEXT NULL,
                benefits TEXT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                views_count INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (job_type_id) REFERENCES job_types(id)
            );

            CREATE TABLE IF NOT EXISTS applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id INTEGER NOT NULL,
                user_id INTEGER NULL,
                applicant_name TEXT NOT NULL,
                applicant_email TEXT NOT NULL,
                applicant_phone TEXT NOT NULL,
                cover_letter TEXT NULL,
                resume_path TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                admin_notes TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS saved_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                job_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, job_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
            );
        ");

        // Seed Admins
        $adminHash = '$2y$10$E6CtN3i8KziodY91tAwnZeC7rYyYYPRcSnweEuftK5aXtMdf1wYxS';
        $userHash = '$2y$10$oZvwL8ZckbC8jD6lsMWbLO1npBOrB4K6ZFuj/vtJpGyGSqBiFNsd2';

        $pdo->exec("
            INSERT OR IGNORE INTO admins (username, email, password, full_name, role)
            VALUES ('admin', 'admin@whatjobs.com', '$adminHash', 'System Administrator', 'superadmin');

            INSERT OR IGNORE INTO users (full_name, email, password, phone, current_job_title, location, resume_path, bio, status)
            VALUES 
            ('Alex Johnson', 'seeker@example.com', '$userHash', '+1 (555) 234-5678', 'Senior Full-Stack Developer', 'New York, NY', 'uploads/resumes/sample-alex-resume.pdf', 'Passionate web software engineer with 6+ years of experience in modern PHP, JavaScript, and cloud architectures.', 'active'),
            ('Sarah Miller', 'sarah.miller@example.com', '$userHash', '+1 (555) 345-6789', 'Registered Nurse', 'Austin, TX', NULL, 'Dedicated Healthcare professional with BSN and 4 years of ICU and emergency clinical care experience.', 'active');

            INSERT OR IGNORE INTO companies (id, name, slug, logo_url, website, location, industry, company_size, description)
            VALUES 
            (1, 'Oracle', 'oracle', 'https://images.usnlx.com/companylogos/oracle.gif', 'https://www.oracle.com', 'Austin, TX', 'Technology & Cloud', '10,000+ employees', 'Oracle Corporation is an American multinational computer technology corporation headquartered in Austin, Texas.'),
            (2, 'Google', 'google', 'https://www.google.com/favicon.ico', 'https://careers.google.com', 'Mountain View, CA', 'Internet & AI', '10,000+ employees', 'Google LLC specializes in internet-related services and products including search, cloud computing, and AI.'),
            (3, 'Microsoft', 'microsoft', 'https://c.s-microsoft.com/favicon.ico', 'https://careers.microsoft.com', 'Redmond, WA', 'Software & Hardware', '10,000+ employees', 'Microsoft enables digital transformation for the era of an intelligent cloud and an intelligent edge.'),
            (4, 'Amazon', 'amazon', 'https://www.amazon.com/favicon.ico', 'https://www.amazon.jobs', 'Seattle, WA', 'E-commerce & Cloud', '10,000+ employees', 'Amazon strives to be Earth’s most customer-centric company, leading e-commerce, cloud computing, and logistics.'),
            (5, 'Deloitte', 'deloitte', 'https://www.deloitte.com/favicon.ico', 'https://www2.deloitte.com', 'New York, NY', 'Consulting & Accounting', '10,000+ employees', 'Deloitte provides industry-leading audit and assurance, tax, consulting, and risk advisory services.'),
            (6, 'Kaiser Permanente', 'kaiser-permanente', 'https://healthy.kaiserpermanente.org/favicon.ico', 'https://www.kaiserpermanentejobs.org', 'Oakland, CA', 'Healthcare', '10,000+ employees', 'One of America’s leading health care providers and not-for-profit health plans serving 12.5 million members.'),
            (7, 'Spotify', 'spotify', 'https://open.spotifycdn.com/cdn/images/favicon.0f31d2ea.ico', 'https://www.lifeatspotify.com', 'New York, NY', 'Music & Streaming', '5,000-10,000 employees', 'Spotify unlocked the world of music by allowing listeners to access millions of tracks from creators globally.'),
            (8, 'Tesla', 'tesla', 'https://www.tesla.com/themes/custom/tesla_frontend/assets/favicons/favicon.ico', 'https://www.tesla.com/careers', 'Austin, TX', 'Automotive & Clean Energy', '10,000+ employees', 'Tesla is accelerating the world’s transition to sustainable energy with electric cars, solar, and clean energy.');

            INSERT OR IGNORE INTO job_types (id, name, slug)
            VALUES 
            (1, 'Full-Time', 'full-time'),
            (2, 'Part-Time', 'part-time'),
            (3, 'Contract', 'contract'),
            (4, 'Remote', 'remote'),
            (5, 'Internship', 'internship');

            INSERT OR IGNORE INTO categories (id, name, slug, icon, job_count)
            VALUES 
            (1, 'Technology & IT', 'technology-it', 'computer', 3),
            (2, 'Healthcare & Nursing', 'healthcare-nursing', 'medical_services', 1),
            (3, 'Finance & Accounting', 'finance-accounting', 'attach_money', 1),
            (4, 'Sales & Marketing', 'sales-marketing', 'trending_up', 1),
            (5, 'Customer Service', 'customer-service', 'support_agent', 1),
            (6, 'Human Resources', 'human-resources', 'people', 1),
            (7, 'Engineering', 'engineering', 'precision_manufacturing', 1),
            (8, 'Design & Creative', 'design-creative', 'palette', 1);

            INSERT OR IGNORE INTO jobs (id, company_id, category_id, job_type_id, title, slug, location, zip_code, salary_min, salary_max, salary_type, is_salary_negotiable, is_remote, is_featured, experience_level, description, responsibilities, requirements, benefits, status, views_count)
            VALUES
            (1, 1, 1, 1, 'Software Developer 5 - Cloud Infrastructure', 'software-developer-5-cloud-infrastructure', 'Dover, DE', '19904', 125000.00, 185000.00, 'year', 0, 1, 1, 'Senior Level',
            'Join the high-impact team building Oracle Cloud Infrastructure state-of-the-art distributed observability platform, powering mission-critical cloud telemetry across global regions. You will design, develop, and operate hyperscale services handling millions of operations per second.',
            'Design, implement, and maintain distributed backend microservices in Java, Go, and C++.\nArchitect fault-tolerant systems with 99.999% reliability targets.\nCollaborate with cross-functional architecture and security teams.\nMentor junior and mid-level software engineers on system design principles.',
            'Bachelor or Master degree in Computer Science, Software Engineering, or equivalent experience.\n7+ years of professional backend software development.\nStrong expertise with distributed systems, cloud computing, and microservice architectures.\nProficiency with relational and NoSQL databases.',
            'Comprehensive healthcare, dental, and vision insurance\n401(k) matching up to 6%\nGenerous stock equity compensation\nUnlimited Paid Time Off (PTO)\nRemote work flexibility stipend', 'active', 412),

            (2, 2, 1, 1, 'Senior Full Stack Engineer', 'senior-full-stack-engineer', 'Mountain View, CA', '94043', 145000.00, 210000.00, 'year', 0, 0, 1, 'Senior Level',
            'Google is looking for a versatile Senior Full Stack Engineer to lead front-to-back development on our next-generation developer tooling ecosystem. You will bridge intuitive user interfaces with resilient distributed infrastructure.',
            'Build responsive web interfaces utilizing modern HTML5, CSS3, and JavaScript standards.\nDesign robust RESTful and gRPC APIs powering real-time workflows.\nOptimize client-side performance, accessibility, and cross-browser resilience.\nParticipate in peer code reviews and architectural RFC discussions.',
            '5+ years building and scaling complex web applications.\nSolid understanding of browser internals, DOM manipulation, and asynchronous programming.\nProficiency in backend languages (PHP, Go, Python, or Java).\nExperience with CI/CD deployment pipelines.',
            'World-class health and wellness coverage\nFree gourmet on-site dining and snacks\nCompetitive bonus and stock options\nAnnual education reimbursement and conference budget', 'active', 680),

            (3, 3, 1, 4, 'Cloud Solutions Architect', 'cloud-solutions-architect', 'Remote, USA', '00000', 130000.00, 175000.00, 'year', 0, 1, 1, 'Senior Level',
            'As a Cloud Solutions Architect at Microsoft, you will partner with enterprise clients to architect modern hybrid and multi-cloud solutions on Azure, modernizing legacy systems and accelerating digital transformation.',
            'Serve as the technical authority on Azure cloud architecture and migration roadmaps.\nConduct technical deep-dives and proof-of-concept demonstrations.\nEnsure enterprise security, compliance, and governance benchmarks are met.\nDeliver technical whitepapers and best-practice blueprints.',
            'Demonstrated track record in cloud architecture and enterprise systems integration.\nCertified Azure Solutions Architect Expert or equivalent AWS certification.\nExceptional client communication and presentation skills.\nStrong foundation in network security and infrastructure-as-code.',
            '100% remote flexibility across the US\nHealth, dental, vision, and mental health support\n401(k) with immediate vesting\nHome office equipment stipend ($1,500)', 'active', 320),

            (4, 4, 5, 2, 'Customer Support Specialist', 'customer-support-specialist', 'Seattle, WA', '98101', 22.00, 28.50, 'hour', 0, 1, 0, 'Entry Level',
            'Amazon is seeking empathetic, problem-solving Customer Support Specialists to deliver world-class service to millions of customers. This role offers flexible part-time and remote shifts.',
            'Handle inbound customer inquiries via chat, email, and phone with courtesy and efficiency.\nTroubleshoot delivery, account, and order concerns using internal diagnostic tools.\nMaintain high first-contact resolution rates and customer satisfaction scores.\nDocument feedback to help product teams improve customer journeys.',
            'Excellent verbal and written English communication skills.\nPrevious customer service or helpdesk experience preferred.\nHigh-speed reliable internet connection and quiet dedicated workspace.\nAbility to adapt to rotating shifts and weekend hours.',
            'Competitive hourly wages with shift differentials\nEmployee merchandise discounts\nFlexible scheduling options\nCareer advancement opportunities within 6 months', 'active', 215),

            (5, 6, 2, 1, 'Registered Nurse (Emergency Department)', 'registered-nurse-emergency-department', 'Austin, TX', '78701', 85000.00, 115000.00, 'year', 0, 0, 1, 'Mid Level',
            'Kaiser Permanente is hiring passionate Registered Nurses to join our state-of-the-art Emergency Department. Work alongside dedicated medical professionals providing urgent, high-quality patient care in a supportive environment.',
            'Triage and deliver clinical nursing care to patients with acute and critical conditions.\nAdminister medications, IV therapies, and emergency medical treatments safely.\nCollaborate closely with attending physicians and emergency medical technicians.\nEducate patients and families on post-discharge care protocols.',
            'Active, unencumbered RN license in the State of Texas (or compact license).\nMinimum 2 years of emergency department or acute care nursing experience.\nBLS, ACLS, and PALS certifications required.\nStrong clinical critical-thinking and rapid decision-making skills.',
            'Sign-on bonus up to $15,000 for qualifying candidates\nPremium healthcare coverage with low copays\nDefined pension plan and 401(k)\nTuition reimbursement for advanced nursing degrees', 'active', 540),

            (6, 5, 3, 1, 'Senior Financial Analyst', 'senior-financial-analyst', 'New York, NY', '10001', 110000.00, 140000.00, 'year', 0, 0, 0, 'Mid Level',
            'Deloitte Financial Advisory team is seeking a Senior Financial Analyst to deliver strategic financial modeling, valuation, and forecasting for top Fortune 500 corporate clients.',
            'Develop dynamic three-statement financial models and scenario forecasts.\nAnalyze historical financial statements, variances, and KPI performance metrics.\nPrepare executive-level slide decks and reporting packages for C-suite stakeholders.\nSupport mergers & acquisitions due diligence and capital budgeting initiatives.',
            'Bachelor degree in Finance, Accounting, Economics, or related quantitative field.\n3-5 years in corporate finance, investment banking, or management consulting.\nAdvanced mastery of Microsoft Excel, financial modeling, and data visualization.\nCFA or CPA progress is a strong advantage.',
            'Competitive base salary plus performance bonus\nComprehensive medical, dental, and life insurance\nGenerous paternity/maternity parental leave\nProfessional development sponsorship', 'active', 190),

            (7, 7, 8, 1, 'UI/UX Product Designer', 'ui-ux-product-designer', 'New York, NY', '10012', 115000.00, 155000.00, 'year', 0, 1, 1, 'Mid Level',
            'Spotify is looking for a creative UI/UX Product Designer to shape the future of music listening experiences. You will design engaging features across web, mobile, and connected devices for over 500 million active music lovers.',
            'Design intuitive user journeys, wireframes, high-fidelity prototypes, and design systems.\nConduct user research, usability testing, and analyze behavioral telemetry.\nCollaborate closely with product managers and frontend engineering teams.\nAdvocate for accessible, inclusive, and visually delightful design standards.',
            '4+ years of UI/UX design experience for consumer-facing digital applications.\nCompelling portfolio demonstrating user-centered problem-solving and visual craft.\nDeep familiarity with modern typography, layout, spacing, and interaction patterns.\nFluency in Figma, prototyping tools, and web design constraints.',
            'Flexible working options (All The World Is Your Workplace program)\nSix months paid parental leave\nExtensive wellness days and mental wellbeing programs\nFree Spotify Premium for you and family', 'active', 890),

            (8, 8, 7, 1, 'Mechanical Systems Engineer', 'mechanical-systems-engineer', 'Austin, TX', '78725', 105000.00, 145000.00, 'year', 0, 0, 0, 'Mid Level',
            'Tesla is seeking a driven Mechanical Systems Engineer to design, validate, and manufacture next-generation thermal and structural components for our vehicle and energy platforms at Gigafactory Texas.',
            'Lead mechanical packaging and 3D CAD modeling for high-voltage energy systems.\nPerform finite element analysis (FEA) and computational fluid dynamics (CFD).\nPrototype, assemble, and rigorously test physical test articles.\nRoot-cause manufacturing anomalies and implement design-for-manufacturability improvements.',
            'BS or MS in Mechanical, Aerospace, or Mechatronics Engineering.\n3+ years experience with 3D CAD (CATIA, SolidWorks, or NX).\nHands-on prototyping, machining, and structural test validation experience.\nHigh passion for sustainable transport and first-principles thinking.',
            'Generous Tesla stock awards with annual refreshers\nComprehensive medical and dental coverage\nDiscounts on Tesla vehicles and accessories\nRelocation assistance to Austin, TX', 'active', 430),

            (9, 1, 4, 1, 'Product Marketing Manager', 'product-marketing-manager', 'San Francisco, CA', '94105', 120000.00, 160000.00, 'year', 0, 1, 0, 'Mid Level',
            'Lead end-to-end go-to-market strategies for enterprise cloud software. Position features, craft high-converting messaging, and launch multi-channel campaigns driving developer adoption.',
            'Craft compelling value propositions, sales enablement collateral, and pitch decks.\nCollaborate with product and sales to define pricing and packaging strategies.\nExecute webinar, conference, and digital campaign activations.\nMeasure campaign pipeline impact and ROI.',
            '4+ years B2B SaaS product marketing experience.\nProven track record of launching tech products successfully.\nExcellent copywriting and storytelling skills.\nAnalytical mindset with hubspot/salesforce familiarity.',
            'Competitive salary and quarterly incentives\n401(k) matching\nComprehensive healthcare\nRemote working allowance', 'active', 160),

            (10, 2, 6, 1, 'Human Resources Specialist', 'human-resources-specialist', 'Chicago, IL', '60601', 75000.00, 95000.00, 'year', 0, 0, 0, 'Mid Level',
            'Join Google People Operations team supporting talent onboarding, employee relations, and policy compliance across Midwest office hubs.',
            'Facilitate employee onboarding, orientation, and training programs.\nAddress employee relations inquiries with impartiality and care.\nPartner with recruitment to streamline candidate interview cycles.\nMaintain HRIS records and ensure compliance with employment regulations.',
            '3+ years in human resources or people operations.\nSolid knowledge of state and federal employment laws.\nStrong interpersonal empathy and conflict resolution skills.\nSHRM-CP or PHR certification is a plus.',
            'Health and dental plans\nGenerous paid time off\nParental leave benefits\n401k match', 'active', 280);

            INSERT OR IGNORE INTO applications (id, job_id, user_id, applicant_name, applicant_email, applicant_phone, cover_letter, resume_path, status)
            VALUES
            (1, 1, 1, 'Alex Johnson', 'seeker@example.com', '+1 (555) 234-5678', 'Dear Oracle Hiring Team,\n\nI am writing to express my strong enthusiasm for the Software Developer 5 position.', 'uploads/resumes/sample-alex-resume.pdf', 'reviewing');

            INSERT OR IGNORE INTO saved_jobs (user_id, job_id)
            VALUES (1, 2), (1, 3);
        ");
    }
}

