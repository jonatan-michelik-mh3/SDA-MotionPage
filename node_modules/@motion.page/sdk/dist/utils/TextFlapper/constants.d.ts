/**
 * TextFlapper constants — charsets, defaults, and the module-level WeakMaps
 * that track per-element state across prepare/render/revert.
 */
import type { BoardParts } from './types';
export declare const CHARSETS: Record<string, string>;
export declare const DEFAULT_SPEED = 80;
export declare const DEFAULT_PERSPECTIVE = 400;
export declare const DEFAULT_CYCLES: [number, number];
export declare const originalContentMap: WeakMap<HTMLElement, string>;
export declare const boardPartsMap: WeakMap<HTMLElement, BoardParts>;
/**
 * Tracks elements pinned by `stableWidth: 'container'` so revert() can
 * restore their original `display` and `width` inline styles without
 * clobbering unrelated user-set inline styles.
 */
export declare const containerPinnedMap: WeakMap<HTMLElement, {
    display: string;
    width: string;
}>;
