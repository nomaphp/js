<?php

namespace Noma\Js\Jsi\Traits;

/**
 * @template T of object
 */
trait HasEventTarget
{
    /**
     * @param string $type
     * @param (callable(T): void) $listener
     * @param array|null $options
     * @return void
     */
    public function addEventListener(string $type, callable $listener, ?array $options = null): void
    {}

    /**
     * @param string $type
     * @param (callable(T): void) $listener
     * @return void
     */
    public function removeEventListener(string $type, callable $listener): void
    {}
}