/**
 * Minimal interface for timeline playback control.
 * Using an interface instead of importing Timeline directly to avoid circular dependencies
 * (utils/ never imports from core/).
 */
export interface PlaybackTarget {
    play(): unknown;
    pause(): unknown;
    reverse(): unknown;
    restart(): unknown;
    progress(value: number): unknown;
}
export type TimelineAction = 'play' | 'pause' | 'resume' | 'reverse' | 'restart' | 'reset' | 'complete' | 'none';
/**
 * Execute a GSAP-compatible timeline action. Maps action strings to method calls.
 * Covers all 8 GSAP toggleActions keywords.
 */
export declare function executeTimelineAction(action: TimelineAction, timeline: PlaybackTarget): void;
