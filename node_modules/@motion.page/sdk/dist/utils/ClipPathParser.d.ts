/**
 * ClipPathParser - Parse and interpolate CSS clip-path shape functions
 *
 * Supports:
 * - circle(<radius> at <x> <y>)
 * - ellipse(<rx> <ry> at <x> <y>)
 * - inset(<top> <right>? <bottom>? <left>? round <radius>?)
 * - polygon(<x1> <y1>, <x2> <y2>, …)
 * - rect(<top> <right> <bottom> <left> round <radius>?)
 * - xywh(<x> <y> <w> <h> round <radius>?)
 *
 * Interpolation rules:
 * - From and To must use the same shape function — otherwise we snap to the
 *   end value at progress >= 0.5 (no shape morphing across types).
 * - polygon vertex counts must match — otherwise we snap to the end value.
 * - Each numeric component is lerped while preserving the unit emitted in the
 *   end value (start unit kept when end has none).
 *
 * Loaded conditionally through SDKRegistry so the bundler can drop it when
 * no animation uses clip-path.
 */
export type ClipPathShape = 'circle' | 'ellipse' | 'inset' | 'polygon' | 'rect' | 'xywh';
/**
 * A single numeric component with its CSS unit (e.g. 71 + "%" or 10 + "px").
 */
export interface ClipPathComponent {
    value: number;
    unit: string;
}
/**
 * Parsed clip-path shape function.
 *
 * `components` holds the ordered numeric values that get interpolated. The
 * meaning of each slot is shape-specific — see the assemble* helpers.
 *
 * `extras` carries non-numeric tokens (e.g. the `round` keyword for inset/rect/
 * xywh) that we preserve verbatim when re-serializing.
 */
export interface ParsedClipPath {
    shape: ClipPathShape;
    components: ClipPathComponent[];
    extras?: {
        hasRound?: boolean;
    };
}
/**
 * Parse a clip-path value string into a structured `ParsedClipPath`.
 * Returns null if the value is `none`, empty, or an unsupported shape.
 */
export declare function parseClipPath(value: string): ParsedClipPath | null;
/**
 * Read the current computed clip-path on an element.
 * Returns null when there is no clip-path or it's a non-shape value.
 */
export declare function getCurrentClipPath(element: Element): ParsedClipPath | null;
/**
 * Returns true when start/end are interpolatable: same shape and component
 * count. Polygon morphing requires identical vertex counts.
 */
export declare function canInterpolate(start: ParsedClipPath, end: ParsedClipPath): boolean;
/**
 * Interpolate two parsed clip-paths at `progress` (0-1).
 *
 * If shapes/lengths don't match, falls back to a hard swap at progress >= 0.5
 * — no smooth morphing is possible across different shape functions.
 *
 * Returns a `ParsedClipPath` whose `components` array is reused on subsequent
 * calls — consume it (e.g. via `clipPathToString`) before the next invocation.
 */
export declare function interpolateClipPaths(start: ParsedClipPath, end: ParsedClipPath, progress: number): ParsedClipPath;
/**
 * Serialize a parsed clip-path back into a valid CSS clip-path string.
 */
export declare function clipPathToString(parsed: ParsedClipPath): string;
