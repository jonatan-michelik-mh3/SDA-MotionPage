/**
 * StaggerResolver - Calculates stagger delays for multiple targets
 */
import type { StaggerVars, AnimationTarget } from '../types';
export declare class StaggerResolver {
    /**
     * Calculate stagger delays for an array of targets
     * Works with both DOM elements and plain objects
     */
    static resolve(targets: AnimationTarget[], stagger: number | StaggerVars): number[];
    /**
     * Resolve complex stagger configuration with from, grid, etc.
     */
    private static resolveComplexStagger;
    /**
     * Get target ordering based on 'from' parameter
     */
    private static getOrdering;
    /**
     * Get ordering for grid-based stagger
     */
    private static getGridOrdering;
    /**
     * Auto-detect grid dimensions from element positions
     * Only works with DOM elements. For plain objects, falls back to linear arrangement.
     */
    private static detectGrid;
}
