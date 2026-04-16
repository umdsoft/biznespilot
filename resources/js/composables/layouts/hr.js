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

export const hrLayoutConfig = {
  // Styling
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-6',
  contentClass: 'max-w-screen-2xl mx-auto',
  titleClass: 'bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/hr',
  brandName: 'Kadrlar',
  brandSubtitle: 'Bo\'limi',
  logoGradient: 'bg-gradient-to-br from-purple-600 to-pink-600',
  logoTextGradient: 'bg-gradient-to-r from-purple-600 to-pink-600',
  logoIcon: UserGroupIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: true,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: true,
  showTitleIcon: true,
  titleIcon: UserGroupIcon,
  titleIconBgClass: 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30',
  titleIconClass: 'text-purple-600 dark:text-purple-400',

  // Business info
  businessAvatarClass: 'bg-gradient-to-br from-purple-500 to-pink-600',
  businessRoleClass: 'text-purple-600 dark:text-purple-400',
  businessRoleLabel: 'Kadrlar bo\'limi',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/50 dark:to-pink-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: 'HR mutaxassisi',

  // User menu
  userMenuItems: [
    { href: '/hr/profile', label: 'Profil' },
    { href: '/business', label: 'Biznes Paneliga', class: 'text-blue-600 hover:bg-blue-50' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      items: [
        { href: '/hr', label: 'Bosh sahifa', icon: HomeIcon, exact: true },
      ]
    },
    {
      title: 'Xodimlar Boshqaruvi',
      items: [
        { href: '/hr/employees', label: 'Xodimlar', icon: UsersIcon },
      ]
    },
    {
      title: 'Tashkiliy Tuzilma',
      items: [
        { href: '/hr/org-structure', label: 'Org struktura', icon: BuildingOfficeIcon },
      ]
    },
    {
      title: 'Ishga Qabul',
      items: [
        { href: '/hr/recruiting', label: 'Vakansiyalar', icon: BriefcaseIcon },
        { href: '/hr/onboarding', label: 'Yangi xodimlar', icon: ArrowPathIcon },
      ]
    },
    {
      title: 'Moliyaviy',
      items: [
        { href: '/hr/payroll', label: 'Ish haqi', icon: CurrencyDollarIcon },
      ]
    },
    {
      title: 'Analitika',
      items: [
        { href: '/hr/surveys', label: 'So\'rovnomalar', icon: DocumentTextIcon },
      ]
    },
    {
      title: 'Hisobotlar',
      items: [
        { href: '/hr/reports', label: 'HR Hisobotlar', icon: DocumentChartBarIcon },
      ]
    },
  ],
};
