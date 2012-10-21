<?php
/**
 * @author Henry Schmieder
   @example
 * @version 0.1 24/06/12 13:04
 */
namespace Ripple;

class Event
{
    /**
     * @var string eventType
     */
    protected $_type;

    /**
     * @var string|object The event target
     */
    protected $_target;

    /**
     * @var array|\ArrayAccess|object The event parameters
     */
    protected $_params = array();


    /** @var bool whether or not the event can be stopped from propagating */
    protected $_cancelable;


    /** @var bool */
    protected $_propagationStopped;



    /**
     * Constructor
     *
     * Accept a target and its parameters.
     *
     * @param  string $name Event name
     * @param  string|object $target
     * @param  array|\ArrayAccess $params
     * @return void
     */
    public function __construct( $type = null, $target = null, $params = null, $cancelable = true )
    {
        if ( null !== $type ) {
            $this->setType( $type );
        }

        if ( null !== $target ) {
            $this->setTarget( $target );
        }

        if ( null !== $params ) {
            $this->setParams( $params );
        }

        $this->setCancelable( $cancelable );
    }


     /**
     * Get event type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Get the event target
     *
     * This may be either an object, or the name of a static method.
     *
     * @return string|object
     */
    public function getTarget()
    {
        return $this->_target;
    }

    /**
     * Set parameters
     *
     * Overwrites parameters
     *
     * @param  array|\ArrayAccess|object $params
     * @return Event
     */
    public function setParams($params)
    {
        /*
        if (!is_array($params) && !is_object($params)) {
            throw new Pyrrad_Exception_InvalidArgument(sprintf(
                'Event parameters must be an array or object; received "%s"', gettype($params)
            ));
        }
        */
        $this->_params = $params;
        return $this;
    }

    /**
     * Get all parameters
     *
     * @return array|object|\ArrayAccess
     */
    public function getParams()
    {
        return empty( $this->_params ) ? null : $this->_params;
    }

    /**
     * Get an individual parameter
     *
     * If the parameter does not exist, the $default value will be returned.
     *
     * @param  string|int $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParam($name = null, $default = null)
    {
        if( empty( $name ) ) {
            return empty( $this->_params ) ? null : $this->_params;
        }
        // Check in params that are arrays or implement array access
        if (is_array($this->_params) || $this->_params instanceof \ArrayAccess) {
            if (!isset($this->_params[$name])) {
                return $default;
            }

            return $this->_params[$name];
        }

        // Check in normal objects
        if (!isset($this->_params->{$name})) {
            return $default;
        }
        return $this->_params->{$name};
    }



    /**
     * Set the event name
     *
     * @param  string $name
     * @return Event
     */
    public function setType($name)
    {
        $this->_type = (string) $name;
        return $this;
    }

    /**
     * Set the event target/context
     *
     * @param  null|string|object $target
     * @return Event
     */
    public function setTarget($target)
    {
        $this->_target = $target;
        return $this;
    }

    /**
     * Set an individual parameter to a value
     *
     * @param  string|int $name
     * @param  mixed $value
     * @return Event
     */
    public function setParam($name, $value)
    {
        if (is_array($this->_params) || $this->_params instanceof \ArrayAccess) {
            // Arrays or objects implementing array access
            $this->_params[$name] = $value;
        } else {
            // Objects
            $this->_params->{$name} = $value;
        }
        return $this;
    }

    /**
     * Stop further event propagation
     *
     * @param  bool $flag
     * @return void
     */
    public function stopPropagation($flag = true)
    {
        if( $this->_cancelable ) {
            $this->_propagationStopped = (bool) $flag;
        }
    }

    /**
     * Is propagation stopped?
     *
     * @return bool
     */
    public function propagationIsStopped()
    {
        return $this->_propagationStopped;
    }

    /**
     * @param boolean $cancelable
     * @return Event
     */
    public function setCancelable( $cancelable )
    {
        $this->_cancelable = $cancelable;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCancelable()
    {
        return $this->_cancelable;
    }


    public function __toString()
    {
       return $this->getType();
    }

}

?>