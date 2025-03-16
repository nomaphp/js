<?php

namespace Noma\Js\Jsi;

use Noma\Js\Attributes\JsInteropClass;
use Noma\Js\Attributes\JsInteropFunction;
use Noma\Js\Attributes\JsInteropMethod;
use Noma\Js\Attributes\JsInteropOverrideOutput;
use Noma\Js\Jsi\Location;
use Noma\Js\Jsi\Traits\HasEventTarget;

#[JsInteropClass(name: "window")]
class Window
{
    use HasEventTarget;

    public function alert(mixed $message): void {}

    #[JsInteropMethod(isProperty: true)]
    public function location(): Location {
        return new Location();
    }

    /**
     * @template T
     * @param string $from
     * @param class-string<T> $as
     * @return T
     */
    #[JsInteropMethod(isAwait: true, isRoot: true, paramsToPass: 1)]
    public function import(string $from, string $as): object
    {
        return new self();
    }

    #[JsInteropMethod(isAsync: true, isRoot: true)]
    public function async(callable $closure): callable
    {
        return $closure;
    }
}