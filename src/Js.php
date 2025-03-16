<?php

namespace Noma\Js;

use Noma\Js\Attributes\JsInteropClass;
use Noma\Js\Attributes\JsInteropFunction;
use Noma\Js\Attributes\JsInteropMethod;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Const_;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Param;
use PhpParser\Node\Arg;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

class Js
{
    /**
     * @var Stmt[]|null $ast
     */
    private ?array $ast;
    private string $phpVersion;
    private ?string $rootDir;
    private string $js = "";

    public function __construct(string $contents, string $phpVersion, ?string $rootDir = null)
    {
        $astParser = new ParserFactory()->createForVersion(PhpVersion::fromString($phpVersion));
        $this->phpVersion = $phpVersion;
        $nameResolver = new \PhpParser\NodeVisitor\NameResolver;
        $nodeTraverser = new \PhpParser\NodeTraverser;
        $nodeTraverser->addVisitor($nameResolver);
        $this->ast = $nodeTraverser->traverse($astParser->parse($contents));
        $this->rootDir = $rootDir;

        if (!$this->ast) return;

        $this->traverseTree($this->ast);
    }

    /**
     * @return Jsi
     */
    public static function i(): Jsi
    {
        return new Jsi();
    }

    public static function fromFile(string $path, string $version = '8.4', ?string $rootDir = null): string
    {
        return new self(file_get_contents($path), $version, $rootDir)->toString();
    }

    public static function fromString(string $contents, string $version = '8.4', ?string $rootDir = null): string
    {
        return new self($contents, $version, $rootDir)->toString();
    }

    private function traverseTree(?array $tree = []): void
    {
        foreach ($tree as $node) {
            $this->js .= $this->parseNode($node) . "\n";
        }
    }

    private function parseExpressionStmt(Stmt\Expression $node): string
    {
        return $this->parseNode($node->expr) . ";";
    }

    private function parseEchoStmt(Stmt\Echo_ $node): string
    {
        return Composer::print(array_map(fn($p) => $this->parseNode($p), $node->exprs)) . ";";
    }

    private function parseFunctionStmt(Stmt\Function_ $node): string
    {
        return Composer::function(
            name: $node->name->name,
            params: array_map(fn($x) => $this->parseNode($x), $node->params),
            stmts: array_map(fn($p) => $this->parseNode($p), $node->stmts)
        );
    }

    private function parseReturnStmt(Stmt\Return_ $node): string
    {
        return Composer::return($this->parseNode($node->expr)) . ";";
    }

    private function parseAssign(Expr\Assign $node): string
    {
        if ($node->var instanceof Expr\PropertyFetch) {
            return Composer::propertyVar($this->parseNode($node->var), $this->parseNode($node->expr));
        }

        if ($node->var instanceof Expr\ArrayDimFetch) {
            return Composer::methodCall($this->parseNode($node->var), "push", [$this->parseNode($node->expr)]);
        }

        return Composer::var($this->parseNode($node->var), $this->parseNode($node->expr));
    }

    private function parseVariable(Expr\Variable $node): string
    {
        return $node->name;
    }

    private function parseFunctionCall(Expr\FuncCall $node): string
    {
        return Composer::functionCall(
            name: $node->name->name,
            args: array_map(fn($x) => $this->parseNode($x), $node->args)
        );
    }

    private function parseList(Expr\List_ $node): string
    {
        return Composer::list(array_map(fn($item) => $this->parseNode($item), $node->items));
    }

    private function parseBinaryOp(Expr\BinaryOp $node, string $op): string
    {
        $left = $this->parseNode($node->left);
        $right = $this->parseNode($node->right);

        return Composer::binaryOp($left, $right, $op);
    }

    private function parseString(Scalar\String_ $node): string
    {
        return "\"$node->value\"";
    }

    private function parseInterpolatedString(Scalar\InterpolatedString $node): string
    {
        return Composer::interpolateString($node->parts);
    }

    private function parseParam(Param $node): string
    {
        return $this->parseNode($node->var);
    }

    private function parseArg(Arg $node): string
    {
        return $this->parseNode($node->value);
    }

