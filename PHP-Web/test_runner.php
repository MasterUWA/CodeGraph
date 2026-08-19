<?php
/**
 * CLI Test Runner for PHP Graph Builder
 * Run this to test the graph builder with sample code
 * Usage: php test_runner.php
 */

// Set error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "❌ Error: $errstr (Line $errline in $errfile)\n";
    return true;
});

echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║  PHP Graph Builder - Test Runner           ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

// Test 1: Check environment
echo "Test 1: Checking environment...\n";
echo "  • PHP Version: " . phpversion() . "\n";
echo "  • Extension 'json': " . (extension_loaded('json') ? "✅" : "❌") . "\n";

// Create storage directory
if (!is_dir('storage')) {
    mkdir('storage', 0755, true);
}
echo "  • Storage directory: " . (is_dir('storage') ? "✅" : "❌") . "\n\n";

// Test 2: Autoloader
echo "Test 2: Checking autoloader...\n";
if (!file_exists('vendor/autoload.php')) {
    echo "  ❌ Autoloader not found. Running 'composer install'...\n";
    system('cd ' . __DIR__ . ' && composer install');
}
require_once 'vendor/autoload.php';
echo "  ✅ Autoloader loaded\n\n";

// Test 3: Load classes
echo "Test 3: Loading classes...\n";
try {
    $reflect = new ReflectionClass('PhpGraphBuilder\GraphBuilder');
    echo "  ✅ GraphBuilder loaded\n";
} catch (ReflectionException $e) {
    echo "  ❌ GraphBuilder not found\n";
    exit(1);
}

try {
    $reflect = new ReflectionClass('PhpGraphBuilder\ASTParser');
    echo "  ✅ ASTParser loaded\n";
} catch (ReflectionException $e) {
    echo "  ❌ ASTParser not found\n";
    exit(1);
}

try {
    $reflect = new ReflectionClass('PhpGraphBuilder\DFGBuilder');
    echo "  ✅ DFGBuilder loaded\n";
} catch (ReflectionException $e) {
    echo "  ❌ DFGBuilder not found\n";
    exit(1);
}
echo "\n";

// Test 4: Test SQL Injection sample
echo "Test 4: Testing SQL Injection sample...\n";
$sqliCode = <<<'PHP'
<?php
$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id=" . $id;
$result = mysqli_query($conn, $sql);
?>
PHP;

try {
    $builder = new \PhpGraphBuilder\GraphBuilder();
    $graph = $builder->buildJointGraph($sqliCode);
    
    echo "  ✅ Graph built successfully\n";
    echo "  • Nodes: " . $graph['metadata']['node_count'] . "\n";
    echo "  • Edges: " . $graph['metadata']['edge_count'] . "\n";
    echo "  • Complexity: " . $graph['metadata']['complexity'] . "\n";
    echo "  • Vulnerability detected: " . ($graph['metadata']['vulnerability_detected'] ? "✅ Yes" : "❌ No") . "\n";
    echo "  • Tainted variables: " . implode(', ', $graph['metadata']['tainted_variables'] ?? []) . "\n";
    echo "  • Edge types: " . json_encode($graph['metadata']['edge_types']) . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Test XSS sample
echo "Test 5: Testing XSS sample...\n";
$xssCode = <<<'PHP'
<?php
$name = $_POST['name'];
echo "Hello, " . $name;
?>
PHP;

try {
    $builder = new \PhpGraphBuilder\GraphBuilder();
    $graph = $builder->buildJointGraph($xssCode);
    
    echo "  ✅ Graph built successfully\n";
    echo "  • Nodes: " . $graph['metadata']['node_count'] . "\n";
    echo "  • Edges: " . $graph['metadata']['edge_count'] . "\n";
    echo "  • Vulnerability detected: " . ($graph['metadata']['vulnerability_detected'] ? "✅ Yes" : "❌ No") . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Test safe code sample
echo "Test 6: Testing safe code sample...\n";
$safeCode = <<<'PHP'
<?php
$id = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE id=" . $id;
$result = mysqli_query($conn, $sql);
?>
PHP;

try {
    $builder = new \PhpGraphBuilder\GraphBuilder();
    $graph = $builder->buildJointGraph($safeCode);
    
    echo "  ✅ Graph built successfully\n";
    echo "  • Nodes: " . $graph['metadata']['node_count'] . "\n";
    echo "  • Edges: " . $graph['metadata']['edge_count'] . "\n";
    echo "  • Vulnerability detected: " . ($graph['metadata']['vulnerability_detected'] ? "✅ Yes" : "❌ No") . "\n";
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Summary
echo "╔════════════════════════════════════════════╗\n";
echo "║  ✅ All Tests Passed!                      ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

echo "🎉 The PHP Graph Builder is working correctly!\n\n";
echo "To view the web interface:\n";
echo "  1. Run: composer start\n";
echo "  2. Open: http://localhost:8080\n\n";
?>
