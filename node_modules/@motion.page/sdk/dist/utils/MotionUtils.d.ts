/**
 * Motion.utils — GSAP-compatible utility functions
 *
 * Drop-in replacements for common gsap.utils.* helpers.
 * Enables migration from GSAP custom code to the Motion SDK.
 *
 * @example Migration from GSAP
 * // Before: gsap.utils.toArray(".section").length
 * // After:  Motion.utils.toArray(".section").length
 */
/**
 * Convert a selector, NodeList, or value into a flat Array of Elements.
 * Drop-in replacement for gsap.utils.toArray().
 *
 * @param target - CSS selector string, NodeList, HTMLCollection, Element, or array
 * @param scope  - Optional parent element to scope selector queries
 * @returns Flat array of matched elements
 *
 * @example
 * Motion.utils.toArray(".h-scroll-section")          // all matching elements
 * Motion.utils.toArray(".item", containerEl)          // scoped to container
 * Motion.utils.toArray(document.querySelectorAll("p")) // NodeList → Array
 */
export declare function toArray<T extends Element = Element>(target: string | NodeList | HTMLCollection | Element | T[] | ArrayLike<T>, scope?: Element | Document): T[];
/**
 * Clamp a value between min and max.
 * Drop-in replacement for gsap.utils.clamp().
 *
 * Can be called with 3 args or curried with 2:
 * - `clamp(0, 100, 150)` → `100`
 * - `const c = clamp(0, 1); c(1.5)` → `1`
 *
 * @param min - Minimum allowed value
 * @param max - Maximum allowed value
 * @param value - Value to clamp (optional — returns curried function if omitted)
 */
export declare function clamp(min: number, max: number, value: number): number;
export declare function clamp(min: number, max: number): (value: number) => number;
/**
 * Generate a random number between min and max (inclusive).
 * Drop-in replacement for gsap.utils.random().
 *
 * @param min - Minimum value
 * @param max - Maximum value
 * @param snapIncrement - Optional increment to snap to (e.g., 5 → multiples of 5)
 * @returns Random number in the given range
 *
 * @example
 * Motion.utils.random(0, 100)       // 0–100 (float)
 * Motion.utils.random(0, 100, 10)   // 0, 10, 20, ... 100
 * Motion.utils.random(-1, 1)        // -1 to 1
 */
export declare function random(min: number, max: number, snapIncrement?: number): number;
/**
 * Snap a number to the nearest increment.
 * Drop-in replacement for gsap.utils.snap().
 *
 * @param snapTo - Increment value or array of values to snap to
 * @param value  - Value to snap (optional — returns curried function if omitted)
 *
 * @example
 * Motion.utils.snap(5, 13)        // 15
 * Motion.utils.snap([0, 25, 50, 100], 30)  // 25
 * const s = Motion.utils.snap(10); s(27)   // 30
 */
export declare function snap(snapTo: number | number[], value: number): number;
export declare function snap(snapTo: number | number[]): (value: number) => number;
/**
 * Linear interpolation between two values.
 * Drop-in replacement for gsap.utils.interpolate() (simple 2-value case).
 *
 * @param start - Start value
 * @param end   - End value
 * @param progress - Interpolation factor (0–1)
 * @returns Interpolated value
 *
 * @example
 * Motion.utils.interpolate(0, 100, 0.5)  // 50
 */
export declare function interpolate(start: number, end: number, progress: number): number;
/**
 * Map a value from one range to another.
 * Drop-in replacement for gsap.utils.mapRange().
 *
 * @param inMin  - Input range minimum
 * @param inMax  - Input range maximum
 * @param outMin - Output range minimum
 * @param outMax - Output range maximum
 * @param value  - Value to map (optional — returns curried function if omitted)
 *
 * @example
 * Motion.utils.mapRange(0, 100, 0, 1, 50)  // 0.5
 * const map = Motion.utils.mapRange(0, 1, -100, 100); map(0.75) // 50
 */
export declare function mapRange(inMin: number, inMax: number, outMin: number, outMax: number, value: number): number;
export declare function mapRange(inMin: number, inMax: number, outMin: number, outMax: number): (value: number) => number;
/**
 * Normalize a value from a range to 0–1.
 * Drop-in replacement for gsap.utils.normalize().
 *
 * @param min   - Range minimum
 * @param max   - Range maximum
 * @param value - Value to normalize (optional — returns curried function if omitted)
 *
 * @example
 * Motion.utils.normalize(0, 100, 25)  // 0.25
 */
export declare function normalize(min: number, max: number, value: number): number;
export declare function normalize(min: number, max: number): (value: number) => number;
/**
 * Wrap a value within a range (modular arithmetic).
 * Drop-in replacement for gsap.utils.wrap().
 *
 * @param min   - Range minimum
 * @param max   - Range maximum
 * @param value - Value to wrap (optional — returns curried function if omitted)
 *
 * @example
 * Motion.utils.wrap(0, 100, 150)  // 50
 * Motion.utils.wrap(0, 360, -90)  // 270
 */
export declare function wrap(min: number, max: number, value: number): number;
export declare function wrap(min: number, max: number): (value: number) => number;
/**
 * The complete utils namespace, attached to Motion.utils
 */
export declare const MotionUtils: {
    readonly toArray: typeof toArray;
    readonly clamp: typeof clamp;
    readonly random: typeof random;
    readonly snap: typeof snap;
    readonly interpolate: typeof interpolate;
    readonly mapRange: typeof mapRange;
    readonly normalize: typeof normalize;
    readonly wrap: typeof wrap;
};
