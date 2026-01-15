<template>
    <div class="manage-vendors-page">
        <div class="container">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
                    <h1>🏪 Manage Vendors</h1>
                    <p>Review, verify, and manage vendor accounts</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section card">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>Status</label>
                        <select v-model="filters.verified" @change="fetchVendors" class="input">
                            <option value="">All Vendors</option>
                            <option value="true">Verified</option>
                            <option value="false">Pending Verification</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Category</label>
                        <select v-model="filters.category" @change="fetchVendors" class="input">
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="filter-group search-group">
                        <label>Search</label>
                        <input type="text" v-model="filters.search" @input="debouncedSearch" class="input"
                            placeholder="Search by name or business..." />
                    </div>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="stats-row">
                <div class="stat-pill">
                    <span class="stat-count">{{ pagination.total }}</span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-pill verified">
                    <span class="stat-count">{{ verifiedCount }}</span>
                    <span class="stat-label">Verified</span>
                </div>
                <div class="stat-pill pending">
                    <span class="stat-count">{{ pendingCount }}</span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>

            <!-- Vendors Table -->
            <div class="table-card card" v-if="!loading">
                <table class="vendors-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>City</th>
                            <th>Reliability</th>
                            <th>Events</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vendor in vendors" :key="vendor.id">
                            <td class="vendor-cell">
                                <div class="vendor-avatar">{{ getInitials(vendor.business_name) }}</div>
                                <div class="vendor-info">
                                    <span class="vendor-name">{{ vendor.business_name }}</span>
                                    <span class="vendor-owner">{{ vendor.user?.name || 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge">{{ formatCategory(vendor.category) }}</span>
                            </td>
                            <td>{{ vendor.city }}</td>
                            <td>
                                <span class="reliability-score" :class="getScoreClass(vendor.reliability_score)">
                                    ⭐ {{ vendor.reliability_score?.toFixed(1) || 'N/A' }}
                                </span>
                            </td>
                            <td>{{ vendor.total_events_completed || 0 }}</td>
                            <td>
                                <span :class="['status-badge', vendor.is_verified ? 'verified' : 'pending']">
                                    {{ vendor.is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td class="actions-cell">
                                <button v-if="!vendor.is_verified" class="btn btn-sm btn-success"
                                    @click="openVerifyModal(vendor)">
                                    ✓ Verify
                                </button>
                                <button v-if="vendor.is_verified && vendor.is_active" class="btn btn-sm btn-danger"
                                    @click="openSuspendModal(vendor)">
                                    Suspend
                                </button>
                                <button class="btn btn-sm btn-secondary" @click="viewVendorDetails(vendor)">
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="vendors.length === 0" class="empty-state">
                    <span>🏪</span>
                    <p>No vendors found matching your criteria</p>
                </div>

                <!-- Pagination -->
                <div class="pagination" v-if="pagination.lastPage > 1">
                    <button class="btn btn-sm btn-secondary" :disabled="pagination.currentPage <= 1"
                        @click="goToPage(pagination.currentPage - 1)">
                        ← Prev
                    </button>
                    <span class="page-info">Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
                    <button class="btn btn-sm btn-secondary" :disabled="pagination.currentPage >= pagination.lastPage"
                        @click="goToPage(pagination.currentPage + 1)">
                        Next →
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-else class="loading-state">
                <div class="spinner"></div>
                <p>Loading vendors...</p>
            </div>
        </div>

        <!-- Verify Modal -->
        <div v-if="showVerifyModal" class="modal-overlay" @click.self="showVerifyModal = false">
            <div class="modal-content">
                <h2>✓ Verify Vendor</h2>
                <p>You are about to verify <strong>{{ selectedVendor?.business_name }}</strong>.</p>
                <p class="modal-note">This will allow them to receive bookings on the platform.</p>

                <div class="form-group">
                    <label>Verification Note (Optional)</label>
                    <textarea v-model="verifyReason" class="input" rows="2"
                        placeholder="Any notes about the verification..."></textarea>
                </div>

                <div class="modal-actions">
                    <button class="btn btn-secondary" @click="showVerifyModal = false">Cancel</button>
                    <button class="btn btn-success" @click="confirmVerify" :disabled="actionLoading">
                        {{ actionLoading ? 'Verifying...' : 'Confirm Verification' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Suspend Modal -->
        <div v-if="showSuspendModal" class="modal-overlay" @click.self="showSuspendModal = false">
            <div class="modal-content">
                <h2>⚠️ Suspend Vendor</h2>
                <p>You are about to suspend <strong>{{ selectedVendor?.business_name }}</strong>.</p>
                <p class="modal-note warning">This will prevent them from receiving new bookings.</p>

                <div class="form-group">
                    <label>Reason for Suspension *</label>
                    <textarea v-model="suspendReason" class="input" rows="3"
                        placeholder="Explain why this vendor is being suspended..." required></textarea>
                </div>

                <div class="modal-actions">
                    <button class="btn btn-secondary" @click="showSuspendModal = false">Cancel</button>
                    <button class="btn btn-danger" @click="confirmSuspend" :disabled="actionLoading || !suspendReason">
                        {{ actionLoading ? 'Suspending...' : 'Confirm Suspension' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, inject, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { adminApi, vendorApi } from '../services/api'

const router = useRouter()
const showToast = inject('showToast')

const loading = ref(true)
const actionLoading = ref(false)
const vendors = ref([])
const categories = ref([])

const filters = ref({
    verified: '',
    category: '',
    search: ''
})

const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0
})

const verifiedCount = ref(0)
const pendingCount = ref(0)

// Modals
const showVerifyModal = ref(false)
const showSuspendModal = ref(false)
const selectedVendor = ref(null)
const verifyReason = ref('')
const suspendReason = ref('')

let searchTimeout = null

function debouncedSearch() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        fetchVendors()
    }, 300)
}

async function fetchVendors(page = 1) {
    loading.value = true
    try {
        const params = {
            page,
            per_page: 15,
            ...filters.value
        }
        // Clean empty params
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) delete params[key]
        })

        const response = await adminApi.getVendors(params)
        vendors.value = response.data.data
        pagination.value = {
            currentPage: response.data.meta?.current_page || 1,
            lastPage: response.data.meta?.last_page || 1,
            total: response.data.meta?.total || response.data.data.length
        }
    } catch (err) {
        console.error('Failed to fetch vendors', err)
        showToast('Failed to load vendors', 'error')
    } finally {
        loading.value = false
    }
}

