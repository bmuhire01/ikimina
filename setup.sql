-- Ikimina savings group database
-- Run this in phpMyAdmin or MySQL. It is safe to run again:
-- existing tables and records are kept, and missing columns are added.

CREATE DATABASE IF NOT EXISTS ikimina
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE ikimina;

-- 1. Families (a parent/guardian and the members who save together)
CREATE TABLE IF NOT EXISTS families (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  guardian_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  join_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Members (the people in the savings group)
-- Family members have a family_id; children use member_type = 'child'.
CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  join_date DATE NOT NULL,
  date_of_birth DATE NULL,
  member_type ENUM('adult', 'child') NOT NULL DEFAULT 'adult',
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  family_id INT NULL,
  UNIQUE KEY uq_members_phone (phone),
  KEY idx_members_family (family_id),
  CONSTRAINT fk_members_family FOREIGN KEY (family_id)
    REFERENCES families(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Login accounts. member_id links a member account to its member record.
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
  member_id INT NULL,
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_member_id (member_id),
  CONSTRAINT fk_users_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Contributions paid into the group
CREATE TABLE IF NOT EXISTS contributions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  pay_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  KEY idx_contributions_member (member_id),
  KEY idx_contributions_date (pay_date),
  CONSTRAINT fk_contributions_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Loans given to members
-- Loans that several people share together share the same group_id.
-- interest_amount is set on shared loans so each person's share is exact.
CREATE TABLE IF NOT EXISTS loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  group_id INT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  interest_percent DECIMAL(5, 2) NOT NULL DEFAULT 0,
  interest_amount DECIMAL(12, 2) NULL,
  loan_date DATE NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('active', 'paid', 'overdue') NOT NULL DEFAULT 'active',
  KEY idx_loans_member (member_id),
  KEY idx_loans_group (group_id),
  KEY idx_loans_due_date (due_date),
  CONSTRAINT fk_loans_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Money paid back against a loan
CREATE TABLE IF NOT EXISTS repayments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  pay_date DATE NOT NULL,
  KEY idx_repayments_loan (loan_id),
  CONSTRAINT fk_repayments_loan FOREIGN KEY (loan_id)
    REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6b. A group of people who take one loan together
CREATE TABLE IF NOT EXISTS loan_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 7. Penalties charged to members
CREATE TABLE IF NOT EXISTS penalties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  penalty_date DATE NOT NULL,
  status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
  KEY idx_penalties_member (member_id),
  CONSTRAINT fk_penalties_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Group meetings
CREATE TABLE IF NOT EXISTS meetings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_date DATE NOT NULL,
  place VARCHAR(150) NOT NULL,
  notes TEXT NULL,
  KEY idx_meetings_date (meeting_date)
) ENGINE=InnoDB;

-- 9. Who attended each meeting
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id INT NOT NULL,
  member_id INT NOT NULL,
  present TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_attendance (meeting_id, member_id),
  CONSTRAINT fk_attendance_meeting FOREIGN KEY (meeting_id)
    REFERENCES meetings(id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Group settings (one row, id = 1)
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_name VARCHAR(150) NOT NULL,
  contribution_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  loan_interest_percent DECIMAL(5, 2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Upgrade columns if this database was created by an older version.
ALTER TABLE members
  ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER join_date;

-- Children may not have a phone number, so the column must allow NULL.
ALTER TABLE members
  MODIFY COLUMN phone VARCHAR(20) NULL;

ALTER TABLE members
  ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER join_date;

ALTER TABLE members
  ADD COLUMN IF NOT EXISTS member_type ENUM('adult', 'child') NOT NULL DEFAULT 'adult' AFTER date_of_birth;

ALTER TABLE members
  ADD COLUMN IF NOT EXISTS family_id INT NULL AFTER status;

-- Add the family index and foreign key only if an older database is missing them.
SET @has_family_index = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND INDEX_NAME = 'idx_members_family'
);
SET @sql = IF(@has_family_index = 0,
  'ALTER TABLE members ADD INDEX idx_members_family (family_id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_family_fk = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND CONSTRAINT_NAME = 'fk_members_family'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql = IF(@has_family_fk = 0,
  'ALTER TABLE members ADD CONSTRAINT fk_members_family FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS member_id INT NULL AFTER role;

ALTER TABLE contributions
  ADD COLUMN IF NOT EXISTS note VARCHAR(255) NULL AFTER pay_date;

-- Shared loans: rows that share a group_id were taken together as one loan.
ALTER TABLE loans
  ADD COLUMN IF NOT EXISTS group_id INT NULL AFTER member_id;

-- Shared loans keep their own interest amount so each share is exact.
ALTER TABLE loans
  ADD COLUMN IF NOT EXISTS interest_amount DECIMAL(12, 2) NULL AFTER interest_percent;

SET @has_loans_group_index = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loans'
    AND INDEX_NAME = 'idx_loans_group'
);
SET @sql = IF(@has_loans_group_index = 0,
  'ALTER TABLE loans ADD INDEX idx_loans_group (group_id)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_loans_group_fk = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'loans'
    AND CONSTRAINT_NAME = 'fk_loans_group'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql = IF(@has_loans_group_fk = 0,
  'ALTER TABLE loans ADD CONSTRAINT fk_loans_group FOREIGN KEY (group_id) REFERENCES loan_groups(id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- First admin account: username admin / password admin123
-- Change this password before using the app with real money.
-- Log in and open the "Password" page to change it from the app.
INSERT IGNORE INTO users (username, password, role, member_id) VALUES (
  'admin',
  '$2y$10$P0R0GY1YEUm5iC2dkmi.ou2kSYkpKT7/c1OGrG0odb0jTgbWjP.1q',
  'admin',
  NULL
);

-- Default group settings. Change the group name and amounts for your group.
INSERT IGNORE INTO settings (id, group_name, contribution_amount, loan_interest_percent) VALUES (
  1,
  'Ikimina Savings Group',
  0,
  0
);
