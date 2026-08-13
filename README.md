# CodeGraph - PHP Security Analysis Platform

## Overview

CodeGraph is a comprehensive PHP security analysis platform built for academic research and professional code auditing. It uses advanced program graph analysis (AST, CFG, DFG) to detect security vulnerabilities including SQL Injection, XSS, Command Injection, and more.

## Features

### 🔐 Security Features
- **User Authentication**: Secure login/registration system with password hashing
- **Analysis History**: Track and review all previous analyses
- **Role-Based Access**: Admin and analyst roles for different permission levels
- **Dashboard**: Comprehensive analytics and vulnerability statistics

### 📊 Analysis Capabilities
- **Program Graph Visualization**: D3.js interactive graph rendering
- **AST Analysis**: Abstract Syntax Tree parsing with fallback regex implementation
- **Taint Tracking**: Automatic detection of tainted data flows
- **Vulnerability Detection**: 
  - SQL Injection (including blind/second-order)
  - Cross-Site Scripting (XSS - reflected, stored, DOM-based)
  - Command Injection
  - Path Traversal
  - Insecure Deserialization
  - Information Disclosure

### 🎯 Test Datasets
Includes 50+ pre-built vulnerable and safe code samples for comprehensive testing:
- SQL Injection variants (10+)
- XSS patterns (10+)
- Command Injection examples (5+)
- Path Traversal cases (5+)
- Insecure Deserialization (5+)
- Safe coding examples (10+)

### 📈 Analytics
- Vulnerability distribution charts
- Analysis history with filtering
- Edge type distribution
- Tainted variable tracking
- Complexity metrics

## Installation

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB
- XAMPP or similar PHP development environment
- Composer (optional, for package management)

### Setup Steps

1. **Clone or Extract Project**
   ```bash
   cd /path/to/php-graph-builder
   ```

2. **Configure Database** (if using MySQL)
   - Edit `config/database.php` with your credentials
   - Default: localhost, root user, password empty

3. **Create Database**
   ```bash
   php setup.php
   ```
   Or manually import `config/schema.sql`

4. **Start Apache**
   ```bash
   # In XAMPP Control Panel or via command line
   xampp_control.exe start
   ```

5. **Access the Application**
   - Homepage: `http://localhost/php-graph-builder/`
   - Home: `http://localhost/php-graph-builder/public/home.html`
   - Register: `http://localhost/php-graph-builder/public/register.php`
   - Login: `http://localhost/php-graph-builder/public/login.php`

## File Structure

```
php-graph-builder/
├── public/
│   ├── home.html              # Landing page
│   ├── register.php           # User registration
│   ├── login.php              # User login
│   ├── analyzer.php           # Main analysis interface (requires login)
│   ├── dashboard.php          # User dashboard (requires login)
│   ├── logout.php             # Logout handler
│   ├── api.php                # Graph analysis API
│   ├── api_samples.php        # Test dataset API
│   ├── api_analyses.php       # Analysis history API
│   ├── app.js                 # Frontend JavaScript logic
│   ├── index.html             # Original analyzer interface
│   ├── style.css              # Styling
│   └── uploads/               # File upload directory
├── src/
│   ├── Auth.php               # Authentication class
│   ├── GraphBuilder.php       # Core graph building logic
│   ├── ASTParser.php          # PHP AST parsing
│   ├── DFGBuilder.php         # Data Flow Graph analysis
│   ├── TestDataset.php        # 50+ test samples
│   ├── DatabaseOperations.php # Database helper methods
│   └── DataPersistence.php    # Fallback storage layer
├── config/
│   ├── database.php           # Database configuration
│   └── schema.sql             # Database schema
├── vendor/
│   ├── autoload.php           # PSR-4 autoloader
│   └── php-parser-stub.php    # Lightweight PHP parser fallback
├── setup.php                  # Database setup script
└── README.md                  # This file
```

## Usage

### For Anonymous Users
1. Visit landing page at `http://localhost/php-graph-builder/public/home.html`
2. Click "Get Started" or "Launch Analyzer"
3. Must create account or login to use tools

### For Registered Users

#### Code Analysis
1. **Navigate to Analyzer**: `http://localhost/php-graph-builder/public/analyzer.php`
2. **Input Methods**:
   - Paste PHP code in text editor
   - Upload PHP file
   - Load pre-built samples
3. **Analyze**: Click "Analyze Code" button
4. **View Results**:
   - Interactive D3.js graph visualization
   - Node/edge statistics
   - Vulnerability status
   - Tainted variable tracking
   - Edge type distribution

#### Dashboard
1. **View Analytics**: `http://localhost/php-graph-builder/public/dashboard.php`
2. **Features**:
   - Total analyses count
   - Vulnerability statistics
   - Recent analyses list
   - Export functionality (in progress)