    private function parseIfStmt(Stmt\If_ $node): string
    {
        return Composer::if(
            cond: $this->parseNode($node->cond),
            stmts: array_map(fn($x) => $this->parseNode($x), $node->stmts)
        );
    }

    private function parseInclude(Expr\Include_ $node): string
    {
        if (!$this->rootDir) return "";

        $path = match (get_class($node->expr)) {
            Scalar\String_::class => $node->expr->value
        };

        return $this::fromFile(
            path: $this->rootDir . '/' . $path,
            version: $this->phpVersion
        );
    }

    private function parseArrowFunction(Expr\ArrowFunction $node): string
    {
        return "() => " . $this->parseNode($node->expr);
    }

    private function parseBooleanNot(Expr\BooleanNot $node): string
    {
        return Composer::booleanNot($this->parseNode($node->expr));
    }

    private function parseBitwiseNot(Expr\BitwiseNot $node): string
    {
        return Composer::bitwiseNot($this->parseNode($node->expr));
    }

    private function parseTernary(Expr\Ternary $node): string
    {
        $cond = $this->parseNode($node->cond);
        $if = $node->if ? $this->parseNode($node->if) : null;
        $else = $this->parseNode($node->else);

        return Composer::ternary($cond, $if, $else);
    }

    private function parseAssignOp(Expr\AssignOp $node, string $op): string
    {
        return Composer::assignOp(
            var: $this->parseNode($node->var),
            op: $op,
            value: $this->parseNode($node->expr)
        );
    }

    private function parsePostInc(Expr\PostInc $node): string
    {
        return Composer::postInc($this->parseNode($node->var));
    }

    private function parsePostDec(Expr\PostDec $node): string
    {
        return Composer::postDec($this->parseNode($node->var));
    }

    private function parseConstStmt(Stmt\Const_ $node): string
    {
        $consts = [];

        if (isset($node->consts)) {
            foreach ($node->consts as $const) {
                $consts[] = $this->parseNode($const);
            }
        }

        return implode("\n", $consts) . ";";
    }

    private function parseConst(Const_ $node): string
    {
        return Composer::const($node->name, $this->parseNode($node->value));
    }

    private function parseArray(Expr\Array_ $node): string
    {
        $noKeys = array_all($node->items, fn(ArrayItem $x) => $x->key === null);

        if ($noKeys) {
            return Composer::array(array_map(fn($x) => $this->parseNode($x), $node->items));
        }

        return Composer::object(array_map(fn($x) => $this->parseNode($x), $node->items));
    }

    private function parseArrayItem(ArrayItem $node): string
    {
        if (!$node->key) {
            return $this->parseNode($node->value);
        }

        return "{$this->parseNode($node->key)}: {$this->parseNode($node->value)}";
    }

    private function parseConstFetch(Expr\ConstFetch $node): string
    {
        return $node->name;
    }

    private function parseClassStmt(Stmt\Class_ $node): string
    {
        return Composer::class(
            name: $this->parseNode($node->namespacedName),
            stmts: array_map(fn($x) => $this->parseNode($x), $node->stmts),
            extends: $node->extends,
        );
    }

    private function parseClassMethodStmt(Stmt\ClassMethod $node): string
    {
        return Composer::classMethod(
            name: $this->parseNode($node->name) === "__construct" ? "constructor" : $node->name,
            params: array_map(fn($x) => $this->parseNode($x), $node->getParams()),
            stmts: array_map(fn($x) => $this->parseNode($x), $node->getStmts()),
            static: $node->isStatic(),
        );
    }

    private function parsePropertyStmt(Stmt\Property $node): string
    {
        $properties = [];

        foreach($node->props as $prop) {
            $properties[] = $this->parseNode($prop);
        }

        return Composer::propertyStmt(
            properties: $properties,
            static: $node->isStatic()
        );
    }

    private function parsePropertyItem(PropertyItem $node): string
    {
        return Composer::property(
            name: $this->parseNode($node->name),
            default: $this->parseNode($node->default),
        );
    }

    private function parseVarLikeIdentifier(VarLikeIdentifier $node): string
    {
        return $node->name;
    }

