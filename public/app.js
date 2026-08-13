// State management
let currentGraph = null;
let simulation = null;
let svg = null;
let zoom = null;
let currentView = 'force';
let isDarkTheme = true;

// Sample code templates
const samples = {
	sqli: `<?php
$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id=" . $id;
$result = mysqli_query($conn, $sql);
?>`,
	
	xss: `<?php
$name = $_POST['name'];
echo "Hello, " . $name;
?>`,
	
	safe: `<?php
$id = $_GET['id'];
$safe_id = mysqli_real_escape_string($conn, $id);
$sql = "SELECT * FROM users WHERE id=" . $safe_id;
$result = mysqli_query($conn, $sql);
?>`,
	
	complex: `<?php
$user = $_POST['username'];
$pass = $_POST['password'];
$remember = isset($_POST['remember']);

if ($remember) {
	$token = md5($user . time());
	setcookie('remember', $token);
}

if ($user && $pass) {
	$safe_user = mysqli_real_escape_string($conn, $user);
	$query = "SELECT * FROM users WHERE username='$safe_user'";
	$result = mysqli_query($conn, $query);
	
	if ($row = mysqli_fetch_assoc($result)) {
		if (password_verify($pass, $row['password'])) {
			$_SESSION['user'] = $user;
			header('Location: dashboard.php');
		} else {
			$error = "Invalid password";
		}
	}
}

if (isset($error)) {
	echo "<div class='error'>$error</div>";
}
?>`
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
	initializeTabs();
	initializeUpload();
	initializeEditor();
	loadSample('sqli');
	updateCharCount();
});

// Tab Management
function initializeTabs() {
	document.querySelectorAll('.tab-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const tabName = this.dataset.tab;
			showTab(tabName);
		});
	});
}

function showTab(tabName) {
	document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
	document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
	
	const btn = document.querySelector(`[data-tab="${tabName}"]`);
	const panel = document.getElementById(`${tabName}-panel`);
	
	if (btn) btn.classList.add('active');
	if (panel) panel.classList.add('active');
}

// Editor Management
function initializeEditor() {
	const editor = document.getElementById('codeInput');
	editor.addEventListener('input', updateCharCount);
	editor.addEventListener('keydown', handleEditorKeydown);
}

function updateCharCount() {
	const code = document.getElementById('codeInput').value;
	const charCount = code.length;
	const lineCount = code.split('\n').length;
	
	document.getElementById('charCount').textContent = `${charCount} characters`;
	document.getElementById('lineCount').textContent = `${lineCount} lines`;
}

function handleEditorKeydown(e) {
	if (e.key === 'Tab') {
		e.preventDefault();
		const start = this.selectionStart;
		const end = this.selectionEnd;
		this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
		this.selectionStart = this.selectionEnd = start + 4;
	}
}

// Upload Management
function initializeUpload() {
	const uploadArea = document.getElementById('uploadArea');
	const fileInput = document.getElementById('fileInput');
	
	uploadArea.addEventListener('click', () => fileInput.click());
	fileInput.addEventListener('change', handleFileSelect);
	
	uploadArea.addEventListener('dragover', (e) => {
		e.preventDefault();
		uploadArea.classList.add('dragover');
	});
	
	uploadArea.addEventListener('dragleave', () => {
		uploadArea.classList.remove('dragover');
	});
	
	uploadArea.addEventListener('drop', (e) => {
		e.preventDefault();
		uploadArea.classList.remove('dragover');
		const file = e.dataTransfer.files[0];
		if (file) handleFile(file);
	});
}

function handleFileSelect(e) {
	const file = e.target.files[0];
	if (file) handleFile(file);
}

function handleFile(file) {
	const reader = new FileReader();
	reader.onload = function(e) {
		document.getElementById('codeInput').value = e.target.result;
		updateCharCount();
	};
	reader.readAsText(file);
	
	document.getElementById('uploadedFileName').textContent = file.name;
	document.getElementById('uploadedFileInfo').style.display = 'flex';
	document.getElementById('uploadArea').style.display = 'none';
	document.getElementById('uploadBtn').disabled = false;
}

