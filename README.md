# Ripple

Ripple is a small, fast PHP event dispatcher. It lets you build event-driven systems with a simple interface for
registering listeners and dispatching events, with priorities, propagation control, wildcard subscriptions, one-time
listeners, subscriber objects, and PSR-14 interoperability.

An event dispatcher decouples the parts of an application: components communicate through events instead of direct
calls, which makes the code more maintainable and flexible.

## Features

- Simple event dispatching and handling
- Priority-based ordering (higher priority fires first; equal priority is last-in-first-out)
- Stoppable propagation (cancelable events)
- Wildcard listeners (`user.*`, `order:*`, separator-agnostic)
- One-time listeners (`once`)
- Subscriber objects that declare their handlers in one place
- [PSR-14](https://www.php-fig.org/psr/psr-14/) compatible adapter
- Instance or static/singleton usage
- Fully unit-tested; ordering and reflection are resolved once at registration, not on every dispatch

## Requirements

- PHP 8.1+

## Installation

```bash
composer require xblabs/ripple
```

```php
require 'vendor/autoload.php';

$dispatcher = new XB\Ripple\Dispatcher();
```

## Usage

### Dispatching events

```php
use XB\Ripple\Event;
use XB\Ripple\Dispatcher;

$dispatcher = new Dispatcher();

// Dispatch by name; returns an array of listener responses, or null if nothing handled it.
$responses = $dispatcher->dispatch( 'user.login' );

// Dispatch with a target object and params. $user is the target; ['id' => 123] the params.
$dispatcher->dispatch( 'user.login', $user, [ 'id' => 123 ] );

// Or build the Event yourself.
$dispatcher->dispatch( new Event( 'user.login', $user, [ 'id' => 123 ] ) );

// Dispatch and stop at the first listener that returns a truthy value.
$result = $dispatcher->dispatchUntil( 'user.login' );

// Dispatch and return only the first response.
$result = $dispatcher->dispatchGetFirst( 'user.login' );
```

> Event names are opaque strings. No character is special — `user.login`, `user:login` and `user/login` are all just
> names. (In 1.x a `:` was reserved for the aggregate system; that is no longer the case. See [UPGRADE.md](UPGRADE.md).)

### Listeners

A listener is any `callable`. By default it receives the `Event` object.

```php
// Closure
$dispatcher->addListener( 'user.login', static function ( Event $e ) {
    // $e->getType(), $e->getTarget(), $e->getParams()
} );

// Class method
$dispatcher->addListener( 'user.login', [ $service, 'onLogin' ] );
```

If a closure declares more than one parameter and its first parameter is **not** named `e` or `event`, Ripple spreads
the event's params as positional arguments instead of passing the `Event`. You can force this with the fourth argument
to `dispatch()`:

```php
$dispatcher->addListener( 'math.add', static function ( $a, $b ) {
    return $a + $b;
} );
$dispatcher->dispatch( 'math.add', null, [ 2, 3 ] ); // => [5]
```

### Priorities

Default priority is `0`. Higher priorities fire first; listeners with equal priority fire last-in-first-out.

```php
$dispatcher->addListener( 'event.name', $low, -10 );
$dispatcher->addListener( 'event.name', $high, 100 ); // fires first
```

### Stopping propagation

```php
$dispatcher->addListener( 'event.name', static function ( Event $e ) {
    $e->stopPropagation(); // remaining listeners are skipped
} );

// Events created with cancelable=false ignore stopPropagation().
$event = new Event( 'event.name', null, null, cancelable: false );
$dispatcher->dispatch( $event );
```

### One-time listeners

`once()` fires a listener at most once, then removes it. (A once listener that is never reached — e.g. propagation was
stopped before it — is retained for a future dispatch.)

```php
$dispatcher->once( 'app.boot', static fn( Event $e ) => bootstrap() );
$dispatcher->onceWildcard( 'cache.*', static fn( Event $e ) => warmCache( $e->getType() ) );
```

### Wildcard listeners

Wildcard patterns use `*` to match any run of characters, including your separator of choice.

```php
$dispatcher->addWildcardListener( 'user.*', static function ( Event $e ) {
    // receives user.login, user.logout, user.profile.update, ...
    match ( $e->getType() ) {
        'user.login'  => /* ... */ null,
        'user.logout' => /* ... */ null,
        default       => null,
    };
} );
```

Exact and wildcard listeners compose in a single dispatch and are ordered together by priority (on a tie, exact
listeners fire before wildcard ones).

### Subscriber objects

A subscriber declares all the events it handles in one place. Keys may contain `*` (registered as a wildcard).

```php
use XB\Ripple\Event;
use XB\Ripple\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'user.login'  => 'onLogin',                    // method name
            'user.logout' => [ 'onLogout', 100 ],          // method + priority
            'user.*'      => [ [ 'audit', 200 ], [ 'log', -10 ] ], // multiple handlers
        ];
    }

    public function onLogin( Event $e ): void { /* ... */ }
    public function onLogout( Event $e ): void { /* ... */ }
    public function audit( Event $e ): void { /* ... */ }
    public function log( Event $e ): void { /* ... */ }
}

$dispatcher->addSubscriber( new UserSubscriber() );
// $dispatcher->removeSubscriber( $subscriber ); // detaches everything it registered
```

### Managing listeners

```php
$dispatcher->hasListener( 'user.login' );
$dispatcher->getListenersForEvent( 'user.login' );
$dispatcher->getAllListeners();
$dispatcher->getWildcardListeners();

$dispatcher->removeListener( 'user.login', $listener );
$dispatcher->removeWildcardListener( 'user.*', $listener );
$dispatcher->removeListenersForEvent( 'user.login' );
$dispatcher->removeAllListeners();
```

### Custom event class

```php
$dispatcher->setEventClass( MyEvent::class ); // must extend XB\Ripple\Event
```

### Static / singleton usage

`DispatcherStatic` proxies a single shared dispatcher. It can be reset or injected, which is useful in tests.

```php
use XB\Ripple\DispatcherStatic;

DispatcherStatic::addListener( 'user.login', $listener );
DispatcherStatic::dispatch( 'user.login' );

DispatcherStatic::setDispatcher( $myDispatcher ); // inject a specific instance
DispatcherStatic::reset();                        // drop the shared instance entirely
```

### PSR-14 interoperability

`Event` implements `Psr\EventDispatcher\StoppableEventInterface`. The `Psr14\Psr14Dispatcher` and `Psr14\ListenerProvider`
adapters let Ripple act as a PSR-14 `EventDispatcherInterface`, reusing the same listener storage and priority ordering.

```php
use XB\Ripple\Dispatcher;
use XB\Ripple\Psr14\Psr14Dispatcher;

$ripple = new Dispatcher();
$ripple->addListener( 'user.login', $listener );

$psr = new Psr14Dispatcher( $ripple );
$event = $psr->dispatch( new XB\Ripple\Event( 'user.login' ) ); // returns the event
```

Note the difference between the two APIs: native Ripple dispatches event **names** and collects listener return values;
PSR-14 dispatches an **object** and returns that same object.

## Testing

```bash
composer install
composer test        # phpunit
composer analyse     # phpstan
composer cs          # php-cs-fixer (dry run)
```

## License

MIT License. See [LICENSE](LICENSE) for details.
