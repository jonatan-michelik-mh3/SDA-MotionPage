/**
 * FitResolver — SDK-native Fit animation geometry resolution
 *
 * Captures source and target element bounding rects at play time,
 * computes position and scale deltas, and creates PropTweens that
 * animate the source element toward the target's geometry.
 *
 * Non-absolute mode (default): animates CSS transforms (x, y, scaleX, scaleY)
 * Resize mode: animates CSS transforms (x, y) + width/height properties (no content distortion)
 * Absolute mode: animates CSS left/top/width/height properties
 */
export {};
