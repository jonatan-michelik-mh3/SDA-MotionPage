/**
 * Motion API - Unified entry point for animations
 *
 * All animations are named timelines. Config-based API for consistency.
 *
 * @example Single animation
 * Motion('fadeIn', '.box', {
 *   from: { opacity: 0 },
 *   to: { opacity: 1 },
 *   duration: 0.5
 * }).play()
 *
 * @example Multi-animation timeline
 * Motion('hero', [
 *   { target: '.title', from: { y: 50 }, to: { y: 0 }, duration: 1 },
 *   { target: '.subtitle', from: { opacity: 0 }, to: { opacity: 1 }, position: 0.5 }
 * ]).play()
 *
 * @example With trigger
 * Motion('scroll', '.box', { from: { x: -100 }, to: { x: 0 } })
 *   .onScroll({ scrub: true })
 *
 * @example Replay
 * Motion('fadeIn').play()
 */
import type { TargetInput, AnimationConfig, AnimationEntry, AnimationVars } from '../types';
import { Timeline } from './Timeline';
import { MotionContext } from './MotionContext';
import type { ResponsiveManager, ResponsiveVariants, ResponsiveBreakpointConfig } from '../responsive/ResponsiveManager';
/**
 * Motion function overloads
 */
declare function MotionFunction(name: string): Timeline;
declare function MotionFunction(name: string, target: TargetInput, config: AnimationConfig): Timeline;
declare function MotionFunction(name: string, animations: AnimationEntry[]): Timeline;
declare namespace MotionFunction {
    var reset: (targets: TargetInput) => void;
    var set: (target: TargetInput, vars: AnimationVars) => void;
    var get: (name: string) => Timeline | undefined;
    var has: (name: string) => boolean;
    var getNames: () => string[];
    var kill: (name: string) => void;
    var killAll: () => void;
    var refreshScrollTriggers: () => void;
    var cleanup: () => void;
    var context: (fn: () => void) => MotionContext;
    var utils: {
        readonly toArray: typeof import("../utils/MotionUtils").toArray;
        readonly clamp: typeof import("../utils/MotionUtils").clamp;
        readonly random: typeof import("../utils/MotionUtils").random;
        readonly snap: typeof import("../utils/MotionUtils").snap;
        readonly interpolate: typeof import("../utils/MotionUtils").interpolate;
        readonly mapRange: typeof import("../utils/MotionUtils").mapRange;
        readonly normalize: typeof import("../utils/MotionUtils").normalize;
        readonly wrap: typeof import("../utils/MotionUtils").wrap;
    };
    var responsive: (_name: string, variants: ResponsiveVariants, breakpointConfig: ResponsiveBreakpointConfig) => ResponsiveManager | undefined;
}
export declare const Motion: typeof MotionFunction;
export {};
