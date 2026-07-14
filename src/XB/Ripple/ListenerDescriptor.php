<?php

declare( strict_types=1 );

namespace XB\Ripple;

/**
 * Immutable metadata record for a single registered listener.
 *
 * All routing decisions that used to be recomputed on every dispatch
 * (priority ordering, the raw-params heuristic, wildcard matching) are
 * precomputed once at registration and cached here.
 */
final class ListenerDescriptor
{
	public function __construct(
		/** Event name for exact listeners, or the glob pattern for wildcard listeners. */
		public readonly string $type,
		/** The original callable, kept verbatim so removeListener() can match by identity (===). */
		public readonly mixed $listener,
		public readonly int $priority = 0,
		/** Monotonic registration counter, used as a stable LIFO tie-break for equal priorities. */
		public readonly int $sequence = 0,
		/** True for one-time listeners that self-remove after their first invocation. */
		public readonly bool $once = false,
		/** True when {@see $type} is a wildcard pattern matched via {@see $regex}. */
		public readonly bool $isWildcard = false,
		/** Precomputed: invoke with the event's params spread as args instead of the Event object. */
		public readonly bool $expectsRawParams = false,
		/** Anchored regex compiled from a wildcard pattern; null for exact listeners. */
		public readonly ?string $regex = null
	)
	{
	}
}
