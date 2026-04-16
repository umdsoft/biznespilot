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

export const salesHeadLayoutConfig = {
  // Styling
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-4',
  contentClass: '', // Full width for sales pipeline
  titleClass: 'bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/sales-head',
  brandName: 'Sotuv Bo\'limi',
  brandSubtitle: 'Rahbar paneli',
  logoGradient: 'bg-gradient-to-br from-emerald-600 to-teal-600',
  logoTextGradient: 'bg-gradient-to-r from-emerald-600 to-teal-600',
  logoIcon: PresentationChartLineIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: true,
  showNotifications: false,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: true,
  showTitleIcon: false,

  // Business info
  businessAvatarClass: 'bg-gradient-to-br from-emerald-500 to-teal-600',
  businessRoleClass: 'text-emerald-600 dark:text-emerald-400',
  businessRoleLabel: 'Sotuv bo\'limi',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/50 dark:to-teal-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: 'Sotuv rahbari',

  // User menu
  userMenuItems: [
    { href: '/sales-head/profile', label: 'Profil' },
    { href: '/sales-head/settings', label: 'Sozlamalar' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation - Optimized: Tab-based pages
  navigation: [
    {
      title: null,
      items: [
        { href: '/sales-head', label: 'Bosh sahifa', icon: HomeIcon, exact: true },
        { href: '/sales-head/my-day', label: 'Mening Kunim', icon: SunIcon },
      ]
    },
    {
      title: 'Sotuv',
      items: [
        { href: '/sales-head/leads', label: 'Lidlar', icon: UserGroupIcon, badgeKey: 'new_leads' },
        { href: '/sales-head/deals', label: 'Bitimlar', icon: CurrencyDollarIcon },
        { href: '/sales-head/inbox', label: 'Yagona Inbox', icon: InboxIcon, badgeKey: 'unread_messages' },
      ]
    },
    {
      title: 'Jamoa',
      items: [
        { href: '/sales-head/team', label: 'Operatorlar', icon: UsersIcon },
        { href: '/sales-head/tasks', label: 'Vazifalar', icon: ClipboardDocumentListIcon },
        { href: '/sales-head/pipeline-automation', label: 'Pipeline Avtomatizatsiya', icon: ArrowPathIcon },
        { href: '/sales-head/lead-scoring', label: 'Lead Scoring', icon: FireIcon },
      ]
    },
    {
      title: "Do'kon",
      items: [
        { href: '/sales-head/store/dashboard', label: "Do'kon", icon: ShoppingCartIcon },
        { href: '/sales-head/store/orders', label: 'Buyurtmalar', icon: ClipboardDocumentListIcon, badgeKey: 'store_pending_orders' },
        { href: '/sales-head/store/customers', label: 'Mijozlar', icon: UsersIcon },
        { href: '/sales-head/store/catalog', label: 'Katalog', icon: CubeIcon },
      ]
    },
    {
      title: 'Samaradorlik',
      items: [
        {
          label: 'KPI & Gamification',
          icon: ChartBarIcon,
          children: [
            { href: '/sales-head/sales-kpi', label: 'Dashboard' },
            { href: '/sales-head/sales-kpi/settings', label: 'KPI Sozlamalari' },
            { href: '/sales-head/sales-kpi/targets', label: 'Maqsadlar' },
            { href: '/sales-head/sales-kpi/bonuses', label: 'Bonuslar' },
            { href: '/sales-head/sales-kpi/penalties', label: 'Jarimalar' },
            { href: '/sales-head/sales-kpi/leaderboard', label: 'Reyting' },
            { href: '/sales-head/sales-kpi/achievements', label: 'Yutuqlar' },
          ]
        },
        { href: '/sales-head/calls', label: 'Qo\'ng\'iroq Tahlili', icon: SparklesIcon },
        { href: '/sales-head/sales-analytics', label: 'Sotuv Analitikasi (ROP)', icon: ChartPieIcon },
        { href: '/sales-head/analytics', label: 'Analitika', icon: PresentationChartLineIcon },
      ]
    },
    {
      title: 'Marketing & AI',
      items: [
        // Marketing ma'lumotlari bitta sahifada tab sifatida
        { href: '/sales-head/marketing-info', label: 'Marketing Ma\'lumotlari', icon: MegaphoneIcon },
        // AI tavsiyalar va skriptlar birlashgan
        { href: '/sales-head/competitor-insights', label: 'AI Tavsiyalar', icon: LightBulbIcon },
      ]
    },
  ],
};
