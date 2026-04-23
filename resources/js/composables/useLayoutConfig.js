/**
 * Layout konfiguratsiyasi composable
 *
 * Har bir layout alohida faylga ajratilgan:
 *   - layouts/business.js
 *   - layouts/sales-head.js
 *   - layouts/admin.js
 *   - layouts/marketing.js
 *   - layouts/finance.js
 *   - layouts/hr.js
 *   - layouts/operator.js
 *   - layouts/app.js
 *
 * Backward compatibility: eski barcha `import { xxxLayoutConfig } from '@/composables/useLayoutConfig'`
 * ishlashda davom etadi.
 */

// Re-exports: har bir layout config
export { businessLayoutConfig } from './layouts/business.js';
export { salesHeadLayoutConfig } from './layouts/sales-head.js';
export { adminLayoutConfig } from './layouts/admin.js';
export { marketingLayoutConfig } from './layouts/marketing.js';
export { financeLayoutConfig } from './layouts/finance.js';
export { hrLayoutConfig } from './layouts/hr.js';
export { operatorLayoutConfig } from './layouts/operator.js';
export { appLayoutConfig } from './layouts/app.js';
export { partnerLayoutConfig } from './layouts/partner.js';

// Imports (helper uchun)
import { businessLayoutConfig } from './layouts/business.js';
import { salesHeadLayoutConfig } from './layouts/sales-head.js';
import { adminLayoutConfig } from './layouts/admin.js';
import { marketingLayoutConfig } from './layouts/marketing.js';
import { financeLayoutConfig } from './layouts/finance.js';
import { hrLayoutConfig } from './layouts/hr.js';
import { operatorLayoutConfig } from './layouts/operator.js';
import { appLayoutConfig } from './layouts/app.js';
import { partnerLayoutConfig } from './layouts/partner.js';

const configByType = {
    business: businessLayoutConfig,
    saleshead: salesHeadLayoutConfig,
    marketing: marketingLayoutConfig,
    finance: financeLayoutConfig,
    hr: hrLayoutConfig,
    operator: operatorLayoutConfig,
    admin: adminLayoutConfig,
    app: appLayoutConfig,
    partner: partnerLayoutConfig,
};

/**
 * Layout type bo'yicha config tanlash va badge sonlarini qo'llash
 */
export function useLayoutConfig(type, badgeCounts = {}) {
    const baseConfig = configByType[type] || configByType.app;
    const config = { ...baseConfig };

    if (Object.keys(badgeCounts).length === 0) {
        return config;
    }

    // Badge qiymatlarini navigation'ga qo'shish
    config.navigation = config.navigation.map(section => ({
        ...section,
        items: section.items.map(item => {
            const key = item.badgeKey || item.href.split('/').pop();
            return badgeCounts[key]
                ? { ...item, badge: badgeCounts[key] }
                : item;
        }),
    }));

    return config;
}
