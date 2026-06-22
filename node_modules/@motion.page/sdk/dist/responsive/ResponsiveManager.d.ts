/**
 * ResponsiveManager (Responsive Editing — Stage 2, reactive SDK runtime)
 *
 * Registers a set of per-breakpoint timeline variants and live-swaps between
 * them as the viewport crosses a breakpoint boundary. This upgrades the Stage 1
 * compile-time output (N mutually-exclusive `if(matchMedia(...).matches)` blocks
 * evaluated once at load) to a single `Motion.responsive(...)` call whose active
 * variant follows the viewport in real time.
 *
 * The breakpoint queries are byte-for-byte the SAME strict, non-overlapping
 * mutual-exclusion ranges used by Stage 1 `buildResponsiveBlocks` (RD-2):
 *
 *   desktop  screen and (min-width: L+1)
 *   laptop   screen and (min-width: T+1) and (max-width: L)
 *   tablet   screen and (min-width: P+1) and (max-width: T)
 *   phone    screen and (max-width: P)
 *
 * Exactly one query matches any given width, so the active tier is unambiguous.
 *
 * On activation the manager KILLS the previously-active variant's timeline
 * (cleaning up its triggers + restoring element state) and then invokes the new
 * tier's factory, which rebuilds and (re)registers the timeline. A variant
 * factory returns the `Timeline` it builds so the manager can kill it on the
 * next swap.
 *
 * Tree-shaking: this module self-registers a factory on `SDKRegistry.responsive`
 * and is referenced by `Motion.responsive` only through that registry — never via
 * a static import. It is therefore only bundled when a project actually has a
 * responsive timeline (see `feature:responsive` in the sdk-generator bundler).
 */
/** The four tiers in cascade order (widest → narrowest). */
export type ResponsiveTier = 'desktop' | 'laptop' | 'tablet' | 'phone';
/**
 * A variant factory: builds + registers the tier's timeline and returns it.
 * Returning the timeline lets the manager kill it precisely on the next swap.
 * A factory MAY return nothing (e.g. a tier whose nodes are all disabled);
 * in that case the manager simply has no active timeline for that tier.
 */
export type ResponsiveVariant = () => {
    kill: (clearProps?: boolean) => void;
} | void;
/** Map of tier → variant factory. Tiers may be sparse; missing tiers cascade up. */
export type ResponsiveVariants = Partial<Record<ResponsiveTier, ResponsiveVariant>>;
/** Global breakpoint pixel boundaries (same values the builder stores). */
export interface ResponsiveBreakpointConfig {
    laptops: number;
    tablets: number;
    phones: number;
}
export declare class ResponsiveManager {
    private variants;
    private queries;
    private activeTier;
    private activeTimeline;
    private destroyed;
    /**
     * Register the variants and wire up matchMedia listeners. Immediately
     * activates whichever tier currently matches the viewport.
     *
     * Re-entrant: calling register() again (or after destroy()) first tears down
     * any previously-wired listeners and active timeline via destroy(), then
     * re-arms from a clean slate. This prevents matchMedia 'change' listeners from
     * accumulating across re-registrations (which would otherwise leak listeners
     * and trigger duplicate rebuilds on every breakpoint crossing).
     *
     * In non-DOM / no-matchMedia environments it falls back to building the
     * desktop variant a single time (no live swapping).
     */
    register(variants: ResponsiveVariants, config: ResponsiveBreakpointConfig): void;
    /** Re-evaluate which tier matches and swap if it changed. */
    private handleChange;
    /**
     * Kill the current timeline and build the variant for `tier`. When the tier
     * has no own variant, cascade UP to the nearest wider tier that does (so a
     * sparse variant map still covers every width).
     */
    private activateTier;
    /** Kill the active timeline if any, swallowing teardown errors. */
    private killActive;
    /** The tier currently driving the preview (for tests / debugging). */
    getActiveTier(): ResponsiveTier | null;
    /**
     * Tear down all listeners and kill the active timeline. Idempotent.
     */
    destroy(): void;
}
