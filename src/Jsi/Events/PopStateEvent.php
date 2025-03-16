<?php

namespace Noma\Js\Jsi\Events;

use Noma\Js\Jsi\Event;

class PopStateEvent extends Event
{
    public bool $isTrusted;
    public bool $bubbles;
    public bool $cancelBubble;
    public bool $cancelable;
    public bool $composed;
    public object $currentTarget;
    public bool $defaultPrevented;
    public int $eventPhase;
    public bool $hasUAViusalTransition;
    public bool $returnValue;
    public object $state;
    public string $type;
}