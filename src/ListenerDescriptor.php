<?php

namespace Ripple;

class ListenerDescriptor
{
    public function __construct(
        public string $type,
        public mixed $listener,
        public int $priority
    ) {}
}