# Ripple

Ripple is a PHP event dispatcher library that allows you to create event-driven systems by providing a simple and
easy-to-use interface for handling events.

An event dispatcher can help decouple different parts of an application by allowing components to communicate through
events rather than tight couplings or direct function calls. This makes the code more maintainable and flexible.

## Features

- Simple event dispatching and handling
- Easy-to-use interface for adding, removing, and retrieving event listeners
- Support for priority-based event handling
- Ability to stop event propagation
- Aggregate listeners for event types
- Unit-tested and well-documented

## Use Cases

Some common use cases where an event dispatcher is helpful:

- **Loose coupling** - Dispatching events allows loosely coupled communication between different classes/components
  without needing to know concrete implementations.

- **Signal/notification** - An event dispatcher provides a way to broadcast notifications across the app when certain
  things occur, like user login or data updates.

- **Hooks/plugin support** - By dispatching events you can allow plugins/extensions to hook in and augment behavior in a
  decoupled way.

- **Async/queue** - Events dispatched through a queue allow async, deferred execution of tasks in the background.

- **Logging/auditing** - Hooking into dispatch events provides a way to log or audit interactions and processes.

- **Workflow** - Complex workflows can be built by linking dispatcher events to trigger actions and flow through a
  process.

The main advantages are reduced coupling, increased flexibility, and improved separation of concerns in your application
architecture.

Let me know if you would like me to expand on any of these points or provide additional examples of when using an event
dispatcher makes sense.

## Installation

