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

export const marketingLayoutConfig = {
  // Styling - same as business layout (professional look)
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-6',
  contentClass: 'max-w-screen-2xl mx-auto',
  titleClass: 'bg-gradient-to-r from-gray-900 to-gray-700 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/marketing',
  brandName: 'Marketing',
  brandSubtitle: 'Bo\'limi',
  logoGradient: 'bg-gradient-to-br from-blue-600 to-indigo-600',
  logoTextGradient: 'bg-gradient-to-r from-blue-600 to-indigo-600',
  logoIcon: MegaphoneIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: true,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: false,
  showTitleIcon: false,
  titleIcon: MegaphoneIcon,
  titleIconBgClass: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30',
  titleIconClass: 'text-blue-600 dark:text-blue-400',

  // Business info
  businessAvatarClass: 'bg-gradient-to-br from-blue-500 to-indigo-600',
  businessRoleClass: 'text-blue-600 dark:text-blue-400',
  businessRoleLabel: 'Marketing bo\'limi',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: 'Marketing mutaxassisi',

  // User menu
  userMenuItems: [
    { href: '/marketing/profile', label: 'Profil' },
    { href: '/business', label: 'Biznes Paneliga', class: 'text-blue-600 hover:bg-blue-50' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      items: [
        { href: '/marketing', label: 'Marketing Markazi', icon: HomeIcon, exact: true },
        { href: '/marketing/dashboard', label: 'Bosh sahifa', icon: ChartBarIcon },
        { href: '/marketing/campaigns', label: 'Kampaniyalar', icon: PresentationChartLineIcon },
        { href: '/marketing/content', label: 'Kontent Reja', icon: CalendarIcon, activeMatch: (url) => url.startsWith('/marketing/content') },
        { href: '/marketing/channels', label: 'Kanallar', icon: UsersIcon },
      ]
    },
    {
      title: 'Tadqiqot',
      items: [
        { href: '/marketing/dream-buyer', label: 'Ideal Mijoz', icon: UserGroupIcon },
        { href: '/marketing/custdev', label: 'CustDev So\'rovnoma', icon: ClipboardDocumentListIcon },
      ]
    },
    {
      title: 'Strategiya',
      items: [
        { href: '/marketing/competitors', label: 'Raqobatchilar', icon: ChartBarIcon },
        { href: '/marketing/competitors/dashboard', label: 'Raqobat Dashboard', icon: PresentationChartLineIcon },
        { href: '/marketing/offers', label: 'Takliflar', icon: TagIcon },
        { href: '/marketing/swot', label: 'SWOT Tahlil', icon: AdjustmentsHorizontalIcon },
        { href: '/marketing/competitor-insights', label: 'AI Tavsiyalar', icon: LightBulbIcon },
      ]
    },
    {
      title: 'Kommunikatsiya',
      items: [
        { href: '/marketing/inbox', label: 'Yagona Inbox', icon: InboxIcon },
        { href: '/marketing/lead-forms', label: 'Lead Formalar', icon: DocumentTextIcon },
        { href: '/marketing/chatbot', label: 'Chatbot', icon: ChatBubbleLeftRightIcon },
      ]
    },
    {
      title: null,
      items: [
        { href: '/marketing/tasks', label: 'Vazifalar', icon: ClipboardDocumentListIcon },
        { href: '/marketing/todos', label: 'Kunlik vazifalar', icon: CheckCircleIcon },
      ]
    },
    {
      title: 'Integratsiyalar',
      items: [
        { href: '/integrations', label: 'Integratsiyalar', icon: BoltIcon },
        { href: '/marketing/facebook-analysis', label: 'Target analiz', icon: FacebookIcon, integration: 'facebook' },
        { href: '/integrations/instagram', label: 'Instagram Tahlili', icon: InstagramIcon, integration: 'instagram' },
        { href: '/marketing/telegram-funnels', label: 'Telegram Funnel', icon: TelegramIcon, integration: 'telegram' },
      ]
    },
    {
      title: 'Tahlillar',
      items: [
        { href: '/marketing/analytics', label: 'Umumiy Analitika', icon: ChartBarIcon },
        { href: '/marketing/analytics/campaigns', label: 'Kampaniya Analitika', icon: PresentationChartLineIcon },
        { href: '/marketing/sales-integration', label: 'Sotuv Integratsiyasi (70/30)', icon: ChartPieIcon },
      ]
    },
    {
      title: null,
      items: [
        { href: '/marketing/settings', label: 'Sozlamalar', icon: CogIcon },
      ]
    },
  ],
};
