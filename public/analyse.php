<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>CodeGraph - PHP Joint Program Graph Analyzer</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<script src="https://d3js.org/d3.v7.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<!-- Navigation Bar -->
	<nav class="navbar">
		<div class="nav-container">
			<div class="logo">
				<svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<circle cx="12" cy="12" r="3"/>
					<path d="M12 2v4m0 12v4M2 12h4m12 0h4"/>
					<circle cx="5" cy="5" r="1.5"/>
					<circle cx="19" cy="5" r="1.5"/>
					<circle cx="5" cy="19" r="1.5"/>
					<circle cx="19" cy="19" r="1.5"/>
				</svg>
				<span>CodeGraph</span>
			</div>
			<div class="nav-actions">
				<button class="nav-btn" onclick="window.location.href='home.html'" title="Back to Main Page">
					<i class="fas fa-house"></i>
					<span>Home</span>
				</button>
				<button class="nav-btn" onclick="exportGraph()" title="Export Graph">
					<i class="fas fa-download"></i>
					<span>Export</span>
				</button>
				<button class="nav-btn" onclick="clearAll()" title="Clear All">
					<i class="fas fa-trash-alt"></i>
					<span>Clear</span>
				</button>
				<button class="nav-btn" onclick="toggleTheme()" title="Toggle Theme">
					<i class="fas fa-moon"></i>
				</button>
			</div>
		</div>
	</nav>

	<!-- Main Container -->
	<div class="main-container">
		<!-- Left Sidebar - Input Panel -->
		<aside class="sidebar left-sidebar">
			<div class="sidebar-header">
				<h2><i class="fas fa-code"></i> Code Input</h2>
			</div>
			
			<!-- Tab Navigation -->
			<div class="tab-nav">
				<button class="tab-btn active" data-tab="editor">
					<i class="fas fa-edit"></i> Editor
				</button>
				<button class="tab-btn" data-tab="upload">
					<i class="fas fa-upload"></i> Upload
				</button>
				<button class="tab-btn" data-tab="samples">
					<i class="fas fa-flask"></i> Samples
				</button>
			</div>

			<!-- Editor Tab -->
			<div class="tab-panel active" id="editor-panel">
				<div class="editor-container">
					<div class="editor-header">
						<div class="editor-dots">
							<span class="dot red"></span>
							<span class="dot yellow"></span>
							<span class="dot green"></span>
						</div>
						<span class="editor-filename">untitled.php</span>
						<div class="editor-lang">
							<i class="fab fa-php"></i> PHP
						</div>
					</div>
					<textarea id="codeInput" class="code-editor" spellcheck="false">&lt;?php
