import {
    HomeIcon,
    UsersIcon,
    CurrencyDollarIcon,
    BanknotesIcon,
    CogIcon,
    HandRaisedIcon,
    GiftIcon,
} from '@heroicons/vue/24/outline';

/**
 * Partner Panel Layout konfiguratsiyasi.
 *
 * Partner hamkorlik dasturi uchun alohida clean dashboard — biznes sidebar,
 * obuna (trial) banner'i, KPI widgetlari yo'q. Faqat partner uchun kerak
 * bo'lgan sahifalar: dashboard, referrallar, komissiyalar, payout,
 * sozlamalar.
 */
export const partnerLayoutConfig = {
    // Styling — yashil-firuza gradient (hamkorlik/pul assotsiatsiyasi)
    bgClass: 'bg-gradient-to-br from-emerald-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800',
    sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
    headerClass: 'bg-white/80 dark:bg-gray-800/80 backdrop-blur border-b border-gray-200 dark:border-gray-700',
    logoBorderClass: 'border-gray-200 dark:border-gray-700',
    sectionBorderClass: 'border-gray-200 dark:border-gray-700',
    mainClass: 'p-6',
    contentClass: 'max-w-screen-2xl mx-auto',
    titleClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400 bg-clip-text text-transparent',

    // Logo va branding
    homeUrl: '/partner',
    brandName: 'BiznesPilot',
    brandSubtitle: 'Partner',
    logoGradient: 'bg-gradient-to-br from-emerald-500 to-teal-600',
    logoTextGradient: 'bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400',
    logoIcon: HandRaisedIcon,

    // Badge (Partner Program belgilaydi)
    badge: 'Hamkorlik dasturi',
    badgeClass: 'bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800',
    badgeDotClass: 'bg-emerald-500',
    badgeTextClass: 'text-emerald-700 dark:text-emerald-400',

    // Features — partner panelda biznes o'ziga xosligi YO'Q
    hideTrialBanner: true,           // ⚠️ obuna/trial banner pinhon
    showBusinessSelector: false,      // biznes almashtirish tugmasi yo'q
    showBusinessInfo: false,          // biznes kartasi yo'q
    showNotifications: true,
    showDarkModeToggle: true,
    showFeedbackWidget: false,
    showQuickStats: false,            // quick-stats KPI'lari yo'q
    showTitleIcon: true,
    titleIcon: HandRaisedIcon,
    titleIconBgClass: 'bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30',
    titleIconClass: 'text-emerald-600 dark:text-emerald-400',

    // User styling
    userAvatarClass: 'bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/50 dark:to-teal-900/50',
    userAvatarTextClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400',
    userRoleClass: 'text-emerald-600 dark:text-emerald-400',
    userRoleLabel: 'Hamkor',

    // User menu — qisqa
    userMenuItems: [
        { href: '/partner/settings', label: 'Sozlamalar' },
        { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
    ],

    // Navigation — partner panelga xos 5 ta sahifa
    navigation: [
        {
            title: null,
            items: [
                { href: '/partner', label: 'Dashboard', icon: HomeIcon, exact: true },
            ],
        },
        {
            title: 'Hamkorlik',
            items: [
                { href: '/partner/referrals', label: 'Referrallar', icon: UsersIcon },
                { href: '/partner/commissions', label: 'Komissiyalar', icon: CurrencyDollarIcon },
                { href: '/partner/payouts', label: 'Payouts', icon: BanknotesIcon },
            ],
        },
        {
            title: 'Sozlamalar',
            items: [
                { href: '/partner/settings', label: 'Profil & Bank', icon: CogIcon },
            ],
        },
    ],
};
