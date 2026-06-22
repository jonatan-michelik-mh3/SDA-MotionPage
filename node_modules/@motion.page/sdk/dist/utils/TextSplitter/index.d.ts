/**
 * TextSplitter barrel — preserves the public import path `../utils/TextSplitter`.
 *
 * Re-exports the exact public surface the original single-file module had:
 *   - class `TextSplitter`
 *   - type `SplitType` (re-exported from the shared SDK types)
 *   - interface `SplitOptions`
 *
 * The SDKRegistry self-registration side effect lives in `./TextSplitter`
 * (co-located with the class), so importing this barrel still registers the
 * implementation on SDKRegistry at module load — the behaviour the SDK entry
 * relies on. It is intentionally NOT a separate bare `import './register'`:
 * the chunked SDK bundler does not inline bare side-effect imports and would
 * leak a raw `import` statement into the standalone bundle.
 */
export { TextSplitter } from './TextSplitter';
export type { SplitType, SplitOptions } from './types';
