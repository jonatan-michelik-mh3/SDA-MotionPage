/**
 * TargetResolver - Convert various target formats to AnimationTarget array
 *
 * Supports both DOM elements and plain JS objects for universal animation.
 */
import type { AnimationTarget, TargetInput } from '../types';
/**
 * Execute callback when DOM is ready
 * If DOM is already ready, executes immediately
 */
export declare function whenDOMReady(callback: () => void): void;
/**
 * Check if a resolved target is a DOM Element
 * Used for caching _isElement flag in PropTween
 * Also recognizes mock elements (objects with 'style' property) in test environments
 */
export declare function isElement(target: AnimationTarget): target is Element;
/**
 * Resolve animation targets to an array of AnimationTargets
 * @param targets - CSS selector, string[], Element, NodeList, Element[], plain object, or object array
 * @returns Array of resolved AnimationTargets (Element or ObjectTarget)
 */
export declare function resolveTargets(targets: TargetInput): AnimationTarget[];