## API Endpoints

### Authentication
- `POST /public/login.php` - User login (session-based)
- `POST /public/register.php` - User registration
- `GET /public/logout.php` - User logout

### Analysis APIs
- `POST /public/api.php` - Analyze PHP code
  ```
  {
    "code": "<?php ... ?>",
    "file": (optional file upload)
  }
  ```

- `GET /public/api_samples.php?action=all` - Get all test samples
- `GET /public/api_samples.php?action=get&name=sample_name` - Get specific sample
- `GET /public/api_analyses.php?action=list` - List user analyses
- `GET /public/api_analyses.php?action=stats` - Get statistics
- `POST /public/api_analyses.php?action=save` - Save analysis
- `GET /public/api_analyses.php?action=get&id=analysis_id` - Get analysis details

## Test Datasets

### SQL Injection Samples
- String concatenation vulnerabilities
- Simple numeric injection
- With escape attempts
- Second-order injection
- Prepared statements (safe)
- Multiple parameter injection
- Cookie-based injection
- Search query injection
- ORDER BY injection
- UNION-based injection

### XSS Samples
- Reflected XSS in HTML
- Stored XSS in database
- DOM-based XSS
- Safe with htmlspecialchars
- Multiple input points
- Attribute-based XSS
- JSON context XSS
- Cookie-based XSS
- User function XSS
- File upload name XSS

### Command Injection
- exec() injection
- system() injection
- passthru() injection
- Backtick injection
- Safe with escapeshellarg()

### Path Traversal
- File read vulnerabilities
- File inclusion
- Directory traversal
- Safe with basename()
- Symlink attacks

### Insecure Deserialization
- Direct unserialize()
- Cookie deserialization
- Session deserialization
- Safe with json_decode()
- File-based deserialization

### Safe Code Samples
- htmlspecialchars() usage
- Prepared statements
- Input validation
- escapeshellarg() usage
- Whitelist validation
- json_decode() with error handling
- Type casting
- Constant file lists
- filter_var() validation
- filter_input() validation

## Vulnerability Detection Logic

### Taint Sources
- `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_FILES`

### Taint Sinks (Vulnerable Functions)
- Database: `mysqli_query()`, `mysql_query()`, `->query()`
- System: `exec()`, `system()`, `eval()`, `passthru()`

### Sanitizers (Protected)
- `mysqli_real_escape_string()`
- `htmlspecialchars()`
- `escapeshellarg()`
- `filter_var()`, `filter_input()`
- `json_decode()`

## Database Schema

### users
```sql
id, username, email, password_hash, role, is_active, created_at, updated_at
```

### analyses
```sql
id, user_id, code, graph_json, node_count, edge_count, vulnerability_detected, created_at
```

### vulnerabilities
```sql
id, analysis_id, type, severity, description, created_at
```

## Authentication

- Uses PHP sessions for authentication
- Passwords hashed with bcrypt (password_hash/password_verify)
- Session-based login system
- Auto-redirect to login for protected pages

## Performance Considerations

- Regex-based parser for lightweight operation
- PHP Parser stub fallback (no external dependencies required)
- D3.js force-directed graph optimization
- Database query caching via session

## Future Enhancements

1. **PDF Export**: Generate comprehensive security reports
2. **CSV/JSON Export**: Export findings in multiple formats
3. **API Keys**: Generate API keys for programmatic access
4. **Advanced Filtering**: Filter analyses by type, date, severity
5. **Collaborative Analysis**: Share analyses with team members
6. **GitHub Integration**: Analyze repositories directly
7. **CI/CD Integration**: Integrate with Jenkins/GitHub Actions
8. **Machine Learning**: Predictive vulnerability detection
9. **Custom Rules**: Create custom vulnerability detection rules
10. **Performance Profiling**: Detailed complexity analysis

## Security Considerations

- User passwords are bcrypt hashed
- Session-based authentication
- SQL injections prevented with prepared statements
- File uploads validated
- XSS protection via output escaping
- CSRF tokens can be added for form submissions

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check credentials in `config/database.php`
- Ensure database is created: `php setup.php`

### Parser Error
- Fallback regex parser is used automatically
- Ensure PHP 8.0+

### Graph Not Rendering
- Check browser console for errors
- Verify D3.js is loaded
- Clear browser cache

## System Requirements for Academic Publication

This system is designed for:
- Masters/PhD research projects
- Security analysis coursework
- Vulnerability research
- Code audit documentation
- Comparative analysis studies

Includes dataset for reproducible research and comprehensive benchmarking.

## Contact & Support

For issues or contributions, please refer to your institution's software development guidelines.

---

**CodeGraph** - PHP Security Analysis Platform for Academic Research
Version 1.0.0 | 2026
