<?php
header('Content-Type: application/json');
$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log'); // Ensure this path is writable

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=store_management;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Function to check if a table exists
function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        return true;
    } catch (Exception $e) {
        error_log("Table check failed for $table: " . $e->getMessage());
        return false;
    }
}

// Function to update StockBalance and StockAlerts
function updateStock($pdo, $itemCode, $date) {
    try {
        // Calculate StockOnHand
        $stmt = $pdo->prepare("
            SELECT sb.OpeningStock + 
                   COALESCE(SUM(CASE WHEN st.TransactionType = 'Receipt' THEN st.Quantity ELSE 0 END), 0) -
                   COALESCE(SUM(CASE WHEN st.TransactionType = 'Issue' THEN st.Quantity ELSE 0 END), 0) AS StockOnHand
            FROM StockBalance sb
            LEFT JOIN StockTransactions st ON sb.ItemCode = st.ItemCode
            WHERE sb.ItemCode = ?
        ");
        $stmt->execute([$itemCode]);
        $stockOnHand = $stmt->fetchColumn();

        // Update StockBalance
        $stmt = $pdo->prepare("UPDATE StockBalance SET StockOnHand = ?, LastUpdated = ? WHERE ItemCode = ?");
        $stmt->execute([$stockOnHand, $date, $itemCode]);

        // Update StockAlerts
        $stmt = $pdo->prepare("
            SELECT i.ReorderLevel
            FROM Items i
            WHERE i.ItemCode = ?
        ");
        $stmt->execute([$itemCode]);
        $reorderLevel = $stmt->fetchColumn();

        if ($stockOnHand < $reorderLevel) {
            $status = 'Low';
        } elseif ($stockOnHand < $reorderLevel * 1.5) {
            $status = 'Watch';
        } else {
            $status = 'OK';
        }

        $stmt = $pdo->prepare("
            INSERT INTO StockAlerts (ItemCode, AlertStatus, AlertDate)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE AlertStatus = ?, AlertDate = ?
        ");
        $stmt->execute([$itemCode, $status, $date, $status, $date]);
    } catch (Exception $e) {
        error_log('Error updating stock for ItemCode ' . $itemCode . ': ' . $e->getMessage());
        throw new Exception('Error updating stock');
    }
}

switch ($endpoint) {
    case 'items':
        if (!tableExists($pdo, 'Items')) {
            error_log('Items table does not exist');
            echo json_encode(['success' => false, 'message' => 'Items table does not exist']);
            exit;
        }

        $filter = $_GET['filter'] ?? 'all';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, intval($_GET['limit'] ?? 20));
        $offset = ($page - 1) * $limit;

        $params = [];
        // Simplified query without StockBalance join to avoid errors
        $countQuery = "SELECT COUNT(*) FROM Items i WHERE 1=1";
        $query = "
            SELECT 
                i.ItemCode, 
                i.ItemDescription, 
                i.Unit, 
                i.ReorderLevel,
                COALESCE((
                    SELECT sb.OpeningStock 
                    FROM StockBalance sb 
                    WHERE sb.ItemCode = i.ItemCode
                ), 0) AS OpeningStock,
                COALESCE((
                    SELECT sb.StockOnHand 
                    FROM StockBalance sb 
                    WHERE sb.ItemCode = i.ItemCode
                ), 0) AS StockOnHand,
                COALESCE((
                    SELECT CASE 
                        WHEN sb.StockOnHand < i.ReorderLevel THEN 'Low'
                        WHEN sb.StockOnHand < i.ReorderLevel * 1.5 THEN 'Watch'
                        ELSE 'OK' 
                    END
                    FROM StockBalance sb 
                    WHERE sb.ItemCode = i.ItemCode
                ), 'N/A') AS Status
            FROM Items i
            WHERE 1=1
        ";

        try {
            if ($filter === 'low') {
                $countQuery .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand < i.ReorderLevel)";
                $query .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand < i.ReorderLevel)";
            } elseif ($filter === 'watch') {
                $countQuery .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand >= i.ReorderLevel AND sb.StockOnHand < i.ReorderLevel * 1.5)";
                $query .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand >= i.ReorderLevel AND sb.StockOnHand < i.ReorderLevel * 1.5)";
            } elseif ($filter === 'high') {
                $countQuery .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand >= i.ReorderLevel * 2)";
                $query .= " AND EXISTS (SELECT 1 FROM StockBalance sb WHERE sb.ItemCode = i.ItemCode AND sb.StockOnHand >= i.ReorderLevel * 2)";
            }

            // Execute count query
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            // Main query with pagination
            $query .= " ORDER BY i.ItemCode ASC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($query);
            // Explicitly bind LIMIT and OFFSET as integers
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $data, 'total' => $total]);
        } catch (Exception $e) {
            error_log('Items query failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $e->getMessage()]);
            exit;
        }
        break;

    case 'transactions':
        if (!tableExists($pdo, 'StockTransactions')) {
            error_log('StockTransactions table does not exist');
            echo json_encode(['success' => false, 'message' => 'StockTransactions table does not exist']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT TransactionID, ItemCode, TransactionType, Quantity, TransactionDate
                FROM StockTransactions
                ORDER BY TransactionDate DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            error_log('Transactions query failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $e->getMessage()]);
            exit;
        }
        break;

    case 'add_item':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data['ItemCode'] || !$data['ItemDescription'] || !$data['Unit'] || !isset($data['ReorderLevel']) || !isset($data['OpeningStock'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO Items (ItemCode, ItemDescription, Unit, ReorderLevel) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['ItemCode'], $data['ItemDescription'], $data['Unit'], $data['ReorderLevel']]);
            $stmt = $pdo->prepare("INSERT INTO StockBalance (ItemCode, OpeningStock, StockOnHand, LastUpdated) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['ItemCode'], $data['OpeningStock'], $data['OpeningStock'], date('Y-m-d')]);
            updateStock($pdo, $data['ItemCode'], date('Y-m-d'));
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Item added successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error adding item: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding item: ' . $e->getMessage()]);
        }
        break;

    case 'update_item':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $itemCode = $_GET['ItemCode'] ?? '';
        if (!$itemCode || !$data['ItemDescription'] || !$data['Unit'] || !isset($data['ReorderLevel']) || !isset($data['OpeningStock'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE Items SET ItemDescription = ?, Unit = ?, ReorderLevel = ? WHERE ItemCode = ?");
            $stmt->execute([$data['ItemDescription'], $data['Unit'], $data['ReorderLevel'], $itemCode]);
            $stmt = $pdo->prepare("UPDATE StockBalance SET OpeningStock = ?, LastUpdated = ? WHERE ItemCode = ?");
            $stmt->execute([$data['OpeningStock'], date('Y-m-d'), $itemCode]);
            updateStock($pdo, $itemCode, date('Y-m-d'));
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error updating item: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating item: ' . $e->getMessage()]);
        }
        break;

    case 'delete_item':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $itemCode = $_GET['ItemCode'] ?? '';
        if (!$itemCode) {
            echo json_encode(['success' => false, 'message' => 'Missing ItemCode']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM StockTransactions WHERE ItemCode = ?");
            $stmt->execute([$itemCode]);
            $stmt = $pdo->prepare("DELETE FROM StockBalance WHERE ItemCode = ?");
            $stmt->execute([$itemCode]);
            $stmt = $pdo->prepare("DELETE FROM StockAlerts WHERE ItemCode = ?");
            $stmt->execute([$itemCode]);
            $stmt = $pdo->prepare("DELETE FROM Items WHERE ItemCode = ?");
            $stmt->execute([$itemCode]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error deleting item: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting item: ' . $e->getMessage()]);
        }
        break;

    case 'add_transaction':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data['ItemCode'] || !$data['TransactionType'] || !isset($data['Quantity']) || !$data['TransactionDate']) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        if ($data['Quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be positive']);
            exit;
        }
        if ($data['TransactionType'] === 'Issue') {
            $stmt = $pdo->prepare("SELECT StockOnHand FROM StockBalance WHERE ItemCode = ?");
            $stmt->execute([$data['ItemCode']]);
            $stockOnHand = $stmt->fetchColumn();
            if ($stockOnHand === false || $stockOnHand < $data['Quantity']) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock for issue']);
                exit;
            }
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO StockTransactions (ItemCode, TransactionType, Quantity, TransactionDate) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['ItemCode'], $data['TransactionType'], $data['Quantity'], $data['TransactionDate']]);
            updateStock($pdo, $data['ItemCode'], $data['TransactionDate']);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction added successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error adding transaction: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding transaction: ' . $e->getMessage()]);
        }
        break;

    case 'update_transaction':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $transactionID = $_GET['TransactionID'] ?? '';
        if (!$transactionID || !$data['ItemCode'] || !$data['TransactionType'] || !isset($data['Quantity']) || !$data['TransactionDate']) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        if ($data['Quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be positive']);
            exit;
        }
        if ($data['TransactionType'] === 'Issue') {
            $stmt = $pdo->prepare("
                SELECT sb.StockOnHand + COALESCE((SELECT Quantity FROM StockTransactions WHERE TransactionID = ? AND TransactionType = 'Issue'), 0) AS AvailableStock
                FROM StockBalance sb
                WHERE sb.ItemCode = ?
            ");
            $stmt->execute([$transactionID, $data['ItemCode']]);
            $availableStock = $stmt->fetchColumn();
            if ($availableStock === false || $availableStock < $data['Quantity']) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock for issue']);
                exit;
            }
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE StockTransactions SET ItemCode = ?, TransactionType = ?, Quantity = ?, TransactionDate = ? WHERE TransactionID = ?");
            $stmt->execute([$data['ItemCode'], $data['TransactionType'], $data['Quantity'], $data['TransactionDate'], $transactionID]);
            updateStock($pdo, $data['ItemCode'], $data['TransactionDate']);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction updated successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error updating transaction: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating transaction: ' . $e->getMessage()]);
        }
        break;

    case 'delete_transaction':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $transactionID = $_GET['TransactionID'] ?? '';
        if (!$transactionID) {
            echo json_encode(['success' => false, 'message' => 'Missing TransactionID']);       
            exit;
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT ItemCode FROM StockTransactions WHERE TransactionID = ?");
            $stmt->execute([$transactionID]);
            $itemCode = $stmt->fetchColumn();
            if ($itemCode === false) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM StockTransactions WHERE TransactionID = ?");
            $stmt->execute([$transactionID]);
            updateStock($pdo, $itemCode, date('Y-m-d'));
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error deleting transaction: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting transaction: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
}
?>