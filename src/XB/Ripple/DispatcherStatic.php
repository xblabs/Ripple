<?php

declare( strict_types=1 );

namespace XB\Ripple;

/**
 * Static facade over a single shared {@see Dispatcher} instance.
 *
 * The backing instance is lazily created and can be swapped ({@see setDispatcher})
 * or cleared ({@see reset}) — the latter is important for test isolation, since
 * removeAllListeners() keeps the same instance (and its custom event class).
 */
class DispatcherStatic
{
	private static ?Dispatcher $_dispatcher = null;

	public static function dispatcher(): Dispatcher
	{
		return self::$_dispatcher ??= new Dispatcher();
	}

	public static function setDispatcher( Dispatcher $dispatcher ): void
	{
		self::$_dispatcher = $dispatcher;
	}

	public static function reset(): void
	{
		self::$_dispatcher = null;
	}

	public static function setEventClass( string $class ): void
	{
		self::dispatcher()->setEventClass( $class );
	}

	public static function getEventClass(): string
	{
		return self::dispatcher()->getEventClass();
	}

	public static function dispatch( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		return self::dispatcher()->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
	}

	public static function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		return self::dispatcher()->dispatchUntil( $event, $target, $argv, $useParamsAsCallbackArg );
	}

	public static function dispatchGetFirst( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		return self::dispatcher()->dispatchGetFirst( $event, $target, $argv, $useParamsAsCallbackArg );
	}

	public static function hasListener( string $type ): bool
	{
		return self::dispatcher()->hasListener( $type );
	}

	public static function addListener( string $type, callable $listener, int $priority = 0 ): void
	{
		self::dispatcher()->addListener( $type, $listener, $priority );
	}

	public static function once( string $type, callable $listener, int $priority = 0 ): void
	{
		self::dispatcher()->once( $type, $listener, $priority );
	}

	public static function addWildcardListener( string $pattern, callable $listener, int $priority = 0 ): void
	{
		self::dispatcher()->addWildcardListener( $pattern, $listener, $priority );
	}

	public static function onceWildcard( string $pattern, callable $listener, int $priority = 0 ): void
	{
		self::dispatcher()->onceWildcard( $pattern, $listener, $priority );
	}

	public static function addSubscriber( EventSubscriberInterface $subscriber ): void
	{
		self::dispatcher()->addSubscriber( $subscriber );
	}

	public static function removeSubscriber( EventSubscriberInterface $subscriber ): void
	{
		self::dispatcher()->removeSubscriber( $subscriber );
	}

	public static function removeListener( string $type, callable $listener ): bool
	{
		return self::dispatcher()->removeListener( $type, $listener );
	}

	public static function removeWildcardListener( string $pattern, callable $listener ): bool
	{
		return self::dispatcher()->removeWildcardListener( $pattern, $listener );
	}

	public static function removeListenersForEvent( string $type ): int
	{
		return self::dispatcher()->removeListenersForEvent( $type );
	}

	/**
	 * @return array<string, ListenerDescriptor[]>
	 */
	public static function getAllListenersStructured(): array
	{
		return self::dispatcher()->getAllListenersStructured();
	}

	/**
	 * @return ListenerDescriptor[]
	 */
	public static function getAllListeners(): array
	{
		return self::dispatcher()->getAllListeners();
	}

	/**
	 * @return ListenerDescriptor[]
	 */
	public static function getListenersForEvent( string $type ): array
	{
		return self::dispatcher()->getListenersForEvent( $type );
	}

	/**
	 * @return ListenerDescriptor[]
	 */
	public static function getWildcardListeners(): array
	{
		return self::dispatcher()->getWildcardListeners();
	}

	public static function removeAllListeners(): void
	{
		self::dispatcher()->removeAllListeners();
	}
}