async function fetchCategories() {
    try {
        const response = await vendorApi.getCategories()
        categories.value = response.data.data
    } catch (err) {
        console.error('Failed to fetch categories', err)
    }
}

async function fetchStats() {
    try {
        const [verifiedRes, pendingRes] = await Promise.all([
            adminApi.getVendors({ verified: true, per_page: 1 }),
            adminApi.getVendors({ verified: false, per_page: 1 })
        ])
        verifiedCount.value = verifiedRes.data.meta?.total || 0
        pendingCount.value = pendingRes.data.meta?.total || 0
    } catch (err) {
        console.error('Failed to fetch stats', err)
    }
}

function goToPage(page) {
    pagination.value.currentPage = page
    fetchVendors(page)
}

function getInitials(name) {
    if (!name) return '?'
    return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

function formatCategory(cat) {
    if (!cat) return 'N/A'
    return cat.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function getScoreClass(score) {
    if (!score) return ''
    if (score >= 4.5) return 'excellent'
    if (score >= 4.0) return 'good'
    if (score >= 3.0) return 'average'
    return 'poor'
}

function openVerifyModal(vendor) {
    selectedVendor.value = vendor
    verifyReason.value = ''
    showVerifyModal.value = true
}

function openSuspendModal(vendor) {
    selectedVendor.value = vendor
    suspendReason.value = ''
    showSuspendModal.value = true
}

async function confirmVerify() {
    if (!selectedVendor.value) return
    actionLoading.value = true
    try {
        await adminApi.verifyVendor(selectedVendor.value.id, verifyReason.value)
        showToast('Vendor verified successfully!', 'success')
        showVerifyModal.value = false
        fetchVendors(pagination.value.currentPage)
        fetchStats()
    } catch (err) {
        console.error('Failed to verify vendor', err)
        showToast('Failed to verify vendor', 'error')
    } finally {
        actionLoading.value = false
    }
}

async function confirmSuspend() {
    if (!selectedVendor.value || !suspendReason.value) return
    actionLoading.value = true
    try {
        await adminApi.suspendVendor(selectedVendor.value.id, suspendReason.value)
        showToast('Vendor suspended successfully!', 'success')
        showSuspendModal.value = false
        fetchVendors(pagination.value.currentPage)
        fetchStats()
    } catch (err) {
        console.error('Failed to suspend vendor', err)
        showToast('Failed to suspend vendor', 'error')
    } finally {
        actionLoading.value = false
    }
}

function viewVendorDetails(vendor) {
    router.push(`/vendors/${vendor.slug}`)
}

onMounted(() => {
    fetchVendors()
    fetchCategories()
    fetchStats()
})
</script>

<style scoped>
.manage-vendors-page {
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

/* Stats Row */
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

.stat-pill.verified {
    background: var(--success-50);
}

.stat-pill.verified .stat-count {
    color: var(--success-600);
}

.stat-pill.pending {
    background: var(--warning-50);
}

.stat-pill.pending .stat-count {
    color: var(--warning-600);
}

/* Table */
.table-card {
    padding: 0;
    overflow: hidden;
}

.vendors-table {
    width: 100%;
    border-collapse: collapse;
}

.vendors-table th,
.vendors-table td {
    padding: 14px 16px;
    text-align: left;
    font-size: 0.85rem;
}

.vendors-table th {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    font-weight: 500;
    border-bottom: 1px solid var(--border-color);
}

.vendors-table td {
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

.vendors-table tbody tr:hover {
    background: var(--bg-secondary);
}

.vendor-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.vendor-avatar {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, var(--primary-400), var(--primary-600));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
}

.vendor-info {
    display: flex;
    flex-direction: column;
}

.vendor-name {
    font-weight: 600;
    color: var(--text-primary);
}

.vendor-owner {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.category-badge {
    padding: 4px 10px;
    background: var(--bg-secondary);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    text-transform: capitalize;
}

.reliability-score {
    font-weight: 600;
}

.reliability-score.excellent {
    color: var(--success-600);
}

.reliability-score.good {
    color: var(--primary-600);
}

.reliability-score.average {
    color: var(--warning-600);
}

.reliability-score.poor {
    color: var(--danger-600);
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.verified {
    background: var(--success-50);
    color: var(--success-600);
}

.status-badge.pending {
    background: var(--warning-50);
    color: var(--warning-600);
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
    padding: 32px;
    max-width: 480px;
    width: 100%;
}

.modal-content h2 {
    font-size: 1.25rem;
    margin-bottom: 8px;
}

.modal-content>p {
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.modal-note {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 20px;
}

.modal-note.warning {
    color: var(--danger-600);
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 8px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

@media (max-width: 1024px) {
    .filters-row {
        flex-wrap: wrap;
    }

    .filter-group {
        min-width: 150px;
    }
}

@media (max-width: 768px) {
    .vendors-table {
        display: block;
        overflow-x: auto;
    }

    .stats-row {
        flex-wrap: wrap;
    }
}
</style>
