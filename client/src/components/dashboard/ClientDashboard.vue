<template>
  <div class="client-dashboard">
    <div class="container">
      <!-- Welcome Header -->
      <div class="dashboard-header">
        <div class="welcome-section">
          <h1>Welcome back, {{ user?.name?.split(' ')[0] }} 👋</h1>
          <p>Here's what's happening with your events</p>
        </div>
        <RouterLink to="/vendors" class="btn btn-primary">
          <span>+</span> Book New Vendor
        </RouterLink>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <span class="stat-value">{{ upcomingCount }}</span>
            <span class="stat-label">Upcoming Events</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <span class="stat-value">{{ completedCount }}</span>
            <span class="stat-label">Completed Events</span>
          </div>
        </div>
        <div class="stat-card protected">
          <div class="stat-icon">🛡️</div>
          <div class="stat-content">
            <span class="stat-value">100%</span>
            <span class="stat-label">Protected by Backup</span>
          </div>
        </div>
      </div>

      <!-- Upcoming Events -->
      <div class="section">
        <div class="section-header">
          <h2>Upcoming Events</h2>
          <RouterLink to="/bookings" class="view-all">View All →</RouterLink>
        </div>

        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
        </div>

        <div v-else-if="upcomingBookings.length" class="events-list">
          <div v-for="event in upcomingBookings" :key="event.id" class="event-card card">
            <div class="event-date">
              <span class="date-day">{{ formatDay(event.event_date) }}</span>
              <span class="date-month">{{ formatMonth(event.event_date) }}</span>
            </div>
            <div class="event-info">
              <h3>{{ event.title }}</h3>
              <div class="event-meta">
                <span>📍 {{ event.venue || event.city }}</span>
                <span>{{ formatEventType(event.type) }}</span>
              </div>
              <div class="event-vendors" v-if="event.vendors?.length">
                <span class="vendor-tag" v-for="ev in event.vendors" :key="ev.id">
                  {{ ev.vendor?.business_name }}
                </span>
              </div>
            </div>
            <div class="event-status">
              <span :class="['status-badge', `status-${event.status}`]">
                {{ event.status }}
              </span>
              <div class="backup-status" v-if="!event.has_emergency">
                <span class="backup-icon">🛡️</span>
                <span>Backup Ready</span>
              </div>
            </div>
            <div class="event-actions">
              <RouterLink :to="`/bookings/${event.id}`" class="btn btn-secondary btn-sm">
                View Details
              </RouterLink>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="empty-icon">📅</span>
          <h3>No upcoming events</h3>
          <p>Book a vendor to get started with your event</p>
          <RouterLink to="/vendors" class="btn btn-primary">Find Vendors</RouterLink>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
          <RouterLink to="/vendors" class="action-card">
            <span class="action-icon">🔍</span>
            <span class="action-label">Find Vendors</span>
          </RouterLink>
          <RouterLink to="/bookings" class="action-card">
            <span class="action-icon">📋</span>
            <span class="action-label">My Bookings</span>
          </RouterLink>
          <RouterLink to="/vendors?category=photographer" class="action-card">
            <span class="action-icon">📷</span>
            <span class="action-label">Photographers</span>
          </RouterLink>
          <RouterLink to="/vendors?category=decorator" class="action-card">
            <span class="action-icon">🎨</span>
            <span class="action-label">Decorators</span>
          </RouterLink>
        </div>
      </div>

      <!-- How We Protect You -->
      <div class="protection-banner card">
        <div class="protection-content">
          <h3>🛡️ Your Events Are Protected</h3>
          <p>
            Every booking on Event Reliability comes with automatic backup vendor assignment. 
            If your vendor fails, we guarantee a verified replacement.
          </p>
        </div>
        <div class="protection-stats">
          <div class="p-stat">
            <span>3</span>
            <span>Backup Vendors Ready</span>
          </div>
          <div class="p-stat">
            <span>&lt; 2hrs</span>
            <span>Avg Replacement Time</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { bookingApi } from '../../services/api' 

const authStore = useAuthStore()

const loading = ref(true)
const upcomingBookings = ref([])
const completedCount = ref(0)
const upcomingCount = ref(0) // Now a ref, not computed from list (though list size might differ from total count if paginated/limited)

const user = computed(() => authStore.user)

function formatDay(dateStr) {
  return new Date(dateStr).getDate()
}

function formatMonth(dateStr) {
  return new Date(dateStr).toLocaleDateString('en', { month: 'short' })
}

function formatEventType(type) {
  return type?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || ''
}

onMounted(async () => {
  loading.value = true
  try {
    const { data } = await bookingApi.getDashboard()
    upcomingCount.value = data.stats.upcoming_count
    completedCount.value = data.stats.completed_count
    upcomingBookings.value = data.upcoming_events
  } catch (err) {
    console.error('Failed to load dashboard data', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.client-dashboard {
  padding: 40px 0;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.welcome-section h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.welcome-section p {
  color: var(--text-secondary);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-color);
}

.stat-card.protected {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
  border-color: rgba(16, 185, 129, 0.2);
}

.stat-icon { font-size: 2.5rem; }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.section { margin-bottom: 40px; }

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 1.25rem;
  color: var(--text-primary);
}

.view-all {
  color: var(--primary-600);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.event-card {
  display: grid;
  grid-template-columns: 80px 1fr auto auto;
  gap: 24px;
  align-items: center;
  padding: 20px 24px;
}

.event-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  background: var(--primary-50);
  border-radius: var(--radius-lg);
}

.date-day {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-600);
}

.date-month {
  font-size: 0.8rem;
  color: var(--primary-600);
  text-transform: uppercase;
}

.event-info h3 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.event-meta {
  display: flex;
  gap: 16px;
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.event-vendors { display: flex; gap: 8px; }

.vendor-tag {
  padding: 4px 10px;
  background: var(--bg-secondary);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.event-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending { background: var(--warning-50); color: var(--warning-600); }
.status-confirmed { background: var(--success-50); color: var(--success-600); }
.status-completed { background: var(--gray-100); color: var(--gray-600); }
.status-cancelled { background: var(--danger-50); color: var(--danger-600); }

.backup-status {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8rem;
  color: var(--success-600);
}

.empty-state {
  text-align: center;
  padding: 60px 24px;
  background: var(--bg-secondary);
  border-radius: var(--radius-xl);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 16px;
  display: block;
}

.empty-state h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.quick-actions h2 {
  font-size: 1.25rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 40px;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-xl);
  text-decoration: none;
  transition: all var(--transition-fast);
}

.action-card:hover {
  border-color: var(--primary-300);
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.action-icon { font-size: 2rem; }

.action-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-primary);
}

.protection-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 32px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(124, 58, 237, 0.1));
  border-color: rgba(99, 102, 241, 0.2);
}

.protection-content h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.protection-content p {
  color: var(--text-secondary);
  max-width: 500px;
}

.protection-stats {
  display: flex;
  gap: 32px;
}

.p-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.p-stat span:first-child {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-600);
}

.p-stat span:last-child {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 60px;
}

@media (max-width: 1024px) {
  .stats-grid { grid-template-columns: 1fr; }
  .event-card { grid-template-columns: 1fr; gap: 16px; }
  .actions-grid { grid-template-columns: repeat(2, 1fr); }
  .protection-banner { flex-direction: column; gap: 24px; text-align: center; }
}
</style>
