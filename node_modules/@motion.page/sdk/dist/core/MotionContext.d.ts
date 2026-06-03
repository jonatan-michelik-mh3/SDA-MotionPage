/**
 * MotionContext — Scoped animation lifecycle management
 *
 * Tracks all timelines created during a callback execution, enabling
 * clean teardown and re-initialization when DOM content changes.
 *
 * Solves the universal "dynamic content" problem:
 * - WordPress AJAX filters (Bricks, WooCommerce, Jetpack)
 * - SPA navigation (React, Astro view transitions, Next.js)
 * - Infinite scroll / pagination
 * - Any scenario where DOM elements are replaced without a full page reload
 *
 * @example WordPress (generated code)
 * const ctx = Motion.context(() => {
 *   Motion('hero', '.title', { from: { opacity: 0 }, to: { opacity: 1 } }).onPageLoad()
 *   Motion('cards', '.card', { from: { y: 100 }, to: { y: 0 } }).onScroll({ scrub: true })
 * })
 * // After AJAX filter replaces content:
 * ctx.refresh()
 *
 * @example React
 * useEffect(() => {
 *   const ctx = Motion.context(() => {
 *     Motion('fade', '.box', { from: { opacity: 0 }, to: { opacity: 1 } }).onScroll({ scrub: true })
 *   })
 *   return () => ctx.revert()
 * }, [])
 *
 * @example Astro (view transitions)
 * document.addEventListener('astro:page-load', () => {
 *   window._ctx?.revert()
 *   window._ctx = Motion.context(() => { ... })
 * })
 */
export declare class MotionContext {
    /** The init function that creates animations — re-executed on refresh() */
    private _fn;
    /** Timeline names created within this context */
    private _timelineNames;
    /** Whether this context has been reverted (prevents double-revert) */
    private _reverted;
    constructor(fn: () => void);
    /**
     * Kill all timelines created in this context, restore elements to their
     * initial CSS state, and clean up DOM artifacts (ScrollTrigger spacers/markers).
     *
     * After revert(), the context is empty and can be refreshed or discarded.
     */
    revert(): void;
    /**
     * Revert all animations and re-execute the init function.
     * Selectors are re-resolved against the current DOM, so new/replaced
     * elements are picked up automatically.
     *
     * This is the primary method for handling dynamic content changes.
     */
    refresh(): void;
    /**
     * Add more animations to this context without affecting existing ones.
     * Useful for lazy-loaded content or progressive enhancement.
     *
     * @example
     * ctx.add(() => {
     *   Motion('lazy-section', '.lazy-card', { ... }).onScroll({ scrub: true })
     * })
     */
    add(fn: () => void): void;
    /**
     * Get the timeline names tracked by this context (for debugging/testing).
     */
    getTimelineNames(): string[];
    /**
     * Execute a function while capturing timeline names into this context.
     * Uses Engine's capture mechanism for zero-overhead tracking.
     */
    private _execute;
}
