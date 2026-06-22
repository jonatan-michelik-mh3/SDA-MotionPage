/**
 * PropertyParser relative values — detect and compute relative operators
 * (`+=`, `-=`, `*=`, `/=`).
 */
import type { PropertyValue } from './types';
/**
 * Check if a value is relative (+=, -=, *=, /=)
 */
export declare function isRelativeValue(value: PropertyValue): boolean;
/**
 * Get relative operator from value string
 */
export declare function getRelativeOperator(value: string): '+=' | '-=' | '*=' | '/=' | null;
/**
 * Calculate relative end value
 */
export declare function calculateRelativeValue(startValue: number, relativeValue: number, operator: '+=' | '-=' | '*=' | '/='): number;
