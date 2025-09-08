<?php
require_once 'config.php';

// Check if user is System Manager
function restrict_to_system_manager() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'system_manager') {
        header("Location: ../../login.html");
        exit();
    }
}

// Update Store Manager password
function update_manager_password($conn, $new_password) {
    $message = "";
    // Note: Using plain text password to match sample data. In production, use password_hash().
    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE username = 'manager'");
    $stmt->bind_param("s", $new_password);
    if ($stmt->execute()) {
        $message = $stmt->affected_rows > 0 ? "Store Manager password updated successfully." : "No store manager found.";
    } else {
        $message = "Error updating password.";
    }
    $stmt->close();
    return $message;
}

// Get recent login logs (successful logins, limited to 10)
function get_recent_logins($conn) {
    return $conn->query("SELECT username_or_email, login_time, status FROM LoginLogs WHERE status = 'success' ORDER BY login_time DESC LIMIT 10");
}

// Get recent stock transactions (limited to 10)
function get_recent_transactions($conn) {
    return $conn->query("
        SELECT st.TransactionID, i.ItemDescription, st.TransactionType, st.Quantity, st.TransactionDate 
        FROM StockTransactions st 
        JOIN Items i ON st.ItemCode = i.ItemCode 
        ORDER BY st.TransactionDate DESC, st.TransactionID DESC LIMIT 10
    ");
}

// Get all stock transactions
function get_all_transactions($conn) {
    return $conn->query("
        SELECT st.TransactionID, i.ItemDescription, st.TransactionType, st.Quantity, st.TransactionDate 
        FROM StockTransactions st 
        JOIN Items i ON st.ItemCode = i.ItemCode 
        ORDER BY st.TransactionDate DESC, st.TransactionID DESC
    ");
}
?>