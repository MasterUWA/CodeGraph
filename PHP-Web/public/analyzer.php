<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /php-graph-builder/public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeGraph - PHP Security Analyzer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="#" class="navbar-logo">
                <i class="fas fa-code-branch"></i>
                <span>CodeGraph</span>
            </a>
            <div class="navbar-user">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-logout">Sign Out</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3>Analysis Tools</h3>
                <button id="buildBtn" class="btn btn-primary btn-block">
                    <i class="fas fa-play"></i> Build Joint Graph
                </button>
            </div>

            <div class="sidebar-section">
                <h3>Code Input</h3>
                <div class="input-group">
                    <textarea id="codeInput" placeholder="Paste your PHP code here..." rows="15"></textarea>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>File Upload</h3>
                <div class="file-upload">
                    <input type="file" id="fileInput" accept=".php" hidden>
                    <label for="fileInput" class="btn btn-secondary btn-block">
                        <i class="fas fa-upload"></i> Choose File
                    </label>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Sample Code</h3>
                <div class="sample-buttons">
                    <button class="btn btn-outline" onclick="loadSample('sqli')">
                        <i class="fas fa-shield-alt"></i> SQL Injection
                    </button>
                    <button class="btn btn-outline" onclick="loadSample('xss')">
                        <i class="fas fa-shield-alt"></i> XSS Attack
                    </button>
                    <button class="btn btn-outline" onclick="loadSample('safe')">
                        <i class="fas fa-check-circle"></i> Safe Code
                    </button>
                    <button class="btn btn-outline" onclick="loadSample('complex')">
                        <i class="fas fa-code"></i> Complex App
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="content">
            <!-- Graph Container -->
            <div id="graphContainer" class="graph-container">
                <div class="graph-placeholder">
                    <i class="fas fa-chart-network"></i>
                    <p>Graph visualization will appear here</p>
                </div>
            </div>

            <!-- Statistics Panel -->
            <div class="stats-panel">
                <div class="stat-card">
                    <div class="stat-label">Nodes</div>
                    <div class="stat-value" id="nodeCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Edges</div>
                    <div class="stat-value" id="edgeCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Complexity</div>
                    <div class="stat-value" id="complexity">0.0</div>
                </div>
            </div>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <!-- Vulnerability Status -->
            <div class="sidebar-section">
                <h3>Vulnerability Status</h3>
                <div id="vulnStatusContainer" class="vuln-status">
                    <p class="text-muted">No analysis yet</p>
                </div>
            </div>

            <!-- Edge Distribution -->
            <div class="sidebar-section">
                <h3>Edge Distribution</h3>
                <div id="edgeDistribution" class="edge-distribution">
                    <p class="text-muted">No analysis yet</p>
                </div>
            </div>

            <!-- Tainted Variables -->
            <div class="sidebar-section">
                <h3>Tainted Variables</h3>
                <div id="taintedVars" class="tainted-vars">
                    <p class="text-muted">No tainted variables found</p>
                </div>
            </div>
        </aside>
    </div>

    <!-- Toast Notifications -->
    <div id="toast" class="toast"></div>

    <!-- Scripts -->
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="app.js"></script>
</body>
</html>
