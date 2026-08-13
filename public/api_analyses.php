<?php
header('Content-Type: application/json');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();
    
    if ($action === 'save') {
        // Save analysis
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $code = $input['code'] ?? '';
        $nodes = $input['nodes'] ?? [];
        $edges = $input['edges'] ?? [];
        $metadata = $input['metadata'] ?? [];
        
        $node_count = count($nodes);
        $edge_count = count($edges);
        $vulnerability_detected = $metadata['vulnerability_detected'] ?? false;
        
        $graph_json = json_encode(['nodes' => $nodes, 'edges' => $edges, 'metadata' => $metadata]);
        
        // Save analysis
        $stmt = $db->prepare("
            INSERT INTO analyses (user_id, code, node_count, edge_count, vulnerability_detected, graph_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $code, $node_count, $edge_count, $vulnerability_detected ? 1 : 0, $graph_json]);
        
        $analysis_id = $db->lastInsertId();
        
        // Save vulnerabilities
        $tainted_vars = $metadata['tainted_variables'] ?? [];
        foreach ($tainted_vars as $var) {
            $stmt = $db->prepare("
                INSERT INTO vulnerabilities (analysis_id, type, severity, description)
                VALUES (?, 'Taint Flow', 'High', ?)
            ");
            $stmt->execute([$analysis_id, 'Variable: ' . $var]);
        }
        
        echo json_encode(['success' => true, 'analysis_id' => $analysis_id]);
        
    } elseif ($action === 'list') {
        // List user analyses
        $limit = intval($_GET['limit'] ?? 10);
        $offset = intval($_GET['offset'] ?? 0);
        
        $stmt = $db->prepare("
            SELECT id, created_at, node_count, edge_count, vulnerability_detected
            FROM analyses
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        $analyses = $stmt->fetchAll();
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM analyses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total = $stmt->fetch()['count'];
        
        echo json_encode(['success' => true, 'data' => $analyses, 'total' => $total]);
        
    } elseif ($action === 'get' && isset($_GET['id'])) {
        // Get specific analysis
        $analysis_id = intval($_GET['id']);
        
        $stmt = $db->prepare("
            SELECT * FROM analyses
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$analysis_id, $user_id]);
        $analysis = $stmt->fetch();
        
        if (!$analysis) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Analysis not found']);
            exit;
        }
        
        // Get vulnerabilities
        $stmt = $db->prepare("
            SELECT id, type, severity, description, created_at
            FROM vulnerabilities
            WHERE analysis_id = ?
        ");
        $stmt->execute([$analysis_id]);
        $vulnerabilities = $stmt->fetchAll();
        
        $analysis['vulnerabilities'] = $vulnerabilities;
        $analysis['graph'] = json_decode($analysis['graph_json'], true);
        
        echo json_encode(['success' => true, 'data' => $analysis]);
        
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        // Delete analysis
        $analysis_id = intval($_GET['id']);
        
        // Check ownership
        $stmt = $db->prepare("SELECT user_id FROM analyses WHERE id = ?");
        $stmt->execute([$analysis_id]);
        $analysis = $stmt->fetch();
        
        if (!$analysis || $analysis['user_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }
        
        // Delete vulnerabilities
        $stmt = $db->prepare("DELETE FROM vulnerabilities WHERE analysis_id = ?");
        $stmt->execute([$analysis_id]);
        
        // Delete analysis
        $stmt = $db->prepare("DELETE FROM analyses WHERE id = ?");
        $stmt->execute([$analysis_id]);
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'stats') {
        // Get user statistics
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_analyses,
                SUM(CASE WHEN vulnerability_detected = 1 THEN 1 ELSE 0 END) as vulnerable_count,
                AVG(node_count) as avg_nodes,
                AVG(edge_count) as avg_edges
            FROM analyses
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
