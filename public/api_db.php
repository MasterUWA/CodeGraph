<?php
/**
 * Enhanced API with Database Support
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/ASTParser.php';
require_once __DIR__ . '/../src/DFGBuilder.php';
require_once __DIR__ . '/../src/GraphBuilder.php';
require_once __DIR__ . '/../src/DatabaseOperations.php';

use PhpGraphBuilder\GraphBuilder;
use PhpGraphBuilder\DatabaseOperations;

$action = $_GET['action'] ?? 'analyze';

try {
    $dbOps = new DatabaseOperations();
    
    switch ($action) {
        case 'analyze':
            handleAnalyze($dbOps);
            break;
            
        case 'history':
            handleHistory($dbOps);
            break;
            
        case 'view':
            handleView($dbOps);
            break;
            
        case 'vulnerabilities':
            handleVulnerabilities($dbOps);
            break;
            
        case 'stats':
            handleStats($dbOps);
            break;
            
        case 'search':
            handleSearch($dbOps);
            break;
            
        case 'export':
            handleExport($dbOps);
            break;
            
        default:
            throw new Exception("Unknown action: $action");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleAnalyze($dbOps) {
    $code = '';
    $filename = 'untitled.php';
    
    if (isset($_FILES['phpfile'])) {
        $code = file_get_contents($_FILES['phpfile']['tmp_name']);
        $filename = basename($_FILES['phpfile']['name']);
    } elseif (isset($_POST['code'])) {
        $code = $_POST['code'];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $filename = $input['filename'] ?? 'untitled.php';
    }
    
    if (empty(trim($code))) {
        throw new Exception('No code provided');
    }
    
    // Build graph
    $builder = new GraphBuilder();
    $graph = $builder->buildJointGraph($code);
    
    // Save to database
    $result = $dbOps->saveAnalysis($filename, $code, $graph);
    
    echo json_encode([
        'success' => true,
        'data' => $graph,
        'analysis_id' => $result['analysis_id'],
        'file_id' => $result['file_id']
    ]);
}

function handleHistory($dbOps) {
    $limit = $_GET['limit'] ?? 10;
    $analyses = $dbOps->getRecentAnalyses($limit);
    
    echo json_encode([
        'success' => true,
        'data' => $analyses
    ]);
}

function handleView($dbOps) {
    $analysisId = $_GET['id'] ?? 0;
    $analysis = $dbOps->getAnalysis($analysisId);
    
    if (!$analysis) {
        throw new Exception('Analysis not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $analysis
    ]);
}

function handleVulnerabilities($dbOps) {
    $analysisId = $_GET['analysis_id'] ?? 0;
    $vulns = $dbOps->getVulnerabilities($analysisId);
    
    echo json_encode([
        'success' => true,
        'data' => $vulns
    ]);
}

function handleStats($dbOps) {
    $days = $_GET['days'] ?? 30;
    $stats = $dbOps->getVulnerabilityStats($days);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function handleSearch($dbOps) {
    $term = $_GET['q'] ?? '';
    $results = $dbOps->searchCode($term);
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
}

function handleExport($dbOps) {
    $limit = $_GET['limit'] ?? 100;
    $export = $dbOps->exportForML($limit);
    
    header('Content-Disposition: attachment; filename="training_data.json"');
    echo json_encode($export);
}