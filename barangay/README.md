# Barangay Document Request and Tracking System

A professional web-based system for managing document requests and tracking in barangay offices. Built with PHP, MySQL, and modern web technologies.

## 🚀 Features

### For Residents
- **User Registration & Authentication**: Secure registration and login system
- **Document Request Submission**: Submit requests for various barangay documents
- **Real-time Status Tracking**: Monitor request status from submission to release
- **Document Download**: Download approved documents in PDF format
- **Profile Management**: Update personal information and view request history
- **Dashboard Overview**: View statistics and recent activities

### For Administrators
- **Admin Dashboard**: Comprehensive overview with statistics and quick actions
- **Resident Management**: Approve/reject registrations, manage resident accounts
- **Request Processing**: Update request status, add notes, and manage workflow
- **Document Generation**: Auto-generate official documents with templates
- **Reports & Analytics**: Generate reports on requests, residents, and system usage
- **System Settings**: Configure barangay information and system parameters

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+ (OOP with PDO)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery
- **Theme**: Azure Blue + Lily White
- **Security**: Password hashing, SQL injection prevention, XSS protection

## 📋 System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- PHP extensions: PDO, PDO_MySQL, mbstring, json

## 🚀 Installation

### 1. Clone or Download
```bash
git clone https://github.com/yourusername/barangay-system.git
cd barangay-system
```

### 2. Database Setup
1. Create a MySQL database
2. Import the database schema:
```bash
mysql -u username -p database_name < database/barangay.sql
```

### 3. Configuration
1. Edit `includes/config.php`:
```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Application configuration
define('APP_URL', 'http://your-domain.com/barangay');
```

### 4. File Permissions
```bash
chmod 755 docs/generated/
chmod 644 includes/config.php
```

### 5. Web Server Configuration
Ensure your web server points to the project directory and has proper permissions.

## 🔐 Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123
- **Email**: admin@barangay.com

⚠️ **Important**: Change the default admin password after first login!

## 📁 File Structure

```
barangay/
├── public/                 # Public-facing files
│   ├── index.php          # Landing page / login
│   ├── register.php       # Resident registration
│   ├── logout.php         # Logout handler
│   └── assets/            # CSS, JS, images
├── resident/              # Resident portal
│   ├── dashboard.php      # Resident dashboard
│   ├── request_new.php    # Submit new request
│   ├── request_status.php # Track requests
│   └── profile.php        # Profile management
├── admin/                 # Admin portal
│   ├── dashboard.php      # Admin dashboard
│   ├── residents.php      # Manage residents
│   ├── requests.php       # Manage requests
│   ├── documents.php      # Document templates
│   └── reports.php        # Reports generation
├── includes/              # Core files
│   ├── config.php         # Database & app config
│   ├── auth.php           # Authentication
│   └── functions.php      # Helper functions
├── api/                   # API endpoints
│   ├── admin_api.php      # Admin AJAX handlers
│   ├── request_api.php    # Request AJAX handlers
│   └── resident_api.php   # Resident AJAX handlers
├── database/              # Database files
│   └── barangay.sql       # Database schema
├── docs/                  # Document storage
│   ├── templates/         # Document templates
│   └── generated/         # Generated documents
└── README.md             # This file
```

## 🔧 Configuration

### System Settings
Update barangay information in the admin panel or directly in the database:

```sql
UPDATE system_settings SET setting_value = 'Your Barangay Name' WHERE setting_key = 'barangay_name';
UPDATE system_settings SET setting_value = 'Your Address' WHERE setting_key = 'barangay_address';
UPDATE system_settings SET setting_value = 'Your Contact' WHERE setting_key = 'barangay_contact';
UPDATE system_settings SET setting_value = 'your@email.com' WHERE setting_key = 'barangay_email';
```

### Document Types
The system comes with pre-configured document types:
- Barangay Clearance (₱50.00)
- Certificate of Indigency (₱25.00)
- Certificate of Residency (₱30.00)
- Certificate of Good Moral Character (₱40.00)
- Business Permit (₱100.00)
- Certificate of Live Birth (₱35.00)

## 📊 Features Overview

### Request Workflow
1. **Resident submits request** → Status: Pending
2. **Admin reviews request** → Status: Processing
3. **Admin approves/rejects** → Status: Approved/Rejected
4. **Document generated** (if approved)
5. **Document released** → Status: Released

### Security Features
- Password hashing with bcrypt
- SQL injection prevention with PDO prepared statements
- XSS protection with input sanitization
- Session management
- Role-based access control

### Document Generation
- HTML-based templates with placeholders
- Automatic data population
- Professional formatting
- Print-friendly design

## 🎨 Customization

### Theme Colors
Modify `public/assets/css/style.css`:
```css
:root {
    --primary-color: #0078d4; /* Azure Blue */
    --secondary-color: #f8f9fa; /* Lily White */
    /* Add your custom colors */
}
```

### Document Templates
Edit templates in the admin panel or modify the database:
```sql
UPDATE document_templates SET template_content = 'Your custom template' WHERE id = 1;
```

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**
   - Check file permissions on `docs/generated/`
   - Verify PHP upload settings in `php.ini`
   - Ensure directory is writable

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

4. **404 Errors**
   - Enable mod_rewrite for Apache
   - Check web server configuration
   - Verify file paths

### Debug Mode
Enable debug mode in `includes/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Performance Optimization

1. **Database Indexing**: Indexes are already created for optimal performance
2. **Caching**: Consider implementing Redis/Memcached for session storage
3. **CDN**: Use CDN for static assets (CSS, JS, images)
4. **Compression**: Enable GZIP compression on web server

## 🔒 Security Best Practices

1. **Regular Updates**: Keep PHP and MySQL updated
2. **Backup**: Regular database and file backups
3. **SSL**: Use HTTPS in production
4. **Firewall**: Configure server firewall
5. **Monitoring**: Monitor system logs for suspicious activity

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Contact: support@barangay.com
- Documentation: [Wiki Link]

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 🗺️ Roadmap

- [ ] Mobile app development
- [ ] SMS notifications
- [ ] Advanced reporting
- [ ] Multi-language support
- [ ] API for third-party integrations
- [ ] Cloud deployment support

## 📝 Changelog

### Version 1.0.0 (Current)
- Initial release
- Basic document request system
- Admin and resident portals
- Document generation
- Real-time tracking

---

**Made with ❤️ for better barangay services**
