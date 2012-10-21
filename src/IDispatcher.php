<?php
/**
 * @author Henry Schmieder
 * @version 0.1 01/07/12 18:41
 */
namespace Ripple;

interface IDispatcher
{
    public function setEventClass( $class );

    public function dispatch( $event, $target = null, $argv = array() );

    public function dispatchUntil( $event, $target = null, $argv = array() );

    public function hasListener( $type );

    public function addListener( $type, $listener = null, $priority = 1 );

    public function removeListener( $type, $listener );

    public function removeListenersForEvent( $type );

    public function getAllListeners();

    public function getListenersForEvent( $type );

    public function removeAllListeners();

}

?>