/**
 * PageLoadTrigger
 *
 * Triggers timelines based on page load events:
 * - timing: 'before' - plays immediately (before DOMContentLoaded)
 * - timing: 'during' - plays on DOMContentLoaded
 * - timing: 'after' - plays on window load event
 */
import type { Timeline } from '../core/Timeline';
import type { PageLoadTriggerConfig } from './TriggerManager';
export declare class PageLoadTrigger {
    private _registrations;
    private _domContentLoaded;
    private _windowLoaded;
    private _initialized;
    constructor();
    private _initialize;
    /**
     * Register a timeline with pageLoad trigger
     */
    register(timeline: Timeline, config: PageLoadTriggerConfig): void;
    /**
     * Unregister a timeline from page load trigger
     */
    unregister(timeline: Timeline): void;
    /**
     * Clear all registrations
     */
    clear(): void;
    private _triggerDuring;
    private _triggerAfter;
    /**
     * Reset for testing - DO NOT USE IN PRODUCTION
     * @internal
     */
    _reset(): void;
}
