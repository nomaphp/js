<?php

namespace Noma\Js\Attributes;

#[\Attribute]
class JsInteropClass
{
    public function __construct(public ?string $name = null) {}
}