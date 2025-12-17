# Monthly Comparison Report

## Overview

The Monthly Comparison Report is a comprehensive tool that compares payroll data between two periods to identify all changes, including new employees, departures, promotions, allowance changes, and deduction modifications.

## Features

### 1. Employee Movement Tracking

- **New Employees**: Staff who appear in current period but not in previous period
- **Departed Employees**: Staff who appear in previous period but not in current period (retirement, resignation, termination)
- **Status Changes**: Same employee but different employment status between periods

### 2. Promotion/Demotion Tracking

- **Grade Changes**: Track grade level increases or decreases
- **Step Changes**: Track step level increases or decreases
- **Combined Changes**: Both grade and step changes for the same employee

### 3. Allowance Changes

- **New Allowances**: Allowances that appear in current month but not previous month
- **Removed Allowances**: Allowances that were in previous month but not current month
- **Amount Changes**: Same allowance but different amounts between periods
- **Descriptions**: Full allowance descriptions from the allocode table

### 4. Deduction Changes

- **New Deductions**: Deductions that appear in current month but not previous month
- **Removed Deductions**: Deductions that were in previous month but not current month
- **Amount Changes**: Same deduction but different amounts between periods
- **Descriptions**: Full deduction descriptions from the allocode table

### 5. Summary Statistics

- Total count of changes by category
- Visual summary cards showing key metrics
- Quick overview of all changes

## How to Use

### 1. Access the Report

- Navigate to **Monthly Comparison** in the sidebar menu
- Available only to Admin users

### 2. Select Periods

- Choose **Current Period** (the period you want to analyze)
- Choose **Previous Period** (the period to compare against)
- Click **Generate Report**

### 3. Review Results

The report displays in organized sections:

- **Summary Cards**: Quick overview of all changes
- **New Employees**: List of newly added staff
- **Departed Employees**: List of staff no longer in payroll
- **Promotions/Demotions**: Grade and step changes
- **Allowance Changes**: All allowance modifications
- **Deduction Changes**: All deduction modifications

### 4. Export Report

- Click **Export Report** button to download as CSV
- File includes all sections with detailed data
- Filename includes both periods and generation date

## Data Sources

The report uses the following database tables:

- `payperiods`: Period information and descriptions
- `master_staff`: Employee details, grade, step, status
- `tbl_master`: Allowance and deduction amounts
- `allocode`: Allowance/deduction descriptions and transaction codes
- `tbl_dept`: Department information

## Use Cases

### HR Department

- Track employee movements and status changes
- Monitor promotions and career progression
- Verify new hires and departures

### Finance Department

- Audit payroll changes between periods
- Track allowance and deduction modifications
- Identify anomalies or unexpected changes

### Management

- Generate comprehensive reports for board meetings
- Monitor organizational changes
- Ensure payroll accuracy and compliance

## Technical Notes

- Report compares data at the period level (not individual payroll runs)
- Uses LEFT JOINs to identify new/removed items
- Handles NULL values appropriately for missing data
- Exports in UTF-8 CSV format for Excel compatibility
- Responsive design works on desktop and mobile devices

## Security

- Requires Admin role access
- Session-based authentication
- Input validation and SQL injection protection
- Sanitized output for XSS prevention
