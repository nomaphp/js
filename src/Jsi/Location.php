<?php

namespace Noma\Js\Jsi;

use Noma\Js\Attributes\JsInteropClass;

#[JsInteropClass(name: "location")]
class Location
{
    public string $hash;
    public string $host;
    public string $hostname;
    public string $href;
    public string $origin;
    public string $pathname;
    public int $port;
    public string $protocol;
    public string $search;

    public function assign(string $url): void {}
    public function reload(): void {}
    public function replace(string $url): void {}
    public function toString(): string {}
}