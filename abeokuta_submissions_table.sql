-- Create table for storing Abeokuta submissions
CREATE TABLE IF NOT EXISTS abeokuta_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_id INT NOT NULL,
    submission_date DATE NOT NULL,
    submitted_gross DECIMAL(15,2) NOT NULL,
    submitted_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_period_id (period_id),
    INDEX idx_submission_date (submission_date),
    INDEX idx_created_at (created_at)
);

-- Add foreign key constraint if payperiods table exists
-- ALTER TABLE abeokuta_submissions 
-- ADD CONSTRAINT fk_abeokuta_period 
-- FOREIGN KEY (period_id) REFERENCES payperiods(periodId) ON DELETE CASCADE;
