<?php

namespace Noma\Js;

use Noma\Js\Jsi\Window;
use Noma\Js\Attributes\JsInteropClass;

#[JsInteropClass(name: 'window')]
class Jsi extends Window
{
}