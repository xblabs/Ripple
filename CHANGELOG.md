# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]

A correctness- and interoperability-focused rewrite. See [UPGRADE.md](UPGRADE.md) for migration steps.

### Fixed

- **Aggregate stale-listener re-invocation** — a listener that did not handle a routed event could cause the previously
  matched listener to fire twice. The implicit method-routing path that caused this has been removed.
- **`useParamsAsCallbackArg` leaking across listeners** — the "spread params as arguments" decision is now made
  per-listener at registration, so one multi-argument closure no longer forces later listeners to receive raw params.
- **`Event::setParam()` crash** — calling `setParam()` on an event whose params are `null` (the default) or a scalar now
  lazily initializes an array instead of throwing a fatal `Error`.
- **`removeListener()` + `removeListenersForEvent()`** — removals no longer leave sparse array keys, which previously
  produced an undefined-key warning and an incorrect removal count.
- **Falsy target/params dropped** — `dispatch()` no longer discards falsy-but-valid values (`0`, `'0'`, `''`, `false`,
  `[]`) for the target or params.
- **Colon event names unreachable** — an event name containing `:` is now an ordinary name and reaches its exact
  listeners; in 1.x it was force-routed to the aggregate system.

### Added

- `once()` / `onceWildcard()` — one-time listeners that remove themselves after firing.
- `addWildcardListener()` / `removeWildcardListener()` / `getWildcardListeners()` — `*` glob patterns matched against
  event names, separator-agnostic.
- `EventSubscriberInterface` with `addSubscriber()` / `removeSubscriber()` — declare all handlers in one place.
- PSR-14 support: `Event` implements `StoppableEventInterface`; new `Psr14\Psr14Dispatcher` (`EventDispatcherInterface`)
  and `Psr14\ListenerProvider` (`ListenerProviderInterface`). Provides `psr/event-dispatcher-implementation`.
- `DispatcherStatic::setDispatcher()` and `DispatcherStatic::reset()` for injectable/resettable static usage.
- `Dispatcher::getEventClass()` and `Dispatcher::resolveDescriptors()`.
- Tooling: PHPStan (level 8), PHP-CS-Fixer, GitHub Actions CI (PHP 8.1–8.4), PHPUnit coverage config.

### Changed

- **Minimum PHP is now 8.1** (was 8.0).
- **Default listener priority is now `0`** (was `1`).
- Listener ordering and the closure-parameter reflection are computed once at registration and cached on
  `ListenerDescriptor`, instead of on every dispatch.
- `ListenerDescriptor` is now `final` with `readonly` promoted properties and additional metadata fields.
- `IDispatcher` now describes the full public surface; `DispatcherStatic::dispatchGetFirst()` no longer double-reduces.
- All source files declare `strict_types=1`.

### Removed

- `addListenerAggregate()` and the implicit `component:method` colon-routing. Use wildcard listeners and/or
  `EventSubscriberInterface` instead.
