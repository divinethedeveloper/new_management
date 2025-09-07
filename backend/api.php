<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$endpoint = $_GET['endpoint'] ?? '';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-1 month'));
$to = $_GET['to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 20);
$period = $_GET['period'] ?? 'monthly';
$offset = ($page - 1) * $limit;

// Database connection with error handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=store_management;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
    exit;
}

try {
    switch ($endpoint) {
        case 'overview':
            // Simplified overview query
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT i.ItemCode) AS total_items,
                    SUM(COALESCE(sb.StockOnHand, 0)) AS total_stock,
                    SUM(CASE WHEN COALESCE(sb.StockOnHand, 0) < i.ReorderLevel THEN 1 ELSE 0 END) AS low_alerts,
                    SUM(CASE WHEN COALESCE(sb.StockOnHand, 0) >= i.ReorderLevel * 1.5 THEN 1 ELSE 0 END) AS normal_count,
                    SUM(CASE WHEN COALESCE(sb.StockOnHand, 0) >= i.ReorderLevel AND COALESCE(sb.StockOnHand, 0) < i.ReorderLevel * 1.5 THEN 1 ELSE 0 END) AS watch_count
                FROM Items i
                LEFT JOIN StockBalance sb ON i.ItemCode = sb.ItemCode
            ");
            $stmt->execute();
            $result = $stmt->fetch();

            // Get last updated date
            $lastStmt = $pdo->prepare("SELECT MAX(LastUpdated) AS last_updated FROM StockBalance");
            $lastStmt->execute();
            $lastResult = $lastStmt->fetch();

            $total_items = intval($result['total_items']);
            $normal_count = intval($result['normal_count']);
            $watch_count = intval($result['watch_count']);
            $low_count = intval($result['low_alerts']);
            $total_counts = $normal_count + $watch_count + $low_count;
            
            $normal_pct = $total_counts > 0 ? round(($normal_count / $total_counts) * 100) : 0;
            $watch_pct = $total_counts > 0 ? round(($watch_count / $total_counts) * 100) : 0;
            $low_pct = $total_counts > 0 ? round(($low_count / $total_counts) * 100) : 0;

            echo json_encode([
                'total_items' => $total_items,
                'total_stock' => intval($result['total_stock']),
                'low_alerts' => $low_count,
                'last_updated' => $lastResult['last_updated'] ?? 'N/A',
                'normal_pct' => $normal_pct,
                'watch_pct' => $watch_pct,
                'low_pct' => $low_pct
            ]);
            break;

        case 'table_data':
            // Build WHERE clause for search
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($search) {
                $whereClause .= " AND (i.ItemCode LIKE :search OR i.ItemDescription LIKE :search)";
                $params['search'] = "%$search%";
            }

            // Base query with simpler calculations
            $baseQuery = "
                SELECT 
                    i.ItemCode,
                    i.ItemDescription,
                    i.Unit,
                    i.ReorderLevel,
                    COALESCE(sb.OpeningStock, 0) as OpeningStockPeriod,
                    COALESCE(
                        (SELECT SUM(Quantity) FROM StockTransactions st1 
                         WHERE st1.ItemCode = i.ItemCode 
                         AND st1.TransactionType = 'Receipt' 
                         AND st1.TransactionDate BETWEEN :from AND :to), 0
                    ) as TotalReceipts,
                    COALESCE(
                        (SELECT SUM(Quantity) FROM StockTransactions st2 
                         WHERE st2.ItemCode = i.ItemCode 
                         AND st2.TransactionType = 'Issue' 
                         AND st2.TransactionDate BETWEEN :from AND :to), 0
                    ) as TotalIssues,
                    COALESCE(sb.StockOnHand, 0) as StockOnHand,
                    CASE 
                        WHEN COALESCE(sb.StockOnHand, 0) < i.ReorderLevel THEN 'Low'
                        WHEN COALESCE(sb.StockOnHand, 0) < i.ReorderLevel * 1.5 THEN 'Watch'
                        ELSE 'OK'
                    END as Status
                FROM Items i
                LEFT JOIN StockBalance sb ON i.ItemCode = sb.ItemCode
                $whereClause
            ";

            // Add filter conditions
            if ($filter !== 'all') {
                switch ($filter) {
                    case 'low':
                        $baseQuery .= " HAVING Status = 'Low'";
                        break;
                    case 'watch':
                        $baseQuery .= " HAVING Status = 'Watch'";
                        break;
                    case 'high':
                        $baseQuery .= " HAVING StockOnHand >= ReorderLevel * 2";
                        break;
                    case 'recent_issued':
                        // For now, just order by ItemCode - you can enhance this later
                        break;
                    case 'recent_received':
                        // For now, just order by ItemCode - you can enhance this later
                        break;
                }
            }

            // Add FROM and TO parameters
            $params['from'] = $from;
            $params['to'] = $to;

            // Get total count
            $countQuery = "SELECT COUNT(*) FROM ($baseQuery) AS sub";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Add ordering and pagination
            $finalQuery = $baseQuery . " ORDER BY i.ItemCode ASC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            $stmt = $pdo->prepare($finalQuery);
            
            // Bind parameters with correct types
            foreach ($params as $key => $value) {
                if (in_array($key, ['limit', 'offset'])) {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }
            
            $stmt->execute();
            $rows = $stmt->fetchAll();

            echo json_encode([
                'data' => $rows,
                'total' => intval($total)
            ]);
            break;

        case 'trend_data':
            $params = ['from' => $from, 'to' => $to];
            
            // Default to monthly if period is not recognized
            if ($period === 'quarterly') {
                $query = "
                    SELECT 
                        CONCAT('Q', QUARTER(TransactionDate), ' ', YEAR(TransactionDate)) AS label,
                        SUM(CASE WHEN TransactionType = 'Receipt' THEN Quantity ELSE 0 END) AS received,
                        SUM(CASE WHEN TransactionType = 'Issue' THEN Quantity ELSE 0 END) AS issued
                    FROM StockTransactions
                    WHERE TransactionDate BETWEEN :from AND :to
                    GROUP BY YEAR(TransactionDate), QUARTER(TransactionDate)
                    ORDER BY YEAR(TransactionDate), QUARTER(TransactionDate)
                ";
            } elseif ($period === 'yearly') {
                $query = "
                    SELECT 
                        YEAR(TransactionDate) AS label,
                        SUM(CASE WHEN TransactionType = 'Receipt' THEN Quantity ELSE 0 END) AS received,
                        SUM(CASE WHEN TransactionType = 'Issue' THEN Quantity ELSE 0 END) AS issued
                    FROM StockTransactions
                    WHERE TransactionDate BETWEEN :from AND :to
                    GROUP BY YEAR(TransactionDate)
                    ORDER BY YEAR(TransactionDate)
                ";
            } else { // monthly
                $query = "
                    SELECT 
                        DATE_FORMAT(TransactionDate, '%b %Y') AS label,
                        SUM(CASE WHEN TransactionType = 'Receipt' THEN Quantity ELSE 0 END) AS received,
                        SUM(CASE WHEN TransactionType = 'Issue' THEN Quantity ELSE 0 END) AS issued
                    FROM StockTransactions
                    WHERE TransactionDate BETWEEN :from AND :to
                    GROUP BY DATE_FORMAT(TransactionDate, '%Y-%m')
                    ORDER BY TransactionDate
                ";
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            $labels = array_column($results, 'label');
            $received = array_map('intval', array_column($results, 'received'));
            $issued = array_map('intval', array_column($results, 'issued'));

            echo json_encode([
                'labels' => $labels,
                'received' => $received,
                'issued' => $issued
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error occurred',
        'details' => $e->getMessage(),
        'endpoint' => $endpoint
    ]);
}
?>