function removeUploadedFile() {
	document.getElementById('fileInput').value = '';
	document.getElementById('uploadedFileInfo').style.display = 'none';
	document.getElementById('uploadArea').style.display = 'block';
	document.getElementById('uploadBtn').disabled = true;
}

// Load Sample
async function loadSample(name) {
	try {
		const response = await axios.get('api_samples.php?action=get&name=' + encodeURIComponent(name));
		if (response.data.success && response.data.data) {
			document.getElementById('codeInput').value = response.data.data.code;
			updateCharCount();
			showToast('Sample loaded: ' + response.data.data.name, 'success');
		} else {
			// Fallback to inline samples if API fails
			if (samples[name]) {
				document.getElementById('codeInput').value = samples[name];
				updateCharCount();
				showToast('Sample loaded successfully', 'success');
			}
		}
	} catch (error) {
		// Fallback to inline samples
		if (samples[name]) {
			document.getElementById('codeInput').value = samples[name];
			updateCharCount();
			showToast('Sample loaded successfully', 'success');
		} else {
			showToast('Error loading sample', 'error');
		}
	}
}

// API Calls
async function analyzeCode() {
	const code = document.getElementById('codeInput').value.trim();
	
	if (!code) {
		showToast('Please enter some PHP code', 'error');
		return;
	}
	
	showLoading(true);
	
	try {
		const response = await axios.post('api.php', { code }, {
			headers: { 'Content-Type': 'application/json' }
		});
		
		if (response.data.success) {
			currentGraph = response.data.data;
			visualizeGraph(currentGraph);
			updateStats(currentGraph);
			updateVulnerabilityStatus(currentGraph);
			updateEdgeDistribution(currentGraph.metadata.edge_types || {});
			showToast('Graph built successfully!', 'success');
		} else {
			showToast('Error: ' + response.data.error, 'error');
		}
	} catch (error) {
		const msg = error.response?.data?.error || error.message;
		showToast('Failed to build graph: ' + msg, 'error');
	} finally {
		showLoading(false);
	}
}

async function uploadFile() {
	const fileInput = document.getElementById('fileInput');
	const file = fileInput.files[0];
	
	if (!file) {
		showToast('Please select a file first', 'error');
		return;
	}
	
	showLoading(true);
	
	const formData = new FormData();
	formData.append('phpfile', file);
	
	try {
		const response = await axios.post('api.php', formData);
		
		if (response.data.success) {
			currentGraph = response.data.data;
			visualizeGraph(currentGraph);
			updateStats(currentGraph);
			updateVulnerabilityStatus(currentGraph);
			updateEdgeDistribution(currentGraph.metadata.edge_types || {});
			showToast('File analyzed successfully!', 'success');
		} else {
			showToast('Error: ' + response.data.error, 'error');
		}
	} catch (error) {
		const msg = error.response?.data?.error || error.message;
		showToast('Upload failed: ' + msg, 'error');
	} finally {
		showLoading(false);
	}
}

