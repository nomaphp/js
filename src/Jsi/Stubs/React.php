<?php

namespace Noma\Js\Jsi\Stubs;

class React {
    public function createElement(...$args) {

    }

    public function useEffect(callable $callback, array $deps) {}

    public function useState(mixed $defaultValue): array {
        return [];
    }
}