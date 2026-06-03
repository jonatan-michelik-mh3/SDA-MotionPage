/**
 * DrawSVGParser - Parse and animate SVG stroke drawing
 *
 * Handles:
 * - Percentage values: "0% 100%", "20% 80%"
 * - Pixel values: "100px 500px"
 * - Object format: { start: 0, end: 100 }
 * - Single values: "50%" (draws from 0 to 50%)
 *
 * Uses stroke-dasharray and stroke-dashoffset for performant rendering.
 */
/**
 * Parsed DrawSVG values (normalized to 0-1 range)
 */
export interface ParsedDrawSVG {
    start: number;
    end: number;
}
/**
 * DrawSVG input value types
 */
type DrawSVGValue = string | {
    start?: number;
    end?: number;
};
/**
 * Get the total length of an SVG element's stroke path.
 * Cached per element for performance.
 */
export declare function getPathLength(element: Element): number;
/**
 * Clear cached path length for an element.
 * Call when SVG path data changes.
 */
export declare function clearPathLengthCache(element: Element): void;
/**
 * Parse a DrawSVG value string or object into normalized 0-1 values.
 *
 * Supported formats:
 * - "0% 100%" - percentage range
 * - "100px 500px" - pixel range (requires element for length)
 * - "50%" - single percentage (0 to value)
 * - "50" - single number treated as percentage
 * - { start: 0, end: 100 } - object with percentages
 */
export declare function parseDrawSVG(value: DrawSVGValue, element?: Element): ParsedDrawSVG | null;
/**
 * Get the current DrawSVG state from an element's computed style.
 * Parses existing stroke-dasharray and stroke-dashoffset.
 */
export declare function getCurrentDrawSVG(element: Element): ParsedDrawSVG;
/**
 * Apply DrawSVG values to an element's style.
 * Sets stroke-dasharray and stroke-dashoffset for the draw effect.
 *
 * @param element - Target SVG element
 * @param start - Start position (0-1)
 * @param end - End position (0-1)
 * @param length - Pre-calculated path length
 */
export declare function applyDrawSVG(element: Element, start: number, end: number, length: number): void;
export {};
