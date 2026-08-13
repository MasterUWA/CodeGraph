<?php
namespace PhpGraphBuilder;

class GraphBuilder {
    private $astParser;
    
    public function __construct() {
        $this->astParser = new ASTParser();
    }

    public function buildJointGraph(string $code): array {
        // Step 1: Parse AST
        $astResult = $this->astParser->parse($code);
        $nodes = $astResult['nodes'];
        $edges = $astResult['edges'];

        // Step 2: Build DFG with taint tracking
        $dfgBuilder = new DFGBuilder($nodes, $edges, $code);
        $dfgEdges = $dfgBuilder->buildDFG();
        $edges = array_merge($edges, $dfgEdges);

        // Step 3: Enrich nodes with metadata
        $nodes = $this->enrichNodes($nodes, $edges);

        // Step 4: Detect vulnerabilities and tainted variables
        $taintedVars = $this->extractTaintedVariables($edges);
        $vulnerabilityDetected = $this->detectVulnerability($edges);

        // Step 5: Count edge types
        $edgeTypes = $this->countEdgeTypes($edges);

        // Step 6: Calculate complexity
        $complexity = count($nodes) > 0 ? round(count($edges) / count($nodes), 2) : 0;

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
            'metadata' => [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'vulnerability_detected' => $vulnerabilityDetected,
                'tainted_variables' => $taintedVars,
                'edge_types' => $edgeTypes,
                'complexity' => $complexity,
                'graph_type' => 'Joint Program Graph',
                'layers' => ['AST', 'DFG']
            ]
        ];
    }

    private function enrichNodes(array $nodes, array $edges): array {
        // Calculate node degrees
        $degrees = [];
        foreach ($edges as $edge) {
            $degrees[$edge['source']] = ($degrees[$edge['source']] ?? 0) + 1;
            $degrees[$edge['target']] = ($degrees[$edge['target']] ?? 0) + 1;
        }
        
        foreach ($nodes as $id => &$node) {
            $node['color'] = $this->getNodeColor($node['type']);
            $node['size'] = $this->getNodeSize($node['type']);
            $node['degree'] = $degrees[$id] ?? 0;
            
            // Add risk indicators
            if ($this->isSource($node)) {
                $node['is_source'] = true;
                $node['color'] = '#ef4444';
            }
            if ($this->isSink($node)) {
                $node['is_sink'] = true;
                $node['color'] = '#f59e0b';
            }
        }
        
        return $nodes;
    }

    private function getNodeColor(string $type): string {
        $colors = [
            'AST_ROOT' => '#ef4444',
            'Stmt_Echo' => '#f59e0b',
            'Stmt_Expression' => '#8b5cf6',
            'Stmt_If' => '#3b82f6',
            'Stmt_While' => '#3b82f6',
            'Stmt_For' => '#3b82f6',
            'Expr_Variable' => '#10b981',
            'Expr_Assign' => '#6366f1',
            'Expr_FuncCall' => '#ec4899',
            'Expr_ArrayDimFetch' => '#14b8a6',
            'Scalar_String' => '#94a3b8',
            'Scalar_LNumber' => '#94a3b8',
        ];
        
        return $colors[$type] ?? '#94a3b8';
    }

    private function getNodeSize(string $type): int {
        $sizes = [
            'AST_ROOT' => 12,
            'Stmt_Echo' => 10,
            'Stmt_Expression' => 8,
            'Expr_FuncCall' => 10,
            'Expr_Variable' => 8,
        ];
        
        return $sizes[$type] ?? 6;
    }

    private function isSource(array $node): bool {
        $code = $node['code'] ?? '';
        $sources = ['$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_FILES'];
        foreach ($sources as $source) {
            if (strpos($code, $source) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isSink(array $node): bool {
        $code = $node['code'] ?? '';
        $sinks = ['mysqli_query', 'mysql_query', 'exec', 'system', 'eval', 'echo'];
        foreach ($sinks as $sink) {
            if (stripos($code, $sink) !== false) {
                return true;
            }
        }
        return false;
    }

    private function detectVulnerability(array $edges): bool {
        foreach ($edges as $edge) {
            if (($edge['label'] ?? '') === 'TAINT_FLOW') {
                return true;
            }
        }
        return false;
    }

    private function extractTaintedVariables(array $edges): array {
        $taintedVars = [];
        foreach ($edges as $edge) {
            if (($edge['label'] ?? '') === 'TAINT_SOURCE' || ($edge['label'] ?? '') === 'TAINT_FLOW') {
                // Extract variable name from edge source
                $source = $edge['source'] ?? '';
                if (preg_match('/\$([a-zA-Z_]\w*)/', $source, $matches)) {
                    $taintedVars[$matches[1]] = true;
                }
            }
        }
        return array_keys($taintedVars);
    }

    private function countEdgeTypes(array $edges): array {
        $edgeTypes = [];
        foreach ($edges as $edge) {
            $label = $edge['label'] ?? 'unknown';
            $edgeTypes[$label] = ($edgeTypes[$label] ?? 0) + 1;
        }
        return $edgeTypes;
    }
}