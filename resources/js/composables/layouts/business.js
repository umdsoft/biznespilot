import {
    HomeIcon, ChartBarIcon, UsersIcon, DocumentTextIcon, CogIcon, BellIcon,
    InboxIcon, MegaphoneIcon, CalendarIcon, UserGroupIcon, ClipboardDocumentListIcon,
    PhoneIcon, ChatBubbleLeftRightIcon, ShieldCheckIcon, BuildingOfficeIcon,
    CreditCardIcon, ClockIcon, CheckCircleIcon, ArrowLeftIcon, BoltIcon,
    PresentationChartLineIcon, TagIcon, LightBulbIcon, DocumentChartBarIcon,
    AdjustmentsHorizontalIcon, UserIcon, CurrencyDollarIcon, BriefcaseIcon,
    UserPlusIcon, BookOpenIcon, SunIcon, ArrowPathIcon, FireIcon, ScaleIcon,
    ChartPieIcon, SparklesIcon, BanknotesIcon, ShoppingCartIcon, CubeIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';
import { FacebookIcon, InstagramIcon, TelegramIcon } from './social-icons.js';

export const businessLayoutConfig = {
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
  homeUrl: '/business',
  brandName: 'BiznesPilot AI',
  brandSubtitle: null,
  logoGradient: 'bg-gradient-to-br from-blue-600 to-indigo-600',
  logoTextGradient: 'bg-gradient-to-r from-blue-600 to-indigo-600',
  logoIcon: BoltIcon,

  // Features
  showBusinessSelector: true,
  showBusinessInfo: false,
  showNotifications: true,
  showDarkModeToggle: true,
  showFeedbackWidget: true,
  showQuickStats: false,
  showTitleIcon: false,

  // Business avatar
  businessAvatarClass: 'bg-gradient-to-br from-blue-500 to-indigo-600',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50',
  userAvatarTextClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: null, // Will show @login

  // User menu
  userMenuItems: [
    { href: '/business/settings', label: 'Sozlamalar' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: null,
      titleKey: null,
      items: [
        { href: '/business', label: 'Bosh sahifa', labelKey: 'nav.dashboard', icon: HomeIcon, exact: true },
        { href: '/business/ai-agent', label: 'AI Agent', icon: SparklesIcon, activeMatch: (url) => url.startsWith('/business/ai-agent') },
        { href: '/business/hr', label: 'HR va Xodimlar', icon: UserGroupIcon, activeMatch: (url) => url.startsWith('/business/hr') },
        { href: '/business/marketing', label: 'Marketing', labelKey: 'nav.marketing', icon: MegaphoneIcon, activeMatch: (url) => url.startsWith('/business/marketing') && !url.startsWith('/business/marketing/content') },
        { href: '/business/marketing/content', label: 'Kontent Reja', labelKey: 'nav.content_plan', icon: CalendarIcon, activeMatch: (url) => url.startsWith('/business/marketing/content') },
        { href: '/business/inbox', label: 'Yagona Inbox', labelKey: 'nav.inbox', icon: InboxIcon, badgeKey: 'unread_messages' },
      ]
    },
    {
      title: null,
      titleKey: null,
      items: [
        { href: '/business/sales', label: 'Lidlar', labelKey: 'nav.leads', icon: PresentationChartLineIcon, badgeKey: 'new_leads' },
        { href: '/business/tasks', label: 'Vazifalar', labelKey: 'nav.tasks', icon: ClipboardDocumentListIcon },
        { href: '/business/todos', label: 'Kunlik vazifalar', labelKey: 'nav.daily_tasks', icon: CheckCircleIcon },
      ]
    },
    {
      title: 'Tahlillar',
      titleKey: 'nav.analytics_section',
      items: [
        { href: '/business/marketer', label: 'Marketer Punkti', labelKey: 'nav.marketer_dashboard', icon: SparklesIcon },
        { href: '/business/kpi', label: 'KPI Reja', labelKey: 'nav.kpi', icon: PresentationChartLineIcon },
        { href: '/business/calls', label: 'Qo\'ng\'iroq Tahlili', labelKey: 'nav.call_center', icon: SparklesIcon },
        { href: '/business/sales-scripts', label: 'Sotuv Skriptlari', labelKey: 'nav.sales_scripts', icon: DocumentTextIcon },
        { href: '/business/operator-scorecards', label: 'Operator Reyting', labelKey: 'nav.operator_scorecards', icon: ChartBarIcon },
        { href: '/business/coaching-tasks', label: 'Coaching Vazifalari', labelKey: 'nav.coaching_tasks', icon: AcademicCapIcon },
        { href: '/business/competitor-insights', label: 'AI Tavsiyalar', labelKey: 'nav.ai_insights', icon: LightBulbIcon },
      ]
    },
    {
      title: 'Integratsiyalar',
      titleKey: 'nav.integrations_section',
      items: [
        { href: '/integrations', label: 'Integratsiyalar', labelKey: 'nav.integrations', icon: BoltIcon },
        { href: '/business/facebook-analysis', label: 'Target Analiz', labelKey: 'nav.target_analysis', icon: FacebookIcon, integration: 'facebook' },
        { href: '/integrations/instagram', label: 'Instagram', labelKey: 'nav.instagram', icon: InstagramIcon, integration: 'instagram' },
        { href: '/business/telegram-funnels', label: 'Telegram Bot', labelKey: 'nav.telegram', icon: TelegramIcon, activeMatch: (url) => url.startsWith('/business/telegram-funnels') || url.startsWith('/business/telegram') },
      ]
    },
    {
      title: null,
      titleKey: null,
      items: [
        { href: '/business/subscription', label: 'Tarif va To\'lov', labelKey: 'nav.billing', icon: CreditCardIcon },
        { href: '/business/settings', label: 'Sozlamalar', labelKey: 'nav.settings', icon: CogIcon },
      ]
    },
  ],
};