    private function parseIdentifier(Identifier $node): string
    {
        return $node->name;
    }

    private function parsePropertyFetch(Expr\PropertyFetch $node): string
    {
        return "{$this->parseNode($node->var)}.{$this->parseNode($node->name)}";
    }

    private function parseMethodCall(Expr\MethodCall $node): string
    {
        $name = $this->parseNode($node->name);
        $args = array_map(fn($x) => $this->parseNode($x), $node->getArgs());

        if (isset($node->var->class)) {
            try {
                $reflectionClass = new \ReflectionClass($node->var->class->name ?? "");
                $classAttributes = $reflectionClass->getAttributes(JsInteropClass::class);

                // Whole class is an interop
                if (!empty($classAttributes)) {
                    $iteropClassName = $node->var->class->name;

                    /** @var JsInteropClass $jsInteropClass */
                    $jsInteropClass = $classAttributes[0]->newInstance();

                    if ($jsInteropClass->name) {
                        $iteropClassName = $jsInteropClass->name;
                    }

                    $reflectionMethod = $reflectionClass->getMethod($node->name->name);
                    $JsInteropMethodAttributes = $reflectionMethod->getAttributes(JsInteropMethod::class);

                    if (!empty($JsInteropMethodAttributes)) {
                        /** @var JsInteropMethod $jsInteropMethod */
                        $jsInteropMethod = $JsInteropMethodAttributes[0]->newInstance();

                        return Composer::staticCall($iteropClassName, $name, $args, [
                            'isProperty' => $jsInteropMethod->isProperty,
                            'isAwait' => $jsInteropMethod->isAwait,
                            'isAsync' => $jsInteropMethod->isAsync,
                            'isRoot' => $jsInteropMethod->isRoot,
                            'paramsToPass' => $jsInteropMethod->paramsToPass
                        ]);
                    }

                    return Composer::staticCall($iteropClassName, $name, $args, [
                        'isProperty' => null,
                        'isAwait' => null,
                        'isAsync' => null,
                        'isRoot' => null,
                        'paramsToPass' => null
                    ]);
                }

                $reflectionMethod = $reflectionClass->getMethod($node->name->name);

                // JsInteropFunction
                $JsInteropFunctionAttributes = $reflectionMethod->getAttributes(JsInteropFunction::class);

                // Method is an interop
                if (!empty($JsInteropFunctionAttributes)) {
                    $iteropMethodName = $node->name->name;
                    /** @var JsInteropFunction $jsInteropFunction */
                    $jsInteropFunction = $JsInteropFunctionAttributes[0]->newInstance();

                    if ($jsInteropFunction->name) {
                        $iteropMethodName = $jsInteropFunction->name;
                    }

                    return $this->parseNode(new Expr\FuncCall(new Name($iteropMethodName), $node->getArgs()));
                }
            } catch (\ReflectionException $e) {
                return "[js-interop error: {$node->var?->class}]";
            }
        }

        return Composer::methodCall($this->parseNode($node->var), $name, $args);
    }

    private function parseStaticCall(Expr\StaticCall $node): string
    {
        return Composer::staticCall(
            class: str_replace('\\', '_', $node->class->name),
            name: $this->parseNode($node->name),
            args: array_map(fn($x) => $this->parseNode($x), $node->getArgs()),
            opts: [
                'isProperty' => null,
                'isAwait' => null,
                'isAsync' => null,
                'isRoot' => null,
                'paramsToPass' => null
            ]
        );
    }

    private function parseNew(Expr\New_ $node): string
    {
        $class = match($this->parseNode($node->class)) {
            "static", "self" => "this",
            default => $this->parseNode($node->class)
        };

        return Composer::new(
            class: $class,
            args: array_map(fn($x) => $this->parseNode($x), $node->getArgs())
        );
    }

    private function parseName(Name $node): string
    {
        return str_replace('\\', '_', $node->name);
    }

    private function parseFullyQualifiedName(Name\FullyQualified $node): string
    {
        return str_replace('\\', '_', $node->name);
    }

