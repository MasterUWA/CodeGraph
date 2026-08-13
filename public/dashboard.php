<?php
session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /php-graph-builder/public/login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get statistics
$totalAnalyses = 0;
$vulnerabilityStats = [
    'SQL Injection' => 0,
    'XSS' => 0,
    'Command Injection' => 0,
    'Other' => 0
];
$recentAnalyses = [];

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();
    
    // Get total analyses
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM analyses WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $totalAnalyses = $result['count'] ?? 0;
    
    // Get vulnerability statistics
    $stmt = $db->prepare("
        SELECT v.type, COUNT(*) as count 
        FROM vulnerabilities v
        JOIN analyses a ON v.analysis_id = a.id
        WHERE a.user_id = ?
        GROUP BY v.type
    ");
    $stmt->execute([$user_id]);
    $vulnResults = $stmt->fetchAll();
    
    foreach ($vulnResults as $row) {
        $type = $row['type'];
        if (isset($vulnerabilityStats[$type])) {
            $vulnerabilityStats[$type] = $row['count'];
        } else {
            $vulnerabilityStats['Other'] += $row['count'];
        }
    }
    
    // Get recent analyses
    $stmt = $db->prepare("
        SELECT id, created_at, node_count, edge_count, vulnerability_detected
        FROM analyses
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recentAnalyses = $stmt->fetchAll();
} catch (\Exception $e) {
    // Database not available
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CodeGraph</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --secondary: #8b5cf6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --dark-bg: #0f172a;
            --darker-bg: #0a0e27;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navigation */
        nav {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-actions {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-actions a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-actions a:hover {
            color: var(--primary);
        }

        .btn-logout {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Sections */
        .section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(59, 130, 246, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        /* Chart */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .chart-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .chart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .chart-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .chart-bar {
            flex: 1;
            height: 30px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 0.25rem;
            margin: 0 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border);
            margin-top: 3rem;
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            nav .container {
                justify-content: center;
                padding: 0 1rem;
            }

            .logo {
                width: 100%;
                justify-content: center;
                font-size: 1.2rem;
            }

            .nav-actions {
                width: 100%;
                justify-content: center;
                gap: 0.8rem;
            }

            .btn-logout {
                width: 100%;
                text-align: center;
            }

            h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 0.9rem;
            }

            th,
            td {
                padding: 0.65rem;
                white-space: nowrap;
            }

            .chart-grid {
                grid-template-columns: 1fr;
            }

            .chart-item {
                gap: 0.4rem;
            }

            .chart-bar {
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-code-branch"></i>
                <span>CodeGraph</span>
            </a>
            <div class="nav-actions">
                <a href="analyzer.php"><i class="fas fa-arrow-right"></i> Back to Analyzer</a>
                <span style="color: var(--text-muted);"><?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="btn-logout">Sign Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h1>Analysis Dashboard</h1>
        <p class="subtitle">Track your code security analyses and vulnerability findings</p>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalAnalyses; ?></div>
                <div class="stat-label">Total Analyses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo array_sum($vulnerabilityStats); ?></div>
                <div class="stat-label">Vulnerabilities Found</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $vulnerabilityStats['SQL Injection']; ?></div>
                <div class="stat-label">SQL Injections</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $vulnerabilityStats['XSS']; ?></div>
                <div class="stat-label">XSS Issues</div>
            </div>
        </div>

        <!-- Vulnerability Statistics -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-chart-pie"></i> Vulnerability Breakdown
            </h2>
            <div class="chart-grid">
                <div class="chart-card">
                    <div class="chart-title">Detected Vulnerabilities</div>
                    <div class="chart-item">
                        <span>SQL Injection</span>
                        <div class="chart-bar" style="width: <?php echo min(100, ($vulnerabilityStats['SQL Injection'] * 20)); ?>%;"></div>
                        <span><?php echo $vulnerabilityStats['SQL Injection']; ?></span>
                    </div>
                    <div class="chart-item">
                        <span>XSS</span>
                        <div class="chart-bar" style="width: <?php echo min(100, ($vulnerabilityStats['XSS'] * 20)); ?>%;"></div>
                        <span><?php echo $vulnerabilityStats['XSS']; ?></span>
                    </div>
                    <div class="chart-item">
                        <span>Command Injection</span>
                        <div class="chart-bar" style="width: <?php echo min(100, ($vulnerabilityStats['Command Injection'] * 20)); ?>%;"></div>
                        <span><?php echo $vulnerabilityStats['Command Injection']; ?></span>
                    </div>
                    <div class="chart-item">
                        <span>Other</span>
                        <div class="chart-bar" style="width: <?php echo min(100, ($vulnerabilityStats['Other'] * 20)); ?>%;"></div>
                        <span><?php echo $vulnerabilityStats['Other']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Analyses -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i> Recent Analyses
            </h2>
            
            <?php if (empty($recentAnalyses)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No analyses yet. <a href="analyzer.php" style="color: var(--primary);">Start analyzing code now</a></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Nodes</th>
                                <th>Edges</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAnalyses as $analysis): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($analysis['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($analysis['node_count'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($analysis['edge_count'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($analysis['vulnerability_detected']): ?>
                                            <span class="badge badge-danger">Vulnerable</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Safe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="#" style="color: var(--primary); text-decoration: none;">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 CodeGraph - PHP Security Analysis Platform</p>
    </footer>
</body>
</html>
