<template>
  <div class="audit-log-page">
    <div class="container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
          <h1>📝 Audit Log</h1>
          <p>Track all administrative actions on the platform</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-section card">
        <div class="filters-row">
          <div class="filter-group">
            <label>Action Type</label>
            <select v-model="filters.actionType" @change="fetchLogs" class="input">
              <option value="">All Actions</option>
              <option value="vendor_verified">Vendor Verified</option>
              <option value="vendor_suspended">Vendor Suspended</option>
              <option value="reliability_adjusted">Reliability Adjusted</option>
              <option value="backup_override">Backup Override</option>
              <option value="user_activated">User Activated</option>
              <option value="user_deactivated">User Deactivated</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Target Type</label>
            <select v-model="filters.targetType" @change="fetchLogs" class="input">
              <option value="">All Targets</option>
              <option value="vendor">Vendor</option>
              <option value="user">User</option>
              <option value="emergency_request">Emergency Request</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Per Page</label>
            <select v-model="filters.perPage" @change="fetchLogs" class="input">
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading audit log...</p>
      </div>

      <!-- Logs Table -->
      <div v-else class="table-card card">
        <table class="logs-table" v-if="logs.length">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Admin</th>
              <th>Action</th>
              <th>Target</th>
              <th>Reason</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td class="timestamp-cell">
                <span class="date">{{ formatDate(log.created_at) }}</span>
                <span class="time">{{ formatTime(log.created_at) }}</span>
              </td>
              <td>
                <span class="admin-name">{{ log.admin?.name || 'System' }}</span>
              </td>
              <td>
                <span :class="['action-badge', getActionClass(log.action_type)]">
                  {{ formatActionType(log.action_type) }}
                </span>
              </td>
              <td>
                <span class="target-info">
                  <span class="target-type">{{ formatTargetType(log.target_type) }}</span>
                  <span class="target-id">#{{ log.target_id }}</span>
                </span>
              </td>
              <td class="reason-cell">
                <span v-if="log.reason" class="reason-text">{{ log.reason }}</span>
                <span v-else class="no-reason">—</span>
              </td>
              <td>
                <button 
                  v-if="log.data_before || log.data_after" 
                  class="btn btn-sm btn-secondary"
                  @click="viewDetails(log)"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span>📝</span>
          <p>No audit logs found</p>
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="pagination.lastPage > 1">
          <button 
            class="btn btn-sm btn-secondary" 
            :disabled="pagination.currentPage <= 1"
            @click="goToPage(pagination.currentPage - 1)"
          >
            ← Prev
          </button>
          <span class="page-info">
            Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
            <span class="total-count">({{ pagination.total }} total)</span>
          </span>
          <button 
            class="btn btn-sm btn-secondary" 
            :disabled="pagination.currentPage >= pagination.lastPage"
            @click="goToPage(pagination.currentPage + 1)"
          >
            Next →
          </button>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="showDetailsModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>📋 Action Details</h2>
          <button class="close-btn" @click="showDetailsModal = false">×</button>
        </div>
        
        <div class="details-grid">
          <div class="detail-section" v-if="selectedLog.data_before">
            <h3>Before</h3>
            <pre class="json-display">{{ formatJson(selectedLog.data_before) }}</pre>
          </div>
          <div class="detail-section" v-if="selectedLog.data_after">
            <h3>After</h3>
            <pre class="json-display">{{ formatJson(selectedLog.data_after) }}</pre>
          </div>
        </div>

        <div class="modal-footer">
          <span class="ip-address">IP: {{ selectedLog.ip_address || 'N/A' }}</span>
          <button class="btn btn-secondary" @click="showDetailsModal = false">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { adminApi } from '../services/api'

const loading = ref(true)
const logs = ref([])
const filters = ref({
  actionType: '',
  targetType: '',
  perPage: 50
})
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0
})

