/**
 * Engine Singleton - Central animation registry and coordinator
 *
 * Manages:
 * - Global animation registry
 * - Named timeline registry
 * - Object pool coordination
 * - Ticker integration
 * - Render queue routing
 */
import { Ticker } from './Ticker';
import { Animation } from './Animation';
import { Timeline } from './Timeline';
import { AnimationPool } from '../memory/AnimationPool';
import { PropTweenPool } from '../memory/PropTweenPool';
import type { AnimationVars, AnimationTarget } from '../types';
import type { InternalAnimationConfig } from './Animation';
export declare class Engine {
    private static instance;
    private animations;
    private animationIdToIndex;
    private nextAnimationId;
    private totalCreated;
    private _animationSnapshot;
    private timelines;
    private _captureTarget;
    private ticker;
    private _tickerSubscribed;
    private animationPool;
    private propTweenPool;
    private constructor();
    /**
     * Get the singleton Engine instance
     */
    static getInstance(): Engine;
    /**
     * Main update loop called by Ticker
     */
    private update;
    private _ensureTickerSubscription;
    private _hasActiveWork;
    private _releaseTickerIfIdle;
    /**
     * Create a new animation (called by AnimationBuilder)
     * Returns either a single Animation or an array of Animations (for stagger)
     */
    createAnimation(targets: AnimationTarget[], vars: AnimationVars, duration: number, delay: number, ease: string, isFrom: boolean, fromVars?: AnimationVars, config?: InternalAnimationConfig): Animation | Animation[];
    /**
     * Create staggered animations - one animation per target
     */
    private createStaggeredAnimations;
    /**
     * Set up property tweens for an animation
     */
    private setupPropertyTweens;
    /**
     * Register an animation with the engine
     */
    registerAnimation(animation: Animation): number;
    /**
     * Unregister an animation from the engine
     * Uses swap-and-pop for O(1) removal
     */
    unregisterAnimation(id: number): void;
    /**
     * Get an animation by ID
     */
    getAnimation(id: number): Animation | undefined;
    /**
     * Kill all animations
     */
    killAll(): void;
    /**
     * Kill all animations targeting specific elements
     * Used by Motion.reset() to stop animations before clearing styles
     */
    killAnimationsForTargets(targets: Element[]): void;
    /**
     * Get active animation count
     */
    getActiveCount(): number;
    /**
     * Get total animations created
     */
    getTotalCreated(): number;
    /**
     * Get or create a named timeline.
     * When a context capture is active, the timeline name is recorded into
     * the capture set so that MotionContext can track ownership.
     */
    getTimeline(name: string): Timeline;
    /**
     * Start capturing timeline names into the provided set.
     * Used by MotionContext to track which timelines belong to a context.
     * @internal
     */
    startCapture(target: Set<string>): void;
    /**
     * Stop capturing timeline names.
     * @internal
     */
    stopCapture(): void;
    /**
     * Check if timeline exists
     */
    hasTimeline(name: string): boolean;
    /**
     * Register a timeline
     */
    registerTimeline(name: string, timeline: Timeline): void;
    /**
     * Get timeline count
     */
    getTimelineCount(): number;
    /**
     * Get all timeline names
     */
    getTimelineNames(): string[];
    /**
     * Remove a timeline from registry (called after kill)
     */
    removeTimeline(name: string): void;
    /**
     * Kill all timelines and their triggers
     */
    killAllTimelines(): void;
    /**
     * Kill everything - all animations, all timelines, and cleanup resources
     */
    killEverything(): void;
    /**
     * Get animation pool
     */
    getAnimationPool(): AnimationPool;
    /**
     * Get PropTween pool
     */
    getPropTweenPool(): PropTweenPool;
    /**
     * Get pool statistics
     */
    getPoolStats(): {
        animations: number;
        propTweens: number;
    };
    /**
     * Clear all pools (for debugging)
     */
    clearPools(): void;
    /**
     * Get memory estimate in bytes
     */
    getMemoryEstimate(): number;
    /**
     * Get ticker reference
     */
    getTicker(): Ticker;
}
