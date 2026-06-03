/**
 * GlobalScrollListener
 *
 * Consolidates per-instance scroll listeners into a single listener per scroller.
 * This mirrors GSAP's architecture where one global scroll handler reads scrollY
 * once and dispatches to all active ScrollTrigger instances in a single pass.
 *
 * Benefits:
 * - Eliminates N scroll event listeners (one per ScrollTrigger) → 1 per scroller
 * - For instant scrub: wraps all instance updates in a single runBatched() call,
 *   so DOM writes are flushed once instead of N times per scroll event
 * - Reads scrollY once per scroll event, shared across all instances
 *
 * Architecture:
 * - Each unique scroller (window or custom element) gets ONE passive scroll listener
 * - On scroll, all registered callbacks for that scroller run inside a single
 *   runBatched() wrapper (batches instant-scrub DOM writes into one flush)
 * - When the last callback for a scroller is removed, the listener is detached
 */
type ScrollCallback = (scrollTop: number) => void;
/**
 * Register a scroll callback for a given scroller.
 * The first registration for a scroller attaches a single passive scroll listener.
 */
export declare function addScrollCallback(scroller: EventTarget, callback: ScrollCallback): void;
/**
 * Remove a scroll callback for a given scroller.
 * When the last callback is removed, the scroll listener is detached.
 */
export declare function removeScrollCallback(scroller: EventTarget, callback: ScrollCallback): void;
/**
 * Reset all state (for testing only).
 * @internal
 */
export declare function _resetGlobalScrollListener(): void;
export {};
