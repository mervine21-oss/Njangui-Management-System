-- DigiTon database schema (MySQL)
-- Currency: XAF. All monetary values stored as DECIMAL(15,2).

CREATE DATABASE IF NOT EXISTS digiton CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digiton;

-- users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30),
  avatar VARCHAR(255) NULL,
  reset_token VARCHAR(255) NULL,
  reset_token_expires_at DATETIME NULL,
  role ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- tontine_groups
CREATE TABLE tontine_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  admin_user_id INT UNSIGNED NOT NULL,
  capacity INT UNSIGNED NOT NULL DEFAULT 12,
  contribution_amount DECIMAL(15,2) NOT NULL,
  cycle_interval ENUM('weekly','monthly','bimonthly','annually') NOT NULL,
  collateral_reserve_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- group_members
CREATE TABLE group_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_admin TINYINT(1) DEFAULT 0,
  status ENUM('active','inactive','flagged') DEFAULT 'active',
  is_new_member TINYINT(1) DEFAULT 1,
  UNIQUE KEY uq_group_user (group_id,user_id),
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- wallets (logical balances per user per group)
CREATE TABLE wallets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rotational_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  savings_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wallet_group (group_id),
  INDEX idx_wallet_user (user_id)
) ENGINE=InnoDB;

-- transactions log (immutable)
CREATE TABLE transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  gateway_ref VARCHAR(200) NOT NULL,
  network ENUM('MTN','ORANGE','OTHER') NOT NULL,
  msisdn VARCHAR(50) NOT NULL,
  user_id INT UNSIGNED,
  group_id INT UNSIGNED,
  amount DECIMAL(15,2) NOT NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE SET NULL,
  INDEX idx_transactions_user (user_id),
  INDEX idx_transactions_group (group_id)
) ENGINE=InnoDB;

-- penalties
CREATE TABLE penalties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
  INDEX idx_penalties_group (group_id)
) ENGINE=InnoDB;

-- invites
CREATE TABLE invites (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT UNSIGNED,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- admin logs
CREATE TABLE admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED,
  action VARCHAR(255) NOT NULL,
  details JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- group messages (chat in group) with file attachment support
CREATE TABLE group_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  file_path VARCHAR(255) NULL,
  file_type ENUM('image','video','pdf','document','other') NULL,
  file_name VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_group_messages (group_id,created_at)
) ENGINE=InnoDB;

-- sample data (realistic example)
INSERT INTO users (first_name,last_name,email,password_hash,phone,role) VALUES
('Alice','Nkem','alice@example.com','', '+237650000000','super_admin');

INSERT INTO tontine_groups (name,admin_user_id,capacity,contribution_amount,cycle_interval,collateral_reserve_percent) VALUES
('Chama Alpha',1,12,250000.00,'monthly',5.00);
