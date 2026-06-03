/**
 * RenderBatch - Batched DOM write system
 *
 * Collects all property changes during a frame and flushes them
 * in a single batch at frame end. This eliminates layout thrashing
 * and reduces DOM writes from O(properties) to O(elements).
 *
 * Key optimizations:
 * - One transform string build per element (not per property)
 * - One DOM write per element for transforms
 * - Batched CSS property writes
 * - Zero allocations during animation (uses Sets/Maps)
 */
/**
 * Queue a transform update for an element
 * Called after setTransformValue updates the cache
 */
export declare function queueTransform(element: Element): void;
/**
 * Queue a CSS property update for an element
 */
export declare function queueStyle(element: Element, property: string, value: string): void;
/**
 * Flush all pending DOM writes
 * Called once at end of each animation frame
 */
export declare function flush(): void;
