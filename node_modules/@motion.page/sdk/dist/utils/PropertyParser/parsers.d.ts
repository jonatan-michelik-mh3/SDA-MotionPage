/**
 * PropertyParser parsers — the main `parseProperty` entry point and the
 * per-type parsers it delegates to (color, filter, CSS variable, drawSVG,
 * clip-path, path). The optional parsers read their implementations from
 * SDKRegistry and gracefully no-op when a parser is not loaded.
 */
import type { AnimationTarget } from '../../types';
import type { PropertyValue, ParsedProperty } from './types';
/**
 * Parse a property for animation
 * Handles both DOM Elements and plain objects
 */
export declare function parseProperty(target: AnimationTarget, prop: string, targetValue: PropertyValue, fromValue?: PropertyValue): ParsedProperty;
