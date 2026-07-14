<?php

/**
 * @version 0.2 2026 v2 subscriber form
 */

namespace Test\XB\Ripple;

use XB\Ripple\EventSubscriberInterface;

class EventTestListener implements EventSubscriberInterface
{
	/** @var \stdClass */
	public $registrar;

	public function __construct()
	{
		$this->registrar = new \stdClass();
		$this->registrar->capturedTypes = [];
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'test:beforeTest' => 'beforeTest',
			'test:afterTest'  => 'afterTest',
		];
	}

	/**
	 * @param $e \XB\Ripple\Event
	 */
	public function beforeTest( $e )
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
	}

	/**
	 * @param $e \XB\Ripple\Event
	 */
	public function afterTest( $e )
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
		$this->registrar->params = $e->getParams();
		$this->registrar->target = $e->getTarget();
	}

}