    private function parseClosure(Expr\Closure $node): string
    {
        return Composer::closure(
            static: $node->static,
            params: array_map(fn($x) => $this->parseNode($x), $node->getParams()),
            stmts: array_map(fn($x) => $this->parseNode($x), $node->getStmts()),
        );
    }

    private function parseNamespaceStmt(Stmt\Namespace_ $node): string
    {
        return implode("\n", array_map(fn($x) => $this->parseNode($x), $node->stmts));
    }

    private function parseArrayDimFetch(Expr\ArrayDimFetch $node): string
    {
        return $this->parseNode($node->var);
    }

    private function parseForeachStmt(Stmt\Foreach_ $node): string
    {
        return Composer::foreach(
            expr: $this->parseNode($node->expr),
            keyVar: $this->parseNode($node->keyVar),
            valueVar: $this->parseNode($node->valueVar),
            stmts: array_map(fn($x) => $this->parseNode($x), $node->stmts)
        );
    }

    private function parseMatch(Expr\Match_ $node): string
    {
        $cond = $this->parseNode($node->cond);
        $arms = array_map(fn($x) => $this->parseNode($x), $node->arms);

        return Composer::match($cond, $arms);
    }

    private function parseMatchArm(MatchArm $node): string
    {
        $conds = $node->conds ? array_map(fn($x) => $this->parseNode($x), $node->conds) : null;
        $body = $this->parseNode($node->body);

        return Composer::matchArm($conds, $body);
    }

