<?php

declare( strict_types=1 );

namespace XB\Ripple;

class Exception extends \Exception
{
	public const INVALID_EVENT_CLASS = 'Event class must extend XB\Ripple\Event.';
}
