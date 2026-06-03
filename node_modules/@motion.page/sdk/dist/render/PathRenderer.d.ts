/**
 * PathRenderer - Encapsulates motion path rendering logic
 *
 * Extracted from PropTween to improve code organization.
 * PropTween still holds the data (for pooling), but rendering is delegated here.
 */
import type { PathPoint } from '../utils/PathParser';
interface PathRenderState {
    pathLUT: PathPoint[];
    alignOffset: {
        x: number;
        y: number;
    };
    pathOffset: {
        x: number;
        y: number;
    };
    pathRotate: boolean;
}
/**
 * Render path position at given progress
 */
export declare function renderPath(target: Element, progress: number, state: PathRenderState): void;
export {};
