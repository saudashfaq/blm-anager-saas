CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    subscription_plan ENUM('free', 'basic', 'premium', 'enterprise') NOT NULL DEFAULT 'free',
    subscription_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_company_email (email),
    INDEX idx_company_status (status),
    INDEX idx_subscription (subscription_plan, subscription_expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_superadmin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    UNIQUE KEY unique_username_per_company (username, company_id),
    UNIQUE KEY unique_email_per_company (email, company_id),
    INDEX idx_user_role (role, is_superadmin),
    INDEX idx_user_company (company_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    base_url VARCHAR(255) NOT NULL,
    verification_frequency ENUM('daily', 'weekly', 'every_two_weeks', 'monthly') NOT NULL DEFAULT 'weekly',
    last_checked DATETIME DEFAULT NULL,
    status ENUM('enabled', 'disabled') NOT NULL DEFAULT 'enabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campaign_status (status, last_checked),
    INDEX idx_campaign_company (company_id, status),
    INDEX idx_campaign_user (user_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS backlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    backlink_url VARCHAR(255) NOT NULL,
    base_domain VARCHAR(255) NULL COMMENT 'this will hold the base_domain from the backlink_url',
    target_url VARCHAR(255) NULL,
    anchor_text VARCHAR(255) NULL,
    `status` ENUM('alive', 'dead', 'pending') NOT NULL DEFAULT 'pending',
    anchor_text_found BOOLEAN NULL COMMENT 'Nullable boolean for anchor text presence', 
    is_duplicate ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    created_by INT NOT NULL,
    last_checked TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_backlink_status (status, last_checked),
    INDEX idx_backlink_campaign (campaign_id, status),
    INDEX idx_backlink_domain (base_domain, status),
    INDEX idx_backlink_created (created_by, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backlink_id INT NOT NULL,
    `status` ENUM('alive', 'dead') NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    INDEX idx_verification_backlink (backlink_id, checked_at),
    INDEX idx_verification_status (status, checked_at)
) ENGINE=InnoDB;

CREATE TABLE verification_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backlink_id INT NOT NULL,
    proxy_key VARCHAR(255) DEFAULT NULL,
    error_type ENUM('http', 'proxy', 'dom') NOT NULL,
    error_message TEXT NOT NULL,
    attempt INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backlink_id) REFERENCES backlinks(id) ON DELETE CASCADE,
    INDEX idx_error_backlink (backlink_id, created_at),
    INDEX idx_error_type (error_type, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS backlink_verification_helper (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    last_run DATETIME NOT NULL,
    pending_backlinks INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_verification_helper_campaign (campaign_id, last_run)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    purchase_key VARCHAR(255) NOT NULL,
    site_url VARCHAR(255) NOT NULL,
    verification_interval INT NOT NULL DEFAULT 24,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_settings (company_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cron_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(50) NOT NULL,
    last_run DATETIME DEFAULT NULL,
    next_run DATETIME DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (job_name),
    INDEX idx_cron_status (status, next_run)

) ENGINE=InnoDB;

-- Create SuperAdmin user
-- INSERT INTO users (username, email, password, role, is_superadmin, created_at) 
--VALUES ('superadmin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());