This library can be installed via [Composer](https://getcomposer.org/):

```bash
composer require xtrabyteslab/ripple
```

Or add the following to your `composer.json` file:

```json
{
  "require": {
    "xtrabyteslab/ripple": "^1.0"
  }
}
```

Then run `composer install` to install the library:

```bash
composer install
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then instantiate classes from the `XB\Ripple` namespace:

```php
$dispatcher = new XB\Ripple\Dispatcher();
```

The library also includes unit tests, you can run them using PHPUnit after installing dev dependencies:

```bash
composer install --dev
./vendor/bin/phpunit
```

## Usage


### Instantiated Dispatcher, Singleton

There are two ways to dispatch events : 1) using the dispatcher instance and 2) using the static dispatcher.

In order to use the dispatcher instance, it is best to use a dependency injection container or a registry to instantiate
the dispatcher and then inject it into the classes that need it to be able to centralize the event handling into a
single dispatcher instance.

If you only want to use one dispatcher instance to manage every aspect of your application, you can use the static
dispatcher instead of the instance dispatcher or you can use the Singleton pattern to instantiate the dispatcher.

```php

### Singleton pattern - best to use when you want to use only one dispatcher instance in your application
// in application component A
$dispatcher = Dispatcher::getInstance();
// use to dispatch events : $dispatcher->dispatch(...) 

// in application component B
// get the same instance to register listeners 
$dispatcher = Dispatcher::getInstance();
// use to register listeners : $dispatcher->addListener(...) 



### Dependency Injection Container - best to use when you want to use multiple dispatcher instances in your application
// stub container ... use your own DI container implementation!
// set dispatcher instance in container
$container->set('dispatcher', new Dispatcher());
// retrieve dispatcher instance from container in another part of the application
$dispatcher = $container->get('dispatcher');



### Registry pattern
// stub registry ... use your own registry implementation!
// register dispatcher instance in registry
$registry->set('dispatcher', new Dispatcher());
// retrieve dispatcher instance from registry in aother part of the application
$dispatcher = $registry->get('dispatcher');

```

### Dispatching Events

```php
use XB\Ripple\Event;
use XB\Ripple\Dispatcher;

// retrieve $dispatcher from DI container or registry or use singleton pattern as above

// simple event dispatching
$dispatcher->dispatch("myEventName");
DispatcherStatic::dispatch("myEventName");

// event dispatching either to be used with single listener or via listener aggregates ( see below )
$dispatcher->dispatch("user:comment");
DispatcherStatic::dispatch("user:comment");

// Dispatch and get first result ( from registered listeners )
$result = $dispatcher->dispatchGetFirst("user:login");
$result = DispatcherStatic::dispatchGetFirst("user:login");

// Dispatch Event object with target and params... $user is the target of the event and is an arbitrary object here,  ["id" => 123] is the params array which is accessible in the event object through the listener
$dispatcher->dispatch( new Event("user:login"), $user, ["id" => 123]);
DispatcherStatic::dispatch( new Event("user:login"), $user, ["id" => 123]);

// Dispatch until listener returns non-null
$result = $dispatcher->dispatchUntil("user:login");
$result = DispatcherStatic::dispatchUntil("user:login");

```


#### Halting Propagation

```php
// Add listener that cancels event from propagating to other listeners
$dispatcher->addListener("event.name", function(Event $e) {
  $e->stopPropagation();
});

// Dispatch non-cancelable events 
$event = new Event("event.name", null, null, false);
$dispatcher->dispatch( $event );
```



### Listeners



#### Listener Closures

```php
use XB\Ripple\Event;
use XB\Ripple\Dispatcher;
use XB\Ripple\DispatcherStatic;

// directly instantiated or part of the class or retrieved via registry or DI
//$dispatcher = new Dispatcher();

// Add event listener for event with name "user.login"
$dispatcher->addListener("user:login", static function( Event $e ) {
  // listens to the specific login event
});

// Static registration, if you don't want to use DI or registry
DispatcherStatic::addListener( 'user:login',static function( Event $e ) {
  // listens to the specific login event
});

```

#### Listener methods in a class

```php
use XB\Ripple\Event;
use XB\Ripple\Dispatcher;
use XB\Ripple\DispatcherStatic;

# listener registration inside a class instance. Class has a public method "listenerA"
class MyClass {

  public function userLoginListener( Event $e ) {
    // do something
  }
  public function invokingDispatcherRegistration() {
    //... instance part of the class or retrieved via registry or DI
    $dispatcher->addListener("user.login", [$this, 'userLoginListener']);
    
    // Static registration, if you don't want to use DI or registry
    DispatcherStatic::addListener( 'user.login', [$this, 'userLoginListener'] );
    
    // or
    $listener = Closure::fromCallable( [ $this, 'userLoginListener' ] );
    $dispatcher->addListener("user.login", $listener); 
    DispatcherStatic::addListener( 'user.login', $listener );
  }
}

```

#### Listener Priorities

```php
$eventPriorityHigh = 100; // default is 0
$eventPriorityLow = -10; // default is 0
$dispatcher->addListener("event.name", $listenerCallback, $eventPriorityLow );
$dispatcher->addListener("event.name", $listenerCallback2, $eventPriorityHigh ); // gets always invoked first

```

#### Removing Listeners

```php
// Remove a single listener
Dispatcher::removeListener("event.name", $listener);

// Remove all listeners for an event
Dispatcher::removeListenersForEvent("event.name"); 

// Remove all listeners
Dispatcher::removeAllListeners();
```


#### Listener Aggregates

```php
use XB\Ripple\Event;
use XB\Ripple\Dispatcher;

$dispatcher->addListenerAggregate("user", static function( Event $e ) {
  // listeners to all events that start with "user" in the event name
  // will receive all 4 events below
  // $e->getType() will return the event name ( e.g. "user.login" )
});
$dispatcher->addListenerAggregate("user.comment", static function( Event $e ) {
  // listeners to all events that start with "user.comment" in the event name
  // will receive only the last 2 events below
    switch ($e->getType()) {
        case 'user.login':
            // do something
            break;
        case 'user.logout':
            // do something else
            break;
    }
});


$listener = new class {

    public array $registeredEvents = [];
    public bool $loginCalledInSpecificListenerMethod = false;
    public bool $commentAddCalled = false;

    // listener for all events that are not specifically mapped to a listener method
    public function __invoke(Event $e): void
    {
        $this->registeredEvents[] = $e->getType();

        switch ($e->getType()) {
            case 'user.login':
                // do something
                break;
            case 'user.logout':
                // do something else
                break;
        }
    }

    // listener for "user.login" event
    public function login( Event $e ): void
    {
        $this->registeredEvents[] = $e->getType();
        $this->loginCalledInSpecificListenerMethod = true;
    }

    // listener for "user.comment.add" event
    public function comment_add( Event $e ): void
    {
        $this->registeredEvents[] = $e->getType();
        $this->commentAddCalled = true;
    }

};

$dispatcher->addListenerAggregate("user", $listener); // listener to all events that start with "user" in the event name
$dispatcher->addListenerAggregate("user.comment", $listener); // listener to all events that start with "user.comment" in the event name

        
// Dispatch event by name 
// an aggregate token ends with "."
$dispatcher->dispatch("user.login");
$dispatcher->dispatch("user.logout");
$dispatcher->dispatch("user.comment.add");
$dispatcher->dispatch("user.comment.edit");
```