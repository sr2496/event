<template>
  <div class="admin-dashboard">
    <div class="container">
      <!-- Welcome Header -->
      <div class="dashboard-header">
        <div class="welcome-section">
          <span class="role-badge admin">👑 Admin</span>
          <h1>Admin Dashboard</h1>
          <p>Platform overview and management</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-primary" @click="manageVendors">
            👥 Manage Vendors
          </button>
        </div>
      </div>

      <!-- Platform Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <span class="stat-value">{{ stats.totalUsers }}</span>
            <span class="stat-label">Total Users</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🏪</div>
          <div class="stat-content">
            <span class="stat-value">{{ stats.totalVendors }}</span>
            <span class="stat-label">Verified Vendors</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <span class="stat-value">{{ stats.totalEvents }}</span>
            <span class="stat-label">Total Bookings</span>
          </div>
        </div>
        <div class="stat-card revenue">
          <div class="stat-icon">💰</div>
          <div class="stat-content">
            <span class="stat-value">₹{{ formatAmount(stats.totalRevenue) }}</span>
            <span class="stat-label">Platform Revenue</span>
          </div>
        </div>
      </div>

      <!-- Emergency / Pending Section -->
      <div class="alerts-section" v-if="pendingItems.length">
        <h2>⚠️ Requires Attention</h2>
        <div class="alerts-grid">
          <div v-for="alert in pendingItems" :key="alert.id" class="alert-card card" :class="alert.type">
            <div class="alert-icon">{{ alert.icon }}</div>
            <div class="alert-info">
              <h3>{{ alert.title }}</h3>
              <p>{{ alert.description }}</p>
            </div>
            <button class="btn btn-sm" :class="alert.buttonClass" @click="handleAlert(alert)">
              {{ alert.action }}
            </button>
          </div>
        </div>
      </div>

      <!-- Dashboard Grid -->
      <div class="dashboard-grid">
        <!-- Recent Bookings -->
        <div class="section">
          <div class="section-header">
            <h2>📋 Recent Bookings</h2>
            <button class="view-all-btn" @click="viewAllBookings">View All →</button>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Event</th>
                  <th>Client</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="booking in recentBookings" :key="booking.id">
                  <td>#{{ booking.id }}</td>
                  <td>{{ booking.title }}</td>
                  <td>{{ booking.client }}</td>
                  <td>{{ booking.date }}</td>
                  <td>
                    <span :class="['status-badge', `status-${booking.status}`]">
                      {{ booking.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Emergency Requests -->
        <div class="section">
          <div class="section-header">
            <h2>🚨 Active Emergencies</h2>
          </div>
          <div v-if="emergencies.length" class="emergency-list">
            <div v-for="emergency in emergencies" :key="emergency.id" class="emergency-item card">
              <div class="emergency-status" :class="emergency.status">
                {{ emergency.status === 'resolved' ? '✅' : '🔴' }}
              </div>
              <div class="emergency-info">
                <h4>{{ emergency.event }}</h4>
                <p>{{ emergency.vendor }} failed • {{ emergency.time }}</p>
              </div>
              <button v-if="emergency.status !== 'resolved'" class="btn btn-secondary btn-sm">
                Manage
              </button>
            </div>
          </div>
          <div v-else class="empty-state small">
            <span>✅</span>
            <p>No active emergencies</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="admin-actions">
        <h2>Admin Actions</h2>
        <div class="actions-grid">
          <div class="action-card" @click="manageVendors">
            <span class="action-icon">🏪</span>
            <span class="action-label">Manage Vendors</span>
            <span class="action-count">{{ stats.pendingVerification }} pending</span>
          </div>
          <div class="action-card" @click="manageUsers">
            <span class="action-icon">👥</span>
            <span class="action-label">Manage Users</span>
          </div>
          <div class="action-card" @click="manageCategories">
            <span class="action-icon">📂</span>
            <span class="action-label">Categories</span>
          </div>
          <div class="action-card" @click="viewReports">
            <span class="action-icon">📊</span>
            <span class="action-label">Reports</span>
          </div>
          <div class="action-card" @click="viewAuditLog">
            <span class="action-icon">📝</span>
            <span class="action-label">Audit Log</span>
          </div>
          <div class="action-card" @click="platformSettings">
            <span class="action-icon">⚙️</span>
            <span class="action-label">Settings</span>
          </div>
        </div>
      </div>

      <!-- Platform Health -->
      <div class="health-section card">
        <h3>🏥 Platform Health</h3>
        <div class="health-grid">
          <div class="health-item">
            <span class="health-label">Average Reliability Score</span>
            <span class="health-value good">4.6</span>
          </div>
          <div class="health-item">
            <span class="health-label">Emergency Resolution Rate</span>
            <span class="health-value good">98%</span>
          </div>
          <div class="health-item">
            <span class="health-label">Avg Replacement Time</span>
            <span class="health-value">1.5 hrs</span>
          </div>
          <div class="health-item">
            <span class="health-label">Vendor Cancellation Rate</span>
            <span class="health-value good">0.8%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { adminApi } from '../../services/api'
import { useRouter } from 'vue-router'
import { showInfo } from '../../utils/sweetalert'

const router = useRouter()
const authStore = useAuthStore()
const user = computed(() => authStore.user)

const loading = ref(true)

// Stats
const stats = ref({
  totalUsers: 0,
  totalVendors: 0,
  totalEvents: 0,
  totalRevenue: 0,
  pendingVerification: 0,
  pendingEmergencies: 0
})

const pendingItems = ref([])
const recentBookings = ref([])
const emergencies = ref([])

function formatAmount(amount) {
  return new Intl.NumberFormat('en-IN').format(amount || 0)
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-IN', {
    month: 'short', day: 'numeric', year: 'numeric'
  })
}

