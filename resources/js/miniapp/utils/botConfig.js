/**
 * Bot type configurations for Telegram Mini App.
 * Each bot type has its own tab layout, accent color, and feature set.
 */

export const botTabConfig = {
    ecommerce: [
        { name: 'home', route: 'home', label: 'Bosh sahifa', icon: 'home' },
        { name: 'catalog', route: 'search', label: 'Katalog', icon: 'search' },
        { name: 'cart', route: 'cart', label: 'Savat', icon: 'cart' },
        { name: 'orders', route: 'orders', label: 'Buyurtmalar', icon: 'orders' },
    ],
    delivery: [
        { name: 'home', route: 'home', label: 'Menyu', icon: 'home' },
        { name: 'search', route: 'search', label: 'Qidirish', icon: 'search' },
        { name: 'cart', route: 'cart', label: 'Savat', icon: 'cart' },
        { name: 'orders', route: 'orders', label: 'Buyurtmalar', icon: 'orders' },
    ],
    queue: [
        { name: 'home', route: 'home', label: 'Xizmatlar', icon: 'home' },
        { name: 'bookings', route: 'my-bookings', label: 'Bandlovlar', icon: 'calendar' },
    ],
    service: [
        { name: 'home', route: 'home', label: 'Xizmatlar', icon: 'home' },
        { name: 'search', route: 'search', label: 'Qidirish', icon: 'search' },
        { name: 'requests', route: 'my-requests', label: "So'rovlar", icon: 'orders' },
    ],
    course: [
        { name: 'home', route: 'home', label: 'Kurslar', icon: 'home' },
        { name: 'search', route: 'search', label: 'Qidirish', icon: 'search' },
        { name: 'cart', route: 'cart', label: 'Savat', icon: 'cart' },
        { name: 'orders', route: 'orders', label: 'Buyurtmalar', icon: 'orders' },
    ],
}

export const botAccentColors = {
    ecommerce: null,
    delivery: '#F97316',
    queue: '#8B5CF6',
    service: '#0EA5E9',
    course: '#8B5CF6',
}

// Route names where bottom nav is shown (per bot type)
export const botBottomNavRoutes = {
    ecommerce: ['home', 'search', 'cart', 'orders', 'order-detail', 'category', 'catalog-item'],
    delivery: ['home', 'search', 'cart', 'orders', 'order-detail', 'category'],
    queue: ['home', 'my-bookings', 'booking-detail'],
    service: ['home', 'search', 'my-requests', 'request-detail'],
    course: ['home', 'search', 'cart', 'orders', 'order-detail', 'category', 'course-detail'],
}

// Active route groups for tab highlighting
export const botActiveRouteGroups = {
    home: ['home'],
    catalog: ['search', 'category', 'catalog-item'],
    search: ['search', 'category'],
    cart: ['cart'],
    orders: ['orders', 'order-detail'],
    bookings: ['my-bookings', 'booking-detail'],
    requests: ['my-requests', 'request-detail'],
}
