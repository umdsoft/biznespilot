import { h } from 'vue';
import {
  HomeIcon,
  ChartBarIcon,
  UsersIcon,
  DocumentTextIcon,
  CogIcon,
  BellIcon,
  InboxIcon,
  MegaphoneIcon,
  CalendarIcon,
  UserGroupIcon,
  ClipboardDocumentListIcon,
  PhoneIcon,
  ChatBubbleLeftRightIcon,
  ShieldCheckIcon,
  BuildingOfficeIcon,
  CreditCardIcon,
  ClockIcon,
  CheckCircleIcon,
  ArrowLeftIcon,
  BoltIcon,
  PresentationChartLineIcon,
  TagIcon,
  LightBulbIcon,
  DocumentChartBarIcon,
  AdjustmentsHorizontalIcon,
  UserIcon,
  CurrencyDollarIcon,
  BriefcaseIcon,
  UserPlusIcon,
  BookOpenIcon,
  SunIcon,
  ArrowPathIcon,
  FireIcon,
  ScaleIcon,
  ChartPieIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline';

// SVG Icons for social platforms
const FacebookIcon = {
  render() {
    return h('svg', { class: 'w-5 h-5', viewBox: '0 0 24 24', fill: 'currentColor' }, [
      h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
    ]);
  }
};

const InstagramIcon = {
  render() {
    return h('svg', { class: 'w-5 h-5', viewBox: '0 0 24 24', fill: 'currentColor' }, [
      h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
    ]);
  }
};

const TelegramIcon = {
  render() {
    return h('svg', { class: 'w-5 h-5', viewBox: '0 0 24 24', fill: 'currentColor' }, [
      h('path', { d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z' })
    ]);
  }
};

// Business Layout Configuration
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
        { href: '/business/marketing', label: 'Marketing', labelKey: 'nav.marketing', icon: MegaphoneIcon },
        { href: '/business/marketing/content', label: 'Kontent Reja', labelKey: 'nav.content_plan', icon: CalendarIcon },
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
        { href: '/business/lead-forms', label: 'Lead Formalar', labelKey: 'nav.lead_forms', icon: DocumentTextIcon },
      ]
    },
    {
      title: 'Tahlillar',
      titleKey: 'nav.analytics_section',
      items: [
        { href: '/business/analytics', label: 'Analitika', labelKey: 'nav.analytics', icon: ChartBarIcon },
        { href: '/business/kpi', label: 'KPI Reja', labelKey: 'nav.kpi', icon: PresentationChartLineIcon },
        { href: '/business/calls', label: 'Qo\'ng\'iroq Tahlili', labelKey: 'nav.call_center', icon: SparklesIcon },
        { href: '/business/competitor-insights', label: 'AI Tavsiyalar', labelKey: 'nav.ai_insights', icon: LightBulbIcon },
      ]
    },
    {
      title: 'Integratsiyalar',
      titleKey: 'nav.integrations_section',
      items: [
        { href: '/business/facebook-analysis', label: 'Target Analiz', labelKey: 'nav.target_analysis', icon: FacebookIcon },
        { href: '/integrations/instagram', label: 'Instagram', labelKey: 'nav.instagram', icon: InstagramIcon },
        { href: '/business/telegram-funnels', label: 'Telegram', labelKey: 'nav.telegram', icon: TelegramIcon },
      ]
    },
    {
      title: null,
      titleKey: null,
      items: [
        { href: '/business/settings', label: 'Sozlamalar', labelKey: 'nav.settings', icon: CogIcon },
      ]
    },
  ],
};

// SalesHead Layout Configuration
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

