<?php
/**
 * @author Henry Schmieder
 * @version 0.1 23/06/12 10:42
 */
namespace Ripple;

class Dispatcher implements IDispatcher
{
    /** @var Dispatcher */
    /*
    protected static $_instance;
    */

    /** @var string */
    protected $_eventClass = '\Ripple\Event';


    /** @var array */
    protected $_listeners = array();


     /** @var array */
    protected $_aggregatePatterns = array();


    /**
     * Set the event class to utilize
     *
     * @param  string $class
     * @return Dispatcher
     */
    public function setEventClass( $class )
    {
        $this->_eventClass = $class;
        return $this;
    }


    /**
     * Trigger all listeners for a given event
     *
     * Can emulate dispatchUntil() if the last argument provided is a callback.
     *
     * @param  string| Event $event
     * @param  string|object $target Object calling emit, or symbol describing target (such as static method name)
     * @param  array|\ArrayAccess $argv Array of arguments; typically, should be associative
     * @return mixed
     */
    public function dispatch( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false )
    {
        /** @var Event $e */
        $e = $this->resolveEventObj( $event, $target, $argv );
        return $this->_dispatch( $e, false, $useParamsAsCallbackArg );
    }

    /**
     * dispatch event and halt at the first listener that returns not null or false
     *
     * @param $event
     * @param null $target
     * @param array $argv
     * @param bool $useParamsAsCallbackArg
     * @return array | null array of gathered respones in the dispatch cycle
     */
    public function dispatchUntil( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false )
    {
        /** @var Event $e */
        $e = $this->resolveEventObj( $event, $target, $argv );
        return $this->_dispatch( $e, true, $useParamsAsCallbackArg );
    }

    /**
     *  Dispatch an event and return the first response.
     *
     * @param $event
     * @param null $target
     * @param array $argv
     * @param bool $useParamsAsCallbackArg
     * @return mixed
     */
    public function dispatchGetFirst( $event, $target = null, $argv = array(), $useParamsAsCallbackArg = false )
    {
        $r = $this->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
        return reset( $r );
    }


    /**
     * @param string $type
     * @return bool
     */
    public function hasListener( $type )
    {
        if ( !isset( $this->_listeners[ $type ] ) ) {
            return false;
        }
        return !empty( $this->_listeners[ $type ] ) ? true : false;
    }


    /**
     * @param string $type
     * @param \callable $listener
     * @param int $priority
     * @return void
     *
     */
    public function addListener( $type, $listener = null, $priority = 1 )
    {
        if ( !is_callable( $listener ) ) {
            throw new Exception( Exception::NOT_CALLABLE );
        }
        if ( !is_int( $priority ) ) {
            $priority = 1;
        }
        if ( !isset( $this->_listeners[ $type ] ) ) {
            $this->_listeners[ $type ] = array();
        }
        $this->_listeners[ $type ][ ] = new ListenerDescriptor( $type, $listener, $priority );
    }

    /**
     * @param $pattern  [component]  ( used in dispatch in the form [component]:[eventType]
     * @param null $listener
     * @param int $priority
     */
    public function addListenerAggregate( $pattern, $listener = null, $priority = 1 )
    {
        if( !is_object( $listener ) ) {
            throw new Exception( Exception::NOT_OBJECT );
        }
        if( !is_int( $priority ) ) {
            $priority = 1;
        }
        if( !isset( $this->_aggregatePatterns[ $pattern ] ) ) {
            $this->_aggregatePatterns[ $pattern ] = array();
        }
        $this->_aggregatePatterns[ $pattern ][] = new ListenerDescriptor( $pattern, $listener, $priority );
    }


    /**
     * @param string $type
     * @param \callable $listener
     * @return boolean - telling if listener was removed successfully
     */
    public function removeListener( $type, $listener )
    {
        if ( !is_callable( $listener ) ) {
            throw new Exception( Exception::NOT_CALLABLE );
        }
        $removed = false;
        $l = count( $this->_listeners[ $type ] );
        for ( $i = $l - 1; $i > -1; $i-- ) {
            /** @var ListenerDescriptor $eventObj  */
            $eventObj = $this->_listeners[ $type ][ $i ];
            if ( $eventObj->type == $type && $eventObj->listener == $listener ) {
                array_splice( $this->_listeners[ $type ], $i, 1 );
                $removed = true;
            }
        }
        return $removed;
    }


