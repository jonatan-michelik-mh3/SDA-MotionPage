/**
 * PropTweenPool - Singleton object pool for PropTween instances
 *
 * Reuses PropTween objects to minimize garbage collection overhead
 */
import { PropTween } from '../core/PropTween';
export declare const PropTweenPool: {
    acquire: () => PropTween;
    release: (propTween: PropTween) => void;
    getPoolSize: () => number;
    getTotalCreated: () => number;
    clear: () => void;
};
export type PropTweenPool = typeof PropTweenPool;
