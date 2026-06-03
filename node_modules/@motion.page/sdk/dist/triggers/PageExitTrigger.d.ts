/**
 * PageExitTrigger
 *
 * Triggers timeline playback when a user clicks a link that would navigate away
 * from the current page. Intercepts the click, plays the exit animation, then
 * navigates to the target URL after the timeline completes.
 *
 * Supports three link selection modes:
 * - 'all': intercept all navigation links (default)
 * - 'include': only intercept links matching specific selectors
 * - 'exclude': intercept all links except those matching specific selectors
 *
 * Supports skipping certain href patterns:
 * - 'anchor': skip # links (same-page anchors)
 * - 'javascript': skip javascript: hrefs
 * - 'mailto': skip mailto: and tel: hrefs
 *
 * Works on all websites — no server-side dependencies (PHP, Node, etc.).
 */
import type { Timeline } from '../core/Timeline';
import { BaseTrigger } from './BaseTrigger';
/** Configuration for PageExitTrigger */
export interface PageExitTriggerConfig {
    /** Link selection mode: 'all' (default), 'include', or 'exclude' */
    mode?: 'all' | 'include' | 'exclude';
    /** CSS selector(s) for links — required for 'include'/'exclude' modes */
    selectors?: string;
    /** Href patterns to skip (never intercept) */
    skipHref?: ('anchor' | 'javascript' | 'mailto')[];
}
export declare class PageExitTrigger extends BaseTrigger<PageExitTriggerConfig> {
    private _links;
    private _listeners;
    private _isNavigating;
    private _pendingUrl;
    private _onCompleteHandler;
    private _pollTimerId;
    constructor(timeline: Timeline, config: PageExitTriggerConfig);
    /**
     * Set up click listeners on qualifying links once DOM is ready.
     */
    protected _onEnable(): void;
    /**
     * Remove all click listeners and cancel pending navigation.
     */
    protected _onDisable(): void;
    /**
     * Detect if we're running inside the Motion.page builder iframe.
     * Exit animations should not intercept clicks in the builder.
     */
    private _isInsideBuilder;
    /**
     * Resolve which <a> elements should be intercepted based on config mode.
     */
    private _resolveLinks;
    /**
     * Determine if a link should be intercepted based on its href and skipHref config.
     * Skips links that don't actually navigate away.
     */
    private _shouldIntercept;
    /**
     * Attach click listeners to all resolved links.
     */
    private _addClickListeners;
    /**
     * Wait for the timeline to complete, then navigate.
     * Uses requestAnimationFrame polling to check timeline progress.
     */
    private _waitForCompletion;
    /**
     * Navigate to the pending URL.
     */
    private _navigate;
}
