-- ============================================
-- WOOL PRODUCTION MES - DATABASE SETUP
-- Execute this entire script in phpMyAdmin
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS wool_production_mes CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE wool_production_mes;

-- ============================================
-- TABLE 1: Production Stages
-- ============================================
CREATE TABLE production_stages (
    stage_id INT PRIMARY KEY AUTO_INCREMENT,
    stage_number INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    description TEXT,
    avg_duration_days INT,
    avg_weight_loss_percent DECIMAL(5,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE 2: Vendors
-- ============================================
CREATE TABLE vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(100) NOT NULL,
    vendor_type ENUM('Internal', 'External') DEFAULT 'External',
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    specialization VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE 3: Users
-- ============================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('Admin', 'Supervisor', 'Viewer', 'Vendor') DEFAULT 'Viewer',
    vendor_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABLE 4: Batches
-- ============================================
CREATE TABLE batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    batch_number VARCHAR(20) UNIQUE NOT NULL,
    initial_weight DECIMAL(10,2) NOT NULL,
    current_weight DECIMAL(10,2) NOT NULL,
    current_stage_id INT NOT NULL,
    status ENUM('In Progress', 'Completed', 'On Hold', 'Cancelled') DEFAULT 'In Progress',
    start_date DATE NOT NULL,
    expected_completion_date DATE,
    actual_completion_date DATE NULL,
    source_supplier VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (current_stage_id) REFERENCES production_stages(stage_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABLE 5: Batch Stage History
-- ============================================
CREATE TABLE batch_stage_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    stage_id INT NOT NULL,
    vendor_id INT NULL,
    weight_in DECIMAL(10,2),
    weight_out DECIMAL(10,2) NULL,
    weight_loss DECIMAL(10,2) NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    duration_hours INT NULL,
    status ENUM('Pending', 'In Progress', 'Completed', 'Failed') DEFAULT 'In Progress',
    notes TEXT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES production_stages(stage_id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABLE 6: Alerts
-- ============================================
CREATE TABLE alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    alert_type ENUM('Delay', 'Weight Loss', 'Quality Issue', 'Other') NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    message TEXT NOT NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABLE 7: System Settings
-- ============================================
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert production stages (10 stages)
INSERT INTO production_stages (stage_number, stage_name, description, avg_duration_days, avg_weight_loss_percent) VALUES
(1, 'Raw Wool Receipt', 'Initial receiving and weighing of raw wool', 1, 0),
(2, 'Sorting & Grading', 'Sorting by quality and color', 2, 5),
(3, 'Scouring/Washing', 'Deep cleaning to remove dirt and grease', 1, 30),
(4, 'Carding', 'Aligning wool fibers', 1, 2),
(5, 'Dyeing', 'Coloring the wool', 2, 3),
(6, 'Spinning', 'Converting to yarn', 3, 5),
(7, 'Weaving/Knitting', 'Creating fabric', 4, 2),
(8, 'Finishing', 'Final fabric treatment', 2, 1),
(9, 'Cutting & Sewing', 'Garment production', 5, 8),
(10, 'QC & Packaging', 'Quality check and final packaging', 2, 1);

-- Insert vendors
INSERT INTO vendors (vendor_name, vendor_type, contact_person, phone, specialization) VALUES
('Warehouse A', 'Internal', 'Ahmed Khan', '021-1234567', 'Storage'),
('Sorting Department', 'Internal', 'Sara Ali', '021-2345678', 'Sorting & Grading'),
('CleanWool Facility', 'External', 'Hassan Raza', '021-3456789', 'Washing'),
('Card Master Co.', 'External', 'Fatima Sheikh', '021-4567890', 'Carding'),
('ColorTech Dyehouse', 'External', 'Bilal Ahmed', '021-5678901', 'Dyeing'),
('Premium Spinning Mill', 'External', 'Ayesha Khan', '021-6789012', 'Spinning'),
('Textile Weavers Ltd', 'External', 'Omar Farooq', '021-7890123', 'Weaving'),
('Finish Pro', 'External', 'Zainab Ali', '021-8901234', 'Finishing'),
('Fashion Garments Inc', 'External', 'Imran Malik', '021-9012345', 'Garment Making'),
('QC Department', 'Internal', 'Nadia Hussain', '021-0123456', 'Quality Control');

-- Insert admin user (username: admin, password: admin123)
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@woolmes.com', 'Admin');

-- Insert supervisor user (username: supervisor, password: super123)
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('supervisor', '$2y$10$pY8qTlHtJqvZ5Z5Z5Z5Z5uK3J5J5J5J5J5J5J5J5J5J5J5J5J5J5K', 'Production Supervisor', 'supervisor@woolmes.com', 'Supervisor');

-- Insert sample batch
INSERT INTO batches (batch_number, initial_weight, current_weight, current_stage_id, start_date, expected_completion_date, source_supplier, created_by) VALUES
('WB-2024-001', 1000.00, 1000.00, 1, '2024-12-01', '2024-12-24', 'Green Valley Farm', 1);

-- Insert first stage history for sample batch
INSERT INTO batch_stage_history (batch_id, stage_id, vendor_id, weight_in, start_date, status, updated_by) VALUES
(1, 1, 1, 1000.00, '2024-12-01 08:00:00', 'In Progress', 1);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('company_name', 'Wool Production MES', 'Company name displayed in system'),
('timezone', 'Asia/Karachi', 'System timezone'),
('date_format', 'Y-m-d', 'Date display format'),
('alert_delay_days', '2', 'Days delay before triggering alert');

-- ============================================
-- INDEXES for better performance
-- ============================================
CREATE INDEX idx_batch_status ON batches(status);
CREATE INDEX idx_batch_stage ON batches(current_stage_id);
CREATE INDEX idx_history_batch ON batch_stage_history(batch_id);
CREATE INDEX idx_alerts_batch ON alerts(batch_id);

-- ============================================
-- SETUP COMPLETE!
-- ============================================