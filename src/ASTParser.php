<?php
namespace PhpGraphBuilder;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class ASTParser {
    private $parser;
    private $nodes = [];
    private $edges = [];
    private $nodeCounter = 0;

    public function __construct() {
        if (class_exists('PhpParser\\ParserFactory')) {
            $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        } else {
            $this->parser = null;
        }
    }

    public function parse(string $code): array {
        $this->nodes = [];
        $this->edges = [];
        $this->nodeCounter = 0;

        if ($this->parser !== null) {
            try {
                $ast = $this->parser->parse($code);
                if (class_exists('PhpParser\\NodeTraverser')) {
                    $traverser = new NodeTraverser();
                    if (class_exists('PhpParser\\NodeVisitor\\NameResolver')) {
                        $traverser->addVisitor(new NameResolver());
                    }
                    $ast = $traverser->traverse($ast);
                }
            } catch (\Throwable $e) {
                $ast = $this->parseFallback($code);
            }
        } else {
            $ast = $this->parseFallback($code);
        }

        if ($ast) {
            $rootId = $this->addNode('AST_ROOT', 'root', null, 0);
            foreach ($ast as $stmt) {
                $childId = $this->processNode($stmt);
                if ($childId) {
                    $this->edges[] = [
                        'source' => $rootId,
                        'target' => $childId,
                        'label'  => 'AST_CHILD'
                    ];
                }
            }
        }

        return ['nodes' => $this->nodes, 'edges' => $this->edges];
    }

    private function parseFallback(string $code): array {
        $normalized = preg_replace('/<\?php\s*/i', '', $code);
        $normalized = preg_replace('/\?>\s*$/i', '', $normalized);

        $nodes = [];
        if (preg_match_all('/\$(\w+)\s*=\s*([^;]+);/', $normalized, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $varNode = new \PhpParser\Node\Assign(new \PhpParser\Node\Variable($match[1]), new \PhpParser\Node\Variable($match[1]));
                $varNode->attributes['startLine'] = 1;
                $nodes[] = $varNode;
            }
        }

        if (!$nodes) {
            $nodes[] = new \PhpParser\Node\Stmt\Expression(new \PhpParser\Node\Expr\Variable('source'));
            $nodes[0]->attributes['startLine'] = 1;
        }

        return $nodes;
    }

    private function processNode($node): string {
        if ($node === null) return null;

        $nodeType = method_exists($node, 'getType') ? $node->getType() : get_class($node);
        $code = $this->getNodeCode($node);
        $line = method_exists($node, 'getLine') ? $node->getLine() : 0;

        $id = $this->addNode($nodeType, 'ast', $code, $line);

        foreach ($this->getSubNodes($node) as $subNode) {
            if ($subNode === null) {
                continue;
            }

            if (is_array($subNode)) {
                foreach ($subNode as $item) {
                    $childId = $this->processNode($item);
                    if ($childId) {
                        $this->edges[] = [
                            'source' => $id,
                            'target' => $childId,
                            'label'  => 'AST_CHILD'
                        ];
                    }
                }
                continue;
            }

            $childId = $this->processNode($subNode);
            if ($childId) {
                $this->edges[] = [
                    'source' => $id,
                    'target' => $childId,
                    'label'  => 'AST_CHILD'
                ];
            }
        }

        return $id;
    }

    private function getSubNodes($node): array {
        if (!is_object($node) || !method_exists($node, 'getSubNodeNames')) {
            return [];
        }

        $result = [];
        foreach ($node->getSubNodeNames() as $name) {
            if (!property_exists($node, $name)) {
                continue;
            }
            $result[] = $node->$name;
        }
        return $result;
    }

    private function getNodeCode($node): string {
        if (is_object($node) && $node instanceof \PhpParser\Node\Expr\Variable) {
            return '$' . ($node->name ?? 'unknown');
        }
        if (is_object($node) && $node instanceof \PhpParser\Node\Scalar\String_) {
            return '"' . substr((string) $node->value, 0, 50) . '"';
        }
        if (is_object($node) && $node instanceof \PhpParser\Node\Expr\FuncCall &&
            $node->name instanceof \PhpParser\Node\Name) {
            return $node->name->toString() . '()';
        }
        if (is_object($node) && property_exists($node, 'name') && is_string($node->name)) {
            return $node->name;
        }
        if (is_object($node) && method_exists($node, 'getType')) {
            return $node->getType();
        }
        return is_string($node) ? $node : gettype($node);
    }

    private function addNode(string $type, string $layer, ?string $code, ?int $line): string {
        $id = 'n' . (++$this->nodeCounter);
        $this->nodes[$id] = [
            'id'    => $id,
            'type'  => $type,
            'layer' => $layer,
            'code'  => $code ?? '',
            'line'  => $line ?? 0
        ];
        return $id;
    }
}