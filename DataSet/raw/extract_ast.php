<?php
// extract_ast.php - Tokenizes a PHP source file into a simplified structural
// token-graph (nodes + edges) and prints it as JSON on stdout.
// Nodes = PHP tokens (whitespace/comments stripped).
// Edges  = sequential token order + bracket/paren/brace nesting pairs.

if ($argc < 2) {
    echo json_encode(["error" => "missing file argument"]);
    exit(1);
}

$filePath = $argv[1];
$code = @file_get_contents($filePath);
if ($code === false) {
    echo json_encode(["error" => "unable to read file: $filePath"]);
    exit(1);
}

$rawTokens = @token_get_all($code);
if ($rawTokens === false) {
    echo json_encode(["error" => "tokenizer failed"]);
    exit(1);
}

$skip = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

$nodes = [];
$edges = [];
$stack = []; // open-bracket node ids, used to link matching close brackets

foreach ($rawTokens as $token) {
    if (is_array($token)) {
        if (in_array($token[0], $skip, true)) {
            continue;
        }
        $type = token_name($token[0]);
        $text = $token[1];
    } else {
        $type = 'CHAR_' . $token;
        $text = $token;
    }

    $nodeId = count($nodes);
    $nodes[] = ["id" => $nodeId, "type" => $type, "text" => trim((string) $text)];

    if ($nodeId > 0) {
        $edges[] = [$nodeId - 1, $nodeId];
    }

    if (in_array($text, ['{', '(', '['], true)) {
        $stack[] = $nodeId;
    } elseif (in_array($text, ['}', ')', ']'], true) && !empty($stack)) {
        $openId = array_pop($stack);
        $edges[] = [$openId, $nodeId];
    }
}

echo json_encode(["nodes" => $nodes, "edges" => $edges]);
