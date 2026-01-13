<?php
// /public_html/backend/config/database.php
// Database Configuration for Learn the Fix - Hostinger

/**
 * SECURITY NOTE:
 * This file contains sensitive database credentials.
 * Ensure this file is outside the web root or properly protected.
 * On Hostinger, it's in /public_html/backend/config/ which is accessible.
 * Consider moving sensitive config to a higher directory if possible.
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Get these credentials from your Hostinger control panel:
// 1. Go to Hostinger → Databases → MySQL Databases
// 2. Create a new database or use existing one
// 3. Copy the credentials shown

define('DB_HOST', 'localhost');           // Usually 'localhost' on Hostinger
define('DB_NAME', 'u644592986_learnthefix'); // Your database name (starts with u123456789_)
define('DB_USER', 'u644592986_admin');    // Your database username
define('DB_PASS', 'HostsingerPassword19'); // Your database password
define('DB_CHARSET', 'utf8mb4');          // Supports emojis and special characters
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// ============================================
// ERROR HANDLING CONFIGURATION
// ============================================

// Display errors only in development
// Change to FALSE in production!
define('DISPLAY_ERRORS', false);

// Log errors to file
define('LOG_ERRORS', false);
define('ERROR_LOG_FILE', __DIR__ . '/../logs/db_errors.log');

// ============================================
// DATABASE CONNECTION FUNCTION
// ============================================

/**
 * Get a PDO database connection
 * 
 * @return PDO Returns a PDO database connection object
 * @throws Exception If connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    // Return existing connection if available
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Create DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // PDO Options
        // PDO Options for Hostinger (no SSL required)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Disable SSL verification
];
        
        // Create PDO instance
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Log successful connection (optional)
        if (LOG_ERRORS) {
            error_log(date('[Y-m-d H:i:s]') . " Database connected successfully to " . DB_NAME, 3, ERROR_LOG_FILE);
        }
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error
        $errorMessage = "Database Connection Failed: " . $e->getMessage();
        
        if (LOG_ERRORS) {
            error_log(date('[Y-m-d H:i:s]') . " " . $errorMessage, 3, ERROR_LOG_FILE);
        }
        
        // Display user-friendly error
        if (DISPLAY_ERRORS) {
            // Show detailed error in development
            die("<div style='padding: 20px; margin: 20px; border: 2px solid #dc2626; border-radius: 8px; background: #fef2f2; color: #dc2626;'>
                <h3>Database Connection Error</h3>
                <p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>Code:</strong> " . $e->getCode() . "</p>
                <p><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")</p>
                <hr>
                <p><strong>Troubleshooting:</strong></p>
                <ul>
                    <li>Check if database credentials are correct in config/database.php</li>
                    <li>Verify the database exists in Hostinger MySQL Databases</li>
                    <li>Check if the database user has proper permissions</li>
                    <li>Ensure database server is running</li>
                </ul>
                <p><small>This error is only visible in development mode.</small></p>
            </div>");
        } else {
            // Generic error in production
            die("<div style='padding: 20px; margin: 20px; border: 2px solid #dc2626; border-radius: 8px; background: #fef2f2; color: #dc2626; text-align: center;'>
                <h3>Database Connection Error</h3>
                <p>We're experiencing technical difficulties. Please try again later.</p>
                <p><small>Error code: DB_CONN_001</small></p>
            </div>");
        }
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Sanitize user input for database queries
 * 
 * @param string $input The input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Prepare and execute a SQL query with parameters
 * 
 * @param string $sql The SQL query with placeholders
 * @param array $params Array of parameters for the query
 * @return PDOStatement Returns the executed statement
 */
function executeQuery($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Check if a table exists in the database
 * 
 * @param string $tableName Name of the table to check
 * @return bool True if table exists, false otherwise
 */
function tableExists($tableName) {
    try {
        $pdo = getDBConnection();
        $sql = "SELECT 1 FROM `$tableName` LIMIT 1";
        $pdo->query($sql);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get database information (for admin/debugging)
 * 
 * @return array Database information
 */
function getDBInfo() {
    $pdo = getDBConnection();
    $info = [];
    
    // Database version
    $info['version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    
    // Connection status
    $info['connected'] = true;
    
    // Database name
    $info['database'] = DB_NAME;
    
    // List tables
    $stmt = $pdo->query("SHOW TABLES");
    $info['tables'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Table counts
    $info['table_counts'] = [];
    foreach ($info['tables'] as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $info['table_counts'][$table] = $countStmt->fetch()['count'];
    }
    
    return $info;
}

// ============================================
// INITIALIZATION
// ============================================

// Create logs directory if it doesn't exist
if (LOG_ERRORS && !file_exists(dirname(ERROR_LOG_FILE))) {
    mkdir(dirname(ERROR_LOG_FILE), 0755, true);
}

// Test connection on include (optional - remove in production)
if (defined('TEST_DB_ON_INCLUDE') && TEST_DB_ON_INCLUDE === true) {
    try {
        $testConnection = getDBConnection();
        // Optional: Run a simple test query
        $testConnection->query("SELECT 1");
    } catch (Exception $e) {
        // Error will be caught by getDBConnection()
    }
}

// ============================================
// SECURITY MEASURES
// ============================================

// Prevent direct access to this file if called directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    die("<h1>403 Forbidden</h1><p>You are not allowed to access this file directly.</p>");
}

// End of database.php
?>
