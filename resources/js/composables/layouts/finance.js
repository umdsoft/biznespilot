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

export const financeLayoutConfig = {
  // Styling
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-6',
  contentClass: 'max-w-screen-2xl mx-auto',
  titleClass: 'bg-gradient-to-r from-green-600 to-teal-600 dark:from-green-400 dark:to-teal-400 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/finance',
  brandName: 'Moliya',
  brandSubtitle: 'Bo\'limi',
  logoGradient: 'bg-gradient-to-br from-green-600 to-teal-600',
  logoTextGradient: 'bg-gradient-to-r from-green-600 to-teal-600',
  logoIcon: CurrencyDollarIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: true,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: true,
  showTitleIcon: true,
  titleIcon: CurrencyDollarIcon,
  titleIconBgClass: 'bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/30 dark:to-teal-900/30',
  titleIconClass: 'text-green-600 dark:text-green-400',

  // Business info
  businessAvatarClass: 'bg-gradient-to-br from-green-500 to-teal-600',
  businessRoleClass: 'text-green-600 dark:text-green-400',
  businessRoleLabel: 'Moliya bo\'limi',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-green-100 to-teal-100 dark:from-green-900/50 dark:to-teal-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-green-600 to-teal-600 dark:from-green-400 dark:to-teal-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: 'Moliya mutaxassisi',

  // User menu
  userMenuItems: [
    { href: '/finance/profile', label: 'Profil' },
    { href: '/business', label: 'Biznes Paneliga', class: 'text-blue-600 hover:bg-blue-50' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      items: [
        { href: '/finance', label: 'Bosh sahifa', icon: HomeIcon, exact: true },
      ]
    },
    {
      title: 'Moliyaviy Operatsiyalar',
      items: [
        { href: '/finance/invoices', label: 'Hisob-fakturalar', icon: DocumentTextIcon },
        { href: '/finance/expenses', label: 'Xarajatlar', icon: CreditCardIcon },
        { href: '/finance/budget', label: 'Byudjet', icon: PresentationChartLineIcon },
      ]
    },
    {
      title: 'Hisobotlar',
      items: [
        { href: '/finance/reports', label: 'Barcha Hisobotlar', icon: DocumentChartBarIcon },
        { href: '/finance/reports/profit-loss', label: 'Foyda va Zarar', icon: ChartBarIcon },
        { href: '/finance/reports/cash-flow', label: 'Pul Oqimi', icon: CurrencyDollarIcon },
      ]
    },
    {
      title: 'Vazifalar',
      items: [
        { href: '/finance/tasks', label: 'Vazifalar', icon: ClipboardDocumentListIcon },
        { href: '/finance/todos', label: 'Kunlik vazifalar', icon: CheckCircleIcon },
      ]
    },
    {
      title: 'Marketing Ma\'lumotlari',
      items: [
        { href: '/finance/campaigns', label: 'Kampaniya Byudjetlari', icon: PresentationChartLineIcon },
        { href: '/finance/marketing-analytics', label: 'Marketing ROI', icon: ChartBarIcon },
      ]
    },
  ],
};
