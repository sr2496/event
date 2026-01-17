<template>
  <div class="reports-page">
    <div class="container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
          <h1>📊 Platform Reports</h1>
          <p>Analytics and insights for your platform</p>
        </div>
        <div class="period-selector">
          <button 
            v-for="p in periods" 
            :key="p.value" 
            :class="['period-btn', period === p.value && 'active']"
            @click="changePeriod(p.value)"
          >
            {{ p.label }}
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading reports...</p>
      </div>

      <template v-else>
        <!-- Key Metrics -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon">📅</div>
            <div class="metric-content">
              <span class="metric-value">{{ data.booking_stats?.total || 0 }}</span>
              <span class="metric-label">Total Bookings</span>
            </div>
            <div class="metric-breakdown">
              <span class="breakdown-item success">✓ {{ data.booking_stats?.completed || 0 }} completed</span>
              <span class="breakdown-item warning">⏳ {{ data.booking_stats?.confirmed || 0 }} confirmed</span>
              <span class="breakdown-item danger">✕ {{ data.booking_stats?.cancelled || 0 }} cancelled</span>
            </div>
          </div>

          <div class="metric-card revenue">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
              <span class="metric-value">₹{{ formatAmount(data.revenue_stats?.platform_revenue) }}</span>
              <span class="metric-label">Platform Revenue</span>
            </div>
            <div class="metric-breakdown">
              <span class="breakdown-item">Assurance Fees: ₹{{ formatAmount(data.revenue_stats?.assurance_fees) }}</span>
              <span class="breakdown-item">Commissions: ₹{{ formatAmount(data.revenue_stats?.platform_commission) }}</span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">🚨</div>
            <div class="metric-content">
              <span class="metric-value">{{ data.emergency_stats?.resolution_rate || 100 }}%</span>
              <span class="metric-label">Emergency Resolution Rate</span>
            </div>
            <div class="metric-breakdown">
              <span class="breakdown-item">{{ data.emergency_stats?.total || 0 }} emergencies</span>
              <span class="breakdown-item">Avg: {{ data.emergency_stats?.avg_resolution_time || 0 }} mins</span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
              <span class="metric-value">{{ data.user_stats?.new_users || 0 }}</span>
              <span class="metric-label">New Users</span>
            </div>
            <div class="metric-breakdown">
              <span class="breakdown-item">Total Users: {{ data.user_stats?.total_users || 0 }}</span>
              <span class="breakdown-item">New Vendors: {{ data.user_stats?.new_vendors || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Two Column Layout -->
        <div class="reports-grid">
          <!-- Top Vendors -->
          <div class="report-section card">
            <h2>🏆 Top Performing Vendors</h2>
            <div class="vendors-list" v-if="data.top_vendors?.length">
              <div v-for="(vendor, index) in data.top_vendors" :key="vendor.id" class="vendor-row">
                <span class="vendor-rank">{{ index + 1 }}</span>
                <div class="vendor-info">
                  <span class="vendor-name">{{ vendor.business_name }}</span>
                  <span class="vendor-category">{{ formatCategory(vendor.category) }}</span>
                </div>
                <div class="vendor-stats">
                  <span class="vendor-events">{{ vendor.completed_events }} events</span>
                  <span class="vendor-score">⭐ {{ formatScore(vendor.reliability_score) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="empty-state small">
              <p>No vendor data available</p>
            </div>
          </div>

          <!-- Category Distribution -->
          <div class="report-section card">
            <h2>📂 Vendors by Category</h2>
            <div class="category-list" v-if="data.category_stats?.length">
              <div v-for="cat in data.category_stats" :key="cat.category" class="category-row">
                <span class="category-name">{{ formatCategory(cat.category) }}</span>
                <div class="category-bar-container">
                  <div 
                    class="category-bar" 
                    :style="{ width: getCategoryWidth(cat.vendor_count) + '%' }"
                  ></div>
                </div>
                <span class="category-count">{{ cat.vendor_count }}</span>
              </div>
            </div>
            <div v-else class="empty-state small">
              <p>No category data available</p>
            </div>
          </div>
        </div>

        <!-- Revenue Summary -->
        <div class="revenue-summary card">
          <h2>💵 Revenue Breakdown</h2>
          <div class="revenue-grid">
            <div class="revenue-item">
              <span class="revenue-label">Total Booking Value</span>
              <span class="revenue-value">₹{{ formatAmount(data.revenue_stats?.total_booking_value) }}</span>
            </div>
            <div class="revenue-item highlight">
              <span class="revenue-label">Assurance Fees Collected</span>
              <span class="revenue-value">₹{{ formatAmount(data.revenue_stats?.assurance_fees) }}</span>
            </div>
            <div class="revenue-item highlight">
              <span class="revenue-label">Platform Commissions</span>
              <span class="revenue-value">₹{{ formatAmount(data.revenue_stats?.platform_commission) }}</span>
            </div>
            <div class="revenue-item total">
              <span class="revenue-label">Total Platform Revenue</span>
              <span class="revenue-value">₹{{ formatAmount(data.revenue_stats?.platform_revenue) }}</span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { adminApi } from '../services/api'

const loading = ref(true)
const period = ref('month')
const data = ref({})

const periods = [
  { value: 'week', label: 'This Week' },
  { value: 'month', label: 'This Month' },
  { value: 'year', label: 'This Year' },
]

const maxCategoryCount = ref(1)

async function fetchReports() {
  loading.value = true
  try {
    const response = await adminApi.getReports({ period: period.value })
    data.value = response.data
    
    // Calculate max for category bars
    if (data.value.category_stats?.length) {
      maxCategoryCount.value = Math.max(...data.value.category_stats.map(c => c.vendor_count))
    }
  } catch (err) {
    console.error('Failed to fetch reports', err)
  } finally {
    loading.value = false
  }
}

function changePeriod(newPeriod) {
  period.value = newPeriod
  fetchReports()
}

function formatAmount(amount) {
  return new Intl.NumberFormat('en-IN').format(amount || 0)
}

function formatCategory(cat) {
  if (!cat) return 'N/A'
  return cat.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function getCategoryWidth(count) {
  return (count / maxCategoryCount.value) * 100
}

function formatScore(score) {
  if (score == null) return 'N/A'
  const num = parseFloat(score)
  return isNaN(num) ? 'N/A' : num.toFixed(1)
}

onMounted(() => {
  fetchReports()
})
</script>

<style scoped>
.reports-page {
  padding: 40px 0 80px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.back-link {
  display: inline-block;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
  margin-bottom: 12px;
}

.back-link:hover {
  color: var(--primary-600);
}

.page-header h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.page-header p {
  color: var(--text-secondary);
}

/* Period Selector */
.period-selector {
  display: flex;
  gap: 8px;
  background: var(--bg-secondary);
  padding: 4px;
  border-radius: var(--radius-lg);
}

.period-btn {
  padding: 8px 16px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  font-size: 0.85rem;
  font-weight: 500;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.period-btn:hover {
  color: var(--text-primary);
}

.period-btn.active {
  background: var(--bg-primary);
  color: var(--primary-600);
  box-shadow: var(--shadow-sm);
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.metric-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-xl);
  padding: 24px;
}

.metric-card.revenue {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.05));
  border-color: rgba(16, 185, 129, 0.2);
}

.metric-icon {
  font-size: 1.5rem;
  margin-bottom: 12px;
}

.metric-content {
  margin-bottom: 16px;
}

.metric-value {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.metric-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.metric-breakdown {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-top: 12px;
  border-top: 1px solid var(--border-color);
}

.breakdown-item {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.breakdown-item.success { color: var(--success-600); }
.breakdown-item.warning { color: var(--warning-600); }
.breakdown-item.danger { color: var(--danger-600); }

/* Reports Grid */
.reports-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.report-section h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

/* Vendors List */
.vendors-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vendor-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
}

.vendor-rank {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-100);
  color: var(--primary-700);
  font-weight: 700;
  font-size: 0.85rem;
  border-radius: var(--radius-full);
}

.vendor-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.vendor-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.vendor-category {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: capitalize;
}

.vendor-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.vendor-events {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
}

.vendor-score {
  font-size: 0.75rem;
  color: var(--success-600);
}

/* Category List */
.category-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.category-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.category-name {
  width: 120px;
  font-size: 0.85rem;
  color: var(--text-primary);
  text-transform: capitalize;
}

.category-bar-container {
  flex: 1;
  height: 24px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.category-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-500), var(--primary-400));
  border-radius: var(--radius-md);
  transition: width 0.3s ease;
}

.category-count {
  width: 40px;
  text-align: right;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-primary);
}

/* Revenue Summary */
.revenue-summary h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.revenue-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.revenue-item {
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  text-align: center;
}

.revenue-item.highlight {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(124, 58, 237, 0.05));
}

.revenue-item.total {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(52, 211, 153, 0.1));
}

.revenue-label {
  display: block;
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.revenue-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.revenue-item.total .revenue-value {
  color: var(--success-600);
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 80px 24px;
}

.loading-state p {
  color: var(--text-secondary);
  margin-top: 16px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--text-secondary);
}

.empty-state.small {
  padding: 24px;
}

/* Responsive */
@media (max-width: 1200px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .revenue-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .reports-grid {
    grid-template-columns: 1fr;
  }
  
  .revenue-grid {
    grid-template-columns: 1fr;
  }
}
</style>
