<?php

namespace Noma\Js\Jsi;

use Noma\Js\Attributes\JsInteropClass;
use Noma\Js\Jsi\Traits\HasEventTarget;
use Noma\Js\Jsi\Traits\Queryable;

#[JsInteropClass]
class Element
{
    use HasEventTarget;
    use Queryable;
}