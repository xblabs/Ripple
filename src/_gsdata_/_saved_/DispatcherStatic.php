<?php
/**
 * @author Henry Schmieder
 * @version 0.1 23/06/12 10:42
 */
namespace Ripple;

class DispatcherStatic
{
     /** @var Dispatcher */
    protected static $_dispatcher;


    /**
     * @return Dispatcher
     */
    public static function dispatcher()
    {
        if ( !static::$_dispatcher ) {
            static::$_dispatcher = new Dispatcher();
        }
        return static::$_dispatcher;
    }


    public static function setEventClass( $class )
    {
        return static::dispatcher()->setEventClass( $class );
    }

    /**
     * @static
     * @param $event
     * @param null $target
     * @param array $argv
     * @param bool $useParamsAsCallbackArg
     * @return Event
     */
    public static function dispatch( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false  )
    {
         return static::dispatcher()->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
    }

    public static function dispatchUntil( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false )
    {
        return static::dispatcher()->dispatchUntil( $event, $target, $argv, $useParamsAsCallbackArg );
    }

    public static function dispatchGetFirst( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false  )
    {
        $results = static::dispatcher()->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
        return reset( $results );
    }

    public static function hasListener( $type )
    {
       return static::dispatcher()->hasListener( $type );
    }

    public static function addListener( $type, $listener = null, $priority = 1 )
    {
        static::dispatcher()->addListener( $type, $listener, $priority );
    }

    public static function addListenerAggregate( $type, $listener = null, $priority = 1 )
    {
        static::dispatcher()->addListenerAggregate( $type, $listener, $priority );
    }

    public static function removeListener( $type, $listener )
    {
        return static::dispatcher()->removeListener( $type, $listener );
    }

    public static function removeListenersForEvent( $type )
    {
        return static::dispatcher()->removeListenersForEvent( $type );
    }

    public static function getAllListeners()
    {
       return static::dispatcher()->getAllListeners();
    }

    public static function getListenersForEvent( $type )
    {
        return static::dispatcher()->getListenersForEvent( $type );
    }

    public static function removeAllListeners()
    {
        static::dispatcher()->removeAllListeners();
    }
}

?>