    private function parseNode(mixed $node): string
    {
        if (!$node) return "";

        return match (get_class($node)) {
            Stmt\Expression::class => $this->parseExpressionStmt($node),
            Stmt\Echo_::class => $this->parseEchoStmt($node),
            Stmt\Function_::class => $this->parseFunctionStmt($node),
            Stmt\Return_::class => $this->parseReturnStmt($node),
            Stmt\If_::class => $this->parseIfStmt($node),
            Stmt\Const_::class => $this->parseConstStmt($node),
            Stmt\Class_::class => $this->parseClassStmt($node),
            Stmt\ClassMethod::class => $this->parseClassMethodStmt($node),
            Stmt\Property::class => $this->parsePropertyStmt($node),
            Stmt\Use_::class => "",
            Stmt\Namespace_::class => $this->parseNamespaceStmt($node),
            Stmt\Foreach_::class => $this->parseForeachStmt($node),
            Expr\PropertyFetch::class => $this->parsePropertyFetch($node),
            Expr\Array_::class => $this->parseArray($node),
            Expr\Include_::class => $this->parseInclude($node),
            Expr\ArrowFunction::class => $this->parseArrowFunction($node),
            Expr\Assign::class => $this->parseAssign($node),
            Expr\BitwiseNot::class => $this->parseBitwiseNot($node),
            Expr\BooleanNot::class => $this->parseBooleanNot($node),
            Expr\Variable::class => $this->parseVariable($node),
            Expr\FuncCall::class => $this->parseFunctionCall($node),
            Expr\List_::class => $this->parseList($node),
            Expr\Ternary::class => $this->parseTernary($node),
            Expr\PostInc::class => $this->parsePostInc($node),
            Expr\PostDec::class => $this->parsePostDec($node),
            Expr\ConstFetch::class => $this->parseConstFetch($node),
            Expr\MethodCall::class => $this->parseMethodCall($node),
            Expr\StaticCall::class => $this->parseStaticCall($node),
            Expr\New_::class => $this->parseNew($node),
            Expr\Closure::class => $this->parseClosure($node),
            Expr\ArrayDimFetch::class => $this->parseArrayDimFetch($node),
            Expr\Match_::class => $this->parseMatch($node),
            Expr\AssignOp\Concat::class, Expr\AssignOp\Plus::class => $this->parseAssignOp($node, "+="),
            Expr\AssignOp\Minus::class => $this->parseAssignOp($node, "-="),
            Expr\AssignOp\Mul::class => $this->parseAssignOp($node, "*="),
            Expr\AssignOp\BitwiseXor::class => $this->parseAssignOp($node, "^="),
            Expr\AssignOp\BitwiseOr::class => $this->parseAssignOp($node, "|="),
            Expr\AssignOp\BitwiseAnd::class => $this->parseAssignOp($node, "&="),
            Expr\AssignOp\ShiftLeft::class => $this->parseAssignOp($node, "<<="),
            Expr\AssignOp\ShiftRight::class => $this->parseAssignOp($node, ">>="),
            Expr\AssignOp\Div::class => $this->parseAssignOp($node, "/="),
            Expr\AssignOp\Mod::class => $this->parseAssignOp($node, "%="),
            Expr\AssignOp\Pow::class => $this->parseAssignOp($node, "**="),
            Expr\AssignOp\Coalesce::class => $this->parseAssignOp($node, "??="),
            Expr\BinaryOp\BitwiseAnd::class => $this->parseBinaryOp($node, "&"),
            Expr\BinaryOp\BitwiseOr::class => $this->parseBinaryOp($node, "|"),
            Expr\BinaryOp\BitwiseXor::class, Expr\BinaryOp\LogicalXor::class => $this->parseBinaryOp($node, "^"),
            Expr\BinaryOp\BooleanAnd::class, Expr\BinaryOp\LogicalAnd::class => $this->parseBinaryOp($node, "&&"),
            Expr\BinaryOp\BooleanOr::class, Expr\BinaryOp\LogicalOr::class => $this->parseBinaryOp($node, "||"),
            Expr\BinaryOp\Plus::class, Expr\BinaryOp\Concat::class => $this->parseBinaryOp($node, "+"),
            Expr\BinaryOp\Minus::class => $this->parseBinaryOp($node, "-"),
            Expr\BinaryOp\Identical::class => $this->parseBinaryOp($node, "==="),
            Expr\BinaryOp\NotIdentical::class => $this->parseBinaryOp($node, "!=="),
            Expr\BinaryOp\Mod::class => $this->parseBinaryOp($node, "%"),
            Expr\BinaryOp\Equal::class => $this->parseBinaryOp($node, "=="),
            Expr\BinaryOp\NotEqual::class => $this->parseBinaryOp($node, "!="),
            Expr\BinaryOp\ShiftLeft::class => $this->parseBinaryOp($node, "<<"),
            Expr\BinaryOp\ShiftRight::class => $this->parseBinaryOp($node, ">>"),
            Expr\BinaryOp\Div::class => $this->parseBinaryOp($node, "/"),
            Expr\BinaryOp\Greater::class => $this->parseBinaryOp($node, ">"),
            Expr\BinaryOp\GreaterOrEqual::class => $this->parseBinaryOp($node, ">="),
            Expr\BinaryOp\Smaller::class => $this->parseBinaryOp($node, "<"),
            Expr\BinaryOp\SmallerOrEqual::class => $this->parseBinaryOp($node, "<="),
            Expr\BinaryOp\Spaceship::class => $this->parseBinaryOp($node, "<=>"),
            Expr\BinaryOp\Mul::class => $this->parseBinaryOp($node, "*"),
            Expr\BinaryOp\Coalesce::class => $this->parseBinaryOp($node, "??"),
            Expr\BinaryOp\Pow::class => $this->parseBinaryOp($node, "**"),
            Scalar\String_::class => $this->parseString($node),
            Scalar\Int_::class => $node->value,
            Scalar\Float_::class => $node->value,
            Scalar\InterpolatedString::class => $this->parseInterpolatedString($node),
            Param::class => $this->parseParam($node),
            PropertyItem::class => $this->parsePropertyItem($node),
            Arg::class => $this->parseArg($node),
            ArrayItem::class => $this->parseArrayItem($node),
            Const_::class => $this->parseConst($node),
            VarLikeIdentifier::class => $this->parseVarLikeIdentifier($node),
            Identifier::class => $this->parseIdentifier($node),
            Name::class => $this->parseName($node),
            Name\FullyQualified::class => $this->parseFullyQualifiedName($node),
            MatchArm::class => $this->parseMatchArm($node),
            default => "[" . get_class($node) . " not supported yet]"
        };
    }

    private function toString(): string
    {
        return trim($this->js) . PHP_EOL;
    }
}