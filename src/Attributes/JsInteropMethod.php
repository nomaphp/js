<?php

namespace Noma\Js\Attributes;

#[\Attribute]
class JsInteropMethod
{
    public function __construct(
        public bool $isProperty = false,
        public bool $isAwait = false,
        public bool $isAsync = false,
        public bool $isRoot = false,
        public ?int $paramsToPass = null,
    ) {}
}