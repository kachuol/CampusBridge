# Starford International University Management System

This project is a PHP/MySQL based university management platform for Starford International University. It includes a public website, applicant registration, role-based dashboards, secure login, and admin controls.

## Features
- Public website with programs, admissions, news, and contact sections
- Applicant registration and application submission
- Role-based login for applicants, students, lecturers, and administrators
- Student portal for courses, grades, materials, and announcements
- Lecturer portal for course materials and announcements
- Admin portal for reviewing applicants and posting announcements
- Admin admissions review with applicant details and supporting documents
- Library catalogue managed by administrators and visible to students
- Student tuition balances and administrator fee-clearance tools
- Secure password hashing and session management

## Setup Instructions
1. Copy the project into your web server root.
2. Create a MySQL database named `siu_management`.
3. Import the `db.sql` file into the database.
4. Update database credentials in `config.php` if needed.
5. Run `seed_admin.php` once in your browser or CLI to create the admin user.
   - Default admin credentials:
     - Email: `admin@starford.edu`
     - Password: `Admin@1234`
6. Open `index.php` in the browser.

## Notes
- Applicant registration is the only public registration flow.
- Admins can approve or reject applications from `admin_dashboard.php`.
- Admins can open applicant transcripts and ID documents from each application review panel.
- Uploaded files are stored in the `uploads/` folder; application documents are served through the admin-only `applicant_document.php` endpoint.
- Administrators can manage the book catalogue at `library.php`; students can browse it from their dashboard.
- Student balances are shown in the student dashboard. Administrators can update tuition totals and amounts paid in the Finance section of `admin_dashboard.php`.
- Customize programs and departments in the database.
