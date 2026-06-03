/**
 * ScrollPositionParser
 *
 * Shared utility for parsing scroll trigger position values.
 * Used by ScrollTrigger and MarkerManager.
 *
 * Supports:
 * - Keywords: top, center, bottom
 * - Keywords with offsets: top+100px, center-50%, bottom+20vh
 * - Units: px, %, vh, vw
 */
/**
 * Parse a position value into pixels
 * @param value - Position string (e.g., "top", "center", "50%", "100px", "top+100px")
 * @param referenceSize - Reference size for percentage calculations (element height)
 * @param viewportHeight - Viewport height for vh calculations
 * @param viewportWidth - Viewport width for vw calculations (optional)
 */
export declare function parseScrollPosition(value: string, referenceSize: number, viewportHeight: number, viewportWidth?: number): number;