// Paste your PHP code here...
$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id=" . $id;
$result = mysqli_query($conn, $sql);
?&gt;</textarea>
					<div class="editor-footer">
						<span id="charCount">0 characters</span>
						<span id="lineCount">0 lines</span>
					</div>
				</div>
				<button class="action-btn primary" onclick="analyzeCode()">
					<i class="fas fa-project-diagram"></i>
					<span>Build Joint Graph</span>
					<div class="btn-shine"></div>
				</button>
			</div>

			<!-- Upload Tab -->
			<div class="tab-panel" id="upload-panel">
				<div class="upload-area" id="uploadArea">
					<div class="upload-content">
						<i class="fas fa-cloud-upload-alt upload-icon"></i>
						<h3>Drop PHP file here</h3>
						<p>or click to browse</p>
						<span class="upload-hint">Supports .php files only</span>
					</div>
					<input type="file" id="fileInput" accept=".php" hidden>
				</div>
				<div class="uploaded-file" id="uploadedFileInfo" style="display:none;">
					<i class="fas fa-file-code"></i>
					<span id="uploadedFileName"></span>
					<button onclick="removeUploadedFile()" class="icon-btn">
						<i class="fas fa-times"></i>
					</button>
				</div>
				<button class="action-btn primary" onclick="uploadFile()" id="uploadBtn" disabled>
					<i class="fas fa-upload"></i>
					<span>Upload & Analyze</span>
				</button>
			</div>

			<!-- Samples Tab -->
			<div class="tab-panel" id="samples-panel">
				<div class="samples-grid">
					<div class="sample-card" onclick="loadSample('sqli')">
						<div class="sample-icon danger">
							<i class="fas fa-skull-crossbones"></i>
						</div>
						<h3>SQL Injection</h3>
						<p>Unsanitized user input in SQL query</p>
						<span class="sample-badge danger">VULNERABLE</span>
					</div>
					<div class="sample-card" onclick="loadSample('xss')">
						<div class="sample-icon warning">
							<i class="fas fa-bug"></i>
						</div>
						<h3>Cross-Site Scripting</h3>
						<p>Unescaped output to HTML</p>
						<span class="sample-badge warning">VULNERABLE</span>
					</div>
					<div class="sample-card" onclick="loadSample('safe')">
						<div class="sample-icon success">
							<i class="fas fa-shield-alt"></i>
						</div>
						<h3>Secure Code</h3>
						<p>Properly sanitized input</p>
						<span class="sample-badge success">SAFE</span>
					</div>
					<div class="sample-card" onclick="loadSample('complex')">
						<div class="sample-icon info">
							<i class="fas fa-sitemap"></i>
						</div>
						<h3>Complex Flow</h3>
						<p>Multiple branches & functions</p>
						<span class="sample-badge info">MIXED</span>
					</div>
				</div>
			</div>
		</aside>

		<!-- Main Graph Area -->
		<main class="graph-area">
			<div class="graph-toolbar">
				<div class="toolbar-left">
					<h2><i class="fas fa-project-diagram"></i> Joint Program Graph</h2>
					<span class="graph-stats" id="graphStats">No graph generated</span>
				</div>
				<div class="toolbar-right">
					<div class="view-controls">
						<button class="tool-btn active" onclick="setView('force')" title="Force Layout">
							<i class="fas fa-arrows-alt"></i>
						</button>
						<button class="tool-btn" onclick="setView('tree')" title="Tree Layout">
							<i class="fas fa-sitemap"></i>
						</button>
						<button class="tool-btn" onclick="setView('radial')" title="Radial Layout">
							<i class="fas fa-circle-notch"></i>
						</button>
					</div>
					<div class="zoom-controls">
						<button class="tool-btn" onclick="zoomIn()" title="Zoom In">
							<i class="fas fa-search-plus"></i>
						</button>
						<span class="zoom-level" id="zoomLevel">100%</span>
						<button class="tool-btn" onclick="zoomOut()" title="Zoom Out">
							<i class="fas fa-search-minus"></i>
						</button>
						<button class="tool-btn" onclick="resetZoom()" title="Reset View">
							<i class="fas fa-compress-arrows-alt"></i>
						</button>
					</div>
					<button class="tool-btn" onclick="toggleFullscreen()" title="Fullscreen">
						<i class="fas fa-expand"></i>
					</button>
				</div>
			</div>
			<div id="graphContainer" class="graph-container">
				<div class="graph-placeholder" id="graphPlaceholder">
					<div class="placeholder-content">
						<i class="fas fa-project-diagram placeholder-icon"></i>
						<h3>Your Graph Will Appear Here</h3>
						<p>Enter PHP code and click "Build Joint Graph" to visualize</p>
						<div class="feature-list">
							<div class="feature-item">
								<i class="fas fa-check-circle"></i>
								<span>AST Structure</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check-circle"></i>
								<span>Control Flow</span>
							</div>
							<div class="feature-item">
								<i class="fas fa-check-circle"></i>
								<span>Data Flow & Taint</span>
							</div>
						</div>
					</div>
				</div>
				<div id="graphLegend" class="graph-legend" style="display:none;">
					<div class="legend-item">
						<span class="legend-line ast"></span>
						<span>AST Edge</span>
					</div>
					<div class="legend-item">
						<span class="legend-line cfg"></span>
						<span>Control Flow</span>
					</div>
					<div class="legend-item">
						<span class="legend-line dfg"></span>
						<span>Data Flow</span>
					</div>
					<div class="legend-item">
						<span class="legend-line taint"></span>
						<span>Taint Path</span>
					</div>
				</div>
			</div>
		</main>

		<!-- Right Sidebar - Analysis Panel -->
		<aside class="sidebar right-sidebar">
			<div class="sidebar-header">
				<h2><i class="fas fa-chart-bar"></i> Analysis</h2>
			</div>

			<!-- Graph Metrics -->
			<div class="metrics-grid">
				<div class="metric-card">
					<div class="metric-value" id="nodeCount">0</div>
					<div class="metric-label">
						<i class="fas fa-circle-nodes"></i> Nodes
					</div>
				</div>
				<div class="metric-card">
					<div class="metric-value" id="edgeCount">0</div>
					<div class="metric-label">
						<i class="fas fa-arrow-right-arrow-left"></i> Edges
					</div>
				</div>
				<div class="metric-card">
					<div class="metric-value" id="complexity">-</div>
					<div class="metric-label">
						<i class="fas fa-brain"></i> Complexity
					</div>
				</div>
			</div>

			<!-- Vulnerability Status -->
			<div class="vulnerability-section">
				<h3>Vulnerability Detection</h3>
				<div class="vuln-status" id="vulnStatusContainer">
					<div class="status-icon" id="statusIcon">
						<i class="fas fa-circle-question"></i>
					</div>
					<div class="status-text">
						<div class="status-title" id="vulnStatus">No Analysis</div>
						<div class="status-desc" id="vulnDescription">Build a graph to detect vulnerabilities</div>
					</div>
				</div>
			</div>

			<!-- Edge Type Distribution -->
			<div class="distribution-section">
				<h3>Edge Distribution</h3>
				<div class="edge-distribution" id="edgeDistribution">
					<div class="no-data">No data available</div>
				</div>
			</div>

			<!-- Node Details (on hover/click) -->
			<div class="node-details" id="nodeDetails" style="display:none;">
				<h3>Node Details</h3>
				<div class="detail-row">
					<span class="detail-label">Type:</span>
					<span class="detail-value" id="detailType">-</span>
				</div>
				<div class="detail-row">
					<span class="detail-label">Code:</span>
					<code class="detail-code" id="detailCode">-</code>
				</div>
				<div class="detail-row">
					<span class="detail-label">Line:</span>
					<span class="detail-value" id="detailLine">-</span>
				</div>
				<div class="detail-row">
					<span class="detail-label">Connections:</span>
					<span class="detail-value" id="detailConnections">-</span>
				</div>
			</div>

			<!-- Export Options -->
			<div class="export-section">
				<h3>Export Graph</h3>
				<div class="export-buttons">
					<button class="export-btn" onclick="exportAsJSON()">
						<i class="fas fa-file-code"></i> JSON
					</button>
					<button class="export-btn" onclick="exportAsPNG()">
						<i class="fas fa-file-image"></i> PNG
					</button>
					<button class="export-btn" onclick="exportAsSVG()">
						<i class="fas fa-vector-square"></i> SVG
					</button>
				</div>
			</div>
		</aside>
	</div>

	<!-- Toast Notifications -->
	<div class="toast-container" id="toastContainer"></div>

	<!-- Loading Overlay -->
	<div class="loading-overlay" id="loadingOverlay" style="display:none;">
		<div class="loader">
			<div class="loader-ring"></div>
			<div class="loader-text">Building Graph...</div>
		</div>
	</div>

	<script src="app.js"></script>
</body>
</html>