// Graph Visualization
function visualizeGraph(data) {
	const container = document.getElementById('graphContainer');
	const placeholder = document.getElementById('graphPlaceholder');
	const legend = document.getElementById('graphLegend');
	
	// Hide placeholder, show legend
	if (placeholder) placeholder.style.display = 'none';
	if (legend) legend.style.display = 'flex';
	
	container.innerHTML = '';
	
	const width = container.clientWidth;
	const height = container.clientHeight;
	
	svg = d3.select('#graphContainer')
		.append('svg')
		.attr('width', width)
		.attr('height', height)
		.style('cursor', 'grab');
	
	// Define arrow markers
	const defs = svg.append('defs');
	
	const edgeTypes = {
		'AST_CHILD': '#94a3b8',
		'CONTROL_FLOW': '#ef4444',
		'CONTROL_FLOW_CONDITION': '#f59e0b',
		'CONTROL_FLOW_LOOP': '#f59e0b',
		'DATA_FLOW': '#3b82f6',
		'TAINT_SOURCE': '#8b5cf6',
		'TAINT_FLOW': '#ef4444'
	};
	
	Object.entries(edgeTypes).forEach(([type, color]) => {
		defs.append('marker')
			.attr('id', `arrow-${type}`)
			.attr('viewBox', '0 -5 10 10')
			.attr('refX', 20)
			.attr('refY', 0)
			.attr('markerWidth', 6)
			.attr('markerHeight', 6)
			.attr('orient', 'auto')
			.append('path')
			.attr('d', 'M0,-5L10,0L0,5')
			.attr('fill', color);
	});
	
	// Create zoom behavior
	zoom = d3.zoom()
		.scaleExtent([0.1, 4])
		.on('zoom', (event) => {
			g.attr('transform', event.transform);
			document.getElementById('zoomLevel').textContent = 
				Math.round(event.transform.k * 100) + '%';
		});
	
	svg.call(zoom);
	
	const g = svg.append('g');
	
	// Prepare data
	const nodes = data.nodes.map(n => ({...n}));
	const links = data.edges.map(e => ({
		...e,
		source: e.source,
		target: e.target
	}));
	
	// Create simulation
	simulation = d3.forceSimulation(nodes)
		.force('link', d3.forceLink(links).id(d => d.id).distance(80))
		.force('charge', d3.forceManyBody().strength(-200))
		.force('center', d3.forceCenter(width / 2, height / 2))
		.force('collision', d3.forceCollide().radius(25));
	
	// Draw edges
	const link = g.append('g')
		.selectAll('line')
		.data(links)
		.enter()
		.append('line')
		.attr('class', 'link')
		.attr('stroke', d => edgeTypes[d.label] || '#666')
		.attr('stroke-width', d => {
			if (d.label === 'TAINT_FLOW') return 3;
			if (d.label === 'TAINT_SOURCE') return 2.5;
			return 1.5;
		})
		.attr('stroke-dasharray', d => {
			if (d.label === 'DATA_FLOW') return '5,5';
			if (d.label === 'TAINT_FLOW') return '8,4';
			return 'none';
		})
		.attr('marker-end', d => `url(#arrow-${d.label})`);
	
	// Draw nodes
	const node = g.append('g')
		.selectAll('g')
		.data(nodes)
		.enter()
		.append('g')
		.attr('class', 'node')
		.call(d3.drag()
			.on('start', dragStarted)
			.on('drag', dragged)
			.on('end', dragEnded)
		);
	
	// Node circles
	node.append('circle')
		.attr('r', d => d.size || 6)
		.attr('fill', d => d.color || '#94a3b8')
		.attr('stroke', d => d.tainted ? '#f59e0b' : '#fff')
		.attr('stroke-width', d => d.tainted ? 2 : 1.5);
	
	// Node labels
	node.append('text')
		.attr('dy', -12)
		.attr('text-anchor', 'middle')
		.attr('font-size', '9px')
		.attr('fill', '#e4e4eb')
		.text(d => d.code?.substring(0, 20) || d.type);
	
	// Tooltips
	node.append('title')
		.text(d => `${d.type}\n${d.code || ''}\nLine: ${d.line || 'N/A'}`);
	
	// Click handler for node details
	node.on('click', (event, d) => {
		event.stopPropagation();
		showNodeDetails(d);
	});
	
	// Update positions
	simulation.on('tick', () => {
		link
			.attr('x1', d => d.source.x)
			.attr('y1', d => d.source.y)
			.attr('x2', d => d.target.x)
			.attr('y2', d => d.target.y);
		
		node.attr('transform', d => `translate(${d.x},${d.y})`);
	});
	
	// Drag functions
	function dragStarted(event, d) {
		if (!event.active) simulation.alphaTarget(0.3).restart();
		d.fx = d.x;
		d.fy = d.y;
		svg.style('cursor', 'grabbing');
	}
	
	function dragged(event, d) {
		d.fx = event.x;
		d.fy = event.y;
	}
	
	function dragEnded(event, d) {
		if (!event.active) simulation.alphaTarget(0);
		d.fx = null;
		d.fy = null;
		svg.style('cursor', 'grab');
	}
}

