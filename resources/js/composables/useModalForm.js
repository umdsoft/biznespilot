/**
 * Modal forma composable'lari (fasad)
 *
 * Har bir composable alohida faylga ajratilgan:
 *   - modal/useModalForm.js       — forma holat va yuborish
 *   - modal/useModalVisibility.js — modal ochish/yopish
 *   - modal/useQuickDates.js      — tez sana tanlash
 *   - modal/useArrayField.js      — massiv maydon boshqaruvi
 *
 * Backward compatibility: eski importlar ishlashda davom etadi.
 */

export { useModalForm } from './modal/useModalForm.js';
export { useModalVisibility } from './modal/useModalVisibility.js';
export { useQuickDates } from './modal/useQuickDates.js';
export { useArrayField } from './modal/useArrayField.js';

// Default export — eski kod bilan moslik
import { useModalForm } from './modal/useModalForm.js';
import { useModalVisibility } from './modal/useModalVisibility.js';
import { useQuickDates } from './modal/useQuickDates.js';
import { useArrayField } from './modal/useArrayField.js';

export default {
    useModalForm,
    useModalVisibility,
    useQuickDates,
    useArrayField,
};
