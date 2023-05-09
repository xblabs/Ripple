<?php

namespace Ripple;

interface IDispatcher
{
    public function setEventClass( string $class ):self;

    public function dispatch( string|Event $event, string|object|null $target = null, array|\ArrayAccess $argv = [], bool $useParamsAsCallbackArg = false  ): mixed;

    public function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false  );

    public function hasListener( string $type ): bool;

    public function addListener( string $type, callable $listener, int $priority = 1);

    public function removeListener( string $type, callable $listener ): bool;

    public function removeListenersForEvent( string $type ): int;

    public function getAllListeners();

    public function getListenersForEvent( string $type ): array;

    public function removeAllListeners();

}
