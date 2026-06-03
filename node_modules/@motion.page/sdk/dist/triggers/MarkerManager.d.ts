/**
 * MarkerManager
 *
 * Manages debug markers for ScrollTrigger visualization.
 * Shows 4 markers:
 * - scroller-start/end: Fixed position lines showing where triggers fire
 * - start/end: Element-relative lines showing trigger points on the element
 */
import type { MarkerConfig } from '../types';
interface MarkerSetupConfig {
    scroller: Element | Window;
    triggerElement: Element | null;
    startConfig: string;
    endConfig: string;
    markerConfig: MarkerConfig | boolean;
    viewportHeight: number;
}
export declare class MarkerManager {
    private _id;
    private _markers?;
    private _markerContainer?;
    private _triggerElement?;
    private _scrollerStartOffset;
    private _scrollerEndOffset;
    private _scroller;
    private _startConfig;
    private _endConfig;
    private _cachedElStartDocTop;
    private _cachedElEndDocTop;
    private _cachedElPositionValid;
    constructor(triggerId: number);
    /**
     * Create debug markers showing trigger positions
     */
    setup(config: MarkerSetupConfig): void;
    /**
     * Cache element marker positions in document/content coordinates.
     * Called at setup and on refresh. This is the only place getLayoutRect() is called
     * for element markers — the per-frame update() uses cached values instead.
     */
    private _cacheElementPositions;
    /**
     * Update marker positions on scroll.
     * Uses cached document-relative positions to avoid getLayoutRect() per frame.
     */
    update(scrollTop: number, triggerElement: Element | null, viewportHeight: number): void;
    /**
     * Recache element positions after layout changes (resize, refresh).
     */
    recachePositions(viewportHeight: number): void;
    /**
     * Remove all markers
     */
    cleanup(): void;
}
export {};