    /**
     * @param string $type
     * @return int - amount of removed listeners for that event type
     */
    public function removeListenersForEvent( $type )
    {
        $affected = 0;
        $l = count( $this->_listeners[ $type ] );
        for ( $i = $l - 1; $i > -1; $i-- ) {
            /** @var ListenerDescriptor $eventObj  */
            $eventObj = $this->_listeners[ $type ][ $i ];
            if ( $eventObj->type == $type ) {
                array_splice( $this->_listeners[ $type ], $i, 1 );
                $affected++;
            }
        }
        return $affected;
    }


    /**
     * Retrieve all listeners
     * @return array - array with the listener descriptor objects for all registered events strucured by sub arrays with key event type
     */
    public function getAllListenersStructured()
    {
        return $this->_listeners;
    }


    /**
     * Retrieve all listeners
     * @return array - array with the listener descriptor objects for all registered events
     */
    public function getAllListeners()
    {
        $all = array();
        foreach ( $this->_listeners as $typedL ) {
            foreach ( $typedL as $l ) {
                $all[ ] = $l;
            }
        }
        return $all;
    }


    /**
     * @param string $type
     * @return array
     */
    public function getListenersForEvent( $type )
    {
        return $this->_listeners[ $type ];
    }


    /**
     * Clear all listeners for a given event
     *
     * @return void
     */
    public function removeAllListeners()
    {
        $this->_listeners = array();
    }


    /**
     * @param Event $event
     * @param boolean $halt set true for dispatchUntil
     * @return mixed
     */
    protected function _dispatch( $event, $halt = false, $useParamsAsCallbackArg = false )
    {
        $responses = array();
        $descriptors = array();
        $type = $event->getType();

        if( strpos( $event, ':' ) !== false ) {
            $aggParts = explode( ':', $event );
            $aggregate = $aggParts[0];
            $aggEvent = $aggParts[1];
            if( isset( $this->_aggregatePatterns[ $aggregate ] ) ) {
                foreach(  $this->_aggregatePatterns[ $aggregate ] as $aggDesc )
                {
                    $descriptors[] = $aggDesc;
                }
            }
        }else{
            if( isset( $this->_listeners[ $type ] ) ) {
                $descriptors = &$this->_listeners[ $type ];
            }
            $aggEvent = null;
        }

        if ( empty( $descriptors ) ) {
            return null;
        }
        if( count( $descriptors ) > 1 ) {
            usort( $descriptors, /** @param ListenerDescriptor $a @param ListenerDescriptor $b */
            function( $a, $b )
            {
                return $b->priority < $a->priority;
            } );
        }

        $response = null;
        $listener = null;


        /** @var  ListenerDescriptor $d */
        foreach ( $descriptors as $d ) {
            if ( $event->propagationIsStopped() ) {
                break;
            }
            if( $aggEvent !== null ) {
                if( method_exists( $d->listener, $aggEvent ) ) {
                    $listener = array( $d->listener, $aggEvent );
                }
            }else{
                $isClosure = $d->listener instanceof \Closure;
                if( $isClosure && !$useParamsAsCallbackArg ) {
                    $reflInfo = new \ReflectionFunction( $d->listener );
                    if( $reflInfo->getNumberOfParameters() > 1 ) {
                        $reflPr = new \ReflectionParameter( $d->listener, 0 );
                        $cbArgName = $reflPr->getName();
                        if( !in_array( $cbArgName, array( 'e', 'event' ) ) ) {
                            $useParamsAsCallbackArg = true;
                        }
                    }
                }
                $listener = $d->listener;

            }
            if( $listener === null ) {
                continue;
            }
            if( $useParamsAsCallbackArg ) {
                $params = $event->getParams();
                if( !is_array( $params ) ) {
                    $params = array( $params );
                }
                $response = call_user_func_array( $listener, $params );
            } else {
                $response = call_user_func_array( $listener, array( $event ) );
            }
            if ( $halt &&  $response != null  ) {
                return $response;
            }
            $responses[ ] = $response;
        }

        return $halt ? null : ( !empty( $responses ) ? $responses : null );
    }


    protected function resolveEventObj( $event, $target = null, $argv = null )
    {
        /** @var Event $e */
        if ( $event instanceof Event ) {
            $e = $event;
        } elseif ( is_string( $event ) ) {
            $e = new $this->_eventClass();
            $e->setType( $event );
        }
        if ( !empty( $target ) ) {
            $e->setTarget( $target );
        }
        if ( !empty( $argv ) ) {
            $e->setParams( $argv );
        }
        return $e;
    }
}

?>