<?php
namespace PhpGraphBuilder;

/**
 * Data Persistence Layer with fallback support
 * Provides both database and file-based storage options
 */
class DataPersistence {
    private $dbOps = null;
    private $useDatabase = false;
    private $storageDir = '';

    public function __construct() {
        $this->storageDir = __DIR__ . '/../storage';
        
        // Try to initialize database
        try {
            require_once __DIR__ . '/../config/database.php';
            $this->dbOps = new DatabaseOperations();
            $this->useDatabase = true;
        } catch (\Exception $e) {
            // Database not available, use file storage
            $this->useDatabase = false;
            $this->ensureStorageDir();
        }
    }

    /**
     * Save analysis result
     */
    public function saveAnalysis($filename, $code, $graphData) {
        if ($this->useDatabase && $this->dbOps) {
            try {
                return $this->dbOps->saveAnalysis($filename, $code, $graphData);
            } catch (\Exception $e) {
                // Fallback to file storage
                return $this->saveAnalysisToFile($filename, $code, $graphData);
            }
        }
        return $this->saveAnalysisToFile($filename, $code, $graphData);
    }

    /**
     * Save analysis to file storage
     */
    private function saveAnalysisToFile($filename, $code, $graphData) {
        $this->ensureStorageDir();
        
        $timestamp = time();
        $fileHash = substr(md5($code), 0, 8);
        $analysisFile = $this->storageDir . "/analysis_" . $timestamp . "_" . $fileHash . ".json";
        
        $data = [
            'filename' => $filename,
            'code' => $code,
            'graph' => $graphData,
            'timestamp' => $timestamp
        ];
        
        file_put_contents($analysisFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return [
            'success' => true,
            'message' => 'Analysis saved to storage (database unavailable)',
            'file' => basename($analysisFile)
        ];
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureStorageDir() {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Get database operations (if available)
     */
    public function getDbOperations() {
        return $this->dbOps;
    }

    /**
     * Check if database is available
     */
    public function isDatabaseAvailable() {
        return $this->useDatabase && $this->dbOps !== null;
    }

    /**
     * Get storage directory
     */
    public function getStorageDir() {
        return $this->storageDir;
    }
}
?>
