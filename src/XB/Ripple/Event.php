<?php

namespace XB\Ripple;

class Event
{
	public function __construct(
		protected string|null        $type = null,
		protected string|object|null $target = null,
		protected mixed              $params = null,
		protected bool               $cancelable = true,
		protected bool               $propagationStopped = false
	)
	{
	}


	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType( string $type ): static
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Get the event target
	 * This may be either an object, or the name of a static method.
	 */
	public function getTarget(): string|object|null
	{
		return $this->target;
	}

	public function setTarget( string|object $target ): static
	{
		$this->target = $target;
		return $this;
	}

	public function getParams(): mixed
	{
		return $this->params;
	}

	public function setParams( mixed $params ): static
	{
		$this->params = $params;
		return $this;
	}


	/**
	 * Get an individual parameter
	 * If the parameter does not exist, the $default value will be returned.
	 *
	 */
	public function getParam( string|int|null $name = null, mixed $default = null ): mixed
	{
		if( $name === null ) {
			return $this->params;
		}

		if( is_array( $this->params ) || $this->params instanceof \ArrayAccess ) {
			return $this->params[ $name ] ?? $default;
		}

		return $this->params?->{$name} ?? $default;
	}


	public function setParam( string|int $name, mixed $value ): static
	{
		if( is_array( $this->params ) || $this->params instanceof \ArrayAccess ) {
			$this->params[ $name ] = $value;
		} else {
			$this->params->{$name} = $value;
		}

		return $this;
	}


	public function isCancelable(): bool
	{
		return $this->cancelable;
	}

	public function setCancelable( bool $cancelable ): static
	{
		$this->cancelable = $cancelable;
		return $this;
	}

	public function isPropagationStopped(): bool
	{
		return $this->propagationStopped;
	}

	public function stopPropagation(): void
	{
		if( $this->cancelable ) {
			$this->propagationStopped = true;
		}
	}

	public function __toString(): string
	{
		return (string)$this->getType();
	}


}
