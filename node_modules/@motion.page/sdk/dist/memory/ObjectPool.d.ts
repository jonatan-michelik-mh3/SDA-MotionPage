/**
 * ObjectPool<T> - Generic object pool for reusable instances
 *
 * Reuses objects that implement a `reset()` method to minimize
 * garbage collection overhead.
 */
/** Constraint: pooled objects must be resettable */
export interface Poolable {
    reset(): void;
}
export declare class ObjectPool<T extends Poolable> {
    private pool;
    private totalCreated;
    private readonly factory;
    private readonly maxSize;
    constructor(factory: () => T, maxSize?: number);
    /**
     * Acquire an object from the pool or create a new one
     */
    acquire(): T;
    /**
     * Release an object back to the pool
     */
    release(obj: T): void;
    /**
     * Get count of pooled objects
     */
    getPoolSize(): number;
    /**
     * Get total objects created (for stats)
     */
    getTotalCreated(): number;
    /**
     * Clear the pool (for testing/debugging)
     */
    clear(): void;
}
