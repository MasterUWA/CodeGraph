<?php
namespace PhpGraphBuilder;

class DFGBuilder {
    private $dfgEdges = [];
    private $astNodes = [];
    private $variableDefs = [];  // Track last definition node ID of each variable (name => nodeId)
    private $taintedVars = [];   // Tainted variable names (name => true)
    private $code = '';

    private $sources = ['_GET', '_POST', '_REQUEST', '_COOKIE', '_FILES'];
    private $sinks = ['mysqli_query', 'mysql_query', 'exec', 'system', 'eval', '->query'];
    private $sanitizers = ['mysqli_real_escape_string', 'htmlspecialchars'];

    public function __construct(array $astNodes, array $astEdges, string $code = '') {
        $this->astNodes = $astNodes;
        $this->code = $code;
    }

    public function buildDFG(): array {
        // Use a lightweight, regex-based pass over the original code to detect
        // assignments from superglobals and usages in sinks. Then map findings
        // to AST node IDs when possible and emit TAINT edges.
        $this->detectAssignmentsFromSources();
        $this->detectSinksUsingTaintedVars();

        return $this->dfgEdges;
    }

    private function detectAssignmentsFromSources(): void {
        // Match simple assignments: $var = $_GET['id']; or $id = $_POST["p"];
        $pattern = '/\$([a-zA-Z_][\w]*)\s*=\s*[^;]*\$_(' . implode('|', array_map('preg_quote', $this->sources)) . ')[^;]*/i';
        if (preg_match_all($pattern, $this->code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $varName = $m[1];
                $this->taintedVars[$varName] = true;

                $varNodeId = $this->findNodeIdByCode('\$' . $varName);
                $sourceNodeId = $this->findNodeIdByPattern('\$_(' . implode('|', $this->sources) . ')');

                if ($varNodeId && $sourceNodeId) {
                    $this->dfgEdges[] = [
                        'source' => $sourceNodeId,
                        'target' => $varNodeId,
                        'label'  => 'TAINT_SOURCE'
                    ];
                    $this->variableDefs[$varName] = $varNodeId;
                }
            }
        }
    }

    private function detectSinksUsingTaintedVars(): void {
        // Find sink function calls and check if tainted variables appear in their arguments
        $sinkPattern = '/(' . implode('|', array_map('preg_quote', $this->sinks)) . ')\s*\(([^)]*)\)/i';
        if (preg_match_all($sinkPattern, $this->code, $sinkMatches, PREG_SET_ORDER)) {
            foreach ($sinkMatches as $sm) {
                $sinkCall = $sm[1];
                $args = $sm[2];

                foreach ($this->taintedVars as $varName => $_) {
                    // look for $varName in args
                    if (preg_match('/\$' . preg_quote($varName, '/') . '\b/', $args)) {
                        $varNodeId = $this->findNodeIdByCode('\$' . $varName);
                        $sinkNodeId = $this->findNodeIdByPattern(preg_quote($sinkCall, '/'));

                        if ($varNodeId && $sinkNodeId) {
                            $this->dfgEdges[] = [
                                'source' => $varNodeId,
                                'target' => $sinkNodeId,
                                'label'  => 'TAINT_FLOW'
                            ];
                        }
                    }
                }
            }
        }
    }

    private function findNodeIdByCode(string $needle): string {
        foreach ($this->astNodes as $id => $n) {
            if (!empty($n['code']) && strpos($n['code'], $needle) !== false) {
                return $id;
            }
        }
        return '';
    }

    private function findNodeIdByPattern(string $pattern): string {
        foreach ($this->astNodes as $id => $n) {
            if (!empty($n['code']) && preg_match('/' . $pattern . '/i', $n['code'])) {
                return $id;
            }
        }
        return '';
    }
}