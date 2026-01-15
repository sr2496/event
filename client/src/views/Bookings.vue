<template>
  <div class="bookings-page">
    <div class="container">
      <div class="page-header">
        <h1>My Bookings</h1>
        <p>Manage your event bookings and vendor assignments</p>
      </div>

      <!-- Tabs -->
      <div class="booking-tabs">
        <button 
          :class="['tab', activeTab === 'all' && 'active']"
          @click="activeTab = 'all'"
        >
          All Bookings
        </button>
        <button 
          :class="['tab', activeTab === 'upcoming' && 'active']"
          @click="activeTab = 'upcoming'"
        >
          Upcoming
        </button>
        <button 
          :class="['tab', activeTab === 'completed' && 'active']"
          @click="activeTab = 'completed'"
        >
          Completed
        </button>
      </div>

      <!-- Bookings List -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading bookings...</p>
      </div>

      <div v-else-if="filteredBookings.length" class="bookings-list">
        <RouterLink 
          v-for="booking in filteredBookings" 
          :key="booking.id"
          :to="`/bookings/${booking.id}`"
          class="booking-card card"
        >
          <div class="booking-date-col">
            <div class="booking-date">
              <span class="date-day">{{ formatDay(booking.event_date) }}</span>
              <span class="date-month">{{ formatMonth(booking.event_date) }}</span>
              <span class="date-year">{{ formatYear(booking.event_date) }}</span>
            </div>
            <span :class="['status-badge', `status-${booking.status}`]">
              {{ booking.status }}
            </span>
          </div>

          <div class="booking-info">
            <h3>{{ booking.title }}</h3>
            <p class="booking-type">{{ formatEventType(booking.type) }}</p>
            <div class="booking-meta">
              <span>📍 {{ booking.venue || booking.city }}</span>
              <span v-if="booking.expected_guests">👥 {{ booking.expected_guests }} guests</span>
            </div>
            
            <!-- Vendors -->
            <div class="booking-vendors" v-if="booking.vendors?.length">
              <div class="vendor-item" v-for="ev in booking.vendors" :key="ev.id">
                <span class="vendor-role">{{ ev.role }}</span>
                <span class="vendor-name">{{ ev.vendor?.business_name }}</span>
                <span :class="['vendor-status', `vs-${ev.status}`]">{{ ev.status }}</span>
              </div>
            </div>
          </div>

          <div class="booking-amount">
            <span class="amount-label">Total Amount</span>
            <span class="amount-value">₹{{ formatPrice(booking.financials?.total_amount) }}</span>
            <span class="assurance-badge" v-if="!booking.has_emergency">
              🛡️ Backup Protected
            </span>
          </div>

          <div class="booking-arrow">→</div>
        </RouterLink>
      </div>

      <div v-else class="empty-state">
        <span class="empty-icon">📋</span>
        <h3>No bookings found</h3>
        <p>{{ activeTab === 'all' ? 'You haven\'t made any bookings yet' : `No ${activeTab} bookings` }}</p>
        <RouterLink to="/vendors" class="btn btn-primary">Find Vendors</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useBookingStore } from '../stores/bookings'

const bookingStore = useBookingStore()

const loading = ref(true)
const activeTab = ref('all')

const filteredBookings = computed(() => {
  const bookings = bookingStore.bookings
  
  if (activeTab.value === 'upcoming') {
    return bookings.filter(b => b.is_upcoming)
  }
  if (activeTab.value === 'completed') {
    return bookings.filter(b => b.status === 'completed')
  }
  return bookings
})

function formatDay(dateStr) {
  return new Date(dateStr).getDate()
}

function formatMonth(dateStr) {
  return new Date(dateStr).toLocaleDateString('en', { month: 'short' })
}

function formatYear(dateStr) {
  return new Date(dateStr).getFullYear()
}

function formatEventType(type) {
  return type?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || ''
}

function formatPrice(amount) {
  if (!amount) return '0'
  return new Intl.NumberFormat('en-IN').format(amount)
}

onMounted(async () => {
  loading.value = true
  try {
    await bookingStore.fetchBookings()
  } catch (err) {
    console.error('Failed to load bookings', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.bookings-page {
  padding: 40px 0 80px;
}

.page-header {
  margin-bottom: 32px;
}

.page-header h1 {
  font-size: 2rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.page-header p {
  color: var(--text-secondary);
}

/* Tabs */
.booking-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 32px;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0;
}

.tab {
  padding: 12px 24px;
  background: none;
  border: none;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all var(--transition-fast);
}

.tab:hover {
  color: var(--text-primary);
}

.tab.active {
  color: var(--primary-600);
  border-bottom-color: var(--primary-600);
}

/* Bookings List */
.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.booking-card {
  display: grid;
  grid-template-columns: 120px 1fr 180px 40px;
  gap: 24px;
  align-items: center;
  padding: 24px;
  text-decoration: none;
  transition: all var(--transition-fast);
}

.booking-card:hover {
  transform: translateX(8px);
  border-color: var(--primary-300);
}

.booking-date-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.booking-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
}

.date-day {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.date-month {
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.date-year {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.status-badge {
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-pending { background: var(--warning-50); color: var(--warning-600); }
.status-confirmed { background: var(--success-50); color: var(--success-600); }
.status-completed { background: var(--gray-100); color: var(--gray-600); }
.status-cancelled { background: var(--danger-50); color: var(--danger-600); }
.status-emergency { background: var(--danger-50); color: var(--danger-600); }
.status-in_progress { background: var(--primary-50); color: var(--primary-600); }

.booking-info h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.booking-type {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.booking-meta {
  display: flex;
  gap: 16px;
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 12px;
}

.booking-vendors {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.vendor-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  background: var(--bg-secondary);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
}

.vendor-role {
  color: var(--text-secondary);
  text-transform: capitalize;
}

.vendor-name {
  color: var(--text-primary);
  font-weight: 500;
}

.vendor-status {
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.vs-confirmed { background: var(--success-50); color: var(--success-600); }
.vs-pending { background: var(--warning-50); color: var(--warning-600); }
.vs-completed { background: var(--gray-100); color: var(--gray-600); }

.booking-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.amount-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.amount-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.assurance-badge {
  font-size: 0.75rem;
  color: var(--success-600);
  margin-top: 8px;
}

.booking-arrow {
  font-size: 1.5rem;
  color: var(--gray-300);
  transition: color var(--transition-fast);
}

.booking-card:hover .booking-arrow {
  color: var(--primary-600);
}

/* Loading & Empty */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  text-align: center;
}

.loading-state p {
  color: var(--text-secondary);
  margin-top: 16px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 1024px) {
  .booking-card {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .booking-date-col {
    flex-direction: row;
    justify-content: space-between;
  }

  .booking-date {
    flex-direction: row;
    gap: 8px;
  }

  .booking-amount {
    flex-direction: row;
    align-items: center;
    gap: 16px;
  }

  .booking-arrow {
    display: none;
  }
}
</style>
