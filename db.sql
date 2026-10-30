CREATE DATABASE IF NOT EXISTS siu_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siu_management;

DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS library_books;
DROP TABLE IF EXISTS applicants;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS lecturers;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('applicant', 'student', 'lecturer', 'admin') NOT NULL DEFAULT 'applicant',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(180) NOT NULL,
    level VARCHAR(80) NOT NULL,
    duration VARCHAR(80) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lecturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NULL,
    phone VARCHAR(50) NULL,
    office VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    lecturer_id INT NULL,
    code VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    credits INT NOT NULL DEFAULT 3,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(80) NULL,
    address VARCHAR(255) NULL,
    nationality VARCHAR(100) NULL,
    birth_date DATE NULL,
    previous_school VARCHAR(255) NULL,
    program_id INT NULL,
    guardian_name VARCHAR(150) NULL,
    guardian_phone VARCHAR(80) NULL,
    certificate_file VARCHAR(255) NULL,
    id_document VARCHAR(255) NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    registration_number VARCHAR(120) NOT NULL UNIQUE,
    program_id INT NULL,
    enrollment_year VARCHAR(20) NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Active',
    tuition_total DECIMAL(10,2) NOT NULL DEFAULT 2500.00,
    tuition_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester VARCHAR(50) NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    grade VARCHAR(10) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(180) NULL,
    isbn VARCHAR(40) NULL,
    category VARCHAR(120) NULL,
    available_copies INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_library_books_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    audience ENUM('public', 'students', 'lecturers', 'applicants') NOT NULL DEFAULT 'public',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (name, description) VALUES
('Faculty of Science', 'Science and technology departments'),
('School of Business', 'Business and management programs'),
('Faculty of Arts', 'Humanities and creative arts');

INSERT INTO programs (department_id, name, level, duration, description) VALUES
(1, 'BSc Computer Science', 'Undergraduate', '4 Years', 'A practical program for modern computing.'),
(2, 'BBA Business Administration', 'Undergraduate', '4 Years', 'Develop business, management and leadership skills.'),
(3, 'BA English Literature', 'Undergraduate', '4 Years', 'Explore language, writing, and cultural studies.');

INSERT INTO courses (program_id, lecturer_id, code, title, description, credits) VALUES
(1, NULL, 'CSC101', 'Introduction to Computing', 'Foundations of programming and systems.', 3),
(2, NULL, 'BUS101', 'Principles of Management', 'Core business and organizational behavior.', 3),
(3, NULL, 'ENG101', 'English Literature I', 'Study of classic and contemporary texts.', 3);

INSERT INTO announcements (title, message, audience, created_at) VALUES
('Welcome to Starford IU', 'The university management system is ready. Applicants can register and admins can review applications.', 'public', NOW()),
('Semester Registration Open', 'Students may now register for courses through the student portal.', 'students', NOW()),
('Lecture Materials Ready', 'Lecturers can upload materials for their assigned courses.', 'lecturers', NOW()),
('New Academic Year Begins', 'Starford International University welcomes over 500 new students for the 2026-2027 academic year. Our enrollment has grown by 25% this year.', 'public', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('Graduation Ceremony 2026', 'Congratulations to our 350 graduates! This year\'s ceremony featured keynote speaker Dr. Sarah Johnson, Minister of Education.', 'public', DATE_SUB(NOW(), INTERVAL 14 DAY)),
('Research Excellence Award', 'Dr. Michael Chen from the Faculty of Science receives the prestigious International Research Excellence Award for his work in renewable energy.', 'public', DATE_SUB(NOW(), INTERVAL 21 DAY)),
('Student Achievement Spotlight', 'Our students have achieved remarkable success: 95% employment rate within 6 months of graduation, and multiple international competition wins.', 'students', DATE_SUB(NOW(), INTERVAL 28 DAY)),
('Campus Infrastructure Upgrade', 'New state-of-the-art computer labs and modernized classrooms now available. The university has invested $2.5 million in campus improvements.', 'public', DATE_SUB(NOW(), INTERVAL 35 DAY)),
('International Partnership Program', 'Starford IU partners with 15 universities worldwide for student exchange programs. Applications now open for Fall 2026 semester abroad.', 'students', DATE_SUB(NOW(), INTERVAL 42 DAY));

INSERT INTO events (title, description, event_date, location, created_at) VALUES
('Orientation Week', 'Welcome new students and introduce campus services.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Main Auditorium', NOW()),
('Career Fair', 'Meet industry partners and explore internship opportunities.', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Student Center', NOW()),
('International Student Welcome', 'Special orientation for our international students from 12 countries. Cultural activities and academic advising included.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'International Center', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Research Symposium', 'Annual research showcase featuring student and faculty projects. Topics include AI, sustainable energy, and social sciences.', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'Science Building Auditorium', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('Alumni Networking Event', 'Connect with successful alumni in various industries. Career advice, mentorship opportunities, and job referrals available.', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'Grand Ballroom', DATE_SUB(NOW(), INTERVAL 15 DAY)),
('Cultural Diversity Festival', 'Celebrate our multicultural community with food, music, and performances from students representing 25+ nationalities.', DATE_ADD(CURDATE(), INTERVAL 75 DAY), 'Campus Quad', DATE_SUB(NOW(), INTERVAL 20 DAY)),
('Entrepreneurship Workshop', 'Learn from successful entrepreneurs and business leaders. Pitch competitions and startup incubation opportunities.', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'Business School', DATE_SUB(NOW(), INTERVAL 25 DAY));
