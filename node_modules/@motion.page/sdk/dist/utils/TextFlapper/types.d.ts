/**
 * TextFlapper types — public driver/controller interfaces plus internal
 * structural types shared across the TextFlapper parts.
 */
/**
 * A single numeric property to interpolate per flap cycle.
 * AnimationBuilder populates these from user-provided from/to vars so that
 * CSS properties animate once per character-swap cycle instead of once over
 * the full animation duration.
 */
export interface UserPropEntry {
    /** CSS property name or transform shorthand (e.g. 'y', 'opacity', 'rotateX') */
    prop: string;
    /** Start value (from) */
    from: number;
    /** End value (to) */
    to: number;
    /** CSS unit string: 'px', 'deg', '%', or '' for unitless */
    unit: string;
    /** True when prop is a TransformCache property (routes through setTransformValue) */
    isTransform: boolean;
}
/** Lightweight controller for standalone flap() usage (ConnectAI, etc.) */
export interface FlapController {
    kill(): void;
    readonly finished: Promise<void>;
    readonly isComplete: boolean;
}
/**
 * Per-character render driver returned by `prepare()`.
 * AnimationBuilder uses these to create real Animation objects via Engine.
 */
export interface FlapCharDriver {
    /** The character element */
    readonly el: HTMLElement;
    /** Called each frame with progress (0-1). Drives text swaps + visual effects. */
    readonly render: (progress: number) => void;
    /** Snap to resolved final state. Called on animation complete. */
    readonly finalize: () => void;
}
/**
 * Board type DOM structure — real Solari departure board effect.
 *
 * A single two-sided flap rotates 0° → -180° around the split line:
 *   FRONT = OLD char's top half   (visible 0° to ~-90°)
 *   BACK  = NEW char's bottom half (visible ~-90° to -180°, lands at bottom)
 *
 * Two static halves sit behind the flap:
 *   staticTop    = NEW char's top    (revealed as flap falls away)
 *   staticBottom = OLD char's bottom (covered by flapBack when it lands)
 *
 * The flap uses a wrapper with transform-style: preserve-3d (no overflow).
 * Front and back children each have overflow:hidden + backface-visibility:hidden.
 *
 * A persistent split line sits at z:3 above everything for realism.
 * A dynamic shadow overlay on the bottom half adds depth during rotation.
 *
 * Layout:
 *   container (el, perspective)
 *   ├─ staticTop    (top:0,   h:50%, overflow:hidden, z:1) → text (NEW top)
 *   ├─ staticBottom (top:50%, h:50%, overflow:hidden, z:1) → text (OLD→NEW bottom)
 *   ├─ flapWrap     (top:0,   h:50%, transformOrigin:center bottom, preserve-3d, z:2)
 *   │   ├─ flapFront (h:100%, overflow:hidden, backface:hidden)      → text (OLD top)
 *   │   └─ flapBack  (h:100%, overflow:hidden, backface:hidden, rotateX:180°) → text (NEW bottom)
 *   └─ splitLine    (top:50%, h:1px, z:3) — persistent center divider
 */
export interface BoardParts {
    /** Static NEW char top half (background, revealed as flap falls) */
    staticTop: HTMLElement;
    /** Static bottom half (OLD char initially, switches to NEW at midpoint) */
    staticBottom: HTMLElement;
    /** Flap wrapper — rotates 0°→-180°, holds front/back faces */
    flapWrap: HTMLElement;
    /** Front face — OLD char top (visible 0° to -90°) */
    flapFront: HTMLElement;
    /** Back face — NEW char bottom (visible -90° to -180°) */
    flapBack: HTMLElement;
    /** Persistent split line across the center */
    splitLine: HTMLElement;
    /** Dynamic shadow overlay on the bottom half — opacity driven by flap angle */
    shadow: HTMLElement;
    /** Inner text spans */
    staticTopText: HTMLElement;
    staticBottomText: HTMLElement;
    flapFrontText: HTMLElement;
    flapBackText: HTMLElement;
}
/** Normalised internal form of FlapConfig.stableWidth */
export type StableWidthMode = 'none' | 'cells' | 'container';
