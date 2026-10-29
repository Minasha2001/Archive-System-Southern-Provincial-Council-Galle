CREATE DATABASE IF NOT EXISTS archive_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE archive_db;

DROP TABLE IF EXISTS file_issues;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS archive_files;
DROP TABLE IF EXISTS users;

-- role:     user | admin | subject (විෂය නිලධාරී)
-- status:   pending = admin අනුමත කරන තෙක්, active = login විය හැක, disabled = අක්‍රීය, deleted = මකා දැමූ
CREATE TABLE users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(150) NOT NULL,
    name           VARCHAR(150) NULL,
    nic            VARCHAR(15)  NOT NULL UNIQUE,
    email          VARCHAR(150) NOT NULL UNIQUE,
    designation    VARCHAR(120) NOT NULL,
    subject_number VARCHAR(40)  NOT NULL,
    role           ENUM('user','admin','subject') NOT NULL DEFAULT 'user',
    department     ENUM('ආයතන','සභා','ගිණුම්') NOT NULL,
    username       VARCHAR(60)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    status         ENUM('pending','active','disabled','deleted') NOT NULL DEFAULT 'pending',
    approved_by    INT NULL,
    approved_at    DATETIME NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at     DATETIME NULL,
    INDEX (role), INDEX (status), INDEX (department)
) ENGINE=InnoDB;

-- status: pending -> subject officer -> waiting_approval -> admin -> active
--         rejected = admin විසින් subject officer වෙත ආපසු යැවූ
--         deleted  = admin විසින් ලේඛනාගාරයෙන් ඉවත් කළ
--
-- rack_no                          = Archive Tree Number (subject officer විසින් ලබා දෙන, e.g. R-001)
-- loc_rack_no / loc_row / loc_section = File Location (user විසින් submit කරන විට ඇතුළත් කරන
--                                       Rack No, Row, සහ Section: I | II | III)
CREATE TABLE archive_files (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    submitted_by   INT NOT NULL,
    submit_date    DATE NOT NULL,
    subject_no     VARCHAR(40)  NOT NULL,
    subject_number VARCHAR(40)  NOT NULL,
    department     ENUM('ආයතන','සභා','ගිණුම්') NOT NULL,
    file_no        VARCHAR(60)  NOT NULL,
    file_number    VARCHAR(60)  NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    pages          INT NOT NULL,
    page_count     INT NOT NULL,
    right_side     INT NULL,
    left_side      INT NULL,
    archive_no     VARCHAR(60) NULL UNIQUE,
    archive_number VARCHAR(60) NULL UNIQUE,
    rack_no        VARCHAR(60) NULL,
    loc_rack_no    VARCHAR(20) NULL,
    loc_row        VARCHAR(20) NULL,
    loc_section    ENUM('I','II','III') NULL,
    status         ENUM('pending','archived','rejected','waiting_approval','active','deleted') NOT NULL DEFAULT 'pending',
    reviewed_by    INT NULL,
    reviewed_at    DATETIME NULL,
    sent_by        INT NULL,
    sent_date      DATETIME NULL,
    approved_by    INT NULL,
    approved_at    DATETIME NULL,
    rejected_at    DATETIME NULL,
    reject_reason  TEXT NULL,
    deleted_at     DATETIME NULL,
    deleted_by     INT NULL,
    delete_reason  TEXT NULL,
    file_path      VARCHAR(500) NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (department), INDEX (subject_no), INDEX (status),
    INDEX (archive_no), INDEX (file_no),
    INDEX (loc_rack_no, loc_row, loc_section),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by)  REFERENCES users(id),
    FOREIGN KEY (sent_by)      REFERENCES users(id),
    FOREIGN KEY (approved_by)  REFERENCES users(id),
    FOREIGN KEY (deleted_by)   REFERENCES users(id)
) ENGINE=InnoDB;

