<?php
// secure/eval_calc.php - parses simple "a op b" arithmetic without eval().
$expr = trim($_GET['expr']);

if (!preg_match('/^(-?\d+(?:\.\d+)?)\s*([+\-*\/])\s*(-?\d+(?:\.\d+)?)$/', $expr, $m)) {
    die("Invalid expression");
}

[, $a, $op, $b] = $m;
$a = (float) $a;
$b = (float) $b;

switch ($op) {
    case '+': $result = $a + $b; break;
    case '-': $result = $a - $b; break;
    case '*': $result = $a * $b; break;
    case '/': $result = $b != 0 ? $a / $b : null; break;
}

echo "Result: $result";
