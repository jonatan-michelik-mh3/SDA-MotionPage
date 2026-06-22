/**
 * TextSplitter module state.
 *
 * A single WeakMap holds per-element split state, keyed by the split element.
 * It replaces the old originalContentMap + splitResultMap pair.
 */
import type { ElementSplitState } from './types';
export declare const splitStateMap: WeakMap<Element, ElementSplitState>;
