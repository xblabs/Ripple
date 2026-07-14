<?php

declare( strict_types=1 );

namespace XB\Ripple;

interface IDispatcher
{
	public function setEventClass( string $class ): self;

	public function getEventClass(): string;

	public function dispatch( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed;

	public function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed;

	public function dispatchGetFirst( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed;

	public function hasListener( string $type ): bool;

	public function addListener( string $type, callable $listener, int $priority = 0 ): void;

	public function once( string $type, callable $listener, int $priority = 0 ): void;

	public function addWildcardListener( string $pattern, callable $listener, int $priority = 0 ): void;

	public function onceWildcard( string $pattern, callable $listener, int $priority = 0 ): void;

	public function addSubscriber( EventSubscriberInterface $subscriber ): void;

	public function removeSubscriber( EventSubscriberInterface $subscriber ): void;

	public function removeListener( string $type, callable $listener ): bool;

	public function removeWildcardListener( string $pattern, callable $listener ): bool;

	public function removeListenersForEvent( string $type ): int;

	/**
	 * @return ListenerDescriptor[]
	 */
	public function getAllListeners(): array;

	/**
	 * @return array<string, ListenerDescriptor[]>
	 */
	public function getAllListenersStructured(): array;

	/**
	 * @return ListenerDescriptor[]
	 */
	public function getListenersForEvent( string $type ): array;

	/**
	 * @return ListenerDescriptor[]
	 */
	public function getWildcardListeners(): array;

	public function removeAllListeners(): void;
}
