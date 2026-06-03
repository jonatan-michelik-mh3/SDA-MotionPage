/**
 * GlobalPointerListener
 *
 * Consolidates high-frequency mousemove listeners. Each EventTarget gets one
 * native passive listener, and all registered SDK callbacks receive the latest
 * event in a single animation-frame dispatch.
 */
type PointerMoveCallback = (event: MouseEvent) => void;
/**
 * Register a callback for batched mouse movement.
 */
export declare function addPointerMoveCallback(callback: PointerMoveCallback, target?: EventTarget | null): void;
/**
 * Remove a previously registered mousemove callback.
 */
export declare function removePointerMoveCallback(callback: PointerMoveCallback, target?: EventTarget | null): void;
/**
 * Reset all state (for testing only).
 * @internal
 */
export declare function _resetGlobalPointerListener(): void;
export {};
