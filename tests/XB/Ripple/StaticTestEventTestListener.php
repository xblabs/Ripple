<?php
/**
 * @version 0.1 09/05/2023 17:08
 */

namespace Test\XB\Ripple;

use XB\Ripple\Event;

class StaticTestEventTestListener
{
	public ?\stdClass $registrar;

	public function __construct()
	{
		$this->registrar = new \stdClass();
		$this->registrar->capturedTypes = array();
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