-- ලේඛනාගාරයෙන් ලිපිගොනුවක් ලබා ගැනීම / ආපසු බාර දීම
CREATE TABLE file_issues (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    file_id       INT NOT NULL,
    taken_by      VARCHAR(150) NOT NULL,
    taken_date    DATE NOT NULL,
    returned_date DATE NULL,
    issued_by     INT NULL,
    received_by   INT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (file_id), INDEX (returned_date),
    FOREIGN KEY (file_id) REFERENCES archive_files(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== පරීක්ෂා කිරීමට default accounts (පසුව password වෙනස් කරන්න) =====
--   admin    / Admin@123
--   subject1 / Subject@123
--   minasha  / User@123
INSERT INTO users (full_name, nic, email, designation, subject_number, role, department, username, password_hash, status, approved_at) VALUES
('System Administrator',    '199000000001', 'admin@example.com',   'Administrator',        'ADM-001', 'admin',   'ආයතන', 'admin',
 '$2y$10$D9uenJdVoENbIXEHUHR4yeei9qJ7RmVrRyewdVj/pyKhoC7vuxC4K', 'active', NOW()),
('Subject Officer',         '198500000002', 'subject@example.com', 'Subject Officer',      'SUB-100', 'subject', 'ගිණුම්', 'subject1',
 '$2y$10$Jbu17qxjBfPt8aoPqz7AMO8aNvCFHuFjZ.lHLdRp2Gx4niEcX1fqy', 'active', NOW()),
('P.G. Minasha Shenali',    '199500000003', 'minasha@example.com', 'Management Assistant', 'SUB-015', 'user',    'ආයතන', 'minasha',
 '$2y$10$5XCCl.lH/QNmxz/6EzE9w.goZ8kCh.FM1uR6OBhOowWorUvwP0wMW', 'active', NOW()),
('K.A. Nuwan Perera',       '199200000004', 'nuwan@example.com',   'Clerk',                'SUB-016', 'user',    'සභා',   'nuwan.p',
 '$2y$10$5XCCl.lH/QNmxz/6EzE9w.goZ8kCh.FM1uR6OBhOowWorUvwP0wMW', 'pending', NULL),
('S.M. Dilini Fernando',    '198800000005', 'dilini@example.com',  'Subject Officer',      'SUB-101', 'subject', 'ආයතන', 'dilini.f',
 '$2y$10$Jbu17qxjBfPt8aoPqz7AMO8aNvCFHuFjZ.lHLdRp2Gx4niEcX1fqy', 'pending', NULL),
('R.D. Chamara Silva',      '199100000006', 'chamara@example.com', 'Clerk',                'SUB-017', 'user',    'ගිණුම්', 'chamara',
 '$2y$10$5XCCl.lH/QNmxz/6EzE9w.goZ8kCh.FM1uR6OBhOowWorUvwP0wMW', 'deleted', NOW()),
('W.H. Anjalee Jayasinghe', '198700000007', 'anjalee@example.com', 'Subject Officer',      'SUB-102', 'subject', 'සභා',   'anjalee',
 '$2y$10$Jbu17qxjBfPt8aoPqz7AMO8aNvCFHuFjZ.lHLdRp2Gx4niEcX1fqy', 'active', NOW());

UPDATE users SET deleted_at = '2026-09-01 10:00:00' WHERE username = 'chamara';

INSERT INTO archive_files
(submitted_by, submit_date, subject_no, subject_number, department, file_no, file_number, file_name, start_date, end_date,
 pages, page_count, right_side, left_side, archive_no, archive_number, rack_no, status,
 reviewed_by, reviewed_at, sent_by, sent_date, approved_by, approved_at, rejected_at, reject_reason, deleted_at, deleted_by, delete_reason) VALUES
(3,'2026-06-15','SUB-001','SUB-001','ගිණුම්',  'F-101','F-101','වාර්ෂික මුදල් වාර්තාව 2026','2026-01-01','2026-05-30',120,120,60,60,'ARC-001','ARC-001','R-001','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-06-10','SUB-002','SUB-002','ගිණුම්',  'F-102','F-102','වාර්ෂික අයවැය සැලැස්ම','2026-01-01','2026-04-30',85,85,42,43,'ARC-002','ARC-002','R-002','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-12-01','SUB-015','SUB-015','ආයතන',    'F-115','F-115','කාර්ය මණ්ඩල විස්තර','2025-01-01','2025-11-30',64,64,32,32,'ARC-015','ARC-015','R-003','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-05-20','SUB-021','SUB-021','සභා',     'F-121','F-121','චක්‍රලේඛ අංක 07/2026','2026-03-01','2026-05-15',12,12,6,6,'ARC-021','ARC-021','R-004','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-04-18','SUB-030','SUB-030','ආයතන',    'F-130','F-130','තොරතුරු පද්ධති යෝජනාව','2026-02-01','2026-04-10',40,40,20,20,'ARC-030','ARC-030','R-005','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-11-25','SUB-045','SUB-045','සභා',     'F-145','F-145','ව්‍යාපෘති වාර්තාව','2025-06-01','2025-11-20',210,210,105,105,'ARC-045','ARC-045','R-006','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-11-20','SUB-046','SUB-046','සභා',     'F-146','F-146','මාසික ප්‍රගති වාර්තාව','2025-10-01','2025-10-31',18,18,9,9,'ARC-046','ARC-046','R-007','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-03-15','SUB-058','SUB-058','සභා',     'F-158','F-158','රැස්වීම් වාර්තා','2026-01-05','2026-03-10',33,33,16,17,'ARC-058','ARC-058','R-008','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-10-12','SUB-063','SUB-063','ගිණුම්',  'F-163','F-163','ප්‍රසම්පාදන සැලැස්ම','2025-04-01','2025-09-30',76,76,38,38,'ARC-063','ARC-063','R-009','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-09-30','SUB-070','SUB-070','ආයතන',    'F-170','F-170','නිවාඩු වාර්තා','2025-01-01','2025-09-15',52,52,26,26,'ARC-070','ARC-070','R-010','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-02-08','SUB-074','SUB-074','ආයතන',    'F-174','F-174','ජාලය විගණනය','2025-11-01','2026-01-31',28,28,14,14,'ARC-074','ARC-074','R-011','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2026-01-22','SUB-081','SUB-081','සභා',     'F-181','F-181','පුහුණු කාලසටහන','2026-01-01','2026-01-20',9,9,4,5,'ARC-081','ARC-081','R-012','active',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,NULL,NULL,NULL),
(3,'2025-08-20','SUB-050','SUB-050','ආයතන',    'F-150','F-150','පැරණි ගිවිසුම් ගොනුව','2025-01-10','2025-08-01',55,55,28,27,'ARC-071','ARC-071','R-013','deleted',2,NOW(),3,NOW(),1,NOW(),NULL,NULL,'2026-09-15 09:30:00',1,'කල් ඉකුත් වූ ගිවිසුම් ගොනුවක් වන බැවින් ලේඛනාගාරයෙන් ඉවත් කරන ලදී.'),
(3,'2026-10-12','SUB-090','SUB-090','ආයතන',    'F-190','F-190','ඉඩම් ලේඛන 2025','2025-01-02','2025-12-20',104,104,60,44,NULL,NULL,NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,'2026-10-14','SUB-091','SUB-091','සභා',     'F-191','F-191','සභා සැසි වාර්තාව','2026-02-01','2026-08-30',50,50,28,22,'ARC-091','ARC-091','R-014','waiting_approval',2,NOW(),2,NOW(),NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,'2026-10-16','SUB-092','SUB-092','ගිණුම්',  'F-192','F-192','ගෙවීම් වවුචර Q3','2026-07-01','2026-09-30',165,165,90,75,'ARC-092','ARC-092','R-015','rejected',2,NOW(),2,NOW(),NULL,NULL,NULL,'පිටු ගණන හා දින පරාසය නැවත පරීක්ෂා කරන්න.',NULL,NULL,NULL);

-- ===== File Location (Rack No, Row, Section) sample data =====
UPDATE archive_files SET loc_rack_no='1', loc_row='1', loc_section='I'   WHERE id=1;
UPDATE archive_files SET loc_rack_no='1', loc_row='2', loc_section='I'   WHERE id=2;
UPDATE archive_files SET loc_rack_no='1', loc_row='3', loc_section='II'  WHERE id=3;
UPDATE archive_files SET loc_rack_no='2', loc_row='1', loc_section='I'   WHERE id=4;
UPDATE archive_files SET loc_rack_no='2', loc_row='2', loc_section='II'  WHERE id=5;
UPDATE archive_files SET loc_rack_no='2', loc_row='3', loc_section='III' WHERE id=6;
UPDATE archive_files SET loc_rack_no='3', loc_row='1', loc_section='I'   WHERE id=7;
UPDATE archive_files SET loc_rack_no='3', loc_row='2', loc_section='II'  WHERE id=8;
UPDATE archive_files SET loc_rack_no='3', loc_row='3', loc_section='III' WHERE id=9;
UPDATE archive_files SET loc_rack_no='4', loc_row='1', loc_section='I'   WHERE id=10;
UPDATE archive_files SET loc_rack_no='4', loc_row='2', loc_section='II'  WHERE id=11;
UPDATE archive_files SET loc_rack_no='4', loc_row='3', loc_section='III' WHERE id=12;
UPDATE archive_files SET loc_rack_no='5', loc_row='1', loc_section='I'   WHERE id=13;
UPDATE archive_files SET loc_rack_no='5', loc_row='2', loc_section='II'  WHERE id=14;
UPDATE archive_files SET loc_rack_no='5', loc_row='3', loc_section='III' WHERE id=15;
UPDATE archive_files SET loc_rack_no='6', loc_row='1', loc_section='I'   WHERE id=16;

INSERT INTO file_issues (file_id, taken_by, taken_date, returned_date, issued_by, received_by) VALUES
(2, 'K. Perera',    '2026-10-10', NULL,         2, NULL),
(4, 'S. Fernando',  '2026-09-22', '2026-09-29', 2, 2);