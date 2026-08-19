<?php
// Manual autoloader fallback
spl_autoload_register(function ($class) {
    // Project-specific namespace
    $prefix = 'PhpGraphBuilder\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Include php-parser if installed via Composer
$composerAutoload = __DIR__ . '/composer/autoload_real.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
    return ComposerAutoloaderInit::getLoader();
}

// Fallback: Try to load php-parser manually
$phpParserPath = __DIR__ . '/nikic/php-parser/lib/PhpParser/';
if (file_exists($phpParserPath)) {
    spl_autoload_register(function ($class) use ($phpParserPath) {
        if (strpos($class, 'PhpParser\\') === 0) {
            $file = $phpParserPath . str_replace('\\', '/', substr($class, 9)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
    });
} else {
    // Last resort: Use PHP Parser stub for demo purposes
    require_once __DIR__ . '/php-parser-stub.php';
}
?>