-- Create table for storing detailed snapshots of employee data at submission time
CREATE TABLE IF NOT EXISTS abeokuta_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    staff_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    dept VARCHAR(100),
    grade INT,
    step INT,
    status VARCHAR(50),
    gross_allowance DECIMAL(15,2) DEFAULT 0,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_submission_id (submission_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_snapshot_date (snapshot_date),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (submission_id) REFERENCES abeokuta_submissions(id) ON DELETE CASCADE
);

-- Create table for tracking individual changes over time
CREATE TABLE IF NOT EXISTS abeokuta_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    staff_id VARCHAR(50) NOT NULL,
    change_type ENUM('new_employee', 'departed_employee', 'status_change', 'promotion', 'allowance_change') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    change_description TEXT,
    detected_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_submission_id (submission_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_change_type (change_type),
    INDEX idx_detected_date (detected_date),
    
    FOREIGN KEY (submission_id) REFERENCES abeokuta_submissions(id) ON DELETE CASCADE
);

-- Create table for tracking variance analysis over time
CREATE TABLE IF NOT EXISTS abeokuta_variance_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    analysis_date DATE NOT NULL,
    submitted_gross DECIMAL(15,2) NOT NULL,
    current_gross DECIMAL(15,2) NOT NULL,
    variance_amount DECIMAL(15,2) NOT NULL,
    variance_percentage DECIMAL(5,2),
    new_employees_count INT DEFAULT 0,
    departed_employees_count INT DEFAULT 0,
    status_changes_count INT DEFAULT 0,
    promotions_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_submission_id (submission_id),
    INDEX idx_analysis_date (analysis_date),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (submission_id) REFERENCES abeokuta_submissions(id) ON DELETE CASCADE
);
