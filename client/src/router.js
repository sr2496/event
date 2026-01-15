import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

// Views
import Home from './views/Home.vue'
import Vendors from './views/Vendors.vue'
import VendorDetail from './views/VendorDetail.vue'
import Login from './views/Login.vue'
import Register from './views/Register.vue'
import Dashboard from './views/Dashboard.vue'
import Bookings from './views/Bookings.vue'
import BookingDetail from './views/BookingDetail.vue'
import CreateBooking from './views/CreateBooking.vue'
import ManageVendors from './views/ManageVendors.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
        meta: { title: 'Event Reliability Platform - Guaranteed Vendor Backup' }
    },
    {
        path: '/vendors',
        name: 'vendors',
        component: Vendors,
        meta: { title: 'Find Reliable Vendors' }
    },
    {
        path: '/vendors/:slug',
        name: 'vendor-detail',
        component: VendorDetail,
        meta: { title: 'Vendor Profile' }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { title: 'Login', guest: true }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { title: 'Create Account', guest: true }
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard', requiresAuth: true }
    },
    {
        path: '/bookings',
        name: 'bookings',
        component: Bookings,
        meta: { title: 'My Bookings', requiresAuth: true }
    },
    {
        path: '/bookings/:id',
        name: 'booking-detail',
        component: BookingDetail,
        meta: { title: 'Booking Details', requiresAuth: true }
    },
    {
        path: '/book/:vendorSlug',
        name: 'create-booking',
        component: CreateBooking,
        meta: { title: 'Book Vendor', requiresAuth: true }
    },
    {
        path: '/admin/vendors',
        name: 'admin-vendors',
        component: ManageVendors,
        meta: { title: 'Manage Vendors', requiresAuth: true, requiresRole: 'admin' }
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        }
        return { top: 0 }
    }
})

// Navigation guards
router.beforeEach((to, from, next) => {
    // Update document title
    document.title = to.meta.title ? `${to.meta.title} | Event Reliability` : 'Event Reliability Platform'

    const authStore = useAuthStore()

    // Check auth requirements
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } })
        return
    }

    // Redirect logged in users from guest pages
    if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' })
        return
    }

    next()
})

export default router
