/**
 * Easing Functions
 *
 * All easings are based on Robert Penner's easing equations
 * and normalized to accept/return values in the 0-1 range.
 *
 * Includes GSAP-compatible aliases (quad, cubic, quart, quint, strong)
 * and special easings (slow, rough) for migration compatibility.
 */
import type { EasingFunction } from '../types';
export declare const linear: (t: number) => number;
export declare const none: (t: number) => number;
export declare const power1: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const power2: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const power3: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const power4: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const quad: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const cubic: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const quart: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const quint: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const strong: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const sine: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const expo: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const circ: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const back: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const elastic: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const bounce: {
    in: (t: number) => number;
    out: (t: number) => number;
    inOut: (t: number) => number;
};
export declare const slow: {
    in: EasingFunction;
    out: EasingFunction;
    inOut: EasingFunction;
};
export declare const rough: {
    in: EasingFunction;
    out: EasingFunction;
    inOut: EasingFunction;
};
export declare const steps: {
    in: EasingFunction;
    out: EasingFunction;
    inOut: EasingFunction;
};
/**
 * Get an easing function by string name
 * Supports both simple names ("back.out") and parameterized ("back.out(1.4)")
 * @param name Easing name (case insensitive)
 * @returns Easing function, or power1.out if not found
 */
export declare function getEasing(name: string): EasingFunction;
