import {
    HomeIcon, ChartBarIcon, UsersIcon, DocumentTextIcon, CogIcon, BellIcon,
    InboxIcon, MegaphoneIcon, CalendarIcon, UserGroupIcon, ClipboardDocumentListIcon,
    PhoneIcon, ChatBubbleLeftRightIcon, ShieldCheckIcon, BuildingOfficeIcon,
    CreditCardIcon, ClockIcon, CheckCircleIcon, ArrowLeftIcon, BoltIcon,
    PresentationChartLineIcon, TagIcon, LightBulbIcon, DocumentChartBarIcon,
    AdjustmentsHorizontalIcon, UserIcon, CurrencyDollarIcon, BriefcaseIcon,
    UserPlusIcon, BookOpenIcon, SunIcon, ArrowPathIcon, FireIcon, ScaleIcon,
    ChartPieIcon, SparklesIcon, BanknotesIcon, ShoppingCartIcon, CubeIcon,
} from '@heroicons/vue/24/outline';
import { FacebookIcon, InstagramIcon, TelegramIcon } from './social-icons.js';

export const adminLayoutConfig = {
  // Styling - with dark mode support (Green theme)
  bgClass: 'bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900',
  sidebarClass: 'bg-white/80 backdrop-blur-xl border-r border-emerald-200/50 shadow-lg dark:bg-gray-800/90 dark:border-gray-700',
  headerClass: 'bg-white/80 backdrop-blur-xl border-b border-emerald-200/50 dark:bg-gray-800/90 dark:border-gray-700',
  logoBorderClass: 'border-emerald-200/50 bg-gradient-to-r from-emerald-50/50 to-transparent dark:border-gray-700 dark:from-gray-800/50',
  sectionBorderClass: 'border-emerald-200/50 dark:border-gray-700',
  mainClass: 'p-6',
  contentClass: 'max-w-screen-2xl mx-auto',
  titleClass: 'bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/dashboard',
  brandName: 'Admin Panel',
  brandSubtitle: null,
  logoGradient: 'bg-gradient-to-br from-emerald-600 to-green-600',
  logoTextGradient: 'bg-gradient-to-r from-emerald-600 to-green-600 dark:from-emerald-400 dark:to-green-400',
  logoIcon: ShieldCheckIcon,

  // Badge
  badge: 'Platforma Administratori',
  badgeClass: 'bg-gradient-to-r from-emerald-50 to-green-50 border-emerald-200/50 dark:from-emerald-900/30 dark:to-green-900/30 dark:border-emerald-700/50',
  badgeDotClass: 'bg-emerald-500',
  badgeTextClass: 'text-emerald-700 dark:text-emerald-300',

  // Features
  showBusinessSelector: false,
  showBusinessInfo: false,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: false,
  showTitleIcon: true,
  titleIcon: ShieldCheckIcon,
  titleIconBgClass: 'bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/30 dark:to-green-900/30',
  titleIconClass: 'text-emerald-600 dark:text-emerald-400',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-emerald-100 to-green-100 dark:from-emerald-900/50 dark:to-green-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-emerald-600 to-green-600 dark:from-emerald-400 dark:to-green-400',
  userRoleClass: 'text-emerald-600 dark:text-emerald-400 font-medium',
  userRoleLabel: 'Admin',

  // User menu
  userMenuItems: [
    { href: '/dashboard/settings', label: 'Sozlamalar' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 hover:bg-red-50' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      items: [
        { href: '/dashboard', label: 'Bosh sahifa', icon: HomeIcon, exact: true },
      ]
    },
    {
      title: 'Platform Boshqaruv',
      items: [
        { href: '/dashboard/businesses', label: 'Bizneslar', icon: BuildingOfficeIcon },
        { href: '/dashboard/users', label: 'Foydalanuvchilar', icon: UsersIcon },
        { href: '/dashboard/feedback', label: 'Fikr-mulohazalar', icon: ChatBubbleLeftRightIcon },
        { href: '/dashboard/notifications', label: 'Bildirishnomalar', icon: BellIcon },
        { href: '/dashboard/subscriptions', label: 'Obunalar', icon: CreditCardIcon },
        { href: '/dashboard/billing-transactions', label: 'To\'lovlar', icon: BanknotesIcon },
        { href: '/dashboard/plans', label: 'Tarif Rejalari', icon: TagIcon },
      ]
    },
    {
      title: 'Monitoring',
      items: [
        { href: '/dashboard/analytics', label: 'Analitika', icon: ChartBarIcon },
        { href: '/dashboard/system-health', label: 'Tizim Salomatligi', icon: CheckCircleIcon },
        { href: '/dashboard/activity-logs', label: 'Faoliyat jurnali', icon: ClockIcon },
      ]
    },
    {
      title: 'Tizim',
      items: [
        { href: '/dashboard/settings', label: 'Sozlamalar', icon: CogIcon },
      ]
    },
  ],
};
