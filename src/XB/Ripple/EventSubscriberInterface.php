<?php

declare( strict_types=1 );

namespace XB\Ripple;

/**
 * A subscriber declares, in one place, every event it listens to and the
 * method that handles each. This replaces the old colon-aggregate object
 * whose method names were matched implicitly at dispatch time.
 *
 * Register with {@see Dispatcher::addSubscriber()} and detach with
 * {@see Dispatcher::removeSubscriber()}.
 *
 * The map returned by {@see getSubscribedEvents()} keys an event name (which
 * may contain a `*` wildcard) to one of:
 *   - 'methodName'
 *   - ['methodName', $priority]
 *   - [['method1', $priority1], ['method2', $priority2], ...]
 *
 * Example:
 * <code>
 * public static function getSubscribedEvents(): array
 * {
 *     return [
 *         'user.login'   => 'onLogin',
 *         'user.logout'  => ['onLogout', 100],
 *         'order.*'      => [['audit', 200], ['notify', -10]],
 *     ];
 * }
 * </code>
 */
interface EventSubscriberInterface
{
	/**
	 * @return array<string, string|array{0: string, 1: int}|array<array{0: string, 1: int}>>
	 */
	public static function getSubscribedEvents(): array;
}
