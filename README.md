# POWERMAGZ GYM

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.6-003545?logo=mariadb)](https://mariadb.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql)](https://www.mysql.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?logo=apache)](https://httpd.apache.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)](https://www.docker.com/)
[![Tailwind](https://img.shields.io/badge/Tailwind-CDN-38BDF8?logo=tailwindcss)](https://tailwindcss.com/)
[![PWA](https://img.shields.io/badge/PWA-Offline--ready-5A0FC8?logo=pwa)](public/sw.js)
[![Security](https://img.shields.io/badge/Security-MFA%20%7C%20AES--256--GCM-3ECF8E)](src/Helpers/crypto.php)
[![License](https://img.shields.io/badge/License-MIT-3ECF8E)](LICENSE)

**A centralized gym management platform for PowerMagz Gym, Tabaco City**
Digitizes memberships, enrollments, payments, attendance, and partner commissions — replacing paper logbooks and manual cash records with real-time digital transparency.

🔗 **Live demo:** https://powermagz-gym.onrender.com

---

## 📑 Table of Contents

- [Overview](#-overview)
- [Problem Statement](#-problem-statement)
- [System Description](#-system-description)
- [Features](#-features)
- [Technical Stack](#-technical-stack)
- [Database Schema](#-database-schema)
- [Getting Started](#-getting-started)
- [Demo Credentials](#-demo-credentials)
- [Project Structure](#-project-structure)
- [Design System & Responsiveness](#-design-system--responsiveness)
- [Security Features](#-security-features)
- [Usage Flows](#-usage-flows)
- [Contributors](#-contributors)
- [License](#-license)
- [Support](#-support)

---

## 🎯 Overview

The **PowerMagz Gym Management System** streamlines the daily operations of a local fitness business by providing:

- **Membership Management** – Tiered plans, enrollments, renewals, and lifecycle control.
- **Payment Tracking** – GCash and cash payments, confirmations, refunds, and receipts.
- **Attendance & Check-In** – QR-code gym entrance scanning with live occupancy.
- **Partner & Commission Module** – Coaches and suppliers log sales and request payouts.
- **Administrative Control** – Reports, exports, analytics, audit logs, and system settings.

> Eliminates handwritten logbooks, lost receipts, and manual commission computation — improving accuracy, accountability, and member experience at PowerMagz Gym.

---

## ❗ Problem Statement

- Members must visit the gym counter in person to enroll, renew, or pay → **long queues**.
- Attendance is recorded on **paper logbooks** → lost entries, no reliable headcount.
- Payments and receipts are tracked manually → **misplaced records and disputes**.
- Partner/coach commissions are computed by hand → **errors and delayed payouts**.
- Owners have no consolidated view of revenue, active members, or gym capacity.
- Members cannot check their remaining plan days, payment history, or attendance.

---

## 💡 System Description

The system provides a **centralized web platform** where members manage their membership online and administrators verify transactions through role-based dashboards. Members enroll in a tier, submit GCash or cash payments, scan the entrance QR code to check in, and book classes. Admins approve enrollments, confirm payments, manage partners and payouts, and generate reports. Every sensitive action is recorded in an audit log, and digital records replace paper ones — improving transparency and operational accuracy.

---

## ✨ Features

### 👑 Admin

- Dashboard with KPIs, revenue, occupancy, and trend analytics
- Member management with search, edit, delete, and CSV export
- Program/tier management (create, duplicate, reorder, preview, toggle)
- Enrollment lifecycle: approve, extend, pause, resume, cancel, upgrade, downgrade, batch renew
- Payment management: confirm, bulk confirm, refund, mark failed, aging report, receipts
- Attendance management: manual check-in, clock-out, edit, delete, export
- Partner sales approval, payout batches, and payout processing
- B2B client management and exports
- Group class management
- System settings: payment methods, collection frequency, grace period, operational cost, commission rate, gym capacity, idle timeout
- Audit log viewer, export, and purge

### 🤝 Partners (Coaches & Suppliers)

- Partner dashboard with client list and B2B linkage
- Log sales and track commission earned
- Wallet balance and payout requests
- Message linked clients
- Profile management

### 🧑‍🎓 Members

- Browse membership tiers and enroll online (Digital, Standard, Premium)
- Submit GCash or cash payment records and view payment history
- QR-code gym check-in / check-out
- Live gym occupancy indicator
- Book and cancel group classes
- Program recommender and AI coach chat
- Attendance history and in-app notifications
- Profile management and password change

### 🌐 System-wide

- 🌙 Dark, high-contrast interface across the public site and dashboards
- 🔔 In-app notifications
- ✅ Server-side validation and CSRF protection on every form
- 📱 Mobile-responsive layouts and accessible markup
- 🔐 Role-based navigation — each role sees only its own tools

---

## 🛠️ Technical Stack

| Layer              | Technology                                              |
|--------------------|---------------------------------------------------------|
| Language           | PHP 8.2                                                 |
| Architecture       | MVC with PSR-4 autoloading (`App\`)                     |
| Database           | MariaDB 10.6 / MySQL 8 (PDO, prepared statements)       |
| Frontend           | Server-rendered PHP views, vanilla CSS & JavaScript     |
| Charts / UI        | Hand-built CSS components, no frontend framework        |
| QR Check-In        | Configurable entrance QR secret (`GYM_QR_DATA`)         |
| Authentication     | Session + remember-me token, bcrypt (cost 12)           |
| MFA                | TOTP with recovery codes for admin accounts              |
| Security Helpers   | CSRF, rate limiting, audit logging, AES encryption      |
| Backup (CLI)       | `mysqldump` pipeline with encryption and rotation       |
| Web Server         | Apache 2.4 with `mod_rewrite`                           |
| Deployment         | Docker (bundled MariaDB) — Render-ready on port 10000   |
| Dependency Manager | Composer (autoload only, no runtime packages)           |

---

## 📊 Database Schema

| Table                | Purpose                                                        |
|----------------------|----------------------------------------------------------------|
| `users`              | Accounts, roles, password hashes, MFA secrets, OTP state       |
| `sessions`           | Server-side session records and remember-me tokens             |
| `member_profiles`    | Member details, contact info, preferences                      |
| `partner_profiles`   | Partner details, commission rate, partner type                 |
| `partners`           | Linked gym partners (coaches, suppliers)                       |
| `b2b_clients`        | Corporate/B2B client records                                   |
| `programs`           | Membership tiers, pricing, duration, visibility                |
| `enrollments`        | Member plan activations, status, start/end dates               |
| `enrollment_events`  | Enrollment lifecycle history (approve, pause, upgrade, etc.)   |
| `payments`           | GCash/cash payments, status, reference numbers, receipts       |
| `attendance`         | Check-in / check-out records per member                        |
| `classes`            | Group class schedules and capacity                             |
| `class_bookings`     | Member class reservations                                      |
| `partner_sales`      | Sales logged by partners and their commission                  |
| `payout_requests`    | Partner payout requests and approval status                    |
| `payout_batches`     | Batched payout processing                                      |
| `password_resets`    | Time-limited reset tokens                                      |
| `audit_logs`         | Immutable trail of sensitive actions                           |
| `notifications`      | Per-user in-app notifications                                  |
| `system_settings`    | Configuration (payment methods, grace period, capacity, etc.)  |

> All queries use PDO prepared statements; identifiers are validated before interpolation.

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.1+ with `pdo_mysql`, `zip` extensions
- MySQL 8 or MariaDB 10.6+
- Apache 2.4 with `mod_rewrite` enabled
- Git

### Installation (XAMPP / local Apache)

```bash
# 1. Create the database and import the schema
mysql -u root -e "CREATE DATABASE powermagz_gym CHARACTER SET utf8mb4;"
mysql -u root powermagz_gym < database/schema.sql

# 2. Copy the environment template
cp .env.example .env

# 3. Generate the application encryption key
php scripts/keygen.php

# 4. Open the app
http://localhost/powermagz-gym
```

### Installation (Docker)

```bash
docker compose up --build
# http://localhost:8000
```

`docker-compose.yml` starts Apache/PHP and MySQL 8, and imports `database/schema.sql` on first boot.

### Production checklist

- Set `APP_ENV=production`, `APP_URL`, `APP_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `DB_*`, and `GYM_QR_DATA`
- Keep `DEMO_LOGINS_ENABLED=false`
- Use a persistent disk or an external MySQL server — the server's local database is rebuilt on every restart
- Configure an SMTP transport before enabling self-registration (OTP codes are emailed)

---

## 🔑 Demo Credentials

| Role            | Dashboard            | Email                             | Password                 |
|-----------------|----------------------|-----------------------------------|--------------------------|
| 👑 Admin        | `/admin-dashboard`   | `powermagzgymadmin@gmail.com`     | `@2026PowermagzGymAdmin` |
| 🧑‍🎓 Member      | `/home`              | `powermagzgymmember@gmail.com`    | `@2026PowermagzGymMember`|
| 🤝 Partner      | `/partner-dashboard` | `powermagzgympartner@gmail.com`   | `@2026PowermagzGymPartner`|

> These accounts ship with `database/schema.sql`. On a deployed instance the boot script applies `ADMIN_PASSWORD` and disables the member/partner logins unless `DEMO_LOGINS_ENABLED=true`. Change the passwords before storing real data.

---

## 📁 Project Structure

```
powermagz-gym/
├── index.php                    # Route table and front controller
├── router.php                   # Dev-server router with the same deny rules as .htaccess
├── config/
│   ├── config.php               # Environment loader and APP_*/DB_* constants
│   └── constants.php            # Tiers, statuses, payment methods, gym capacity
├── database/
│   └── schema.sql               # Full schema + seed data (20 tables)
├── public/                      # Public assets (images, css, js)
├── scripts/                     # CLI: keygen, backup, demo hardening, MFA tools
├── src/
│   ├── bootstrap.php            # Constants, autoloader, helpers, hardened session
│   ├── Core/                    # Router and base Controller
│   ├── Config/Database.php      # PDO singleton with timezone alignment
│   ├── Controllers/             # Admin, Auth, Home, Member, Mfa, Notification, Partner
│   ├── Models/                  # 14 domain models (User, Enrollment, Payment, …)
│   ├── Helpers/                 # security, session, audit, crypto, mfa, mailer, ai
│   └── Views/
│       ├── admin/partials/      # 13 dashboard tabs (members, payments, payouts, …)
│       ├── member/              # home, programs, payments, time-in, classes, profile
│       ├── partner/             # dashboard, clients, sales, wallet, profile
│       ├── auth/ site/ layouts/ partials/ errors/
├── storage/                     # logs, uploads, backups, rate-limit state
├── tests/                       # 8 standalone domain test suites
├── Dockerfile
├── start.sh
└── docker-compose.yml
```

---

## 🎨 Design System & Responsiveness

### Color Palette

| Token          | Value     | Usage                                  |
|----------------|-----------|----------------------------------------|
| Background     | `#060707` | Page background                        |
| Surface        | `#101414` | Cards and panels                       |
| Brand          | `#00A6A6` | Primary actions, active states, focus  |
| Brand bright   | `#22C9C9` | Hover and highlight                    |
| Ink            | `#F4F7F7` | Primary text                           |
| Muted          | `#A6B0B0` | Secondary text                         |
| Success        | `#28A745` | Paid / approved / active               |
| Warning        | `#FFC107` | Pending                                |
| Danger         | `#DC3545` | Failed / cancelled / rejected          |

### Responsive Breakpoints

| Breakpoint | Range        | Layout                                                    |
|------------|--------------|-----------------------------------------------------------|
| Mobile     | < 640px      | Stacked cards, condensed tables, collapsible navigation   |
| Tablet     | 640–1023px   | Two-column grids, scrollable tables                       |
| Desktop    | 1024–1279px  | Full sidebar, multi-column dashboards                     |
| Wide       | ≥ 1280px     | Expanded tables and analytics panels                      |

### UI Highlights

- Dark, high-contrast interface with a teal brand accent
- Dashboard tab navigation shared across admin sections
- Status badges color-coded by payment/enrollment state
- Sortable, exportable data tables with bulk selection
- Modal forms and inline validation messages
- Reduced-motion support via `prefers-reduced-motion`

---

## 🔐 Security Features

- **Role-Based Access Control** – `admin`, `partner`, and `user` (member) are enforced server-side on every route.
- **CSRF Protection** – Tokens are required on every state-changing request.
- **Password Security** – bcrypt cost 12 with automatic rehash of legacy hashes; minimum strength rules on change.
- **Multi-Factor Authentication** – TOTP with recovery codes for admin accounts.
- **Session Hardening** – ID regeneration on login, idle timeout, forced re-authentication, server-side session records.
- **Rate Limiting** – File-backed counters on login (per IP and per account), registration, OTP, and password reset.
- **Secure Transport** – `Secure`, `HttpOnly`, `SameSite=Lax` cookies, HSTS, HTTPS-aware base URL.
- **Defensive Headers** – `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- **File Access Rules** – `.htaccess` and the dev router both deny `config/`, `src/`, `storage/`, `database/`, `scripts/`, `.env`, and `.git`.
- **Audit Logging** – Logins, permission changes, and financial actions are recorded with actor, IP, and timestamp.
- **Encryption Helper** – Sensitive at-rest values are protected with `APP_KEY` (AES-256-GCM).
- **Input Handling** – PDO prepared statements everywhere; output escaped through `e()`/`url_e()`.

---

## 🎯 Usage Flows

### 🧑‍🎓 Member Flow

1. Log in → view membership status, remaining days, and gym occupancy.
2. Browse tiers → enroll in a program of choice.
3. Submit a GCash or cash payment → wait for admin confirmation.
4. Scan the entrance QR code to check in and out → view attendance history.
5. Book a group class, chat with the AI coach, or manage the profile.

### 🤝 Partner Flow

1. Log in → view clients, sales, and wallet balance.
2. Log a sale → commission is computed automatically.
3. Request a payout → admin reviews and processes the batch.

### 👑 Admin Flow

1. Log in → review dashboard KPIs and analytics.
2. Approve enrollments, manage members, and process payouts.
3. Generate reports and exports → adjust system settings.
4. Review the audit log for accountability.

---

## 👥 Contributors

- Briones, Derick
- Namia, Kyle Mathew
- Sambajon, Ryecco Adrian

---

## 📄 License

Released under the [MIT License](LICENSE).
Copyright © 2026 PowerMagz Gym.

---

## 🤝 Support

For support or questions, contact the PowerMagz Gym administrative team or open an issue on this repository.

---

**Built with ❤️ for PowerMagz Gym, Tabaco City**
