# Upgrading from 1.x to 2.0

Version 2.0 is a breaking release. This guide lists every change that can affect existing code.

## Requirements

- **PHP 8.1 or newer** is now required (was 8.0).
- A new runtime dependency, `psr/event-dispatcher`, is pulled in automatically.

## Colon event names are no longer special

In 1.x, any event name containing `:` was routed to the aggregate system, so a regular listener registered on such a
name never fired. In 2.0, event names are opaque strings and `:` has no special meaning.

```php
// 1.x: this listener never fired — 'order:placed' was treated as an aggregate token.
// 2.0: this works as written.
$dispatcher->addListener( 'order:placed', $listener );
$dispatcher->dispatch( 'order:placed' );
```

## Aggregates are removed

`addListenerAggregate()` and the implicit `component:method` routing are gone. Replace them with one of:

**Wildcard listeners** — for "listen to a family of events":

```php
// Before
$dispatcher->addListenerAggregate( 'user', $listener ); // then dispatch 'user:login', 'user:logout', ...

// After
$dispatcher->addWildcardListener( 'user.*', $listener ); // matches user.login, user.logout, ...
```

**Subscriber objects** — for routing different events to different methods:

```php
use XB\Ripple\Event;
use XB\Ripple\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'user.login'  => 'onLogin',
            'user.logout' => 'onLogout',
        ];
    }

    public function onLogin( Event $e ): void { /* ... */ }
    public function onLogout( Event $e ): void { /* ... */ }
}

$dispatcher->addSubscriber( new UserSubscriber() );
```

## Default priority changed from `1` to `0`

If you mixed default-priority listeners with listeners explicitly registered at priority `0`, their relative order
changes. Register explicit priorities where ordering matters.

## `useParamsAsCallbackArg` no longer leaks between listeners

Previously, a multi-argument closure could flip the whole dispatch into "spread params as arguments" mode, so later
listeners also received raw params instead of the `Event`. Each listener now decides independently at registration.
If you relied on the leak, pass the flag explicitly or shape the listener's signature.

## `Event::setParam()` on null/scalar params no longer throws

It now lazily creates an array. Only code that depended on the previous fatal `Error` is affected.

## `ListenerDescriptor` constructor and properties changed

`ListenerDescriptor` is now `final` with `readonly` properties and additional fields (`sequence`, `once`, `isWildcard`,
`expectsRawParams`, `regex`). Code that constructed or mutated it directly must be updated.

## `IDispatcher` widened

The interface now declares the full public surface (including `dispatchGetFirst`, `once`, wildcard, subscriber and
`getWildcardListeners` methods) and aligns `dispatch()`/`dispatchUntil()` signatures with the implementation. Custom
implementers of `IDispatcher` must add the new methods.

## Static dispatcher is resettable

`DispatcherStatic` now exposes `reset()` and `setDispatcher()`. Note that `removeAllListeners()` keeps the same backing
instance (and any custom event class), whereas `reset()` drops the instance entirely — prefer `reset()` for test
isolation.
