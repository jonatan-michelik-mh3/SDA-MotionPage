/**
 * StyleReset - Clear animation-related inline styles from elements
 *
 * Only clears styles that our SDK has animated or set for pinning,
 * preserving user's original inline styles.
 * Uses registries to track which CSS properties have been set by the SDK.
 */
/**
 * Register that a CSS property has been animated on an element.
 * Call this when setting inline styles during animation.
 */
export declare function registerAnimatedProp(element: Element, prop: string): void;
/**
 * Register multiple animated properties at once.
 */
export declare function registerAnimatedProps(element: Element, props: string[]): void;
/**
 * Get all CSS properties that have been animated on an element.
 */
export declare function getAnimatedProps(element: Element): Set<string>;
/**
 * Clear tracking for an element (called after Motion.reset).
 */
export declare function clearAnimatedPropsRegistry(element: Element): void;
/**
 * Clear specific animation properties from an element's inline styles.
 * Only clears the properties specified, preserving other inline styles.
 *
 * @param element - The element to clear styles from
 * @param props - Animation property names to clear (e.g., 'x', 'opacity', 'backgroundColor')
 */
export declare function clearAnimationStylesForProps(element: Element, props: string[]): void;
/**
 * Clear all animation-related inline styles that our SDK has set on an element.
 * Only clears properties tracked in the registry, preserving user's original inline styles.
 */
export declare function clearAnimationStyles(element: Element): void;
/**
 * Clear animation styles and remove from registry (full reset).
 * Use this for Motion.reset() to completely clean up.
 */
export declare function clearAnimationStylesAndUnregister(element: Element): void;
/**
 * Clear animation styles from multiple elements
 */
export declare function clearAnimationStylesAll(elements: Element[]): void;
/**
 * Register that a pin-related CSS property has been set on an element.
 * Call this when PinManager sets styles during pinning.
 */
export declare function registerPinnedProp(element: Element, prop: string): void;
/**
 * Register multiple pinned properties at once.
 */
export declare function registerPinnedProps(element: Element, props: string[]): void;
/**
 * Get all pin-related CSS properties that have been set on an element.
 */
export declare function getPinnedProps(element: Element): Set<string>;
/**
 * Clear tracking for pinned properties on an element.
 */
export declare function clearPinnedPropsRegistry(element: Element): void;
/**
 * Clear pin-related inline styles that our SDK has set on an element.
 * Only clears properties tracked in the pin registry.
 */
export declare function clearPinStyles(element: Element): void;
/**
 * Clear pin styles and remove from registry.
 * Use this for Motion.reset() to completely clean up pin styles.
 */
export declare function clearPinStylesAndUnregister(element: Element): void;
