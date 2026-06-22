/**
 * TextFlapper barrel — preserves the public import path `../utils/TextFlapper`.
 *
 * Re-exports the exact public surface the original single-file module had:
 *   - class `TextFlapper`
 *   - types `UserPropEntry`, `FlapController`, `FlapCharDriver`
 *
 * The SDKRegistry self-registration side effect lives in `./TextFlapper`
 * (co-located with the class), so importing this barrel still registers the
 * implementation on SDKRegistry at module load — the behaviour the SDK entry
 * relies on. It is intentionally NOT a separate bare `import './register'`:
 * the chunked SDK bundler does not inline bare side-effect imports and would
 * leak a raw `import` statement into the standalone bundle.
 */
export { TextFlapper } from './TextFlapper';
export type { UserPropEntry, FlapController, FlapCharDriver } from './types';
