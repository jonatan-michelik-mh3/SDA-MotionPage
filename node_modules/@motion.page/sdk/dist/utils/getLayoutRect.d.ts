/**
 * Returns the element's bounding rect as if no CSS transform or position:fixed were applied.
 * Temporarily strips inline transforms and any fixed positioning, measures, then restores.
 * This gives us the "layout" position in the document flow.
 *
 * Used by ScrollTrigger and MarkerManager to compute trigger positions and
 * marker placements relative to the element's original layout position,
 * regardless of any animation transforms or pin states currently applied.
 *
 * Handles position:fixed elements: a fixed element returns viewport-relative
 * coordinates from getBoundingClientRect(), which would produce incorrect
 * document-relative trigger positions.  Temporarily clearing position/top/left/width
 * restores document-flow measurement before reading the rect.
 *
 * Performance notes:
 * - The getComputedStyle() call to detect position:fixed is skipped when we
 *   can tell from the inline style alone (fast path for the common case).
 * - No forced reflow after restoring styles — the browser batches style
 *   writes and applies them before the next paint automatically.
 */
export declare function getLayoutRect(element: Element): DOMRect;
