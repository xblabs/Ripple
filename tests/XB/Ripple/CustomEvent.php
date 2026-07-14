<?php

declare( strict_types=1 );

namespace Test\XB\Ripple;

use XB\Ripple\Event;

/**
 * Custom event subclass for testing setEventClass() functionality.
 */
class CustomEvent extends Event
{
    public function getCustom(): string
    {
        return 'custom';
    }
}
