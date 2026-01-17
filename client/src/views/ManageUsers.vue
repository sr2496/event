<template>
  <div class="manage-users-page">
    <div class="container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
          <h1>👥 Manage Users</h1>
          <p>View and manage user accounts</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-section card">
        <div class="filters-row">
          <div class="filter-group">
            <label>Role</label>
            <select v-model="filters.role" @change="fetchUsers" class="input">
              <option value="">All Roles</option>
              <option value="client">Clients</option>
              <option value="vendor">Vendors</option>
              <option value="admin">Admins</option>
            </select>
          </div>
          <div class="filter-group search-group">
            <label>Search</label>
            <input 
              type="text" 
              v-model="filters.search" 
              @input="debouncedSearch"
              class="input" 
              placeholder="Search by name or email..."
            />
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-pill">
          <span class="stat-count">{{ pagination.total }}</span>
          <span class="stat-label">Total Users</span>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading users...</p>
      </div>

      <!-- Users Table -->
      <div v-else class="table-card card">
        <table class="users-table" v-if="users.length">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Joined</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="user-cell">
                <div class="user-avatar">{{ getInitials(user.name) }}</div>
                <div class="user-info">
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-phone">{{ user.phone || 'No phone' }}</span>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>
                <span :class="['role-badge', `role-${user.role}`]">
                  {{ user.role }}
                </span>
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>
                <span :class="['status-badge', user.is_active ? 'active' : 'inactive']">
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="actions-cell">
                <button 
                  class="btn btn-sm"
                  :class="user.is_active ? 'btn-danger' : 'btn-success'"
                  @click="toggleStatus(user)"
                  :disabled="user.is_current"
                >
                  {{ user.is_active ? 'Deactivate' : 'Activate' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span>👥</span>
          <p>No users found</p>
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
          <span class="page-info">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
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
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { RouterLink } from 'vue-router'
import { adminApi } from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const showToast = inject('showToast')

const loading = ref(true)
const users = ref([])
const filters = ref({
  role: '',
  search: ''
})
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0
})

let searchTimeout = null

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchUsers()
  }, 300)
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 20,
      ...filters.value
    }
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) delete params[key]
    })

    const response = await adminApi.getUsers(params)
    users.value = response.data.data.map(u => ({
      ...u,
      is_current: u.id === authStore.user?.id
    }))
    pagination.value = {
      currentPage: response.data.meta?.current_page || 1,
      lastPage: response.data.meta?.last_page || 1,
      total: response.data.meta?.total || response.data.data.length
    }
  } catch (err) {
    console.error('Failed to fetch users', err)
    showToast('Failed to load users', 'error')
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  pagination.value.currentPage = page
  fetchUsers(page)
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

async function toggleStatus(user) {
  if (user.is_current) return
  
  try {
    await adminApi.toggleUserStatus(user.id)
    showToast(`User ${user.is_active ? 'deactivated' : 'activated'} successfully`, 'success')
    fetchUsers(pagination.value.currentPage)
  } catch (err) {
    console.error('Failed to toggle user status', err)
    showToast(err.response?.data?.message || 'Failed to update user', 'error')
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.manage-users-page {
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

.filter-group.search-group {
  flex: 1;
}

/* Stats */
.stats-row {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
}

.stat-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-full);
  font-size: 0.85rem;
}

.stat-pill .stat-count {
  font-weight: 700;
  color: var(--text-primary);
}

.stat-pill .stat-label {
  color: var(--text-secondary);
}

/* Table */
.table-card {
  padding: 0;
  overflow: hidden;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 14px 16px;
  text-align: left;
  font-size: 0.85rem;
}

.users-table th {
  background: var(--bg-secondary);
  color: var(--text-secondary);
  font-weight: 500;
  border-bottom: 1px solid var(--border-color);
}

.users-table td {
  border-bottom: 1px solid var(--border-color);
  color: var(--text-primary);
}

.users-table tbody tr:hover {
  background: var(--bg-secondary);
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-full);
  background: linear-gradient(135deg, var(--primary-400), var(--primary-600));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-phone {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.role-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: capitalize;
}

.role-badge.role-admin {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: #7c2d12;
}

.role-badge.role-vendor {
  background: var(--primary-50);
  color: var(--primary-600);
}

.role-badge.role-client {
  background: var(--success-50);
  color: var(--success-600);
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  font-size: 0.7rem;
  font-weight: 600;
}

.status-badge.active {
  background: var(--success-50);
  color: var(--success-600);
}

.status-badge.inactive {
  background: var(--danger-50);
  color: var(--danger-600);
}

.actions-cell {
  display: flex;
  gap: 8px;
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

@media (max-width: 768px) {
  .filters-row {
    flex-wrap: wrap;
  }
  
  .users-table {
    display: block;
    overflow-x: auto;
  }
}
</style>
