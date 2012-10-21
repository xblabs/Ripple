<?php
/**
 * @author Henry Schmieder
 * @version 0.1 24/06/12 13:51
 */
namespace Ripple;

class ListenerDescriptor
{
    /** @var string */
    public $type;
    /** @var mixed callback */
    public $listener;
    /** @var int */
    public $priority;

    public function __construct( $type, $listener, $priority )
    {
        $this->type = $type;
        $this->listener = $listener;
        $this->priority = $priority;
    }
}

?>