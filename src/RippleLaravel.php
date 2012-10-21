<?php
/**
 * @author Henry Schmieder
 * @version 0.1 23/06/12 10:42
 */
namespace Ripple;

class RippleLaravel extends DispatcherStatic
{
    public static function listen($event, $callback, $priority = 1)
    {
        static::addListener( $event, $callback, $priority );
    }

    public static function listenAggregate( $event, $callback, $priority = 1 )
    {
         static::addListenerAggregate( $event, $callback, $priority );
    }

    public static function fire( $events, $parameters = array(), $halt = false )
    {
        if ( is_string( $events ) ) {
            $events = array( $events );
        }
        foreach ( $events as $event ) {
            $useParamsAsCallbackArg = false;
            if( preg_match( '`^laravel\.`', $event ) ) {
                $useParamsAsCallbackArg = true;
            }
            if ( $halt ) {
                static::dispatchUntil( $event, null, $parameters, $useParamsAsCallbackArg );
            } else {
                static::dispatch( $event, null, $parameters, $useParamsAsCallbackArg );
            }
        }
    }

    public static function first($event, $parameters = array())
    {
        $useParamsAsCallbackArg = false;
        if ( preg_match( '`^laravel\.`', $event ) ) {
            $useParamsAsCallbackArg = true;
        }
       return static::dispatchGetFirst( $event, null, $parameters, $useParamsAsCallbackArg );
    }

    public static function until($event, $parameters = array())
    {
        $useParamsAsCallbackArg = false;
        if ( preg_match( '`^laravel\.`', $event ) ) {
            $useParamsAsCallbackArg = true;
        }
        return static::dispatchUntil( $event, null, $parameters, $useParamsAsCallbackArg );
    }


    public static function clear($event)
    {
        static::removeListenersForEvent( $event );
    }

    public static function listeners($event)
    {
        return static::hasListener( $event );
    }

}

?>