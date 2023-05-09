<?php

namespace Ripple;

class Exception extends \Exception
{
    public const NOT_CALLABLE = 'Resource not callable.';
    public const NOT_OBJECT = 'Resource is not an object.';
}