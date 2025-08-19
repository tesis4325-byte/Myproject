<?php
/**
 * Barangay Document Request and Tracking System
 * Installation Script
 */

// Check if already installed
if (file_exists('includes/config.php')) {
    die('System is already installed. Remove install.php for security.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? '';
            $dbName = $_POST['db_name'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
                $error = 'Please fill in all required fields.';
            } else {
                // Test database connection
                try {
                    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Import database schema
                    $sql = file_get_contents('database/barangay.sql');
                    $pdo->exec($sql);
                    
                    // Create config file
                    $configContent = "<?php
/**
 * Database Configuration
 * Barangay Document Request and Tracking System
 */

// Database configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Barangay Document Request and Tracking System');
define('APP_URL', '" . ($_POST['app_url'] ?? 'http://localhost/barangay') . "');
define('APP_VERSION', '1.0.0');

// File upload configuration
define('UPLOAD_PATH', '../docs/generated/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Session configuration
define('SESSION_NAME', 'barangay_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * Database Connection Class using PDO
 */
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Connection failed: \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function query(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt;
        } catch (PDOException \$e) {
            throw new Exception(\"Query failed: \" . \$e->getMessage());
        }
    }
    
    public function fetch(\$sql, \$params = []) {
        \$stmt = \$this->query(\$sql, \$params);
        return \$stmt->fetch();
    }
    
    public function fetchAll(\$sql, \$params = []) {
        \$stmt = \$this->query(\$sql, \$params);
        return \$stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// Initialize database connection
\$db = Database::getInstance();
?>";
                    
                    file_put_contents('includes/config.php', $configContent);
                    
                    // Create directories
                    if (!is_dir('docs/generated')) {
                        mkdir('docs/generated', 0755, true);
                    }
                    if (!is_dir('docs/templates')) {
                        mkdir('docs/templates', 0755, true);
                    }
                    
                    $success = 'Database configuration completed successfully!';
                    $step = 2;
                    
                } catch (Exception $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 2:
            // System configuration
            $barangayName = $_POST['barangay_name'] ?? '';
            $barangayAddress = $_POST['barangay_address'] ?? '';
            $barangayContact = $_POST['barangay_contact'] ?? '';
            $barangayEmail = $_POST['barangay_email'] ?? '';
            
            if (empty($barangayName) || empty($barangayAddress)) {
                $error = 'Please fill in all required fields.';
            } else {
                try {
                    require_once 'includes/config.php';
                    require_once 'includes/functions.php';
                    
                    // Update system settings
                    updateSystemSetting('barangay_name', $barangayName);
                    updateSystemSetting('barangay_address', $barangayAddress);
                    updateSystemSetting('barangay_contact', $barangayContact);
                    updateSystemSetting('barangay_email', $barangayEmail);
                    
                    $success = 'System configuration completed successfully!';
                    $step = 3;
                    
                } catch (Exception $e) {
                    $error = 'Configuration failed: ' . $e->getMessage();
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Barangay Document Request System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #0078d4, #106ebe);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .install-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
            margin: 2rem auto;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: bold;
        }
        .step.active {
            background: #0078d4;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="text-center mb-4">
                <h2 class="text-primary mb-2">
                    <i class="fas fa-building me-2"></i>
                    Barangay Document Request System
                </h2>
                <p class="text-muted">Installation Wizard</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- Step 1: Database Configuration -->
                <form method="POST">
                    <h4 class="mb-3">
                        <i class="fas fa-database me-2"></i>Database Configuration
                    </h4>
                    
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host *</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" 
                               value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name *</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" 
                               value="<?php echo $_POST['db_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database Username *</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" 
                               value="<?php echo $_POST['db_user'] ?? 'root'; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" 
                               value="<?php echo $_POST['db_pass'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="app_url" class="form-label">Application URL</label>
                        <input type="url" class="form-control" id="app_url" name="app_url" 
                               value="<?php echo $_POST['app_url'] ?? 'http://localhost/barangay'; ?>">
                        <small class="text-muted">Example: http://localhost/barangay</small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Continue
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <!-- Step 2: System Configuration -->
                <form method="POST">
                    <h4 class="mb-3">
                        <i class="fas fa-cog me-2"></i>System Configuration
                    </h4>
                    
                    <div class="mb-3">
                        <label for="barangay_name" class="form-label">Barangay Name *</label>
                        <input type="text" class="form-control" id="barangay_name" name="barangay_name" 
                               value="<?php echo $_POST['barangay_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="barangay_address" class="form-label">Barangay Address *</label>
                        <textarea class="form-control" id="barangay_address" name="barangay_address" 
                                  rows="3" required><?php echo $_POST['barangay_address'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="barangay_contact" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="barangay_contact" name="barangay_contact" 
                               value="<?php echo $_POST['barangay_contact'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="barangay_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="barangay_email" name="barangay_email" 
                               value="<?php echo $_POST['barangay_email'] ?? ''; ?>">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>Continue
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Installation Complete -->
                <div class="text-center">
                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                    <h4 class="text-success mb-3">Installation Complete!</h4>
                    <p class="text-muted mb-4">
                        Your Barangay Document Request and Tracking System has been successfully installed.
                    </p>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Default Admin Login</h6>
                        <p class="mb-1"><strong>Username:</strong> admin</p>
                        <p class="mb-0"><strong>Password:</strong> admin123</p>
                        <small class="text-danger">⚠️ Please change the default password after first login!</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="public/index.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                        <a href="admin/dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            For security reasons, please delete this installation file (install.php) from your server.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
