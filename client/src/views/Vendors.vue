<template>
  <div class="vendors-page">
    <!-- Header -->
    <section class="page-header">
      <div class="container">
        <h1>Find Reliable Vendors</h1>
        <p>Browse verified vendors with guaranteed backup assurance</p>
      </div>
    </section>

    <div class="vendors-container">
      <!-- Filters Sidebar -->
      <aside class="filters-sidebar">
        <div class="filters-header">
          <h3>Filters</h3>
          <button class="reset-btn" @click="resetFilters">Reset All</button>
        </div>

        <!-- Category -->
        <div class="filter-group">
          <label>Category</label>
          <select v-model="filters.category" @change="applyFilters">
            <option value="">All Categories</option>
            <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">
              {{ cat.icon }} {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- City -->
        <div class="filter-group">
          <label>City</label>
          <select v-model="filters.city" @change="applyFilters">
            <option value="">All Cities</option>
            <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
          </select>
        </div>

        <!-- Price Range -->
        <div class="filter-group">
          <label>Price Range</label>
          <div class="price-inputs">
            <input 
              type="number" 
              v-model="filters.minPrice" 
              placeholder="Min" 
              @change="applyFilters"
            />
            <span>-</span>
            <input 
              type="number" 
              v-model="filters.maxPrice" 
              placeholder="Max" 
              @change="applyFilters"
            />
          </div>
        </div>

        <!-- Reliability Score -->
        <div class="filter-group">
          <label>Min Reliability Score</label>
          <select v-model="filters.minReliability" @change="applyFilters">
            <option value="">Any</option>
            <option value="4.5">4.5+ (Excellent)</option>
            <option value="4.0">4.0+ (Good)</option>
            <option value="3.5">3.5+ (Average)</option>
          </select>
        </div>

        <!-- Backup Ready -->
        <div class="filter-group">
          <label class="checkbox-label">
            <input 
              type="checkbox" 
              v-model="filters.backupReady" 
              @change="applyFilters"
            />
            <span>Backup Ready Only</span>
          </label>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="vendors-main">
        <!-- Sort & Results Count -->
        <div class="vendors-toolbar">
          <span class="results-count">
            {{ pagination.total }} vendors found
          </span>
          <div class="sort-dropdown">
            <label>Sort by:</label>
            <select v-model="filters.sort" @change="applyFilters">
              <option value="reliability">Reliability Score</option>
              <option value="price_low">Price: Low to High</option>
              <option value="price_high">Price: High to Low</option>
              <option value="experience">Experience</option>
              <option value="events">Events Completed</option>
            </select>
          </div>
        </div>

        <!-- Vendors Grid -->
        <div class="vendors-grid" v-if="!loading && vendors.length">
          <VendorCard 
            v-for="vendor in vendors" 
            :key="vendor.id" 
            :vendor="vendor"
          />
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading vendors...</p>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="empty-icon">🔍</span>
          <h3>No vendors found</h3>
          <p>Try adjusting your filters or search criteria</p>
          <button class="btn btn-secondary" @click="resetFilters">Clear Filters</button>
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="pagination.lastPage > 1">
          <button 
            class="page-btn" 
            :disabled="pagination.currentPage === 1"
            @click="goToPage(pagination.currentPage - 1)"
          >
            ← Previous
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in visiblePages" 
              :key="page"
              :class="['page-num', { active: page === pagination.currentPage }]"
              @click="goToPage(page)"
            >
              {{ page }}
            </button>
          </div>

          <button 
            class="page-btn" 
            :disabled="pagination.currentPage === pagination.lastPage"
            @click="goToPage(pagination.currentPage + 1)"
          >
            Next →
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVendorStore } from '../stores/vendors'
import VendorCard from '../components/vendor/VendorCard.vue'

const route = useRoute()
const router = useRouter()
const vendorStore = useVendorStore()

const loading = ref(true)
const vendors = ref([])
const categories = ref([])
const cities = ref([])
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
const isInitialized = ref(false)