// Node details panel
function showNodeDetails(node) {
	const detailsDiv = document.getElementById('nodeDetails');
	detailsDiv.style.display = 'block';
	
	document.getElementById('detailType').textContent = node.type;
	document.getElementById('detailCode').textContent = node.code || 'N/A';
	document.getElementById('detailLine').textContent = node.line || 'N/A';
	
	// Count connections
	if (currentGraph) {
		const connections = currentGraph.edges.filter(
			e => e.source === node.id || e.target === node.id
		).length;
		document.getElementById('detailConnections').textContent = connections;
	}
	
	// Highlight
	document.getElementById('detailType').style.color = node.color || '#e4e4eb';
}

// Update statistics
function updateStats(data) {
	document.getElementById('nodeCount').textContent = data.metadata.node_count;
	document.getElementById('edgeCount').textContent = data.metadata.edge_count;
	document.getElementById('complexity').textContent = data.metadata.complexity;
	
	const stats = `${data.metadata.node_count} nodes, ${data.metadata.edge_count} edges`;
	document.getElementById('graphStats').textContent = stats;
}

function updateVulnerabilityStatus(data) {
	const container = document.getElementById('vulnStatusContainer');
	const icon = document.getElementById('statusIcon');
	const title = document.getElementById('vulnStatus');
	const desc = document.getElementById('vulnDescription');
	
	if (data.metadata.vulnerability_detected) {
		container.style.borderColor = '#ef4444';
		container.style.background = 'rgba(239, 68, 68, 0.05)';
		icon.innerHTML = '<i class="fas fa-skull-crossbones"></i>';
		icon.style.color = '#ef4444';
		title.textContent = '⚠️ Vulnerability Detected';
		title.style.color = '#ef4444';
		
		const tainted = (data.metadata.tainted_variables || []).join(', ') || 'none';
		desc.textContent = `Tainted variables: ${tainted}`;
	} else {
		container.style.borderColor = '#10b981';
		container.style.background = 'rgba(16, 185, 129, 0.05)';
		icon.innerHTML = '<i class="fas fa-shield-alt"></i>';
		icon.style.color = '#10b981';
		title.textContent = '✅ No Vulnerabilities';
		title.style.color = '#10b981';
		desc.textContent = 'No taint flow detected to sinks';
	}
}

function updateEdgeDistribution(edgeTypes) {
	const container = document.getElementById('edgeDistribution');
	
	if (!edgeTypes || Object.keys(edgeTypes).length === 0) {
		container.innerHTML = '<div class="no-data">No edges generated</div>';
		return;
	}
	
	const colors = {
		'AST_CHILD': '#94a3b8',
		'CONTROL_FLOW': '#ef4444',
		'DATA_FLOW': '#3b82f6',
		'TAINT_SOURCE': '#8b5cf6',
		'TAINT_FLOW': '#ef4444'
	};
	
	let html = '';
	const total = Object.values(edgeTypes).reduce((a, b) => a + b, 0);
	
	for (const [type, count] of Object.entries(edgeTypes)) {
		const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
		const color = colors[type] || '#666';
		
		html += `
			<div class="dist-item">
				<div class="dist-header">
					<span class="dist-dot" style="background: ${color}"></span>
					<span class="dist-label">${type.replace(/_/g, ' ')}</span>
					<span class="dist-count">${count}</span>
				</div>
				<div class="dist-bar">
					<div class="dist-fill" style="width: ${percentage}%; background: ${color}"></div>
				</div>
			</div>
		`;
	}
	
	container.innerHTML = html;
}

// Graph controls
function setView(view) {
	currentView = view;
	document.querySelectorAll('.view-controls .tool-btn').forEach(b => b.classList.remove('active'));
	event.target.closest('.tool-btn').classList.add('active');
	
	if (currentGraph) {
		visualizeGraph(currentGraph);
	}
	showToast(`Switched to ${view} layout`, 'info');
}

function zoomIn() {
	if (svg && zoom) {
		svg.transition().duration(300).call(zoom.scaleBy, 1.3);
	}
}

