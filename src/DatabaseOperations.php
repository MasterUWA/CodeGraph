<?php
namespace PhpGraphBuilder;

require_once __DIR__ . '/../config/database.php';

class DatabaseOperations {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    /**
     * Save a PHP file analysis
     */
    public function saveAnalysis($filename, $code, $graphData) {
        try {
            $this->db->beginTransaction();
            
            // Insert PHP file
            $stmt = $this->db->prepare("
                INSERT INTO php_files (filename, code_content, lines_of_code, file_size, file_hash)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $linesOfCode = substr_count($code, "\n") + 1;
            $fileSize = strlen($code);
            $fileHash = hash('sha256', $code);
            
            $stmt->execute([$filename, $code, $linesOfCode, $fileSize, $fileHash]);
            $fileId = $this->db->lastInsertId();
            
            // Insert graph analysis
            $stmt = $this->db->prepare("
                INSERT INTO graph_analyses (file_id, analysis_type, graph_json, node_count, edge_count, complexity_score)
                VALUES (?, 'joint', ?, ?, ?, ?)
            ");
            
            $graphJson = json_encode($graphData);
            $nodeCount = count($graphData['nodes'] ?? []);
            $edgeCount = count($graphData['edges'] ?? []);
            $complexity = $nodeCount > 0 ? round($edgeCount / $nodeCount, 2) : 0;
            
            $stmt->execute([$fileId, $graphJson, $nodeCount, $edgeCount, $complexity]);
            $analysisId = $this->db->lastInsertId();
            
            // Insert graph nodes
            if (!empty($graphData['nodes'])) {
                $this->saveGraphNodes($analysisId, $graphData['nodes']);
            }
            
            // Insert graph edges
            if (!empty($graphData['edges'])) {
                $this->saveGraphEdges($analysisId, $graphData['edges']);
            }
            
            // Insert vulnerabilities if found
            if ($graphData['metadata']['vulnerability_detected'] ?? false) {
                $this->saveVulnerabilities($analysisId, $graphData);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'analysis_id' => $analysisId,
                'file_id' => $fileId
            ];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Save graph nodes
     */
    private function saveGraphNodes($analysisId, $nodes) {
        $stmt = $this->db->prepare("
            INSERT INTO graph_nodes (id, analysis_id, node_type, node_label, code_snippet, line_number, layer, is_source, is_sink, properties)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($nodes as $node) {
            $stmt->execute([
                $node['id'],
                $analysisId,
                $node['type'] ?? 'unknown',
                $node['code'] ?? '',
                $node['code'] ?? '',
                $node['line'] ?? 0,
                $node['layer'] ?? 'ast',
                $node['is_source'] ?? false,
                $node['is_sink'] ?? false,
                json_encode($node)
            ]);
        }
    }
    
    /**
     * Save graph edges
     */
    private function saveGraphEdges($analysisId, $edges) {
        $stmt = $this->db->prepare("
            INSERT INTO graph_edges (analysis_id, source_node_id, target_node_id, edge_type, edge_label)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($edges as $edge) {
            $stmt->execute([
                $analysisId,
                $edge['source'],
                $edge['target'],
                $edge['label'] ?? 'unknown',
                $edge['label'] ?? ''
            ]);
        }
    }
    
    /**
     * Save detected vulnerabilities
     */
    private function saveVulnerabilities($analysisId, $graphData) {
        $stmt = $this->db->prepare("
            INSERT INTO vulnerabilities (analysis_id, vulnerability_type, severity, description, source_node_id, sink_node_id, line_number, code_snippet)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($graphData['edges'] as $edge) {
            if (($edge['label'] ?? '') === 'TAINT_FLOW') {
                $stmt->execute([
                    $analysisId,
                    'sqli',
                    'high',
                    'Potential SQL injection detected',
                    $edge['source'],
                    $edge['target'],
                    $this->getNodeLine($edge['source'], $graphData['nodes']),
                    $this->getNodeCode($edge['source'], $graphData['nodes'])
                ]);
            }
        }
    }
    
    /**
     * Get recent analyses
     */
    public function getRecentAnalyses($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM v_recent_analyses 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get analysis by ID
     */
    public function getAnalysis($analysisId) {
        $stmt = $this->db->prepare("SELECT * FROM graph_analyses WHERE id = ?");
        $stmt->execute([$analysisId]);
        $analysis = $stmt->fetch();
        
        if ($analysis) {
            $analysis['graph_json'] = json_decode($analysis['graph_json'], true);
        }
        
        return $analysis;
    }
    
    /**
     * Get vulnerabilities for analysis
     */
    public function getVulnerabilities($analysisId) {
        $stmt = $this->db->prepare("SELECT * FROM vulnerabilities WHERE analysis_id = ?");
        $stmt->execute([$analysisId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get vulnerability statistics
     */
    public function getVulnerabilityStats($days = 30) {
        $stmt = $this->db->prepare("CALL GetVulnerabilityStats(?)");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search code for vulnerabilities
     */
    public function searchCode($searchTerm) {
        $stmt = $this->db->prepare("CALL SearchVulnerableCode(?)");
        $stmt->execute([$searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Export analysis for ML training
     */
    public function exportForML($limit = 1000) {
        $stmt = $this->db->prepare("
            SELECT 
                ga.graph_json,
                COUNT(v.id) as vulnerability_count,
                GROUP_CONCAT(DISTINCT v.vulnerability_type) as vuln_types
            FROM graph_analyses ga
            LEFT JOIN vulnerabilities v ON ga.id = v.analysis_id
            GROUP BY ga.id
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        $export = [];
        while ($row = $stmt->fetch()) {
            $export[] = [
                'graph' => json_decode($row['graph_json'], true),
                'has_vulnerability' => $row['vulnerability_count'] > 0,
                'vulnerability_types' => $row['vuln_types'] ? explode(',', $row['vuln_types']) : []
            ];
        }
        
        return $export;
    }
    
    /**
     * Helper: Get node line number
     */
    private function getNodeLine($nodeId, $nodes) {
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node['line'] ?? 0;
            }
        }
        return 0;
    }
    
    /**
     * Helper: Get node code
     */
    private function getNodeCode($nodeId, $nodes) {
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node['code'] ?? '';
            }
        }
        return '';
    }
}