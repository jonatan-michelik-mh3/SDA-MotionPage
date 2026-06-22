/**
 * TextSplitter - Split text into animatable elements
 *
 * Splits text content into chars, words, or lines wrapped in spans.
 * Supports nested splitting (e.g., 'chars,words' = words containing char spans).
 *
 * Preserves existing element structure — inline elements like
 * `<span class="accent">word</span>` keep their wrapper and styling.
 * Only text nodes are replaced with split spans.
 *
 * Usage:
 *   const elements = TextSplitter.split(element, 'chars');
 *   const elements = TextSplitter.split(element, 'words');
 *   const elements = TextSplitter.split(element, 'chars,words');
 *
 * Revert:
 *   TextSplitter.revert(element);
 *   TextSplitter.revert(element, consumer); // reference-counted
 */
import type { SplitType, SplitOptions, SplitResult } from './types';
export declare class TextSplitter {
    /**
     * Split text content into animatable elements.
     *
     * When `options.consumer` is provided the split is reference-counted.
     * A second consumer on the same element will have the DOM upgraded
     * (chars nested inside existing word spans, or word-wraps promoted to
     * word spans) rather than reverted and rebuilt, so the first consumer's
     * DOM references remain valid.
     *
     * Without a consumer token the function behaves exactly like before:
     * any existing split is reverted and a fresh split is performed.
     */
    static split(element: Element, type: SplitType, options?: SplitOptions): HTMLElement[];
    /**
     * Revert element to original content.
     *
     * When `consumer` is provided the revert is reference-counted: the DOM is
     * only restored once all registered consumers have released via this method.
     * If the consumer is the last one (or no consumer tracking is active), the
     * element's innerHTML is restored to the pre-split original immediately.
     *
     * Without `consumer` (legacy / forced revert), the DOM is restored regardless
     * of how many consumers are still registered. All consumer refs are cleared.
     */
    static revert(element: Element, consumer?: object): boolean;
    /**
     * Check if element has been split
     */
    static isSplit(element: Element): boolean;
    /**
     * Get split result for an element
     */
    static getResult(element: Element): SplitResult | undefined;
}
