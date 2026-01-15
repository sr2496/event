import { defineStore } from 'pinia'
import { ref } from 'vue'
import { bookingApi, emergencyApi } from '../services/api'

export const useBookingStore = defineStore('bookings', () => {
    const bookings = ref([])
    const upcomingBookings = ref([])
    const currentBooking = ref(null)
    const loading = ref(false)
    const error = ref(null)

    async function fetchBookings(params = {}) {
        loading.value = true
        error.value = null
        try {
            const response = await bookingApi.getAll(params)
            bookings.value = response.data.data
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to load bookings'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function fetchUpcoming() {
        try {
            const response = await bookingApi.getUpcoming()
            upcomingBookings.value = response.data.data
            return response.data.data
        } catch (err) {
            console.error('Failed to load upcoming bookings', err)
        }
    }

    async function fetchBookingById(id) {
        loading.value = true
        error.value = null
        try {
            const response = await bookingApi.getById(id)
            currentBooking.value = response.data.data
            return response.data.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Booking not found'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function createBooking(data) {
        loading.value = true
        error.value = null
        try {
            const response = await bookingApi.create(data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create booking'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function confirmPayment(id, paymentData) {
        loading.value = true
        try {
            const response = await bookingApi.confirmPayment(id, paymentData)
            if (currentBooking.value?.id === id) {
                currentBooking.value = response.data.event
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Payment confirmation failed'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function cancelBooking(id, reason) {
        loading.value = true
        try {
            const response = await bookingApi.cancel(id, reason)
            // Remove from lists
            bookings.value = bookings.value.filter(b => b.id !== id)
            upcomingBookings.value = upcomingBookings.value.filter(b => b.id !== id)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to cancel booking'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function markCompleted(id) {
        loading.value = true
        try {
            const response = await bookingApi.markCompleted(id)
            if (currentBooking.value?.id === id) {
                currentBooking.value = response.data.event
            }
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to mark as completed'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function triggerEmergency(eventId, data) {
        loading.value = true
        try {
            const response = await emergencyApi.trigger(eventId, data)
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to trigger emergency'
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        bookings,
        upcomingBookings,
        currentBooking,
        loading,
        error,
        fetchBookings,
        fetchUpcoming,
        fetchBookingById,
        createBooking,
        confirmPayment,
        cancelBooking,
        markCompleted,
        triggerEmergency,
    }
})
