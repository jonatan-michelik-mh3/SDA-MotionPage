/**
 * PositionParser - Parse timeline position parameters
 *
 * Supports:
 * - Number: absolute time (e.g., 2)
 * - "+=0.5": relative to current end (0.5s after)
 * - "-=0.5": relative to current end (0.5s before, overlap)
 * - "<": start of previous animation
 * - ">": end of previous animation
 * - "<0.5" or "<+0.5": start of previous + offset
 * - "<-0.5": start of previous - offset
 * - ">0.5" or ">+0.5": end of previous + offset
 * - ">-0.5": end of previous - offset
 */
export declare class PositionParser {
    /**
     * Parse position parameter and return absolute time
     * @param position Position parameter (string or number)
     * @param currentEnd Current end time of timeline
     * @param previousStart Start time of previous child
     * @param previousEnd End time of previous child
     */
    static parse(position: string | number | undefined, currentEnd: number, previousStart: number, previousEnd: number): number;
}
