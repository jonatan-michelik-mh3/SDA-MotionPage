/**
 * TriggerManager
 *
 * Coordinates all trigger types (pageLoad, pageExit, scroll, hover, click, mouseMove, gesture)
 * and activates timelines based on trigger conditions.
 *
 * Uses a registry pattern for trigger classes to avoid importing them directly.
 * Each trigger module registers itself via TriggerManager.registerTrigger() when loaded.
 * This enables tree-shaking: only trigger types included in the bundle are available.
 */
import type { ScrollConfig, MouseMoveConfig, GestureConfig, HoverConfig, ClickConfig, CursorConfig, PageExitConfig } from '../types';
import type { Timeline } from '../core/Timeline';
/** Config for page load trigger */
export interface PageLoadTriggerConfig {
    timing?: 'before' | 'during' | 'after';
    /** When true, the timeline is built but not played — use Motion('name').play() to start it manually */
    paused?: boolean;
}
/** Interface that trigger instances must satisfy for cleanup */
interface TriggerInstance {
    enable(): void;
    disable(): void;
}
/** Interface for PageLoadTrigger specifically */
interface PageLoadTriggerInstance {
    register(timeline: Timeline, config: PageLoadTriggerConfig): void;
    unregister(timeline: Timeline): void;
    clear(): void;
    _reset(): void;
}
/** Trigger class constructors stored in registry */
type TriggerFactory = new (...args: any[]) => TriggerInstance;
export declare class TriggerManager {
    private static _instance;
    /** Registry for trigger class constructors - populated by each trigger module on load */
    private static _triggerRegistry;
    /** PageLoadTrigger factory - separate because it has a different interface */
    private static _pageLoadTriggerFactory;
    /** Debounce delay (ms) for the window resize → refreshScrollTriggers handler */
    private static readonly _RESIZE_DEBOUNCE_MS;
    /** Debounce delay (ms) for the body ResizeObserver → refreshScrollTriggers handler */
    private static readonly _BODY_RESIZE_DEBOUNCE_MS;
    private _pageLoadTrigger;
    private _scrollTriggers;
    private _eventTriggers;
    private _mouseMoveTriggers;
    private _gestureTriggers;
    private _cursorTriggers;
    private _pageExitTriggers;
    /** Bound resize handler (created once, reused for add/removeEventListener) */
    private _resizeHandler;
    /** Timer id for debounced resize */
    private _resizeTimer;
    /** ResizeObserver watching document.body for height changes (layout shifts) */
    private _bodyResizeObserver;
    /** Last known body height — only refresh when height actually changes */
    private _lastBodyHeight;
    /** Timer id for debounced body resize */
    private _bodyResizeTimer;
    /** All trigger maps — used by unregister() and killAll() for bulk iteration */
    private get _allTriggerMaps();
    private constructor();
    static getInstance(): TriggerManager;
    /**
     * Register a trigger class. Called by each trigger module when it loads.
     * @internal
     */
    static registerTrigger(type: string, factory: TriggerFactory): void;
    /**
     * Register the PageLoadTrigger class. Called by PageLoadTrigger module when it loads.
     * @internal
     */
    static registerPageLoadTrigger(factory: new () => PageLoadTriggerInstance): void;
    /** Get or create the PageLoadTrigger instance */
    private _getPageLoadTrigger;
    /** Create a trigger instance from the registry */
    private _createTrigger;
    /**
     * Register a pageLoad trigger
     */
    registerPageLoad(timeline: Timeline, config: PageLoadTriggerConfig): void;
    /**
     * Attach a debounced window resize listener that refreshes all scroll triggers.
     * Called lazily when the first scroll trigger is registered.
     * @internal
     */
    private _attachResizeListener;
    /**
     * Detach the window resize listener.
     * Called when the last scroll trigger is removed or killAll() is invoked.
     * @internal
     */
    private _detachResizeListener;
    /**
     * Attach a ResizeObserver on document.body to detect document height changes.
     *
     * Layout shifts caused by content-visibility:auto, lazy-loaded images,
     * dynamic content insertion, or font loading can change the document height
     * without triggering a window resize event.  This observer detects those
     * shifts and refreshes all scroll triggers so pin positions stay correct.
     *
     * Only reacts to height changes (width changes are already covered by the
     * window resize listener).  Debounced to avoid excessive refreshes during
     * rapid layout shifts (e.g. multiple images loading in sequence).
     * @internal
     */
    private _attachBodyResizeObserver;
    /**
     * Detach the body ResizeObserver.
     * Called when the last scroll trigger is removed or killAll() is invoked.
     * @internal
     */
    private _detachBodyResizeObserver;
    /**
     * Register a scroll trigger
     */
    registerScroll(timeline: Timeline, config: ScrollConfig): void;
    /**
     * Register a hover trigger
     */
    registerHover(timeline: Timeline, config: HoverConfig): void;
    /**
     * Register a click trigger
     */
    registerClick(timeline: Timeline, config: ClickConfig): void;
    /**
     * Register a mouseMove trigger
     */
    registerMouseMove(timeline: Timeline, config: MouseMoveConfig): void;
    /**
     * Register a gesture trigger
     */
    registerGesture(timeline: Timeline, config: GestureConfig): void;
    /**
     * Register a cursor trigger
     */
    registerCursor(timeline: Timeline, config: CursorConfig): void;
    /**
     * Register a page exit trigger
     */
    registerPageExit(timeline: Timeline, config: PageExitConfig): void;
    /**
     * Unregister a timeline
     */
    unregister(timeline: Timeline): void;
    /**
     * Refresh all position-based triggers after layout changes (e.g. window resize).
     * This includes scroll triggers, hover event triggers, gesture triggers, and
     * mouseMove triggers — all of which cache element bounds at setup time.
     *
     * Scroll triggers are refreshed in DOM document order (top to bottom).
     * This ensures that when a pinned trigger's spacer size changes during refresh,
     * triggers lower in the page measure the already-updated spacer positions rather
     * than stale pre-refresh values.  Non-pinned triggers (no pin manager / layout
     * rect) are always sorted after pinned ones so they benefit from all spacer
     * updates before recalculating their own positions.
     */
    refreshScrollTriggers(): void;
    /**
     * Kill all triggers
     */
    killAll(): void;
    /**
     * Reset for testing - DO NOT USE IN PRODUCTION
     * Resets the singleton instance
     * @internal
     */
    static _reset(): void;
}
export {};
