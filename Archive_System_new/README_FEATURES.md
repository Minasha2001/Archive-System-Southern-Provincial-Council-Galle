# Archive System - Complete Dynamic Features

## Overview
This Archive System has been enhanced with full dynamic functionality, including database integration, button functions, and comprehensive management features for all user roles.

## New Files Created

### 1. **functions.php** - Core Functionality Library
Contains all reusable functions for:
- **Database Operations**: File retrieval, user management, statistics
- **File Management**: Submit, archive, reject, update, delete files
- **User Management**: Activate, disable, delete users
- **Statistics**: Get system-wide and department statistics
- **Validation**: Check unique file numbers, archive numbers, usernames, emails
- **Utilities**: Sinhala date formatting, status/role translation

### 2. **submit_file.php** - File Submission
- Users can submit new files with complete details
- Automatic validation of file numbers (must be unique)
- Form includes: submit date, subject number, department, file number, file name, date range, page count
- Redirects to user's dashboard after submission

### 3. **review_files.php** - Subject Officer Review
- Subject officers can review pending files
- Two actions available:
  - **Archive**: Assign archive number, rack number, and page counts (right/left sides)
  - **Reject**: Provide rejection reason
- Modal-based interface for better UX
- Automatic status updates

### 4. **my_files.php** - User File Tracking
- Shows all files submitted by the logged-in user
- Statistics cards: total, pending, archived, rejected
- File list with status badges
- Quick link to submit new files

### 5. **admin_manage.php** - Admin User Management
- Tab-based interface for managing users
- **Active Users Tab**: View and disable users
- **Pending Users Tab**: Approve new user registrations
- **Disabled Users Tab**: Re-activate or permanently delete users
- Statistics overview at the top

### 6. **search_files.php** - Advanced Search
- Search by: file name, file number, archive number, subject number
- Filter by: department, status, date range
- Results displayed in a table with all relevant details
- Clear search functionality

### 7. **statistics.php** - Reports & Analytics
- Overall system statistics
- Department-wise file statistics
- User role statistics
- Recent files list
- Visual dashboard with stat cards

### 8. **navigation_links.php** - Navigation Helper
- Provides role-based navigation links
- Can be included in any dashboard
- Auto-adjusts based on user role

## Database Updates

### Enhanced `archive_files` Table
Added fields for:
- `subject_no`, `file_no` (alternative field names for API compatibility)
- `right_side`, `left_side`, `pages` (page counting)
- `rack_no` (archive tree number)
- Additional status values: `waiting_approval`, `active`, `deleted`
- Review workflow fields: `sent_by`, `sent_date`, `approved_by`, `approved_at`, `rejected_at`, `reject_reason`
- Deletion tracking: `deleted_at`, `deleted_by`, `delete_reason`
- File path storage: `file_path`

### Enhanced `users` Table
Added fields for:
- `name` (alternative to full_name)
- `deleted_at` (for soft deletion)
- Additional status: `deleted`

## How to Use

### For Regular Users
1. **Submit Files**: Go to "Submit File" to add new archive files
2. **Track Files**: View "My Files" to see submission status
3. **Search**: Use "Search Files" to find archived files
4. **View Statistics**: Check system statistics

### For Subject Officers
1. **Review Files**: Access "Review Files" to see pending submissions
2. **Archive Files**: Assign archive numbers and approve files
3. **Reject Files**: Send files back with rejection reasons
4. **View Statistics**: Monitor department performance

### For Administrators
1. **Manage Users**: Access "Manage Users" to approve/disabled users
2. **Approve Registrations**: Review pending user accounts
3. **View Statistics**: Access comprehensive system reports
4. **Search Files**: Search across all files in the system

## Integration with Existing Dashboards

To add these features to existing dashboards, include the navigation helper:

```php
<?php
require_once __DIR__ . '/config.php';
$user = require_login($pdo);
include __DIR__ . '/navigation_links.php';
?>
```

## Security Features

- All forms use CSRF protection
- Input validation on all submissions
- Prepared statements for all database queries
- Role-based access control
- Session management

## Button Functions Summary

### User Dashboard
- **Submit File**: Opens file submission form
- **My Files**: Shows user's submitted files
- **Search**: Opens search interface
- **Statistics**: Views system statistics
- **Change Password**: Update password
- **Logout**: End session

### Subject Officer Dashboard
- **Review Files**: Opens pending file review
- **Archive Button**: Assigns archive number to file
- **Reject Button**: Rejects file with reason
- **Statistics**: View department statistics
- **Change Password**: Update password
- **Logout**: End session

### Admin Dashboard
- **Manage Users**: Opens user management interface
- **Approve Button**: Activates pending user
- **Disable Button**: Disables active user
- **Delete Button**: Permanently removes user
- **Statistics**: View system-wide statistics
- **Change Password**: Update password
- **Logout**: End session

## Setup Instructions

1. **Update Database**: Run the updated `database.sql` to add new fields
2. **Deploy Files**: All new PHP files are ready to use
3. **Test Functionality**: Test each feature with appropriate user roles
4. **Customize**: Modify styling and text as needed

## Future Enhancements

Potential additions:
- File upload for actual documents
- Email notifications for status changes
- Export to PDF/Excel
- Advanced reporting with charts
- Audit logging
- File versioning
- Mobile app API

## Support

For issues or questions, refer to the inline comments in each file or check the database schema in `database.sql`.
