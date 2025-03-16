<?php

namespace Noma\Js\Jsi\Traits;

use Noma\Js\Jsi\Element;

trait Queryable
{
    /**
     * @param string $selectors
     * @return Element
     */
    public function querySelector(string $selectors): Element  {
        return new Element();
    }

    /**
     * @param string $selectors
     * @return Element[]
     */
    public function querySelectorAll(string $selectors): array
    {
        return [];
    }
}