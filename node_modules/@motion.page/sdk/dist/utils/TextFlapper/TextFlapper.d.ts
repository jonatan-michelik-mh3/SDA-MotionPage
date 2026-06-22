/**
 * TextFlapper - Split-flap display animation utility
 *
 * Produces per-character render drivers that animate text through random
 * character cycling before settling on the target character — inspired by
 * split-flap departure boards.
 *
 * Seven visual modes:
 *   - 'flip'  : 3D rotateX (default)
 *   - 'fade'  : opacity fade
 *   - 'slide' : translateY slide
 *   - 'blur'  : filter blur out/in
 *   - 'scale' : scale pop/shrink
 *   - 'board' : mechanical split-flap (departure board)
 *   - 'none'  : instant text swap
 *
 * Integration paths:
 *   1. SDK pipeline — `prepare()` returns drivers consumed by AnimationBuilder,
 *      which creates real Animation objects managed by Engine/Timeline.
 *   2. Standalone — `flap()` drives the animation via requestAnimationFrame
 *      for direct usage outside the SDK pipeline (e.g. ConnectAI button).
 *
 * Usage (standalone):
 *   const ctrl = TextFlapper.flap(charEls, { type: 'flip' });
 *   ctrl.kill();
 *
 * Revert:
 *   TextFlapper.revert(charEls);
 */
import type { FlapConfig } from '../../types';
import type { UserPropEntry, FlapController, FlapCharDriver } from './types';
export declare class TextFlapper {
    /**
     * Prepare per-character animation drivers for the split-flap effect.
     *
     * Returns one FlapCharDriver per non-whitespace character. Each driver
     * provides `duration`, `render(progress)`, and `finalize()` — intended to
     * be consumed by AnimationBuilder, which creates real Engine-managed
     * Animation objects from them.
     *
     * This method handles:
     *   - Recording original textContent for revert
     *   - Applying one-time type-specific setup styles
     *   - Pre-generating random character sequences
     *
     * @param charElements - Pre-split character span elements
     * @param config - Flap animation configuration
     */
    static prepare(charElements: HTMLElement[], config: FlapConfig, continuous?: boolean, userProps?: UserPropEntry[]): FlapCharDriver[];
    /**
     * Standalone split-flap animation using requestAnimationFrame.
     *
     * For use outside the SDK pipeline (e.g. ConnectAI button) where Engine
     * may not be initialised. Returns a lightweight FlapController.
     *
     * Duration is computed internally from cycles and a default speed (80ms).
     * Stagger defaults to sequential character start with 2× speed gap.
     *
     * @param charElements - Pre-split character span elements
     * @param config - Flap animation configuration
     * @param staggerDelays - Optional per-element start delays in milliseconds
     * @param continuous - When true, characters cycle indefinitely (never land on target)
     */
    static flap(charElements: HTMLElement[], config: FlapConfig, staggerDelays?: number[], continuous?: boolean): FlapController;
    /**
     * Revert character elements to their original text content and remove
     * all inline styles applied by TextFlapper.
     */
    static revert(charElements: HTMLElement[]): void;
}
