# Powermagz Gym Management System

A comprehensive, full-stack gym management platform featuring a public marketing site, member self-service portal, B2B partner ecosystem, and a powerful administrative dashboard. Built on a lightweight, custom PHP MVC architecture without relying on heavy frameworks.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CDN-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PWA](https://img.shields.io/badge/PWA-Enabled-5A0FC8?logo=pwa&logoColor=white)](#progressive-web-app)
[![Security](https://img.shields.io/badge/Security-MFA%20%7C%20AES--256--GCM-success)](#security-architecture)

---

## Overview

**Powermagz Gym** is designed to handle every aspect of a modern fitness business. From a highly animated, conversion-focused public landing page to complex backend workflows like batch partner payouts, lifecycle enrollment management, and QR-based attendance tracking, this system provides a seamless, secure, and responsive experience.

The application uses a **Custom MVC framework** (located in `src/Core/`) for routing, database abstraction, and view rendering. It prioritizes zero-dependency security implementations, including native RFC 6238 TOTP (Multi-Factor Authentication) and AES-256-GCM encryption.

**Repository:** [github.com/derickbriones26/powermagz-gym](https://github.com/derickbriones26/powermagz-gym)

---

## Key Features

### ![Public Site](https://img.shields.io/badge/Public_Site-00A6A6?style=for-the-badge&logo=google-chrome&logoColor=white)
*   **Dynamic Landing Page**: Animated hero sections, infinite marquees, and scroll-reveal features.
*   **Membership Pricing**: Clearly defined tiers (Digital, Standard, Premium) with integrated registration flows.
*   **Location & Contact**: Clear display of address, phone number, and operating hours.

### ![Member Portal](https://img.shields.io/badge/Member_Portal-00A6A6?style=for-the-badge&logo=user&logoColor=white)
*   **Personal Dashboard**: Real-time status, active program details, days remaining, and outstanding balances.
*   **QR Time In/Out**: Camera-based QR scanner for gym entrance check-ins. Includes manual entry fallback.
*   **Digital ID**: Scannable member ID card overlay.
*   **Enrollment & Payments**: Browse programs, enroll via GCash, and view payment history.
*   **Attendance Tracking**: Calendar view showing check-in history and current streaks.
*   **Live Occupancy**: Real-time widget displaying how many members are currently inside the gym.

### ![Partner Portal](https://img.shields.io/badge/Partner_Portal-00A6A6?style=for-the-badge&logo=briefcase&logoColor=white)
*   **Affiliate Dashboard**: Track total clients, monthly earnings, and pending commissions.
*   **Referral System**: Unique referral codes and links for tracking new member signups.
*   **Client Management**: Add clients manually, view their status, and send direct messages.
*   **Wallet & Payouts**: Track lifetime earnings, available balance, and request payouts via GCash or Bank Transfer.

### ![Admin Dashboard](https://img.shields.io/badge/Admin_Dashboard-00A6A6?style=for-the-badge&logo=layout-dashboard&logoColor=white)
*   **Comprehensive Overview**: High-level KPIs, revenue charts, active member stats, and live gym capacity.
*   **Member Management**: Search, edit, delete members, reset passwords, and view 30-day attendance.
*   **Program & Enrollment Lifecycle**: Create membership tiers, approve pending enrollments, extend, pause, upgrade, or cancel memberships.
*   **Payment Processing**: Confirm GCash payments, issue full/partial refunds, and generate CSV aging reports.
*   **Partner Payouts**: Approve sales, process individual payouts, or group them into **Batch Payouts** for efficient bank processing.
*   **Live Monitor**: Real-time feed of who is in the gym right now, complete with active session timers.
*   **Audit Logs**: Immutable tracking of all admin actions (who, what, when, before/after data).
*   **System Settings**: Manage payment methods, grace periods, gym capacity, and idle timeouts.

### ![Security](https://img.shields.io/badge/Security-00A6A6?style=for-the-badge&logo=shield&logoColor=white)
*   **Multi-Factor Authentication (MFA)**: Pure-PHP RFC 6238 TOTP implementation for Admin and Staff accounts (Google Authenticator compatible).
*   **AES-256-GCM Encryption**: Secure storage for sensitive data (like payment tokens and database backups).
*   **Hardened Sessions**: Dual-cookie strategy (Session ID + DB Token) with idle timeout enforcement and session fixation prevention.
*   **CSRF Protection**: Strict validation on all state-changing POST requests.
*   **Audit Trails**: Automatic logging of sensitive operations, stripping PII/secrets before storage.

---

## Tech Stack

*   **Backend**: PHP 8.1+ (Custom MVC)
*   **Database**: MySQL 8.0+ / MariaDB
*   **Frontend**: Vanilla JavaScript, Tailwind CSS (via CDN), Chart.js
*   **QR Scanning**: `html5-qrcode`
*   **Infrastructure**: Docker / Docker Compose
*   **Security**: `openssl` (AES-256-GCM), native TOTP, Bcrypt (Cost 12)

---

## Installation & Setup

### Prerequisites
*   PHP 8.1+ (with `pdo_mysql`, `json`, `openssl` extensions)
*   Composer
*   MySQL 8.0+ or MariaDB
*   Docker & Docker Compose (Recommended)

### Option 1: Docker (Quick Start)
The repository includes a ready-to-use `docker-compose.yml` environment.

1. Clone the repository:
   ```bash
   git clone https://github.com/derickbriones26/powermagz-gym.git
   cd powermagz-gym
   ```

2. Copy the environment file and generate the app key:
   ```bash
   cp .env.example .env
   php src/keygen.php
   ```

3. Start the containers:
   ```bash
   docker compose up -d --build
   ```
   *The app will be available at `http://localhost:8000`.*

### Option 2: Local Development

1. **Clone the repository**:
   ```bash
   git clone https://github.com/derickbriones26/powermagz-gym.git
   cd powermagz-gym
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   Copy `.env.example` to `.env` and fill in your database credentials.
   ```bash
   cp .env.example .env
   ```

4. **Generate Encryption Key**:
   ```bash
   php src/keygen.php
   ```
   *This creates a secure `APP_KEY` used for AES-256-GCM encryption and backups.*

5. **Initialize Database**:
   Create a database and import the schema. The provided `schema.sql` also includes seed data for testing.
   ```bash
   mysql -u root -p -e "CREATE DATABASE powermagz_gym;"
   mysql -u root -p powermagz_gym < database/schema.sql
   ```

6. **Start the Server**:
   ```bash
   php -S localhost:8000 router.php
   ```

---

## Default Seed Accounts

The `database/schema.sql` file includes seeded users for immediate testing:

| Role | Dashboard URL | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | `/admin-dashboard` | `admin@powermagz.ph` | `admin1234` |
| **Member** | `/home` | `member@test.com` | `member1234` |
| **Partner** | `/partner-dashboard` | `partner@test.com` | `partner1234` |

> **Important:** Change all default passwords immediately after first login. Enable MFA for Admin accounts via `/mfa/setup`.

---

## Project Structure

```text
├── config/                 # App constants, database wrapper, .env loader
├── database/               # schema.sql (Table definitions + Seed data)
├── public/                 # Web root (CSS, JS, Images, Service Worker)
│   ├── js/                 # a11y.js, form-validation.js, qr-scanner.js, etc.
│   └── sw.js               # PWA Service Worker
├── scripts/                # CLI utilities (show-totp.php)
├── src/
│   ├── Config/             # Database connection class
│   ├── Controllers/        # Admin, Auth, Member, Mfa, Notification, Partner
│   ├── Core/               # Custom MVC (Router, Controller, Model, Request, Response)
│   ├── Helpers/            # audit.php, crypto.php, mfa.php, session_security.php
│   ├── Models/             # User, Program, Enrollment, Payment, PartnerSale, etc.
│   ├── Views/              # Admin, Auth, Errors, Layouts, Member, Partner, Site
│   ├── backup.php          # Automated DB backup pipeline
│   ├── bootstrap.php       # App initialization, session hardening, autoloader
│   └── keygen.php          # Generates APP_KEY
├── index.php               # Route definitions and dispatch
├── router.php              # PHP built-in server router
├── docker-compose.yml
└── .env.example
```

---

## Database Schema Highlights

The system uses 18 relational tables:
*   **Core**: `users`, `sessions`, `system_settings`, `audit_logs`, `notifications`
*   **Members**: `member_profiles`, `enrollments`, `enrollment_events`, `payments`, `attendance`
*   **Programs**: `programs`
*   **Partners**: `partner_profiles`, `partner_sales`, `payout_requests`, `payout_batches`, `b2b_clients`

*All tables use `utf8mb4_unicode_ci` collation and enforce foreign key constraints.*

---

## Security Notes for Production

1. **Environment File**: Ensure `.env` is **NEVER** committed to version control. Ensure `APP_KEY` is backed up securely offline.
2. **Database Backups**: The system includes a robust backup script (`src/backup.php`). Set up a daily cron job:
   ```bash
   # Run daily at 3 AM
   0 3 * * * cd /path/to/powermagz-gym && php src/backup.php >> storage/logs/backup.log 2>&1
   ```
   *Configure S3 or GCS in `.env` for geo-redundant off-site backups.*
3. **MFA**: Enforce MFA setup for all Admin and Staff accounts upon first login.
4. **HTTPS**: Ensure the app runs behind an HTTPS proxy in production (required for Secure cookies and Camera API).
5. **QR Secret**: Change the default `GYM_QR_DATA` in `.env` to a random 32-character string before printing the entrance poster.

---

## Progressive Web App (PWA)

The system includes a Service Worker (`public/sw.js`) and offline fallback page. Users can "Add to Home Screen" on iOS/Android devices. The offline tracker (`public/js/offline.js`) displays a banner if the connection drops.

---

## Contributing

1. Fork the repository: [github.com/derickbriones26/powermagz-gym](https://github.com/derickbriones26/powermagz-gym)
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m "feat: add your feature"`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

---

## License

This project is proprietary software developed for **Powermagz Gym**. All rights reserved.