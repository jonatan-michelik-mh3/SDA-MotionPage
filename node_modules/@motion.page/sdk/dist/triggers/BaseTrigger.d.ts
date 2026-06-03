/**
 * BaseTrigger - Abstract base class for all standard trigger types
 *
 * Extracts the common enable/disable lifecycle pattern shared by
 * ScrollTrigger, EventTrigger, MouseMoveTrigger, GestureTrigger, and CursorTrigger.
 *
 * Provides:
 * - enable/disable guards with _enabled flag
 * - DOM-ready gating via whenDOMReady
 * - Shared helper methods for selector resolution and listener cleanup
 *
 * NOT used by PageLoadTrigger (which has a different lifecycle pattern).
 */
import type { Timeline } from '../core/Timeline';
export declare abstract class BaseTrigger<TConfig> {
    protected _timeline: Timeline;
    protected _config: TConfig;
    protected _enabled: boolean;
    constructor(timeline: Timeline, config: TConfig);
    /**
     * Enable the trigger. Guards against double-enable and waits for DOM ready.
     * Calls _onEnable() once the DOM is ready (or immediately if already ready).
     */
    enable(): void;
    /**
     * Disable the trigger. Guards against double-disable.
     * Calls _onDisable() immediately (no DOM-ready gating needed for teardown).
     */
    disable(): void;
    /**
     * Subclass: set up event listeners, resolve targets, start ticker loops, etc.
     * Called once per enable cycle after DOM is ready.
     */
    protected abstract _onEnable(): void;
    /**
     * Subclass: remove event listeners, clear state, stop ticker loops, etc.
     * Called once per disable cycle.
     */
    protected abstract _onDisable(): void;
    /**
     * Resolve a single element from a string selector or Element reference.
     * Returns the fallback (defaults to null) if selector is undefined, not found, or invalid.
     *
     * Note: non-string values are treated as direct element references without an instanceof
     * check, so this works with both real DOM elements and mock objects in tests.
     */
    protected _resolveElement(selector: string | Element | undefined, fallback?: EventTarget | null): HTMLElement | EventTarget | null;
    /**
     * Resolve multiple elements from a string selector or single Element reference.
     * Returns an empty array if selector is undefined, not found, or invalid.
     *
     * Note: non-string values are treated as direct element references without an instanceof
     * check, so this works with both real DOM elements and mock objects in tests.
     */
    protected _resolveElements(selector: string | Element | undefined): Element[];
    /**
     * Remove all event listeners tracked in a nested listener map and clear the map.
     * Accepts Map<EventTarget, Map<eventName, listener>>.
     */
    protected _cleanupListenerMap(listeners: Map<EventTarget, Map<string, EventListener>>): void;
}
