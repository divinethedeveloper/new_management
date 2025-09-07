<?php
session_start();

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=store_management;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate role
    if (!in_array($role, ['viewer', 'store_manager', 'system_manager'])) {
        $error = 'Invalid role selected.';
    } else {
        // Handle Viewer role (no password required)
        if ($role === 'viewer') {
            if ($username === 'viewer') {
                $_SESSION['role'] = 'viewer';
                header('Location: index.html');
                exit;
            } else {
                $error = 'Invalid username for Viewer role.';
            }
        } else {
            // Handle Store Manager and System Manager roles
            try {
                $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = ? AND role = ?");
                $stmt->execute([$username, $role]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && $user['password'] === $password) {
                    $_SESSION['role'] = $role;
                    header('Location: manager.html');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch (PDOException $e) {
                error_log('Login query failed: ' . $e->getMessage());
                $error = 'An error occurred during login.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockLens - Login Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Login Failed</h2>
        <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error ?? 'An error occurred.'); ?></p>
        <a href="login.html" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">Try Again</a>
    </div>
</body>
</html>