function handleAlert(alert) {
  if (alert.type === 'warning') { // Pending Verification
    // In a real app, navigate to vendors list with filter
    alert.action === 'Review' && console.log('Reviewing vendors')
  } else if (alert.type === 'danger') { // Emergency
    // Navigate to emergencies
    console.log('Viewing emergency', alert)
  }
}

function manageVendors() {
  router.push('/admin/vendors')
}

function manageUsers() {
  router.push('/admin/users')
}

function manageCategories() {
  router.push('/admin/categories')
}

function viewReports() {
  router.push('/admin/reports')
}

function viewAuditLog() {
  router.push('/admin/audit-log')
}

function platformSettings() {
  router.push('/admin/settings')
}

function viewAllBookings() {
  // router.push('/admin/bookings')
  showInfo('Coming Soon!', 'View All Bookings functionality will be available in the next update')
}

async function fetchDashboardData() {
  loading.value = true
  try {
    const response = await adminApi.getDashboard()
    const data = response.data

    // Map stats
    stats.value = {
      totalUsers: data.stats.total_users,
      totalVendors: data.stats.total_vendors, // or verified_vendors depending on what we want to show
      totalEvents: data.stats.total_events,
      totalRevenue: data.stats.total_revenue,
      pendingVerification: data.stats.pending_verification,
      pendingEmergencies: data.stats.pending_emergencies
    }

    // Map Recent Bookings
    recentBookings.value = data.recent_events.map(event => ({
      id: event.id,
      title: event.title,
      client: event.client ? event.client.name : 'Unknown',
      date: formatDate(event.event_date),
      status: event.status
    }))

    // Map Active Emergencies
    emergencies.value = data.active_emergencies.map(emerg => ({
      id: emerg.id,
      event: emerg.event ? emerg.event.title : 'Unknown Event',
      vendor: emerg.event_vendor && emerg.event_vendor.vendor ? emerg.event_vendor.vendor.business_name : 'Unknown Vendor',
      time: new Date(emerg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      status: emerg.status
    }))

    // Construct Pending Items (Alerts)
    const alerts = []

    if (stats.value.pendingVerification > 0) {
      alerts.push({
        id: 'verif',
        type: 'warning',
        icon: '🏪',
        title: `${stats.value.pendingVerification} Vendor${stats.value.pendingVerification > 1 ? 's' : ''} Pending Verification`,
        description: 'New vendor registrations need review',
        action: 'Review',
        buttonClass: 'btn-primary'
      })
    }

    if (stats.value.pendingEmergencies > 0) {
      alerts.push({
        id: 'emerg',
        type: 'danger',
        icon: '🚨',
        title: `${stats.value.pendingEmergencies} Active Emergenc${stats.value.pendingEmergencies > 1 ? 'ies' : 'y'}`,
        description: 'Vendor failure reported - backup being assigned',
        action: 'View',
        buttonClass: 'btn-danger'
      })
    }

    pendingItems.value = alerts

  } catch (err) {
    console.error('Failed to load admin dashboard', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<style scoped>
.admin-dashboard {
  padding: 40px 0;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.role-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 12px;
}

.role-badge.admin {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: #7c2d12;
}

.welcome-section h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.welcome-section p {
  color: var(--text-secondary);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 32px;
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

.stat-card.revenue {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.05));
  border-color: rgba(16, 185, 129, 0.2);
}

.stat-icon {
  font-size: 2rem;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

/* Alerts */
.alerts-section {
  margin-bottom: 32px;
}

.alerts-section h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 16px;
}

.alerts-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.alert-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
}

.alert-card.warning {
  border-left: 4px solid var(--warning-500);
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.1), transparent);
}

.alert-card.danger {
  border-left: 4px solid var(--danger-500);
  background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), transparent);
}

.alert-icon {
  font-size: 1.5rem;
}

.alert-info {
  flex: 1;
}

.alert-info h3 {
  font-size: 0.95rem;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.alert-info p {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 32px;
  margin-bottom: 40px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
}

.view-all-btn {
  background: none;
  border: none;
  color: var(--primary-600);
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
}

/* Table */
.table-container {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 12px 16px;
  text-align: left;
  font-size: 0.85rem;
}

.data-table th {
  background: var(--bg-secondary);
  color: var(--text-secondary);
  font-weight: 500;
}

.data-table td {
  border-top: 1px solid var(--border-color);
  color: var(--text-primary);
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-confirmed {
  background: var(--success-50);
  color: var(--success-600);
}

.status-pending {
  background: var(--warning-50);
  color: var(--warning-600);
}

.status-completed {
  background: var(--gray-100);
  color: var(--gray-600);
}

/* Emergency List */
.emergency-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.emergency-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
}

.emergency-status {
  font-size: 1.25rem;
}

.emergency-info {
  flex: 1;
}

.emergency-info h4 {
  font-size: 0.9rem;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.emergency-info p {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 24px;
  background: var(--bg-secondary);
  border-radius: var(--radius-xl);
}

.empty-state.small {
  padding: 24px;
}

.empty-state span {
  font-size: 2rem;
  display: block;
  margin-bottom: 8px;
}

.empty-state p {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

/* Admin Actions */
.admin-actions {
  margin-bottom: 32px;
}

.admin-actions h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 16px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-xl);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.action-card:hover {
  border-color: var(--primary-300);
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.action-icon {
  font-size: 1.75rem;
}

.action-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--text-primary);
  text-align: center;
}

.action-count {
  font-size: 0.7rem;
  color: var(--warning-600);
  background: var(--warning-50);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

/* Health Section */
.health-section {
  padding: 24px 32px;
}

.health-section h3 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.health-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.health-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.health-label {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.health-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.health-value.good {
  color: var(--success-600);
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .health-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
