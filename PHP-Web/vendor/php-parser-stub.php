<?php
/**
 * PHP Parser Stub Implementation
 * This provides a lightweight fallback when php-parser is not installed
 */

namespace PhpParser;

// Base classes
class Node {
    public $attributes = [];
    
    public function getType() {
        $class = static::class;
        return substr(strrchr($class, '\\'), 1) ?: $class;
    }
    
    public function getLine() {
        return $this->attributes['startLine'] ?? 0;
    }
    
    public function getSubNodeNames() {
        return [];
    }
}

class Name extends Node {
    public $parts = [];
    
    public function __construct($parts = []) {
        $this->parts = $parts;
    }
    
    public function toString() {
        return implode('\\', (array)$this->parts);
    }
}

namespace PhpParser\Node;

class Expr extends \PhpParser\Node {}
class Stmt extends \PhpParser\Node {}
class Scalar extends \PhpParser\Node {}

class Variable extends Expr {
    public $name;
    
    public function __construct($name = null) {
        $this->name = $name;
    }
}

class FuncCall extends Expr {
    public $name;
    public $args = [];
    
    public function __construct($name = null, $args = []) {
        $this->name = $name;
        $this->args = $args;
    }
}

class String_ extends Scalar {
    public $value;
    
    public function __construct($value = '') {
        $this->value = $value;
    }
}

class Echo_ extends Stmt {
    public $exprs = [];
    
    public function __construct($exprs = []) {
        $this->exprs = (array)$exprs;
    }
}

class Expression extends Stmt {
    public $expr;
    
    public function __construct($expr = null) {
        $this->expr = $expr;
    }
}

class If_ extends Stmt {
    public $cond;
    public $stmts = [];
    public $elseifs = [];
    public $else = null;
    
    public function __construct($cond = null, $stmts = []) {
        $this->cond = $cond;
        $this->stmts = (array)$stmts;
    }
}

class While_ extends Stmt {
    public $cond;
    public $stmts = [];
    
    public function __construct($cond = null, $stmts = []) {
        $this->cond = $cond;
        $this->stmts = (array)$stmts;
    }
}

class For_ extends Stmt {
    public $init = [];
    public $cond = [];
    public $loop = [];
    public $stmts = [];
    
    public function __construct($init = [], $cond = [], $loop = [], $stmts = []) {
        $this->init = (array)$init;
        $this->cond = (array)$cond;
        $this->loop = (array)$loop;
        $this->stmts = (array)$stmts;
    }
}

class Assign extends Expr {
    public $var;
    public $expr;
    
    public function __construct($var = null, $expr = null) {
        $this->var = $var;
        $this->expr = $expr;
    }
}

class ArrayDimFetch extends Expr {
    public $var;
    public $dim;
    
    public function __construct($var = null, $dim = null) {
        $this->var = $var;
        $this->dim = $dim;
    }
}

namespace PhpParser\NodeVisitor;

class NameResolver {}

namespace PhpParser;

class Error extends \Exception {}

class ParserFactory {
    const PREFER_PHP7 = 1;
    
    public function create($kind = self::PREFER_PHP7) {
        return new Parser();
    }
}

class Parser {
    public function parse($code) {
        // Simple regex-based parsing for demonstration
        $nodes = [];
        
        // Remove PHP tags
        $code = preg_replace('/<\?php\s*|\s*\?>/', '', $code);
        
        // Parse variable assignments
        if (preg_match_all('/\$(\w+)\s*=\s*([^;]+);/', $code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $node = new Node\Assign();
                $node->var = new Node\Variable($match[1]);
                $nodes[] = $node;
            }
        }
        
        // Parse function calls
        if (preg_match_all('/(\w+)\s*\(([^)]*)\)/', $code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $node = new Node\FuncCall();
                $node->name = new Name([$match[1]]);
                $nodes[] = $node;
            }
        }
        
        // Parse echo statements
        if (preg_match_all('/echo\s+([^;]+);/', $code, $matches)) {
            foreach ($matches[1] as $expr) {
                $node = new Node\Echo_();
                $nodes[] = $node;
            }
        }
        
        return $nodes ?: [new Node()];
    }
}

namespace PhpParser;

class NodeTraverser {
    private $visitors = [];
    
    public function addVisitor($visitor) {
        $this->visitors[] = $visitor;
    }
    
    public function traverse($nodes) {
        return $nodes;
    }
}
?>
