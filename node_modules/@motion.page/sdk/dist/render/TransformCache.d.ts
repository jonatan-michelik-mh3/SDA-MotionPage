/**
 * TransformCache - Optimized transform value storage
 *
 * Uses Float32Array for fast transform value storage per element.
 * Separate array for units (string per property).
 * Dirty flag bitmask to skip unchanged properties.
 * Cached transform string to avoid rebuilding.
 */
interface TransformData {
    values: Float32Array;
    units: string[];
    dirty: number;
    used: number;
    cachedString: string;
    pinDirty: boolean;
}
/**
 * Get or create transform data for an element
 */
export declare function getTransformData(element: Element): TransformData;
/**
 * Set a transform value and unit, mark as dirty
 */
export declare function setTransformValue(element: Element, property: string, value: number, unit?: string): void;
/**
 * Get a transform value
 */
export declare function getTransformValue(element: Element, property: string): number;
/**
 * Get a transform unit
 */
export declare function getTransformUnit(element: Element, property: string): string;
/**
 * Register a pin offset for an element.
 * The pin offset is prepended to the animation transform in buildTransformString().
 * Call this instead of writing directly to element.style.transform from PinManager
 * so that both pin position and animation values are composed into a single write.
 */
export declare function setPinOffset(element: Element, x: number, y: number, z: number): void;
/**
 * Remove the pin offset for an element (call when element exits pin zone).
 * Marks the cached transform string as stale so the next build omits the offset.
 */
export declare function clearPinOffset(element: Element): void;
/**
 * Build the CSS transform string from cached values
 */
export declare function buildTransformString(element: Element): string;
/**
 * Clear transform cache for an element
 * @param clearInlineStyle - If true, also clears element's inline transform to prevent hydration from reading stale values
 */
export declare function clearTransformCache(element: Element, clearInlineStyle?: boolean): void;
export {};
