<?php

namespace XB\Ripple;

class ListenerDescriptor
{
    public function __construct(
        public string $type,
        public mixed $listener,
        public int $priority
    ) {}
}