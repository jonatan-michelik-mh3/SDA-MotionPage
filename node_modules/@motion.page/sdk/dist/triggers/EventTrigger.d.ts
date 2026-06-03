/**
 * EventTrigger
 *
 * Triggers timelines based on DOM events:
 * - hover: plays on mouseenter, reverses on mouseleave
 * - click: plays on click with optional toggle behavior (reverse/restart on 2nd click)
 */
import type { Timeline } from '../core/Timeline';
import { BaseTrigger } from './BaseTrigger';
/** Internal config for EventTrigger */
export interface EventTriggerConfig {
    type: 'hover' | 'click';
    target?: string | Element;
    secondTarget?: string | Element;
    toggle?: 'reverse' | 'restart' | 'play';
    preventDefault?: boolean;
    onEnter?: 'play' | 'restart';
    onLeave?: 'reverse' | 'pause' | 'stop' | 'restart' | 'none';
    leaveDelay?: number;
}
export declare class EventTrigger extends BaseTrigger<EventTriggerConfig> {
    private _targets;
    private _secondTargets;
    private _listeners;
    private _isForward;
    private _frozenRects;
    private _snapshotScrollX;
    private _snapshotScrollY;
    private _wasHovering;
    private _hoverLeaveTimeout;
    private _boundPointerMoveHandler;
    constructor(timeline: Timeline, config: EventTriggerConfig);
    /**
     * Set up event listeners once DOM is ready.
     */
    protected _onEnable(): void;
    /**
     * Remove all event listeners and cancel pending timers.
     */
    protected _onDisable(): void;
    /**
     * Re-snapshot frozen rects after layout changes (e.g. window resize).
     * Called by TriggerManager.refreshScrollTriggers() on resize.
     */
    refresh(): void;
    /** Snapshot frozen rects and the current scroll offset. */
    private _snapshotRects;
    private _isInsideFrozenRects;
    private _isOverLiveTarget;
    private _resolveTargets;
    private _addHoverListeners;
    private _handleLeaveAction;
    private _addClickListeners;
    private _handleSecondClick;
}
