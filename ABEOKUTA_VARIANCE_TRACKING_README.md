# Abeokuta Variance Tracking System

## Overview

The Abeokuta Variance Tracking System allows you to track gross salary submissions to Abeokuta and monitor changes that occur between submission and actual payment. This helps identify discrepancies and their causes.

## Features

### 1. **Submission Management**

- Select any period to work with
- Choose a specific date for submission
- Automatically calculate gross salary as of that date
- Store submission with timestamp and user information

### 2. **Variance Analysis**

- Compare submitted gross vs actual gross
- Calculate variance (positive or negative)
- Track all changes that occurred after submission

### 3. **Change Tracking**

- **New Employees**: Staff added after submission
- **Departed Employees**: Staff who left after submission
- **Status Changes**: Staff status changes (suspended, etc.)
- **Promotions/Demotions**: Grade and step changes

### 4. **Reporting**

- Visual variance summary with color-coded indicators
- Detailed change breakdown tables
- CSV export functionality for further analysis

## Setup Instructions

### 1. **Database Setup**

Run the SQL scripts to create the required tables and permissions:

```sql
-- Execute abeokuta_submissions_table.sql
-- Execute abeokuta_variance_permissions.sql
```

### 2. **Permission Setup**

The system requires proper permissions to access the new pages. You have two options:

**Option A: Automatic Setup (Recommended)**

1. Navigate to `setup_abeokuta_permissions.php` in your browser
2. Click "Setup Permissions" button
3. The system will automatically configure all required permissions

**Option B: Manual Setup**

1. Run the SQL script `abeokuta_variance_permissions.sql` in your database
2. Or use the manual SQL commands provided in the setup page

### 3. **File Structure**

Ensure these files are in your project root:

- `abeokuta_variance_tracking.php` - Main tracking page
- `abeokuta_variance_export.php` - CSV export functionality
- `abeokuta_submissions_table.sql` - Database table creation script

### 3. **Menu Integration**

The system is already integrated into your sidebar menu under "Abeokuta Variance".

## How to Use

### Step 1: Select Period

1. Navigate to "Abeokuta Variance" from the sidebar
2. Select the period you want to work with
3. Click "Load Period"

### Step 2: Submit Gross

1. Choose the submission date (usually first week of the month)
2. Click "Submit Gross" to record the amount
3. The system will calculate and store the gross salary as of that date

### Step 3: Monitor Changes

1. The system automatically tracks all changes after submission
2. View variance analysis showing:
   - Submitted gross amount
   - Current actual gross amount
   - Variance (difference)
   - Detailed breakdown of changes

### Step 4: Export Report

1. Click "Export Report" to download a CSV file
2. The CSV includes all variance data and change details

## Database Schema

### `abeokuta_submissions` Table

```sql
CREATE TABLE abeokuta_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_id INT NOT NULL,
    submission_date DATE NOT NULL,
    submitted_gross DECIMAL(15,2) NOT NULL,
    submitted_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Key Functions

### `calculateGrossAsOfDate($periodId, $date)`

Calculates the total gross salary for a period as of a specific date.

### `calculateVarianceData($periodId, $submissions)`

Compares submitted gross with actual gross and identifies changes.

### `getChangesAfterSubmission($periodId, $submissionDate)`

Identifies all changes that occurred after the submission date.

## Business Benefits

1. **Financial Control**: Track discrepancies between submitted and actual amounts
2. **Audit Trail**: Complete record of all submissions and changes
3. **Change Visibility**: Clear understanding of what caused variances
4. **Compliance**: Proper documentation for financial reporting
5. **Planning**: Better forecasting with historical variance data

## Troubleshooting

### Common Issues

1. **"Unexpected route" Error**:

   - **Cause**: Missing permissions for the new pages
   - **Solution**: Run `setup_abeokuta_permissions.php` or execute the permissions SQL script
   - **Check**: Verify the page exists in the `pages` table and permissions are granted in the `permissions` table

2. **No Data Found**: Ensure the selected period has payroll data
3. **Export Fails**: Check popup blocker settings
4. **Database Errors**: Verify the `abeokuta_submissions` table exists

### Error Messages

- **"Unexpected route. Please contact administrator"**: Missing page permissions
- **"No payroll data found"**: The selected period has no allowance data
- **"Submission not found"**: Invalid submission ID or period
- **"Export Error"**: Missing parameters or data

### Permission Verification

To verify permissions are set up correctly, run this SQL query:

```sql
SELECT p.page, r.role_name
FROM permissions p
JOIN roles r ON p.role_id = r.role_id
WHERE p.page IN ('abeokuta_variance_tracking.php', 'abeokuta_variance_export.php');
```

## Security

- User authentication required
- Session-based access control
- SQL injection protection with prepared statements
- Input validation and sanitization

## Future Enhancements

1. **Email Notifications**: Alert when variances exceed thresholds
2. **Approval Workflow**: Multi-level approval for submissions
3. **Historical Analysis**: Trend analysis over multiple periods
4. **Integration**: Connect with existing payroll systems
5. **Dashboard**: Visual charts and graphs for variance analysis

## Support

For technical support or feature requests, contact your system administrator.