function zoomOut() {
	if (svg && zoom) {
		svg.transition().duration(300).call(zoom.scaleBy, 0.7);
	}
}

function resetZoom() {
	if (svg && zoom) {
		svg.transition().duration(500).call(zoom.transform, d3.zoomIdentity);
		document.getElementById('zoomLevel').textContent = '100%';
	}
}

function toggleFullscreen() {
	const container = document.getElementById('graphContainer');
	if (!document.fullscreenElement) {
		container.requestFullscreen();
	} else {
		document.exitFullscreen();
	}
}

// Export functions
function exportGraph() {
	if (!currentGraph) {
		showToast('No graph to export', 'error');
		return;
	}
	
	const blob = new Blob([JSON.stringify(currentGraph, null, 2)], { type: 'application/json' });
	const url = URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = 'joint-program-graph.json';
	a.click();
	URL.revokeObjectURL(url);
	showToast('Graph exported as JSON', 'success');
}

function exportAsJSON() {
	exportGraph();
}

function exportAsPNG() {
	showToast('PNG export requires additional library', 'info');
}

function exportAsSVG() {
	if (!svg) {
		showToast('No graph to export', 'error');
		return;
	}
	
	const serializer = new XMLSerializer();
	const source = serializer.serializeToString(svg.node());
	const blob = new Blob([source], { type: 'image/svg+xml' });
	const url = URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = 'joint-program-graph.svg';
	a.click();
	URL.revokeObjectURL(url);
	showToast('Graph exported as SVG', 'success');
}

// Theme toggle
function toggleTheme() {
	isDarkTheme = !isDarkTheme;
	document.documentElement.setAttribute('data-theme', isDarkTheme ? 'dark' : 'light');
	const icon = document.querySelector('.nav-btn .fa-moon, .nav-btn .fa-sun');
	if (icon) {
		icon.className = isDarkTheme ? 'fas fa-moon' : 'fas fa-sun';
	}
}

// Clear all
function clearAll() {
	currentGraph = null;
	document.getElementById('graphContainer').innerHTML = '';
	document.getElementById('graphPlaceholder').style.display = 'flex';
	document.getElementById('graphLegend').style.display = 'none';
	document.getElementById('nodeDetails').style.display = 'none';
	document.getElementById('nodeCount').textContent = '0';
	document.getElementById('edgeCount').textContent = '0';
	document.getElementById('complexity').textContent = '-';
	document.getElementById('graphStats').textContent = 'No graph generated';
	document.getElementById('edgeDistribution').innerHTML = '<div class="no-data">No data available</div>';
	
	const container = document.getElementById('vulnStatusContainer');
	container.style.borderColor = 'var(--border-color)';
	container.style.background = 'var(--bg-tertiary)';
	document.getElementById('statusIcon').innerHTML = '<i class="fas fa-circle-question"></i>';
	document.getElementById('vulnStatus').textContent = 'No Analysis';
	document.getElementById('vulnDescription').textContent = 'Build a graph to detect vulnerabilities';
	
	showToast('Cleared all data', 'info');
}

// Loading overlay
function showLoading(show) {
	const overlay = document.getElementById('loadingOverlay');
	overlay.style.display = show ? 'flex' : 'none';
}

// Toast notifications
function showToast(message, type = 'info') {
	const container = document.getElementById('toastContainer');
	const toast = document.createElement('div');
	toast.className = `toast toast-${type}`;
	
	const icons = {
		success: 'fa-check-circle',
		error: 'fa-exclamation-circle',
		info: 'fa-info-circle',
		warning: 'fa-exclamation-triangle'
	};
	
	toast.innerHTML = `
		<i class="fas ${icons[type] || icons.info}"></i>
		<span>${message}</span>
		<button class="toast-close" onclick="this.parentElement.remove()">
			<i class="fas fa-times"></i>
		</button>
	`;
	
	container.appendChild(toast);
	
	setTimeout(() => {
		toast.style.animation = 'slideOut 0.3s ease forwards';
		setTimeout(() => toast.remove(), 300);
	}, 4000);
}