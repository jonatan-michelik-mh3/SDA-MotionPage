/**
 * Ticker Singleton - Single RAF loop for all animations
 *
 * Manages the requestAnimationFrame loop with:
 * - Lag smoothing (cap delta when tab backgrounded)
 * - Priority-based listener ordering
 * - Automatic start/stop based on listener count
 * - Batched DOM writes at end of frame via RenderBatch
 */
/**
 * Fast check if we're inside a tick (exported for hot path performance)
 * Use this instead of Ticker.getInstance().isInTick() in render code
 */
export declare function isInTick(): boolean;
/**
 * Run a callback in "tick mode" — writes are batched, then flushed at the end.
 *
 * Use this when updating timeline progress from outside the RAF loop
 * (e.g. scroll event handlers) to avoid layout thrashing from immediate
 * per-property DOM writes. The callback's DOM mutations are queued via
 * RenderBatch and flushed in a single pass after the callback returns.
 */
export declare function runBatched(callback: () => void): void;
type TickerListener = (deltaTime: number, elapsedTime: number) => void;
export declare class Ticker {
    private static instance;
    private listeners;
    private _listenersDirty;
    private _listenersSnapshot;
    private _isRunning;
    private rafId;
    private lastTime;
    private elapsedTime;
    private lagThreshold;
    private maxDelta;
    private fpsFrames;
    private fpsTime;
    private currentFPS;
    private lastFrameTime;
    private constructor();
    /**
     * Get the singleton Ticker instance
     */
    static getInstance(): Ticker;
    /**
     * Add a listener to the ticker
     * @param callback Function to call on each tick
     * @param priority Higher priority callbacks execute first (default: 0)
     */
    add(callback: TickerListener, priority?: number): void;
    /**
     * Remove a listener from the ticker
     * @param callback Function to remove
     */
    remove(callback: TickerListener): void;
    /**
     * Start the RAF loop
     */
    private start;
    /**
     * Stop the RAF loop
     */
    private stop;
    /**
     * Pause the ticker (for debug purposes)
     */
    pause(): void;
    /**
     * Resume the ticker (for debug purposes)
     */
    resume(): void;
    /**
     * Main tick function called by RAF
     */
    private tick;
    /**
     * Update FPS calculation
     */
    private updateFPS;
    /**
     * Get current FPS
     */
    getFPS(): number;
    /**
     * Get last frame delta time in ms
     */
    getLastDelta(): number;
    /**
     * Check if ticker is running
     */
    isRunning(): boolean;
    /**
     * Check if we're currently inside a tick
     * Used by render system to decide: queue (in tick) or write immediately (outside tick)
     * Note: For hot path performance, use the exported isInTick() function instead
     */
    isInTick(): boolean;
    /**
     * Get listener count
     */
    getListenerCount(): number;
    /**
     * Remove all listeners (for testing)
     */
    removeAll(): void;
}
export {};
