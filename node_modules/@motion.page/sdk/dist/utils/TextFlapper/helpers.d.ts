/**
 * TextFlapper helpers — charset resolution, cycle counting, measurement, and
 * the one-time per-type DOM/style setup that prepare() applies before driving.
 */
import type { FlapConfig } from '../../types';
import type { StableWidthMode } from './types';
/**
 * Walk upward from a character span past any implicit word-wrap spans
 * inserted by TextSplitter ([data-split-word-wrap]). The returned element
 * is the split ROOT — the user-facing container that was passed to split()
 * (e.g. the <a>, <h1>, etc.). This is the element we want to pin in
 * `stableWidth: 'container'` mode, not the intermediate per-word wrappers.
 */
export declare function findSplitRootContainer(charEl: HTMLElement): HTMLElement | null;
export declare function resolveCharset(charset: string | undefined): string;
export declare function getRandomChar(charset: string): string;
export declare function resolveCycleCount(cycles: FlapConfig['cycles']): number;
export declare function isWhitespaceChar(char: string): boolean;
/**
 * Record original textContent for revert, return normalised target char.
 */
export declare function recordAndNormalise(el: HTMLElement): string;
/**
 * One-time setup styles required by each animation type.
 */
/**
 * Measure the widest glyph in `chars` using the same rendering context as
 * `referenceEl` (font, size, weight, letter-spacing etc. all inherit).
 *
 * Used by `stableWidth` to size each char cell (or container) wide enough
 * for BOTH the target text AND the random charset pool. Without this,
 * cycling from a narrow Latin target ("Getting started") through a wider
 * charset (katakana, blocks, CJK) causes per-cycle width jumps since the
 * measurement was only sized for the narrow target.
 *
 * Returns the widest measured width AND the character that produced it —
 * the character is used by `stableWidth: 'container'` to simulate the
 * widest flap state in-place for a precise (subpixel-aware) measurement
 * of the ROOT container's full flap width.
 *
 * Measurement strategy: one absolutely-positioned invisible wrapper holds
 * one inline-block child per unique char. Appended once, all widths are
 * read with getBoundingClientRect() after a single reflow, then the
 * wrapper is removed.
 */
export declare function measureMaxCharsetWidth(referenceEl: HTMLElement, chars: string): {
    width: number;
    char: string;
};
export declare function applyTypeSetup(charElements: HTMLElement[], type: FlapConfig['type'], perspectivePx?: number, styledBoard?: boolean, stableWidth?: StableWidthMode, preserveWhitespaceCells?: boolean, charset?: string): void;
