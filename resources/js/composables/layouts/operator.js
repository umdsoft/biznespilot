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

export const operatorLayoutConfig = {
  // Styling
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-4',
  contentClass: '', // Full width for leads
  titleClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/operator',
  brandName: 'Operator',
  brandSubtitle: 'Panel',
  logoGradient: 'bg-gradient-to-br from-blue-600 to-indigo-600',
  logoTextGradient: 'bg-gradient-to-r from-blue-600 to-indigo-600',
  logoIcon: PhoneIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: true,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: true,
  showTitleIcon: true,
  titleIcon: PhoneIcon,
  titleIconBgClass: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30',
  titleIconClass: 'text-blue-600 dark:text-blue-400',

  // Business info
  businessAvatarClass: 'bg-gradient-to-br from-blue-500 to-indigo-600',
  businessRoleClass: 'text-blue-600 dark:text-blue-400',
  businessRoleLabel: 'Sotuv operatori',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: 'Operator',

  // User menu
  userMenuItems: [
    { href: '/business', label: 'Biznes Paneliga', class: 'text-blue-600 hover:bg-blue-50' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      items: [
        { href: '/operator', label: 'Bosh sahifa', icon: HomeIcon, exact: true },
        { href: '/operator/my-day', label: 'Mening Kunim', icon: SunIcon },
      ]
    },
    {
      title: 'Ishim',
      items: [
        { href: '/operator/leads', label: 'Mening Lidlarim', icon: UserGroupIcon, badgeKey: 'new_leads' },
        { href: '/operator/inbox', label: 'Yagona Inbox', icon: InboxIcon, badgeKey: 'unread_messages' },
        { href: '/operator/tasks', label: 'Vazifalarim', icon: ClipboardDocumentListIcon },
        { href: '/operator/todos', label: 'Kunlik vazifalar', icon: CheckCircleIcon },
      ]
    },
    {
      title: 'Statistika',
      items: [
        { href: '/operator/kpi', label: 'Mening KPI', icon: PresentationChartLineIcon },
      ]
    },
    {
      title: "Do'kon",
      items: [
        { href: '/operator/store/dashboard', label: "Do'kon", icon: ShoppingCartIcon },
        { href: '/operator/store/orders', label: 'Buyurtmalar', icon: ClipboardDocumentListIcon, badgeKey: 'store_pending_orders' },
        { href: '/operator/store/customers', label: 'Mijozlar', icon: UsersIcon },
      ]
    },
    {
      title: 'Bilimlar Bazasi',
      items: [
        { href: '/operator/dream-buyer', label: 'Ideal Mijoz', icon: UserGroupIcon },
        { href: '/operator/offers', label: 'Takliflar', icon: TagIcon },
        { href: '/operator/sales-script', label: 'Sotuv Arsenali', icon: ChatBubbleLeftRightIcon },
      ]
    },
  ],
};
