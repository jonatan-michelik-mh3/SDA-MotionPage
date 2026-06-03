/**
 * GestureTrigger
 *
 * Detects user gestures (pointer, touch, wheel, scroll) and triggers
 * timeline actions based on directional movement and interaction events.
 *
 * Performance optimizations:
 * - RAF synchronization for callback execution
 * - Throttled scroll/wheel event processing
 * - Cached config values
 * - Minimal allocations in hot paths
 */
import type { GestureConfig } from '../types';
import type { Timeline } from '../core/Timeline';
import { BaseTrigger } from './BaseTrigger';
export declare class GestureTrigger extends BaseTrigger<GestureConfig> {
    private _target;
    private _tolerance;
    private _dragMinimum;
    private _dragMinimumSquared;
    private _wheelSpeed;
    private _scrollSpeed;
    private _stopDelay;
    private _preventDefault;
    private _lockAxis;
    private _animationStep;
    private _smooth;
    private _startX;
    private _startY;
    private _deltaX;
    private _deltaY;
    private _lockedAxis;
    private _isPressed;
    private _isDragging;
    private _lastDirectionX;
    private _lastDirectionY;
    private _lastScrollX;
    private _lastScrollY;
    private _stopTimeout;
    private _isMoving;
    private _lastActivityTime;
    private _pendingActions;
    private _rafScheduled;
    private _rafId;
    private _wheelAccumulatorX;
    private _wheelAccumulatorY;
    private _wheelRafId;
    private _scrollRafId;
    private _lastPointerX;
    private _lastPointerY;
    private _activatedDirections;
    private _targetProgress;
    private _currentProgress;
    private _interpolationRafId;
    private _siblings;
    private _index;
    private static _sequenceStates;
    private _boundHandlers;
    private _frozenHoverRect;
    private _gestureHoverActive;
    private _boundHoverMoveHandler;
    constructor(timeline: Timeline, config: GestureConfig);
    /**
     * Resolve target and attach gesture listeners once DOM is ready.
     */
    protected _onEnable(): void;
    /**
     * Cancel all pending timers/RAF and remove all event listeners.
     */
    protected _onDisable(): void;
    private _addPointerListeners;
    private _handlePointerDown;
    private _handlePointerMove;
    private _handlePointerUp;
    private _addTouchListeners;
    private _handleTouchStart;
    private _handleTouchMove;
    private _handleTouchEnd;
    /**
     * Shared start logic for pointer and touch input.
     */
    private _handleInputStart;
    /**
     * Shared move logic for pointer and touch input.
     * Returns true if movement was processed (caller should preventDefault if needed).
     */
    private _handleInputMove;
    /**
     * Shared end logic for pointer and touch input.
     */
    private _handleInputEnd;
    private _addWheelListener;
    private _handleWheel;
    private _addScrollListener;
    private _handleScroll;
    private _processScroll;
    private _addHoverListeners;
    /**
     * Re-snapshot frozen hover rect after layout changes (e.g. window resize).
     * Called by TriggerManager on resize.
     */
    refresh(): void;
    private _updateDeltas;
    /**
     * Check and fire directional callbacks.
     * @param instant - If true (wheel/scroll), skips axis-lock checks and direction tracking.
     *                  If false (pointer/touch), respects axis locking and tracks activated directions.
     */
    private _checkDirectionAndFire;
    /**
     * Check for direction toggle (reversal)
     */
    private _checkToggle;
    private _checkDragStart;
    private _scheduleStopCheck;
    private _fireEvent;
    private _fireEventImmediate;
    private _scheduleRAF;
    private _getStepForEvent;
    private _executeAction;
    /**
     * Play next or previous item in sequence (for each mode)
     * @param direction 1 for next, -1 for previous
     */
    private _playSequenceItem;
    /**
     * Smoothly interpolate progress by the given delta.
     * Uses lerp for continuous scrolling (responsive) with natural ease-out when stopping.
     */
    private _interpolateProgress;
    private _startInterpolationLoop;
    /**
     * Fire directional Complete callbacks for any directions that were activated
     * during this gesture, then clear the tracking set.
     */
    private _fireCompleteCallbacks;
    private _resetGestureState;
}
