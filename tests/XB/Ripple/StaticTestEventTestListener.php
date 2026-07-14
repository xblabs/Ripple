<?php

/**
 * @version 0.2 2026 v2 subscriber form
 */

namespace Test\XB\Ripple;

use stdClass;
use XB\Ripple\Event;
use XB\Ripple\EventSubscriberInterface;

class StaticTestEventTestListener implements EventSubscriberInterface
{
	public ?stdClass $registrar;

	public function __construct()
	{
		$this->registrar = new stdClass();
		$this->registrar->capturedTypes = [];
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'test:beforeTest' => 'beforeTest',
			'test:afterTest'  => 'afterTest',
		];
	}


	public function beforeTest( Event $e ): void
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
	}


	public function afterTest( Event $e ): void
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
	}

}
