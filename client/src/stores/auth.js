import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../services/api'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('auth_token') || null)
    const loading = ref(false)
    const error = ref(null)

    const isAuthenticated = computed(() => !!token.value)
    const isVendor = computed(() => user.value?.role === 'vendor')
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isClient = computed(() => user.value?.role === 'client')

    async function login(credentials) {
        loading.value = true
        error.value = null
        try {
            const response = await authApi.login(credentials)
            setAuth(response.data.user, response.data.token)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Login failed'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function register(data) {
        loading.value = true
        error.value = null
        try {
            const response = await authApi.register(data)
            setAuth(response.data.user, response.data.token)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Registration failed'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        try {
            await authApi.logout()
        } catch (err) {
            // Ignore logout errors
        } finally {
            clearAuth()
        }
    }

    async function fetchUser() {
        if (!token.value) return null
        try {
            const response = await authApi.getUser()
            user.value = response.data.data
            localStorage.setItem('user', JSON.stringify(response.data.data))
            return response.data.data
        } catch (err) {
            clearAuth()
            throw err
        }
    }

    function setAuth(userData, authToken) {
        user.value = userData
        token.value = authToken
        localStorage.setItem('user', JSON.stringify(userData))
        localStorage.setItem('auth_token', authToken)
    }

    function clearAuth() {
        user.value = null
        token.value = null
        localStorage.removeItem('user')
        localStorage.removeItem('auth_token')
    }

    return {
        user,
        token,
        loading,
        error,
        isAuthenticated,
        isVendor,
        isAdmin,
        isClient,
        login,
        register,
        logout,
        fetchUser,
        clearAuth,
    }
})
