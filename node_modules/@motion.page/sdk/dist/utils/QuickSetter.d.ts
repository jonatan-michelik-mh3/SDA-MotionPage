/**
 * QuickSetter - High-performance property setter for per-frame updates
 *
 * Bypasses the full animation pipeline for maximum speed.
 * Used by CursorTrigger for smooth position tracking.
 */
export type QuickSetterFn = (value: number) => void;
/**
 * Create a high-performance property setter for per-frame updates.
 * Returns a function that sets the value directly without animation overhead.
 *
 * @param target - The element to animate
 * @param property - CSS property name (camelCase)
 * @param unit - Unit to append (default: 'px' for transforms, '' for others)
 */
export declare function createQuickSetter(target: Element, property: string, unit?: string): QuickSetterFn;