const showDetailsModal = ref(false)
const selectedLog = ref({})

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      per_page: filters.value.perPage
    }
    
    const response = await adminApi.getAuditLog(params)
    logs.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page || 1,
      lastPage: response.data.last_page || 1,
      total: response.data.total || 0
    }
  } catch (err) {
    console.error('Failed to fetch audit logs', err)
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  pagination.value.currentPage = page
  fetchLogs(page)
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleTimeString('en-IN', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatActionType(type) {
  if (!type) return 'Unknown'
  return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function formatTargetType(type) {
  if (!type) return 'Unknown'
  return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function getActionClass(type) {
  if (!type) return ''
  if (type.includes('verified') || type.includes('activated')) return 'success'
  if (type.includes('suspended') || type.includes('deactivated')) return 'danger'
  if (type.includes('adjusted') || type.includes('override')) return 'warning'
  return 'info'
}

function viewDetails(log) {
  selectedLog.value = log
  showDetailsModal.value = true
}

function formatJson(data) {
  if (!data) return '{}'
  try {
    return JSON.stringify(data, null, 2)
  } catch {
    return String(data)
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.audit-log-page {
  padding: 40px 0 80px;
}

.page-header {
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

/* Filters */
.filters-section {
  padding: 20px 24px;
  margin-bottom: 24px;
}

.filters-row {
  display: flex;
  gap: 20px;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--text-secondary);
}

/* Table */
.table-card {
  padding: 0;
  overflow: hidden;
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
}

.logs-table th,
.logs-table td {
  padding: 14px 16px;
  text-align: left;
  font-size: 0.85rem;
}

.logs-table th {
  background: var(--bg-secondary);
  color: var(--text-secondary);
  font-weight: 500;
  border-bottom: 1px solid var(--border-color);
}

.logs-table td {
  border-bottom: 1px solid var(--border-color);
  color: var(--text-primary);
}

.logs-table tbody tr:hover {
  background: var(--bg-secondary);
}

.timestamp-cell {
  display: flex;
  flex-direction: column;
}

.timestamp-cell .date {
  font-weight: 500;
}

.timestamp-cell .time {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.admin-name {
  font-weight: 500;
}

.action-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
}

.action-badge.success {
  background: var(--success-50);
  color: var(--success-600);
}

.action-badge.danger {
  background: var(--danger-50);
  color: var(--danger-600);
}

.action-badge.warning {
  background: var(--warning-50);
  color: var(--warning-600);
}

.action-badge.info {
  background: var(--primary-50);
  color: var(--primary-600);
}

.target-info {
  display: flex;
  flex-direction: column;
}

.target-type {
  font-weight: 500;
  text-transform: capitalize;
}

.target-id {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.reason-cell {
  max-width: 200px;
}

.reason-text {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.no-reason {
  color: var(--text-muted);
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 16px;
  border-top: 1px solid var(--border-color);
}

.page-info {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.total-count {
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 24px;
}

.empty-state span {
  font-size: 3rem;
  display: block;
  margin-bottom: 12px;
}

.empty-state p {
  color: var(--text-secondary);
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 80px 24px;
}

.loading-state p {
  color: var(--text-secondary);
  margin-top: 16px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.modal-content {
  background: var(--bg-primary);
  border-radius: var(--radius-2xl);
  max-width: 800px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  font-size: 1.25rem;
  margin: 0;
}

.close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: var(--bg-secondary);
  border-radius: var(--radius-full);
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.close-btn:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  padding: 24px 32px;
}

.detail-section h3 {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.json-display {
  background: var(--bg-secondary);
  padding: 16px;
  border-radius: var(--radius-lg);
  font-size: 0.8rem;
  font-family: monospace;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 300px;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 32px;
  border-top: 1px solid var(--border-color);
}

.ip-address {
  font-size: 0.8rem;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .filters-row {
    flex-wrap: wrap;
  }
  
  .logs-table {
    display: block;
    overflow-x: auto;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
