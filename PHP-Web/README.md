# PHP-Web — CodeGraph Web Application

The PHP-facing half of [CodeGraph](../README.md): a web dashboard that lets a user register/log
in, submit PHP source code (upload or paste), and see it turned into an interactive AST + Data
Flow Graph with a vulnerability score.

## What's in this folder

| Path | Purpose |
|---|---|
| [public/](public) | Front controller and pages: `index.html`/`dashboard.php` (UI), `login.php`/`register.php`/`logout.php` (auth pages), `app.js`/`style.css` (client-side graph rendering), and JSON APIs — `api.php` (analyse code), `api_analyses.php`, `api_db.php`, `api_export.php`, `api_samples.php`, `analyse.php`, `analyzer.php`. |
| [src/](src) | Core PHP library (PSR-4 autoloaded under `PhpGraphBuilder\`): `ASTParser.php` (wraps `nikic/php-parser`), `DFGBuilder.php` (taint-tracking data-flow edges from sources like `$_GET`/`$_POST` to sinks like `mysqli_query`/`exec`/`eval`), `GraphBuilder.php` (combines AST + DFG into one graph and computes vulnerability/complexity metadata), `DataPersistence.php` (saves analyses to DB or file fallback), `DatabaseOperations.php`, `Auth.php` (registration/login), `TestDataset.php`. |
| [config/](config) | `database.php` (PDO/MySQL connection singleton), `schema.sql` (table definitions), `setup_database.php` (creates `users`, `analyses`, `vulnerabilities` tables). |
| [storage/](storage) | JSON fallback storage for analysis results when no database is configured. |
| [test_samples/](test_samples) | Example PHP snippets (SQLi, XSS, command injection, etc.) for manually exercising the analyser. |
| [uploads/](uploads) | Destination for uploaded `.php`/`.txt` files. |
| [vendor/](vendor) | Composer dependencies (`nikic/php-parser`). |
| `composer.json` | Declares dependencies and the `composer start` / `composer test` / `composer check` scripts. |
| `setup.php`, `setup.bat`, `setup.sh`, `start.sh` | First-run setup and start-up helper scripts. |
| `test_runner.php` | Runs the analyser against the files in `test_samples/`. |

## Requirements

- PHP >= 7.4 with the PDO MySQL extension
- [Composer](https://getcomposer.org/)
- MySQL (optional — the app falls back to file-based storage in [storage/](storage) if the
  database is unavailable)
- Python 3 with `torch`, `torch_geometric`, and `flask` for the GNN scoring API (see
  [Model Architecture/](../Model%20Architecture))

## Setup

```bash
cd PHP-Web
composer install
```

Optional database setup (skip to run in file-storage-only "demo mode"):

```bash
# create a MySQL database named `codegraph`, matching config/database.php,
# then run:
php setup.php
```

`config/database.php` defaults to `DB_HOST=localhost`, `DB_NAME=codegraph`, `DB_USER=root`,
`DB_PASS=` — edit these constants to match your environment.

## Running the app

```bash
composer start          # equivalent to: php -S localhost:8080 -t public/
```

Open `http://localhost:8080` in a browser, register/log in, and submit PHP code from the
dashboard.

To also get a GNN-based vulnerability probability (rather than just the rule-based DFG taint
score), start the Python model API from a second terminal:

```bash
cd "../Model Architecture"
python api.py            # serves http://127.0.0.1:5000/scan
```

The PHP app's `public/api.php` builds the AST/DFG graph locally with [src/GraphBuilder.php](src/GraphBuilder.php)
and, when the Flask API is reachable, forwards the JSON graph to `http://127.0.0.1:5000/scan` to
get back `vulnerability_probability` and `is_vulnerable`.

## Tests

```bash
composer test    # PHPUnit
php test_runner.php   # exercises test_samples/ through the analyser directly
```

