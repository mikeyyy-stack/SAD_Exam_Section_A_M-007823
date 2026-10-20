# Student Scholarship Monitoring and Academic Compliance System

**SAD Laboratory Examination | Section A | Student ID: M-007823**

---

## 📋 Overview

A browser-based minimum viable system for managing Merit Scholar and Community Scholar grants. Features Scholar and Staff roles with grade submission, evidence verification, policy-based compliance evaluation, and renewal decision workflows.

## 🛠 Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 8.x
- **Database**: Supabase (PostgreSQL)
- **Authentication**: Supabase Auth
- **Server**: XAMPP / Apache

## ⚙️ Setup Instructions

### Prerequisites
- XAMPP with PHP 8.x and cURL enabled
- Active internet connection (for Supabase)

### Environment Variables
The following are configured in `config.php`:
- `SUPABASE_URL` - Supabase project URL
- `SUPABASE_ANON_KEY` - Supabase anonymous/public key

> **Note**: No service-role keys or passwords are stored in source code.

### Installation Steps

1. **Clone the repository**:
   ```bash
   git clone https://github.com/mikeyyy-stack/SAD_Exam_Section_A_M-007823.git
   ```

2. **Place in XAMPP htdocs**:
   ```
   C:\xampp\htdocs\SAD_Exam_Section_A_M-007823\
   ```

3. **Run the database schema** in Supabase SQL Editor:
   - Open Supabase Dashboard → SQL Editor
   - Paste and execute `database/schema.sql`

4. **Seed test data**:
   - Navigate to: `http://localhost/SAD_Exam_Section_A_M-007823/setup/seed.php`
   - Wait for all records to be created

5. **Access the application**:
   - URL: `http://localhost/SAD_Exam_Section_A_M-007823/login.php`

## 👥 Test Accounts

| Role | Email | Password | Student ID | Program |
|------|-------|----------|------------|---------|
| **Staff** | staff@scholarship.edu | Staff@123 | — | — |
| Scholar | scholar.a01@scholarship.edu | Scholar@123 | S-A01 | Merit Scholar |
| Scholar | scholar.a02@scholarship.edu | Scholar@123 | S-A02 | Merit Scholar |
| Scholar | scholar.a03@scholarship.edu | Scholar@123 | S-A03 | Community Scholar |
| Scholar | scholar.a04@scholarship.edu | Scholar@123 | S-A04 | Community Scholar |
| Scholar | scholar.a05@scholarship.edu | Scholar@123 | S-A05 | Merit Scholar |
| Scholar | scholar.a06@scholarship.edu | Scholar@123 | S-A06 | Community Scholar |

## 📁 Project Structure

```
SAD_Exam_Section_A_M-007823/
├── index.php                  # Root redirect
├── login.php                  # Authentication page
├── logout.php                 # Session logout
├── config.php                 # Supabase configuration
├── api/
│   └── handler.php            # API backend with role enforcement
├── staff/
│   ├── dashboard.php          # Staff dashboard with stats & search
│   ├── scholars.php           # Scholar registration & assignment
│   └── submissions.php        # Verification, compliance, renewal
├── scholar/
│   ├── dashboard.php          # Scholar's own submissions view
│   └── submit.php             # Grade submission form
├── assets/
│   └── css/
│       └── style.css          # Premium dark theme stylesheet
├── database/
│   └── schema.sql             # Database schema & seed programs
├── setup/
│   └── seed.php               # Test data seeding script
├── docs/
│   └── analysis_report.md     # Part I: Analysis & Design Document
└── README.md                  # This file
```

## 🔐 Security Features

- Supabase Auth for authentication
- Server-side role enforcement in `api/handler.php`
- Scholars can only access their own data (enforced at API level)
- Row Level Security (RLS) enabled on all tables
- No service-role keys in source code

## 📊 Policy Rules

| Rule | Merit Scholar | Community Scholar |
|------|:------------:|:-----------------:|
| Max GWA | 1.75 | 2.25 |
| Min Units | 18 | 15 |
| Allowed Failed | 0 | 0 |
| Allowed Incomplete | 0 | 0 |
| Allowed Dropped | 0 | 1 |
| Deadline | 2026-10-30 17:00 PHT | 2026-10-30 17:00 PHT |

## 🐛 Known Defects

- None critical at time of submission

## 📝 License

For academic examination purposes only.
