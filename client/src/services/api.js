import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
})

// Request interceptor to add auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => Promise.reject(error)
)

// Response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token')
            localStorage.removeItem('user')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Auth API
export const authApi = {
    login: (credentials) => api.post('/auth/login', credentials),
    register: (data) => api.post('/auth/register', data),
    logout: () => api.post('/auth/logout'),
    getUser: () => api.get('/auth/user'),
    updateProfile: (data) => api.put('/auth/profile', data),
    changePassword: (data) => api.put('/auth/password', data),
}

// Vendors API
export const vendorApi = {
    getAll: (params) => api.get('/vendors', { params }),
    getFeatured: () => api.get('/vendors/featured'),
    getCategories: () => api.get('/vendors/categories'),
    getCities: () => api.get('/vendors/cities'),
    getBySlug: (slug) => api.get(`/vendors/${slug}`),
    checkAvailability: (slug, date) => api.get(`/vendors/${slug}/availability`, { params: { date } }),
}

// Bookings API
export const bookingApi = {
    getAll: (params) => api.get('/bookings', { params }),
    getDashboard: () => api.get('/bookings/dashboard'),
    getUpcoming: () => api.get('/bookings/upcoming'),
    getById: (id) => api.get(`/bookings/${id}`),
    create: (data) => api.post('/bookings', data),
    confirmPayment: (id, data) => api.post(`/bookings/${id}/confirm-payment`, data),
    cancel: (id, reason) => api.post(`/bookings/${id}/cancel`, { reason }),
    markCompleted: (id) => api.post(`/bookings/${id}/complete`),
}

// Vendor Account API (Authenticated)
export const vendorAccountApi = {
    getDashboard: () => api.get('/vendor/dashboard'),
    updateProfile: (data) => api.put('/vendor/profile', data),
    updateContact: (data) => api.put('/vendor/contact-profile', data),
    uploadPortfolio: (data) => api.post('/vendor/portfolio', data),
    deletePortfolio: (id) => api.delete(`/vendor/portfolio/${id}`),
    getEmergencyRequests: () => api.get('/vendor/emergency-requests'),
    acceptEmergency: (id) => api.post(`/vendor/emergency/${id}/accept`),
    rejectEmergency: (id) => api.post(`/vendor/emergency/${id}/reject`),
}

// Emergency API
export const emergencyApi = {
    trigger: (eventId, data) => api.post(`/emergency/${eventId}/trigger`, data),
    getStatus: (eventId) => api.get(`/emergency/${eventId}/status`),
}

// Admin API
export const adminApi = {
    getDashboard: () => api.get('/admin/dashboard'),
    getVendors: (params) => api.get('/admin/vendors', { params }),
    verifyVendor: (id, reason) => api.post(`/admin/vendors/${id}/verify`, { reason }),
    suspendVendor: (id, reason) => api.post(`/admin/vendors/${id}/suspend`, { reason }),
    getEmergencies: (params) => api.get('/admin/emergencies', { params }),
    overrideBackup: (id, data) => api.post(`/admin/emergencies/${id}/override`, data),
}

export default api
