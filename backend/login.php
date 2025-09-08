<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'store_management';
$user = 'root';
$pass = '';

// Create a simple error handler function using SweetAlert
function handle_error($error_message) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>StockLens - Login Error</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>body{font-family:'Inter', sans-serif;}</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'Login Failed',
                text: '" . addslashes($error_message) . "',
                icon: 'error',
                confirmButtonText: 'Try Again'
            }).then(() => {
                window.location.href = '../index.html';
            });
        </script>
    </body>
    </html>";
    exit;
}

// Function to log the login attempt to the simplified table
function log_attempt($pdo, $identifier, $status) {
    try {
        $stmt = $pdo->prepare("INSERT INTO LoginLogs (username_or_email, status) VALUES (?, ?)");
        $stmt->execute([$identifier, $status]);
    } catch (PDOException $e) {
        // Log the error but don't stop the application
        error_log('Failed to log login attempt: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        handle_error('Database connection failed.');
    }

    $login_status = 'failure';
    $loggedInUser = null;
    $redirectLocation = '';
    $identifier = $role === 'viewer' ? $email : $username;

    try {
        if ($role === 'viewer') {
            // Check if the email is not empty and ends with the required domain
            if (!empty($email) && str_ends_with($email, '@mofep.gov.gh')) {
                $loggedInUser = ['id' => null, 'username' => $email, 'role' => 'viewer'];
                $login_status = 'success';
                $redirectLocation = '../views/view_only/';
            }
        } else {
            // Handle manager and admin roles
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM Users WHERE username = ? AND role = ?");
            $stmt->execute([$username, $role]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Using a simple string comparison because the passwords are not hashed in the database.
                if ($password === $user['password']) {
                    $loggedInUser = $user;
                    $login_status = 'success';
                    if ($role === 'store_manager') {
                        $redirectLocation = '../views/store_manager/';
                    } else if ($role === 'system_manager') {
                        $redirectLocation = '../views/system_manager/';
                    }
                }
            }
        }

    } catch (PDOException $e) {
        error_log('Login query failed: ' . $e->getMessage());
        $login_status = 'failure';
        handle_error('An error occurred during login.');
    } finally {
        // Log the attempt regardless of success or failure
        if (!empty($identifier)) {
            log_attempt($pdo, $identifier, $login_status);
        }
    }

    if ($login_status === 'success') {
        $_SESSION['role'] = $loggedInUser['role'];
        $_SESSION['user_id'] = $loggedInUser['id']; // Store the user ID in the session
        header("Location: $redirectLocation");
        exit;
    } else {
        handle_error('Invalid username or password.');
    }
}
?>
