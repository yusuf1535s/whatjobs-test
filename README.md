# WhatJobs.com Full-Stack Recreation

A faithful full-stack recreation of [WhatJobs.com](https://www.whatjobs.com/), built using **pure HTML5, CSS3, Vanilla JavaScript, PHP 8+ (OOP, PDO), and MySQL**.

## 🎨 Visual Design Highlights
- **Authentic Brand Identity**: Recreates WhatJobs signature Black (`#181818`), White (`#FFFFFF`), and Brand Red (`#E64545`) color palette.
- **Original Vector Assets**: Uses WhatJobs authentic vector logo and branding.
- **Signature Hero Search**: Unified rounded search container featuring role search, location search, remote work toggle, and red search CTA button.
- **Responsive Job Cards**: Layout matches WhatJobs job listing cards with company logos, AI-estimated salary badges, metadata tags, and bookmarking.
- **Mobile Friendly**: Custom responsive navigation drawer and adaptive filters for desktop, tablet, and mobile screens.
- **Zero Heavy Frameworks**: 100% custom Vanilla CSS and JS without Bootstrap, Tailwind, React, or Laravel.

---

## 🚀 Getting Started with XAMPP

### 1. Database Setup
1. Launch **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open **phpMyAdmin** in your browser (`http://localhost/phpmyadmin/`).
3. Click on **New** to create a database named:
   ```sql
   whatjobs_clone
   ```
4. Select `whatjobs_clone`, navigate to the **Import** tab, choose the `database.sql` file from this project folder, and click **Go**.

### 2. Project Placement
Copy or link this project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\whatjobs-clone\
```
Then navigate to:
```
http://localhost/whatjobs-clone/
```

### 3. Alternative Quick Run (PHP Built-in Server)
You can also run directly from the project directory:
```bash
php -S localhost:8000
```
Then open:
```
http://localhost:8000/
```
*(The system automatically includes an intelligent database adapter that seamlessly connects to MySQL in XAMPP, and includes an instant local mirrored database for standalone testing if MySQL is not started).*

---

## ⚡ Deploying to Vercel

The project is pre-configured with **`vercel.json`** and a serverless gateway (**`api/index.php`**) powered by the **`vercel-php`** runtime.

### Option A: One-Click Deploy via GitHub (Recommended)
1. Push this project folder to a GitHub repository:
   ```bash
   git init
   git add .
   git commit -m "Initial commit for Vercel deployment"
   git branch -M main
   git remote add origin https://github.com/<your-username>/<your-repo-name>.git
   git push -u origin main
   ```
2. Go to [Vercel Dashboard](https://vercel.com/new).
3. Click **Import Repository** and select your GitHub repo.
4. Leave Build & Output settings as default (Vercel automatically detects `vercel.json`).
5. *(Optional)* Add Cloud MySQL Environment Variables under **Environment Variables** (see below).
6. Click **Deploy**. Your site will be live instantly on `https://<your-project>.vercel.app`.

---

### Option B: Deploy via Vercel CLI
If you have Node.js / Vercel CLI installed:
```bash
# 1. Install Vercel CLI globally (if not already installed)
npm install -g vercel

# 2. Login to Vercel
vercel login

# 3. Deploy from the project root
vercel

# 4. Deploy to production
vercel --prod
```

---

### 🗄️ Database Options on Vercel

1. **Zero Configuration (Instant Out-of-the-Box)**:
   - If no MySQL environment variables are configured, the app automatically runs on its built-in serverless SQLite database, complete with pre-seeded jobs, companies, admin (`admin` / `admin123`), and demo seeker accounts (`seeker@example.com` / `password123`).

2. **Cloud MySQL (Production)**:
   - Connect any cloud MySQL database (e.g. PlanetScale, Supabase, Neon, TiDB Cloud, Aiven, or Clever Cloud) by setting these environment variables in your Vercel Project Settings:
     - `DB_HOST`: Your remote MySQL hostname (e.g. `aws.connect.psdb.cloud`)
     - `DB_PORT`: `3306`
     - `DB_NAME`: `whatjobs_clone`
     - `DB_USER`: Your MySQL username
     - `DB_PASS`: Your MySQL password
     - `DB_CHARSET`: `utf8mb4`

---

## 🔑 Demo Account Credentials

### Job Seeker Account
- **URL**: `login.php`
- **Email**: `seeker@example.com`
- **Password**: `password123`

### Employer & Administrative Account
- **URL**: `admin/login.php`
- **Username**: `admin` *(or `admin@whatjobs.com`)*
- **Password**: `admin123`

---

## 📂 Project Structure

```
whatjobs-clone/
│
├── admin/                           # Administrative Suite
│   ├── index.php                    # Admin gateway redirect
│   ├── login.php                    # Admin login portal
│   ├── dashboard.php                # Admin metrics & recent activity
│   ├── manage-jobs.php              # Full job listings CRUD & status toggles
│   ├── add-job.php                  # Post new job vacancy form
│   ├── edit-job.php                 # Edit existing job posting
│   ├── applications.php             # Review candidate applications & update status
│   ├── users.php                    # Manage registered job seekers
│   └── includes/
│       ├── admin-header.php         # Reusable admin sidebar & topbar
│       └── admin-footer.php         # Reusable admin footer
│
├── assets/                          # Static Frontend Assets
│   ├── css/
│   │   ├── style.css                # Global design system & typography
│   │   └── admin.css                # Admin console layout & data tables
│   ├── js/
│   │   └── main.js                  # Search interactions, mobile nav, AJAX bookmarks
│   └── images/
│       ├── logo.svg                 # Authentic WhatJobs light logo
│       └── logo-dark.svg            # WhatJobs dark variant logo
│
├── config/                          # Configuration & Database
│   ├── config.php                   # Base paths, session rules, upload limits
│   └── db.php                       # Robust PDO connection handler
│
├── includes/                        # Backend Modules & Components
│   ├── header.php                   # Global top navigation bar
│   ├── footer.php                   # Multi-column WhatJobs footer
│   ├── auth.php                     # Auth guards, role checks, session helpers
│   ├── csrf.php                     # CSRF token generator & validator
│   ├── Job.php                      # Job data access object
│   ├── User.php                     # User & admin account model
│   └── Application.php              # Candidate application tracking model
│
├── uploads/
│   └── resumes/                     # Candidate uploaded resumes (.pdf, .doc, .docx)
│
├── index.php                        # Recreated Homepage
├── jobs.php                         # Search results & multi-faceted filtering
├── job-detail.php                   # Full job specification & company details
├── apply.php                        # Candidate application submission form
├── login.php                        # Seeker login
├── register.php                     # Seeker registration & resume upload
├── logout.php                       # Secure session termination
├── seeker-dashboard.php             # Seeker applications tracker & profile editor
├── saved-jobs.php                   # Saved / bookmarked jobs wishlist
└── database.sql                     # Relational schema with seed data
```

---

## 🔒 Security Implementations
- **SQL Injection Prevention**: All queries use PDO prepared statements with bound parameters.
- **XSS Protection**: User-generated outputs are sanitized with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **CSRF Defense**: All form submissions and state-changing actions require verified CSRF tokens.
- **Secure Password Hashing**: Passwords are encrypted with PHP's `password_hash()` using `PASSWORD_BCRYPT`.
- **File Upload Security**: Resumes are strictly validated for MIME type (`application/pdf`, `msword`, `vnd.openxmlformats`), extension, and file size limits with randomized file names.
- **Session Protection**: Hardened session cookies with `HttpOnly` and `SameSite=Lax`.

