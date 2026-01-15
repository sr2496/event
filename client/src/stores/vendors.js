import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { vendorApi } from '../services/api'

export const useVendorStore = defineStore('vendors', () => {
    const vendors = ref([])
    const featuredVendors = ref([])
    const currentVendor = ref(null)
    const categories = ref([])
    const cities = ref([])
    const loading = ref(false)
    const error = ref(null)
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
    })

    const filters = ref({
        category: '',
        city: '',
        minPrice: '',
        maxPrice: '',
        minReliability: '',
        backupReady: false,
        sort: 'reliability',
    })

    async function fetchVendors(params = {}) {
        loading.value = true
        error.value = null
        try {
            const response = await vendorApi.getAll({
                ...filters.value,
                ...params,
                page: params.page || pagination.value.currentPage,
            })
            vendors.value = response.data.data
            if (response.data.meta) {
                pagination.value = {
                    currentPage: response.data.meta.current_page,
                    lastPage: response.data.meta.last_page,
                    total: response.data.meta.total,
                }
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to load vendors'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function fetchFeatured() {
        try {
            const response = await vendorApi.getFeatured()
            featuredVendors.value = response.data.data
            return response.data
        } catch (err) {
            console.error('Failed to load featured vendors', err)
        }
    }

    async function fetchCategories() {
        try {
            const response = await vendorApi.getCategories()
            categories.value = response.data.data
            return response.data.data
        } catch (err) {
            console.error('Failed to load categories', err)
        }
    }

    async function fetchCities() {
        try {
            const response = await vendorApi.getCities()
            cities.value = response.data.data
            return response.data.data
        } catch (err) {
            console.error('Failed to load cities', err)
        }
    }

    async function fetchVendorBySlug(slug) {
        loading.value = true
        error.value = null
        try {
            const response = await vendorApi.getBySlug(slug)
            currentVendor.value = response.data.data
            return response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Vendor not found'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function checkAvailability(slug, date) {
        try {
            const response = await vendorApi.checkAvailability(slug, date)
            return response.data
        } catch (err) {
            throw err
        }
    }

    function setFilters(newFilters) {
        filters.value = { ...filters.value, ...newFilters }
        pagination.value.currentPage = 1
    }

    function resetFilters() {
        filters.value = {
            category: '',
            city: '',
            minPrice: '',
            maxPrice: '',
            minReliability: '',
            backupReady: false,
            sort: 'reliability',
        }
        pagination.value.currentPage = 1
    }

    return {
        vendors,
        featuredVendors,
        currentVendor,
        categories,
        cities,
        loading,
        error,
        pagination,
        filters,
        fetchVendors,
        fetchFeatured,
        fetchCategories,
        fetchCities,
        fetchVendorBySlug,
        checkAvailability,
        setFilters,
        resetFilters,
    }
})
