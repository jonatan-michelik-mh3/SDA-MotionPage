/**
 * FilterParser - Parse CSS filter strings for animation
 *
 * Supports:
 * - blur(px)
 * - brightness(ratio)
 * - contrast(ratio)
 * - saturate(ratio)
 * - grayscale(ratio or %)
 * - sepia(ratio or %)
 * - hue-rotate(deg)
 * - invert(ratio or %)
 * - opacity(ratio or %)
 */
/**
 * Parsed filter function with name, value, and unit
 */
export interface ParsedFilterFunction {
    name: string;
    value: number;
    unit: string;
}
/**
 * Parse a complete filter string into an array of filter functions
 * Example: "blur(10px) brightness(1.5)" -> [{name: 'blur', value: 10, unit: 'px'}, ...]
 */
export declare function parseFilter(filter: string): ParsedFilterFunction[] | null;
/**
 * Convert parsed filter functions back to CSS filter string.
 *
 * Returns 'none' when all filters are at their identity/default values
 * (e.g. blur(0px), brightness(1)). This is critical because any filter
 * value other than 'none' — even visually invisible ones like blur(0px) —
 * creates a CSS containing block that breaks position:fixed on descendants.
 * This would cause pinned sections to disappear during scroll animations.
 */
export declare function filterToString(filters: ParsedFilterFunction[]): string;
/**
 * Get current filter value from element's computed style
 */
export declare function getCurrentFilter(element: Element): ParsedFilterFunction[];
/**
 * Merge two filter arrays, ensuring all functions from both are present
 * Missing functions get their default values
 */
export declare function mergeFilterArrays(start: ParsedFilterFunction[], end: ParsedFilterFunction[]): {
    start: ParsedFilterFunction[];
    end: ParsedFilterFunction[];
};
/**
 * Interpolate between two filter arrays at given progress.
 * Reuses a module-level buffer to avoid allocating a new array every frame.
 * NOTE: The returned array is reused on the next call — consume it immediately.
 */
export declare function interpolateFilters(start: ParsedFilterFunction[], end: ParsedFilterFunction[], progress: number): ParsedFilterFunction[];
