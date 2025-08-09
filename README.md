# Ripple

Ripple is a lightweight event dispatcher for PHP 8+ that makes it easy to build event‑driven applications. It provides an intuitive API for registering listeners, dispatching events, prioritising callbacks, stopping propagation and grouping related listeners. Ripple can be used either through an instance (`Dispatcher`) or via a static façade (`DispatcherStatic`), so it integrates cleanly with a wide range of coding styles.

## Requirements

- PHP 8.0 or higher
- Composer for dependency management

## Installation

Install via Composer:

```bash
composer require xblabs/ripple
````

If you wish to run the unit tests, install the development dependencies as well:

```bash
composer install
```

## Basic usage

### Using the instance dispatcher

```php
use XB\Ripple\Dispatcher;

$dispatcher = new Dispatcher();

# Register a listener for the 'user.registered' event
$dispatcher->addListener('user.registered', function (XB\Ripple\Event $event) {
    $user = $event->getParam('user');
    echo "Welcome, {$user->name}!";
});

# Dispatch the event with a custom target and parameters
$user = (object)['name' => 'Alice'];
$dispatcher->dispatch('user.registered', $this, ['user' => $user]);
```

### Using the static façade

```php
use XB\Ripple\DispatcherStatic as EventBus;

# Register multiple listeners with different priorities
EventBus::addListener('order.placed', function ($event) {
    error_log('Order placed: ' . $event->getType());
    return true;
}, 100);

EventBus::addListener('order.placed', function ($event) {
    // This listener runs after the one above because it has lower priority
    mailAdmin($event);
    return false;
}, 10);

# Dispatch the event; both listeners fire and an array of responses is returned
$responses = EventBus::dispatch('order.placed', null, ['orderId' => 42]);
```

### Stopping propagation

A listener may stop propagation by calling `stopPropagation()` on the event. If the event is cancelable (default), subsequent listeners will not run:

```php
EventBus::addListener('invoice.sent', function (XB\Ripple\Event $e) {
    if (!shouldSend($e)) {
        $e->stopPropagation();
        return false;
    }
    sendInvoice($e);
    return true;
}, 50);

EventBus::addListener('invoice.sent', fn($e) => logAudit($e), 10);

# Only the first listener runs if propagation is stopped
EventBus::dispatch('invoice.sent');
```

### Dispatch until a condition is met

Use `dispatchUntil()` to fire listeners until one returns a truthy value:

```php
$result = $dispatcher->dispatchUntil('file.process', null, ['path' => '/tmp/data.csv']);
if ($result) {
    echo "File processed successfully";
} else {
    echo "No processor succeeded";
}
```

### Getting only the first response

If you only care about the first listener’s response, call `dispatchGetFirst()`:

```php
$result = EventBus::dispatchGetFirst('ping');
# $result contains the response from the highest priority listener
```

### Aggregate listeners

You can register an object as a listener aggregate. When dispatching a namespaced event like `cache:clear`, the dispatcher will look for a corresponding method (`clear()`) on the aggregate:

```php
class CacheListener {
    public function clear(XB\Ripple\Event $e) {
        Cache::clear();
    }
    public function warm(XB\Ripple\Event $e) {
        Cache::warm();
    }
}

$dispatcher->addListenerAggregate('cache', new CacheListener());

$dispatcher->dispatch('cache:clear'); # invokes CacheListener::clear()
$dispatcher->dispatch('cache:warm');  # invokes CacheListener::warm()
```

### Custom event classes

If you need to store additional data or behaviour on events, you can provide your own `Event` subclass. Set it on the dispatcher before dispatching events:

```php
class UserEvent extends XB\Ripple\Event {
    public function getUser(): User {
        return $this->getParam('user');
    }
}

$dispatcher->setEventClass(UserEvent::class);

$dispatcher->addListener('user.updated', function (UserEvent $e) {
    $user = $e->getUser();
    # ...
});

$dispatcher->dispatch('user.updated', null, ['user' => $user]);
```

## API reference

### `Dispatcher`

* `dispatch(string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false): mixed` – dispatches an event to all registered listeners and returns an array of responses or `null` if no listeners match.
* `dispatchUntil(string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false): array|null|bool` – dispatches listeners until one returns a truthy value and returns that response.
* `dispatchGetFirst(string|Event $event, ...)` – dispatches and returns the first response.
* `addListener(string $type, callable $listener, int $priority = 1): void` – adds a listener; higher priority listeners are called first.
* `addListenerAggregate(string $pattern, object $listener, int $priority = 1): void` – registers an object whose methods will be called for namespaced event types.
* `removeListener(string $type, callable $listener): bool` – removes a specific listener and returns whether it was removed.
* `removeListenersForEvent(string $type): int` – removes all listeners for an event type and returns the number removed.
* `removeAllListeners(): void` – clears all listeners.
* `getListenersForEvent(string $type): array` – returns an array of `ListenerDescriptor` objects for the given type.
* `getAllListeners(): array` – flattens all listeners into a single array.
* `getAllListenersStructured(): array` – returns listeners grouped by type.
* `hasListener(string $type): bool` – returns `true` if there is at least one listener for the type.

### `Event`

* Constructor: `__construct(string|null $type = null, string|object|null $target = null, mixed $params = null, bool $cancelable = true, bool $propagationStopped = false)`.
* `getType(): ?string` / `setType(string $type): static` – get or set the event type.
* `getTarget(): string|object|null` / `setTarget(string|object $target): static` – get or set the event target.
* `getParams(): mixed` / `setParams(mixed $params): static` – get or set all parameters.
* `getParam(string|int|null $name = null, mixed $default = null): mixed` – get a single parameter by name or index; returns the default if the parameter does not exist.
* `setParam(string|int $name, mixed $value): static` – set a single parameter.
* `isCancelable(): bool` / `setCancelable(bool $cancelable): static` – check or set whether the event propagation can be stopped.
* `isPropagationStopped(): bool` – returns `true` if propagation has been stopped.
* `stopPropagation(): void` – stops propagation if the event is cancelable.

### `DispatcherStatic`

All of the `Dispatcher` methods are exposed as static methods. A singleton dispatcher instance is lazily created the first time you call a static method.

## Running tests

The project includes a comprehensive PHPUnit test suite. To run it, install the development dependencies and execute:

```bash
vendor/bin/phpunit
```

The tests cover both the instance dispatcher and the static façade, including priority handling, parameter passing, propagation control and aggregate listeners.

## Contributing

Contributions are welcome! If you find a bug or want to propose an enhancement, please open an issue or submit a pull request. When submitting code, please:

* Follow PSR‑12 coding standards and use strongly typed method signatures.
* Write unit tests for new features or to reproduce fixed bugs.
* Ensure existing tests continue to pass.
* Update this documentation if necessary.

## License

Ripple is released under the MIT License.


