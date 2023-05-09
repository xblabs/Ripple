<?php
/**
 * @version 0.1 09/05/2023 16:40
 */

namespace Test\Ripple;

class EventTestListener
{
	/** @var \stdClass */
	public $registrar;

	public function __construct()
	{
		$this->registrar = new \stdClass();
		$this->registrar->capturedTypes = array();
	}

	/**
	 * @param $e \Ripple\Event
	 */
	public function beforeTest( $e )
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
	}

	/**
	 * @param $e \Ripple\Event
	 */
	public function afterTest( $e )
	{
		$this->registrar->setByCb = true;
		$this->registrar->capturedTypes[] = $e->getType();
		$this->registrar->params = $e->getParams();
		$this->registrar->target = $e->getTarget();
	}

}