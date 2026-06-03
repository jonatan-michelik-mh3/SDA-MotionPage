/**
 * AnimationPool - Singleton object pool for Animation instances
 *
 * Reuses Animation objects to minimize garbage collection overhead
 */
import { Animation } from '../core/Animation';
export declare const AnimationPool: {
    acquire: () => Animation;
    release: (animation: Animation) => void;
    getPoolSize: () => number;
    getTotalCreated: () => number;
    clear: () => void;
};
export type AnimationPool = typeof AnimationPool;