const filters = ref({
  category: '',
  city: '',
  minPrice: '',
  maxPrice: '',
  minReliability: '',
  backupReady: false,
  sort: 'reliability',
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage
  
  let start = Math.max(1, current - 2)
  let end = Math.min(last, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

async function loadVendors() {
  loading.value = true
  try {
    const params = { ...filters.value, page: pagination.value.currentPage }
    // Clean empty values
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === false) delete params[key]
    })
    
    const response = await vendorStore.fetchVendors(params)
    vendors.value = vendorStore.vendors
    pagination.value = vendorStore.pagination
  } catch (err) {
    console.error('Failed to load vendors', err)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  pagination.value.currentPage = 1
  updateUrlParams()
  // Don't call loadVendors here - the route watcher will handle it
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
  router.push({ path: '/vendors' })
  // Don't call loadVendors here - the route watcher will handle it
}

function goToPage(page) {
  pagination.value.currentPage = page
  updateUrlParams()
  // Don't call loadVendors here - the route watcher will handle it
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function updateUrlParams() {
  const query = {}
  if (filters.value.category) query.category = filters.value.category
  if (filters.value.city) query.city = filters.value.city
  if (filters.value.sort !== 'reliability') query.sort = filters.value.sort
  if (pagination.value.currentPage > 1) query.page = pagination.value.currentPage
  
  router.push({ path: '/vendors', query })
}

function loadFromUrl() {
  if (route.query.category) filters.value.category = route.query.category
  if (route.query.city) filters.value.city = route.query.city
  if (route.query.sort) filters.value.sort = route.query.sort
  if (route.query.page) pagination.value.currentPage = parseInt(route.query.page)
}

onMounted(async () => {
  loadFromUrl()
  
  // Load categories and cities in parallel
  await Promise.all([
    vendorStore.fetchCategories(),
    vendorStore.fetchCities()
  ])
  
  categories.value = vendorStore.categories
  cities.value = vendorStore.cities
  
  await loadVendors()
  isInitialized.value = true
})

// Watch for route changes - only trigger after initial mount
watch(() => route.query, () => {
  if (isInitialized.value) {
    loadFromUrl()
    loadVendors()
  }
})
</script>

<style scoped>
.vendors-page {
  min-height: 100vh;
}

.page-header {
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%);
  padding: 60px 24px;
  color: white;
  text-align: center;
}

.page-header h1 {
  font-size: 2.5rem;
  margin-bottom: 12px;
}

.page-header p {
  font-size: 1.1rem;
  opacity: 0.9;
}

.vendors-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 40px 24px;
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 40px;
}

/* Filters Sidebar */
.filters-sidebar {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  padding: 24px;
  border: 1px solid var(--border-color);
  height: fit-content;
  position: sticky;
  top: 100px;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-color);
}

.filters-header h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
}

.reset-btn {
  background: none;
  border: none;
  color: var(--primary-600);
  font-size: 0.85rem;
  cursor: pointer;
  font-weight: 500;
}

.reset-btn:hover {
  text-decoration: underline;
}

.filter-group {
  margin-bottom: 20px;
}

.filter-group > label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.filter-group select,
.filter-group input[type="number"] {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 0.9rem;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: var(--primary-500);
}

.price-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-inputs input {
  flex: 1;
}

.price-inputs span {
  color: var(--text-secondary);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--primary-600);
}

.checkbox-label span {
  font-size: 0.9rem;
  color: var(--text-primary);
}

/* Main Content */
.vendors-main {
  min-height: 500px;
}

.vendors-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-color);
}

.results-count {
  font-size: 0.95rem;
  color: var(--text-secondary);
}

.sort-dropdown {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sort-dropdown label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.sort-dropdown select {
  padding: 8px 12px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-primary);
  font-size: 0.9rem;
}

.vendors-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* States */
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

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 48px;
  padding-top: 32px;
  border-top: 1px solid var(--border-color);
}

.page-btn {
  padding: 10px 20px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.page-btn:hover:not(:disabled) {
  border-color: var(--primary-500);
  color: var(--primary-600);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 8px;
}

.page-num {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.page-num:hover {
  border-color: var(--primary-500);
}

.page-num.active {
  background: var(--primary-600);
  border-color: var(--primary-600);
  color: white;
}

/* Responsive */
@media (max-width: 1024px) {
  .vendors-container {
    grid-template-columns: 1fr;
  }

  .filters-sidebar {
    position: static;
  }

  .vendors-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .vendors-grid {
    grid-template-columns: 1fr;
  }

  .vendors-toolbar {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
}
</style>
