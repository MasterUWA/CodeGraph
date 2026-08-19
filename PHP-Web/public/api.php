<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpGraphBuilder\DataPersistence;
use PhpGraphBuilder\GraphBuilder;

function sendResponse(bool $success, $data = null, ?string $error = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, null, 'Only POST requests allowed', 405);
    }

    $code = '';
    $filename = 'editor_input.php';

    if (isset($_FILES['phpfile'])) {
        if ($_FILES['phpfile']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error code: ' . $_FILES['phpfile']['error']);
        }

        $tmpName = $_FILES['phpfile']['tmp_name'];
        $fileName = $_FILES['phpfile']['name'];
        $filename = $fileName;

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'php' && $ext !== 'txt') {
            throw new \Exception('Only .php and .txt files are allowed');
        }

        $code = file_get_contents($tmpName);
        if ($code === false) {
            throw new \Exception('Failed to read uploaded file');
        }
    } elseif ($input = file_get_contents('php://input')) {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }
        $code = $data['code'] ?? '';
        if (!empty($data['filename'])) {
            $filename = basename((string) $data['filename']);
        }
    } elseif (isset($_POST['code'])) {
        $code = $_POST['code'];
        if (!empty($_POST['filename'])) {
            $filename = basename((string) $_POST['filename']);
        }
    }

    $code = trim($code);
    if (empty($code)) {
        throw new \Exception('No PHP code provided');
    }

    if (strpos($code, '<?php') === false) {
        $code = "<?php\n" . $code;
    }

    $builder = new GraphBuilder();
    $graph = $builder->buildJointGraph($code);

    // Persist analysis result. Uses database when available, falls back to file storage.
    $persistence = new DataPersistence();
    $saveResult = $persistence->saveAnalysis($filename, $code, $graph);
    $graph['metadata']['saved'] = [
        'success' => (bool) ($saveResult['success'] ?? false),
        'backend' => $persistence->isDatabaseAvailable() ? 'database' : 'file',
        'result' => $saveResult
    ];

    sendResponse(true, $graph);
} catch (\Throwable $e) {
    $message = $e->getMessage();
    if ($e instanceof \PhpParser\Error) {
        sendResponse(false, null, 'PHP Parse Error: ' . $message, 400);
    }
    sendResponse(false, null, $message, 500);
}
?>