// Admin Layout Configuration
export const adminLayoutConfig = {
  // Styling
  bgClass: 'bg-gradient-to-br from-slate-50 via-red-50/20 to-slate-50',
  sidebarClass: 'bg-white/80 backdrop-blur-xl border-r border-red-200/50 shadow-lg',
  headerClass: 'bg-white/80 backdrop-blur-xl border-b border-red-200/50',
  logoBorderClass: 'border-red-200/50 bg-gradient-to-r from-red-50/50 to-transparent',
  sectionBorderClass: 'border-red-200/50',
  mainClass: 'p-6',
  contentClass: 'max-w-screen-2xl mx-auto',
  titleClass: 'bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent',

  // Logo
  homeUrl: '/dashboard',
  brandName: 'Admin Panel',
  brandSubtitle: null,
  logoGradient: 'bg-gradient-to-br from-red-600 to-rose-600',
  logoTextGradient: 'bg-gradient-to-r from-red-600 to-rose-600',
  logoIcon: ShieldCheckIcon,

  // Badge
  badge: 'Platforma Administratori',
  badgeClass: 'bg-gradient-to-r from-red-50 to-rose-50 border-red-200/50',
  badgeDotClass: 'bg-red-500',
  badgeTextClass: 'text-red-700',

  // Features
  showBusinessSelector: false,
  showBusinessInfo: false,
  showNotifications: false,
  showDarkModeToggle: false,
  showFeedbackWidget: false,
  showQuickStats: false,
  showTitleIcon: true,
  titleIcon: ShieldCheckIcon,
  titleIconBgClass: 'bg-gradient-to-br from-red-100 to-rose-100',
  titleIconClass: 'text-red-600',

  // User styling
  userAvatarClass: 'bg-gradient-to-br from-red-100 to-rose-100',
  userAvatarTextClass: 'bg-gradient-to-r from-red-600 to-rose-600',
  userRoleClass: 'text-red-600 font-medium',
  userRoleLabel: 'Admin',

  // User menu
  userMenuItems: [
    { href: '/dashboard/settings', label: 'Sozlamalar' },
    { href: '/business', label: 'Biznes Paneliga', class: 'text-blue-600 hover:bg-blue-50' },
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
    {
      title: null,
      items: [
        { href: '/business', label: 'Biznes Paneliga', icon: ArrowLeftIcon },
      ]
    },
  ],
};

// Marketing Layout Configuration
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
        { href: '/marketing/content', label: 'Kontent Reja', icon: CalendarIcon },
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
        { href: '/marketing/facebook-analysis', label: 'Target analiz', icon: FacebookIcon },
        { href: '/integrations/instagram', label: 'Instagram Tahlili', icon: InstagramIcon },
        { href: '/marketing/telegram-funnels', label: 'Telegram Funnel', icon: TelegramIcon },
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

// Finance Layout Configuration
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

// HR Layout Configuration
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

// Operator Layout Configuration
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
      title: 'Bilimlar Bazasi',
      items: [
        { href: '/operator/dream-buyer', label: 'Ideal Mijoz', icon: UserGroupIcon },
        { href: '/operator/offers', label: 'Takliflar', icon: TagIcon },
        { href: '/operator/sales-script', label: 'Sotuv Arsenali', icon: ChatBubbleLeftRightIcon },
      ]
    },
  ],
};

// App Layout Configuration (General)
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
        { href: '/target-analysis', label: 'Facebook analiz', icon: FacebookIcon },
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

// Helper to create config with badge counts
export function useLayoutConfig(type, badgeCounts = {}) {
  let config;

  switch (type) {
    case 'business':
      config = { ...businessLayoutConfig };
      break;
    case 'saleshead':
      config = { ...salesHeadLayoutConfig };
      break;
    case 'marketing':
      config = { ...marketingLayoutConfig };
      break;
    case 'finance':
      config = { ...financeLayoutConfig };
      break;
    case 'hr':
      config = { ...hrLayoutConfig };
      break;
    case 'operator':
      config = { ...operatorLayoutConfig };
      break;
    case 'admin':
      config = { ...adminLayoutConfig };
      break;
    case 'app':
    default:
      config = { ...appLayoutConfig };
  }

  // Apply badge counts
  if (Object.keys(badgeCounts).length > 0) {
    config.navigation = config.navigation.map(section => ({
      ...section,
      items: section.items.map(item => {
        // Use badgeKey if defined, otherwise fall back to href
        const key = item.badgeKey || item.href.split('/').pop();
        if (badgeCounts[key]) {
          return {
            ...item,
            badge: badgeCounts[key]
          };
        }
        return item;
      })
    }));
  }

  return config;
}
