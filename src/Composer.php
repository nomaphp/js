<?php

namespace Noma\Js;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\InterpolatedStringPart;

class Composer
{
    public static function print(array $vars): string
    {
        $varsStr = implode(' + ', $vars);

        return "console.log($varsStr)";
    }

    public static function assign(string $name, mixed $value): string
    {
        return "{$name} = {$value}";
    }

    public static function var(string $name, mixed $value): string
    {
        return "let {$name} = {$value}";
    }

    public static function propertyVar(string $name, mixed $value): string
    {
        return "{$name} = {$value}";
    }

    public static function const(string $name, mixed $value): string
    {
        return "const {$name} = {$value}";
    }

    public static function interpolateString(array $parts): string
    {
        $parsedParts = array_map(function ($part) {
            if ($part instanceof InterpolatedStringPart) {
                return $part->value;
            }

            if ($part instanceof Variable) {
                return '${' . $part->name . '}';
            }

            return "";
        }, $parts);

        $_js = implode(' ', $parsedParts);

        return "`$_js`";
    }

    public static function list(array $vals): string
    {
        return "[" . implode(', ', $vals) . "]";
    }

    public static function function(string $name, array $params, array $stmts): string
    {
        $_js = "function {$name}(" . implode(', ', $params) . ") {\n";

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function return(mixed $var): string
    {
        if (empty($var)) {
            return "return";
        }

        return "return {$var}";
    }

    public static function functionCall(string $name, array $args): string
    {
        return match($name) {
            'explode' => "{$args[1]}.split({$args[0]})",
            'implode' => "{$args[1]}.join({$args[0]})",
            'strtoupper' => "{$args[0]}.toUpperCase()",
            'strtolower' => "{$args[0]}.toLowerCase()",
            default => "{$name}(" . implode(', ', $args) . ")"
        };
    }

    public static function binaryOp(mixed $left, mixed $right, string $op): string
    {
        if ($op === "<=>") {
            return "Math.sign({$left} - {$right})";
        }

        return "{$left} {$op} {$right}";
    }

    public static function assignOp(string $var, string $op, mixed $value): string
    {
        return "{$var} {$op} {$value}";
    }

    public static function bitwiseNot(string $value): string
    {
        return "~{$value}";
    }

    public static function booleanNot(string $value): string
    {
        return "!{$value}";
    }

    public static function if(string $cond, array $stmts): string
    {
        $_js = "if ({$cond}) {\n";

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function ternary(string $cond, string | null $if, string $else): string
    {
        if (is_null($if)) {
            return "{$cond} || {$else}";
        }

        return "{$cond} ? {$if} : {$else}";
    }

    public static function postInc(string $var): string
    {
        return "{$var}++";
    }

    public static function postDec(string $var): string
    {
        return "{$var}--";
    }

    public static function array(array $items): string
    {
        return "[" . implode(', ', $items) . "]";
    }

    public static function object(array $items): string
    {
        return "{" . implode(', ', $items) . "}";
    }

    public static function class(string $name, array $stmts, ?string $extends = null): string
    {
        if ($extends) {
            $_js = "class {$name} extends {$extends} {\n";
        } else {
            $_js = "class {$name} {\n";
        }

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function classMethod(string $name, array $params, array $stmts, bool $static = false): string
    {
        $_js = "";

        if ($static) {
            $_js .= "static ";
        }

        $_js .= "{$name}(" . implode(', ', $params) . ") {\n";

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function property(string $name, mixed $default): string
    {
        if (!$default) {
            return "{$name}";
        }

        return "{$name} = {$default}";
    }

    public static function propertyStmt(array $properties, bool $static = false, bool $private = false): string
    {
        $_js = "";

        if ($static) {
            $_js .= "static ";
        }

        $_js .= implode("\n", $properties) . ";";

        return $_js;
    }

    public static function methodCall(string $var, string $name, array $args): string
    {
        return "{$var}.{$name}(" . implode(', ', $args) . ")";
    }

    public static function staticCall(string $class, string $name, array $args, array $opts = []): string
    {
        $out = "";

        if ($opts['isAwait']) {
            $out .= "await ";
        }

        if ($opts['isAsync']) {
            $out .= "(async ";
        }

        if (!$opts['isRoot']) {
            $out .= "{$class}.";
        }

        if ($opts['isProperty']) {
            $out .= "{$name}";
            return $out;
        }

        if (!$opts['isAsync']) {
            $out .= "{$name}(";
        }

        if ($opts['paramsToPass']) {
            $out .= implode(', ', array_slice($args, 0, $opts['paramsToPass']));
        } else {
            $out .= implode(', ', $args);
        }

        if (!$opts['isAsync']) {
            $out .= ")";
        } else {
            $out .= ")()";
        }

        return $out;
    }

    public static function new(string $class, array $args): string
    {
        return "new {$class}(" . implode(', ', $args) . ")";
    }

    public static function closure(bool $static, array $params, array $stmts): string
    {
        $_js = "(" . implode(', ', $params) . ") => {\n";

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function foreach($expr, $keyVar, $valueVar, array $stmts): string
    {
        $_js = "";

        if ($keyVar) {
            $_js .= "for (const [{$keyVar}, {$valueVar}] of {$expr}) {\n";
        } else {
            $_js .= "for (const {$valueVar} of {$expr}) {\n";
        }

        foreach ($stmts as $stmt) {
            $_js .= $stmt . "\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function match($cond, array $arms): string
    {
        $_js = "() => {\n";
        $_js .= "\tconst jsMatchVar = {$cond};\n";

        foreach($arms as $arm) {
            $_js .= "\t{$arm}\n";
        }

        $_js .= "}";

        return $_js;
    }

    public static function matchArm(array|null $conds, string $body): string
    {
        if (is_null($conds)) {
            return Composer::return($body);
        }

        $conds = array_map(fn($c) => "jsMatchVar === {$c}", $conds);

        $_js = "if (" . implode(' || ', $conds) . ") {\n";
        $_js .= "\t\t" . Composer::return($body) . ";\n";
        $_js .= "\t}";

        return $_js;
    }
}