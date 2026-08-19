<?php
// vulnerable/eval_calc.php - evaluates user input directly as PHP code.
$expr = $_GET['expr'];
$result = eval("return $expr;");
echo "Result: $result";
