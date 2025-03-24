<?php
/**
 * Kipay Gateway Installation Script
 * 
 * This script helps set up the database and initial configuration
 */

// Define a constant to prevent direct access to included files
define('INSTALL_SCRIPT', true);

// Turn on error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get application configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$installErrors = [];
$installSuccess = false;
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1 && isset($_POST['db_test'])) {
        // Test database connection
        $db_host = $_POST['db_host'];
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        
        try {
            $dsn = "mysql:host={$db_host}";
            $db = new PDO($dsn, $db_user, $db_pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database exists
            $stmt = $db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$db_name}'");
            $result = $stmt->fetchAll();
            
            if (count($result) === 0) {
                // Create database
                $db->exec("CREATE DATABASE `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            // Test connection to the specific database
            $dsn = "mysql:host={$db_host};dbname={$db_name}";
            $db = new PDO($dsn, $db_user, $db_pass);
            
            // Success, create config file
            $configContent = "<?php
// Database configuration
define('DB_HOST', '{$db_host}');
define('DB_NAME', '{$db_name}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');
";
            
            file_put_contents(__DIR__ . '/config/env.php', $configContent);
            
            // Redirect to next step
            header('Location: install.php?step=2');
            exit;
        } catch (PDOException $e) {
            $installErrors[] = 'Database connection failed: ' . $e->getMessage();
        }
    } else if ($step === 2 && isset($_POST['api_setup'])) {
        // Save API configuration
        $consumer_key = $_POST['consumer_key'];
        $consumer_secret = $_POST['consumer_secret'];
        $environment = $_POST['environment'];
        $callback_url = $_POST['callback_url'];
        $mpesa_paybill = $_POST['mpesa_paybill'];
        $mpesa_till = $_POST['mpesa_till'];
        
        $configContent = file_get_contents(__DIR__ . '/config/env.php');
        $configContent .= "
// Pesapal configuration
define('PESAPAL_CONSUMER_KEY', '{$consumer_key}');
define('PESAPAL_CONSUMER_SECRET', '{$consumer_secret}');
define('PESAPAL_ENVIRONMENT', '{$environment}');
define('PESAPAL_CALLBACK_URL', '{$callback_url}');

// Kenyan payment channels configuration
define('MPESA_PAYBILL', '{$mpesa_paybill}');
define('MPESA_TILL', '{$mpesa_till}');
define('PREFERRED_CHANNELS', ['MPESA', 'VISA', 'MASTERCARD']);
";
        
        file_put_contents(__DIR__ . '/config/env.php', $configContent);
        
        // Redirect to next step
        header('Location: install.php?step=3');
        exit;
    } else if ($step === 3 && isset($_POST['install_db'])) {
        // Import database schema
        $schemaFile = __DIR__ . '/database/migrations/schema.sql';
        
        if (!file_exists($schemaFile)) {
            $installErrors[] = 'Schema file not found.';
        } else {
            try {
                // Get db connection
                $db = getDB();
                
                // Read schema file
                $sql = file_get_contents($schemaFile);
                
                // Split into separate statements
                $statements = explode(';', $sql);
                
                // Execute each statement
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }
                
                // Create default API key
                $apiKey = bin2hex(random_bytes(16)); // Generate a random API key
                
                // Update the config file
                $configContent = file_get_contents(__DIR__ . '/config/env.php');
                $configContent .= "
// API Keys
define('DEFAULT_API_KEY', '{$apiKey}');
";
                file_put_contents(__DIR__ . '/config/env.php', $configContent);
                
                // Installation complete
                $installSuccess = true;
            } catch (Exception $e) {
                $installErrors[] = 'Error importing database schema: ' . $e->getMessage();
            }
        }
    }
}

// HTML header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kipay Gateway Installation</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .steps {
            display: flex;
            margin-bottom: 20px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
        .step.active {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: red;
            background: #ffeeee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            background: #eeffee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Kipay Gateway Installation</h1>
    
    <div class="steps">
        <div class="form-group">
            <label for="callback_url">Callback URL:</label>
            <input type="text" id="callback_url" name="callback_url" value="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/payment/callback'); ?>" required>
            <small>This URL will be called by Pesapal after a payment attempt.</small>
        </div>
        
        <div class="form-group">
            <label for="mpesa_paybill">M-Pesa Paybill Number:</label>
            <input type="text" id="mpesa_paybill" name="mpesa_paybill" value="174379">
            <small>Enter your M-Pesa Paybill number. Default is Pesapal's paybill.</small>
        </div>
        
        <div class="form-group">
            <label for="mpesa_till">M-Pesa Till Number (if applicable):</label>
            <input type="text" id="mpesa_till" name="mpesa_till" value="123456">
            <small>Enter your Lipa Na M-Pesa Till number if you have one.</small>
        </div>
        
        <button type="submit" name="api_setup">Save & Continue</button>step <?php echo $step === 1 ? 'active' : ''; ?>">1. Database Setup</div>
        <div class="step <?php echo $step === 2 ? 'active' : ''; ?>">2. API Configuration</div>
        <div class="step <?php echo $step === 3 ? 'active' : ''; ?>">3. Installation</div>
    </div>
    
    <?php if (!empty($installErrors)): ?>
        <div class="error">
            <?php foreach ($installErrors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($installSuccess): ?>
        <div class="success">
            <h2>Installation Successful!</h2>
            <p>The Kipay Gateway has been successfully installed. You can now proceed to use the gateway.</p>
            <p><strong>Your API Key:</strong> <?php echo htmlspecialchars($apiKey); ?></p>
            <p><a href="index.php">Go to Homepage</a></p>
        </div>
    <?php else: ?>
        <?php if ($step === 1): ?>
            <h2>Step 1: Database Setup</h2>
            <p>Please enter your database connection details:</p>
            
            <form method="post" action="install.php?step=1">
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" value="kipay_gateway" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database User:</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password:</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                
                <button type="submit" name="db_test">Test Connection & Continue</button>
            </form>
        <?php elseif ($step === 2): ?>
            <h2>Step 2: API Configuration</h2>
            <p>Please enter your Pesapal API credentials:</p>
            
            <form method="post" action="install.php?step=2">
                <div class="form-group">
                    <label for="consumer_key">Pesapal Consumer Key:</label>
                    <input type="text" id="consumer_key" name="consumer_key" required>
                </div>
                
                <div class="form-group">
                    <label for="consumer_secret">Pesapal Consumer Secret:</label>
                    <input type="text" id="consumer_secret" name="consumer_secret" required>
                </div>
                
                <div class="form-group">
                    <label for="environment">Environment:</label>
                    <select id="environment" name="environment">
                        <option value="sandbox">Sandbox (Testing)</option>
                        <option value="production">Production (Live)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="callback_url">Callback URL:</label>
                    <input type="text" id="callback_url" name="callback_url" value="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . '/payment/callback'); ?>" required>
                    <small>This URL will be called by Pesapal after a payment attempt.</small>
                </div>
                
                <button type="submit" name="api_setup">Save & Continue</button>
            </form>
        <?php elseif ($step === 3): ?>
            <h2>Step 3: Installation</h2>
            <p>We're now ready to install the database schema and complete the setup.</p>
            
            <form method="post" action="install.php?step=3">
                <p>Clicking the button below will:</p>
                <ul>
                    <li>Create the necessary database tables</li>
                    <li>Generate a secure API key for your application</li>
                    <li>Complete the installation</li>
                </ul>
                
                <button type="submit" name="install_db">Complete Installation</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>