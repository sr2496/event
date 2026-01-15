<template>
  <div class="vendor-dashboard">
    <div class="container">
      <!-- Welcome Header -->
      <div class="dashboard-header">
        <div class="welcome-section">
          <span class="role-badge">🏪 Vendor Account</span>
          <h1>Welcome, {{ user?.name?.split(' ')[0] }} 👋</h1>
          <p>Manage your business and track your performance</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary" @click="editProfile">
            ✏️ Edit Profile
          </button>
        </div>
      </div>

      <!-- Reliability Score Card -->
      <div class="reliability-card card">
        <div class="reliability-main">
          <div class="score-circle" :class="`score-${getScoreBadge(reliabilityScore)}`">
            {{ reliabilityScore }}
          </div>
          <div class="reliability-info">
            <h2>Your Reliability Score</h2>
            <p>Based on your event history, response times, and client feedback</p>
          </div>
        </div>
        <div class="reliability-stats">
          <div class="r-stat">
            <span class="r-value">{{ stats.eventsCompleted }}</span>
            <span class="r-label">Events Completed</span>
          </div>
          <div class="r-stat">
            <span class="r-value">{{ stats.cancellations }}</span>
            <span class="r-label">Cancellations</span>
          </div>
          <div class="r-stat">
            <span class="r-value">{{ stats.noShows }}</span>
            <span class="r-label">No Shows</span>
          </div>
          <div class="r-stat">
            <span class="r-value">{{ stats.emergencyAccepts }}</span>
            <span class="r-label">Emergency Accepts</span>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <span class="stat-value">{{ upcomingEvents }}</span>
            <span class="stat-label">Upcoming Assignments</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">💰</div>
          <div class="stat-content">
            <span class="stat-value">₹{{ formatAmount(totalEarnings) }}</span>
            <span class="stat-label">Total Earnings</span>
          </div>
        </div>
        <div class="stat-card emergency">
          <div class="stat-icon">🚨</div>
          <div class="stat-content">
            <span class="stat-value">{{ pendingEmergencies }}</span>
            <span class="stat-label">Emergency Requests</span>
          </div>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="dashboard-grid">
        <!-- Upcoming Assignments -->
        <div class="section">
          <div class="section-header">
            <h2>📋 Upcoming Assignments</h2>
          </div>
          
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>

          <div v-else-if="assignments.length" class="assignments-list">
            <div v-for="assignment in assignments" :key="assignment.id" class="assignment-card card">
              <div class="assignment-date">
                <span class="a-day">{{ formatDay(assignment.event_date) }}</span>
                <span class="a-month">{{ formatMonth(assignment.event_date) }}</span>
              </div>
              <div class="assignment-info">
                <h3>{{ assignment.event_title }}</h3>
                <p>{{ assignment.event_type }} • {{ assignment.city }}</p>
                <span :class="['assignment-role', assignment.role]">
                  {{ assignment.role === 'primary' ? '⭐ Primary Vendor' : '🛡️ Backup Vendor' }}
                </span>
              </div>
              <div class="assignment-status">
                <span :class="['status-badge', `status-${assignment.status}`]">
                  {{ assignment.status }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="empty-state">
            <span class="empty-icon">📅</span>
            <h3>No upcoming assignments</h3>
            <p>You'll see your upcoming events here</p>
          </div>
        </div>

        <!-- Emergency Requests -->
        <div class="section">
          <div class="section-header">
            <h2>🚨 Emergency Requests</h2>
          </div>
          
          <div v-if="emergencyRequests.length" class="emergency-list">
            <div v-for="emergency in emergencyRequests" :key="emergency.id" class="emergency-card card">
              <div class="emergency-badge">URGENT</div>
              <div class="emergency-info">
                <h3>{{ emergency.event_title }}</h3>
                <p>{{ emergency.event_date }} • {{ emergency.city }}</p>
                <p class="emergency-type">{{ emergency.category }} needed</p>
              </div>
              <div class="emergency-payout">
                <span class="payout-amount">₹{{ formatAmount(emergency.payout) }}</span>
                <span class="payout-label">Emergency Payout</span>
              </div>
              <div class="emergency-actions">
                <button class="btn btn-success btn-sm" @click="acceptEmergency(emergency.id)">
                  Accept
                </button>
                <button class="btn btn-secondary btn-sm" @click="rejectEmergency(emergency.id)">
                  Decline
                </button>
              </div>
            </div>
          </div>

          <div v-else class="empty-state small">
            <span class="empty-icon">✅</span>
            <h3>No emergency requests</h3>
            <p>You're all set!</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
          <div class="action-card" @click="editProfile">
            <span class="action-icon">📝</span>
            <span class="action-label">Edit Profile</span>
          </div>
          <div class="action-card" @click="managePortfolio">
            <span class="action-icon">📷</span>
            <span class="action-label">Portfolio</span>
          </div>
          <div class="action-card" @click="manageAvailability">
            <span class="action-icon">📅</span>
            <span class="action-label">Availability</span>
          </div>
          <div class="action-card" @click="viewEarnings">
            <span class="action-icon">💰</span>
            <span class="action-label">Earnings</span>
          </div>
        </div>
      </div>

      <!-- Tips for Higher Score -->
      <div class="tips-banner card">
        <div class="tips-content">
          <h3>💡 Tips to Improve Your Reliability Score</h3>
          <ul>
            <li>✅ Complete events on time</li>
            <li>✅ Respond to inquiries quickly</li>
            <li>✅ Accept emergency assignments when possible</li>
            <li>✅ Avoid cancellations at all costs</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { vendorAccountApi } from '../../services/api'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const loading = ref(true)
const user = computed(() => authStore.user)

const reliabilityScore = ref(0)
const stats = ref({
  eventsCompleted: 0,
  cancellations: 0,
  noShows: 0,
  emergencyAccepts: 0
})

const upcomingEvents = ref(0)
const totalEarnings = ref(0)
const pendingEmergencies = ref(0)
const assignments = ref([])
const emergencyRequests = ref([])

function getScoreBadge(score) {
  if (score >= 4.5) return 'excellent'
  if (score >= 4.0) return 'good'
  if (score >= 3.5) return 'average'
  return 'poor'
}

function formatDay(dateStr) {
  return new Date(dateStr).getDate()
}

function formatMonth(dateStr) {
  return new Date(dateStr).toLocaleDateString('en', { month: 'short' })
}

function formatAmount(amount) {
  return new Intl.NumberFormat('en-IN').format(amount)
}

function editProfile() {
  alert('Edit Profile - Coming Soon!')
}

function managePortfolio() {
  alert('Manage Portfolio - Coming Soon!')
}

function manageAvailability() {
  alert('Manage Availability - Coming Soon!')
}

function viewEarnings() {
  alert('View Earnings - Coming Soon!')
}

function acceptEmergency(id) {
  alert(`Accept Emergency ${id} - Coming Soon!`)
}

function rejectEmergency(id) {
  alert(`Reject Emergency ${id} - Coming Soon!`)
}

onMounted(async () => {
  loading.value = true
  try {
    const { data } = await vendorAccountApi.getDashboard()
    
    // Stats
    reliabilityScore.value = data.stats.reliability_score || 0
    stats.value = {
      eventsCompleted: data.stats.total_events_completed,
      cancellations: data.stats.cancellations,
      noShows: data.stats.no_shows,
      emergencyAccepts: data.stats.emergency_accepts
    }
    
    upcomingEvents.value = data.stats.upcoming_events
    totalEarnings.value = data.stats.total_earnings
    pendingEmergencies.value = data.stats.emergency_requests
    
    // Lists
    assignments.value = data.upcoming_assignments
    emergencyRequests.value = data.emergency_requests_list
    
  } catch (err) {
    console.error('Failed to load vendor dashboard', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.vendor-dashboard {
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
  background: var(--primary-50);
  color: var(--primary-600);
  border-radius: var(--radius-full);
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 12px;
}

.welcome-section h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.welcome-section p {
  color: var(--text-secondary);
}

/* Reliability Card */
.reliability-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 32px;
  padding: 32px;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.05));
  border-color: rgba(16, 185, 129, 0.2);
  margin-bottom: 32px;
}

.reliability-main {
  display: flex;
  align-items: center;
  gap: 24px;
}

.score-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 700;
  color: white;
}

.score-circle.score-excellent { background: linear-gradient(135deg, #10b981, #34d399); }
.score-circle.score-good { background: linear-gradient(135deg, #22c55e, #86efac); }
.score-circle.score-average { background: linear-gradient(135deg, #f59e0b, #fcd34d); }
.score-circle.score-poor { background: linear-gradient(135deg, #ef4444, #fca5a5); }

.reliability-info h2 {
  font-size: 1.25rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.reliability-info p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.reliability-stats {
  display: flex;
  gap: 32px;
}

.r-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.r-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.r-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

/* Stats Grid */
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

.stat-card.emergency {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(252, 165, 165, 0.1));
  border-color: rgba(239, 68, 68, 0.2);
}

.stat-icon { font-size: 2.5rem; }

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

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  margin-bottom: 40px;
}

.section-header {
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 1.15rem;
  color: var(--text-primary);
}

/* Assignments */
.assignments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.assignment-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
}

.assignment-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 14px;
  background: var(--primary-50);
  border-radius: var(--radius-md);
}

.a-day {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-600);
}

.a-month {
  font-size: 0.7rem;
  color: var(--primary-600);
  text-transform: uppercase;
}

.assignment-info {
  flex: 1;
}

.assignment-info h3 {
  font-size: 0.95rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.assignment-info p {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 6px;
}

.assignment-role {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
}

.assignment-role.primary {
  background: var(--success-50);
  color: var(--success-600);
}

.assignment-role.backup {
  background: var(--primary-50);
  color: var(--primary-600);
}

.status-badge {
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-confirmed { background: var(--success-50); color: var(--success-600); }
.status-pending { background: var(--warning-50); color: var(--warning-600); }

/* Emergency */
.emergency-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.emergency-card {
  padding: 16px 20px;
  border-left: 4px solid var(--danger-500);
}

.emergency-badge {
  display: inline-block;
  padding: 4px 8px;
  background: var(--danger-500);
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: var(--radius-sm);
  margin-bottom: 8px;
}

.emergency-info h3 {
  font-size: 0.95rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.emergency-info p {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.emergency-type {
  color: var(--danger-600) !important;
  font-weight: 500;
}

.emergency-payout {
  margin: 12px 0;
  text-align: center;
}

.payout-amount {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--success-600);
}

.payout-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.emergency-actions {
  display: flex;
  gap: 8px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 24px;
  background: var(--bg-secondary);
  border-radius: var(--radius-xl);
}

.empty-state.small {
  padding: 32px 20px;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 12px;
  display: block;
}

.empty-state h3 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.empty-state p {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

/* Quick Actions */
.quick-actions h2 {
  font-size: 1.15rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 32px;
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
  cursor: pointer;
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

/* Tips Banner */
.tips-banner {
  padding: 24px 32px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(124, 58, 237, 0.05));
  border-color: rgba(99, 102, 241, 0.2);
}

.tips-content h3 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.tips-content ul {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  list-style: none;
}

.tips-content li {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 40px;
}

@media (max-width: 1024px) {
  .reliability-card { flex-direction: column; align-items: flex-start; }
  .dashboard-grid { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: 1fr; }
  .actions-grid { grid-template-columns: repeat(2, 1fr); }
  .tips-content ul { grid-template-columns: 1fr; }
}
</style>
