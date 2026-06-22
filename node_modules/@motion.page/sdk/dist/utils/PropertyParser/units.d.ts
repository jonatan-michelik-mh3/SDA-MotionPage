/**
 * PropertyParser units — numeric/unit parsing, defaulting, and reading current
 * values off a target (with px↔unit conversion via the browser layout engine).
 */
import type { AnimationTarget } from '../../types';
import type { PropertyValue } from './types';
/**
 * Parse a property value and extract numeric value and unit
 */
export declare function parseValue(value: PropertyValue): {
    value: number;
    unit: string;
};
/**
 * Get the default unit for a property
 */
export declare function getDefaultUnit(prop: string): string;
/**
 * Get the current computed value of a property from a target
 * Handles both DOM Elements and plain objects
 */
export declare function getCurrentValue(target: AnimationTarget, prop: string): number;
/**
 * Convert a pixel value to a target CSS unit by measuring the element in the DOM.
 *
 * Uses the browser's layout engine: temporarily sets `100{targetUnit}` on the
 * element, reads the computed pixel equivalent, then derives the conversion ratio.
 * This handles %, em, rem, vw, vh, vmin, vmax, ch, ex — any unit the browser supports.
 *
 * When the target unit is 'px' or empty, returns the value unchanged (no conversion).
 * Falls back to the raw pixel value if measurement fails (e.g. detached element).
 */
export declare function convertPxToUnit(element: Element, prop: string, pxValue: number, targetUnit: string): number;
/**
 * Get the current computed value of a property, converted to the specified unit.
 *
 * Combines getCurrentValue (which always returns px for layout properties)
 * with convertPxToUnit to return the value in the desired CSS unit.
 * Only performs conversion for DOM Element targets with non-px units.
 */
export declare function getCurrentValueInUnit(target: AnimationTarget, prop: string, targetUnit: string): number;
/**
 * Get the original CSS value AND unit of a property (excluding inline styles)
 * Returns both value and unit to preserve the original CSS unit (%, rem, em, vh, etc.)
 */
export declare function getOriginalCSSValueWithUnit(target: Element, prop: string): {
    value: number;
    unit: string;
};
/**
 * Convert camelCase to kebab-case
 */
export declare function camelToKebab(str: string): string;
