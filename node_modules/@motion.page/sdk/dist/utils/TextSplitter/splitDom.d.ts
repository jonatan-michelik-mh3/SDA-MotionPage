/**
 * TextSplitter DOM-walking split functions.
 *
 * The actual text-node → span splitting passes (words, chars, words+chars, and
 * the layout-measuring lines pass) plus the small HTML-serialization helpers the
 * lines pass relies on. Each function preserves existing element wrappers like
 * `<span class="accent">word</span>` and recurses into child elements.
 */
import type { SplitResult } from './types';
/**
 * Split text nodes into word spans, preserving existing element wrappers.
 * Recurses into child elements so `<span class="accent">word</span>` keeps
 * its wrapper and the text inside is split within it.
 */
export declare function splitWordsDom(parent: HTMLElement, result: SplitResult): void;
/**
 * Split text nodes into character spans, preserving existing element wrappers.
 *
 * Characters are grouped by word inside implicit wrapper spans with
 * `white-space: nowrap` so the browser never breaks a word across lines
 * (matching GSAP SplitText behaviour). Only the char spans are exposed
 * as animatable elements — the word wrappers are transparent to consumers.
 */
export declare function splitCharsDom(parent: HTMLElement, result: SplitResult): void;
/**
 * Split text nodes into word spans containing character spans.
 * Preserves existing element wrappers.
 */
export declare function splitWordsWithCharsDom(parent: HTMLElement, result: SplitResult): void;
/**
 * Split with line detection (requires layout measurement).
 *
 * Strategy:
 * 1. DOM-walk to split into word spans (preserving element wrappers)
 * 2. Measure each word span's vertical position to group into lines
 * 3. Wrap each line group in a line span
 */
export declare function splitWithLinesDom(element: HTMLElement, needsWords: boolean, needsChars: boolean, result: SplitResult): void;
/**
 * Get the chain of wrapper elements between a word span and the root element.
 * Returns outermost-first order (root's direct child → … → word's parent).
 */
export declare function getWrapperChain(word: HTMLElement, root: HTMLElement): HTMLElement[];
/**
 * Serialize an element's opening tag, preserving all attributes (class, style, data-*, etc).
 */
export declare function openTag(el: HTMLElement): string;
/**
 * Escape HTML special characters
 */
export declare function escapeHtml(str: string): string;
