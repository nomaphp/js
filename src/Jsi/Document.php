<?php

namespace Noma\Js\Jsi;

use Noma\Js\Attributes\JsInteropClass;
use Noma\Js\Jsi\Traits\HasEventTarget;
use Noma\Js\Jsi\Traits\Queryable;

#[JsInteropClass(name: "document")]
class Document
{
    use HasEventTarget;
    use Queryable;
}