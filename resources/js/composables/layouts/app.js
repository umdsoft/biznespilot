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

export const appLayoutConfig = {
  // Styling
  bgClass: 'bg-gray-50 dark:bg-gray-900',
  sidebarClass: 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700',
  headerClass: 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700',
  logoBorderClass: 'border-gray-200 dark:border-gray-700',
  sectionBorderClass: 'border-gray-200 dark:border-gray-700',
  mainClass: 'p-6',
  contentClass: '',
  titleClass: 'text-gray-900 dark:text-gray-100',

  // Logo
  homeUrl: '/',
  brandName: 'BiznesPilot AI',
  brandSubtitle: null,
  logoGradient: 'bg-gradient-to-br from-blue-600 to-indigo-600',
  logoTextGradient: 'text-primary-600 dark:text-primary-400',
  logoIcon: BoltIcon,

  // Features
  showBusinessSelector: false,
  showBusinessInfo: false,
  showNotifications: false,
  showDarkModeToggle: true,
  showFeedbackWidget: false,
  showQuickStats: false,
  showTitleIcon: false,

  // User styling
  userAvatarClass: 'bg-primary-100 dark:bg-primary-900/50',
  userAvatarTextClass: 'text-primary-700 dark:text-primary-300',
  userRoleClass: 'text-gray-500 dark:text-gray-400',
  userRoleLabel: null,

  // User menu
  userMenuItems: [
    { href: '/business/settings', label: 'Sozlamalar' },
    { href: '/logout', label: 'Chiqish', method: 'post', as: 'button', class: 'w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30' },
  ],

  // Navigation
  navigation: [
    {
      title: 'Asosiy',
      items: [
        { href: '/', label: 'Bosh Sahifa', icon: HomeIcon, exact: true },
        { href: '/kpi-dashboard', label: 'KPI Boshqaruv', icon: ChartBarIcon },
        { href: '/reports', label: 'Hisobotlar', icon: DocumentChartBarIcon },
      ]
    },
    {
      title: 'Biznes',
      items: [
        { href: '/business', label: 'Biznes', icon: BuildingOfficeIcon },
        { href: '/business/dream-buyer', label: 'Ideal Mijoz', icon: UserIcon },
        { href: '/business/marketing', label: 'Marketing', icon: MegaphoneIcon },
        { href: '/inbox', label: 'Yagona Inbox', icon: InboxIcon },
        { href: '/business/sales', label: 'Sotuv / Leadlar', icon: PresentationChartLineIcon },
        { href: '/business/competitors', label: 'Raqobatchilar', icon: ChartBarIcon },
        { href: '/business/offers', label: 'Takliflar', icon: TagIcon },
      ]
    },
    {
      title: 'AI & Tahlil',
      items: [
        { href: '/ai', label: 'AI Tahlil', icon: LightBulbIcon },
        { href: '/target-analysis', label: 'Facebook analiz', icon: FacebookIcon, integration: 'facebook' },
        { href: '/analytics/channels', label: 'Kanal Tahlili', icon: ChartBarIcon },
        { href: '/chatbot', label: 'Chatbot', icon: ChatBubbleLeftRightIcon },
      ]
    },
    {
      title: 'Sozlamalar',
      items: [
        { href: '/business/settings', label: 'Sozlamalar', icon: CogIcon },
      ]
    },
  ],
};
