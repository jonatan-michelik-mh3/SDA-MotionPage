/**
 * TextFlapper per-char render function builders.
 *
 * buildCharRender maps linear progress (0-1) to the current visual state and
 * applies the corresponding CSS/text swap; buildCharFinalize snaps to the
 * resolved final state on completion.
 */
import type { FlapConfig } from '../../types';
import type { UserPropEntry } from './types';
/**
 * Build the render callback for a single character.
 *
 * The render function maps a linear progress (0-1) to the current visual state:
 * which cycle, which phase within the cycle, and applies the corresponding
 * CSS properties + text swap.
 *
 * Pre-generates random characters so the animation is deterministic when
 * scrubbed (seek forward/backward produces consistent results).
 */
export declare function buildCharRender(el: HTMLElement, target: string, cycles: number, charset: string, type: NonNullable<FlapConfig['type']>, perspectivePx: number, continuous: boolean, userProps?: UserPropEntry[]): (progress: number) => void;
export declare function buildCharFinalize(el: HTMLElement, target: string, type: NonNullable<FlapConfig['type']>, userProps?: UserPropEntry[]): () => void;
