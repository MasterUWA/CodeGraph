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

try {
    $action = $_GET['action'] ?? 'all';
    
    if ($action === 'all') {
        // Get all samples
        $samples = \PhpGraphBuilder\TestDataset::getAllSamples();
        echo json_encode(['success' => true, 'data' => $samples]);
    } elseif ($action === 'get' && isset($_GET['name'])) {
        // Get specific sample
        $sample = \PhpGraphBuilder\TestDataset::getSampleByName($_GET['name']);
        if ($sample) {
            echo json_encode(['success' => true, 'data' => $sample]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Sample not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
