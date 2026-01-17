<template>
  <div class="manage-categories-page">
    <div class="container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
          <h1>📂 Manage Categories</h1>
          <p>Organize vendor categories</p>
        </div>
        <button class="btn btn-primary" @click="openCreateModal">
          + Add Category
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading categories...</p>
      </div>

      <!-- Categories Grid -->
      <div v-else class="categories-grid">
        <div 
          v-for="category in categories" 
          :key="category.id" 
          class="category-card card"
          :class="{ inactive: !category.is_active }"
        >
          <div class="category-header">
            <span class="category-icon">{{ category.icon || '📁' }}</span>
            <div class="category-info">
              <h3>{{ category.name }}</h3>
              <p class="category-slug">{{ category.slug }}</p>
            </div>
          </div>
          
          <p class="category-description" v-if="category.description">
            {{ category.description }}
          </p>
          
          <div class="category-stats">
            <span class="vendor-count">
              <strong>{{ category.vendors_count || 0 }}</strong> vendors
            </span>
            <span :class="['status-badge', category.is_active ? 'active' : 'inactive']">
              {{ category.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="category-actions">
            <button class="btn btn-sm btn-secondary" @click="openEditModal(category)">
              ✏️ Edit
            </button>
            <button 
              class="btn btn-sm"
              :class="category.is_active ? 'btn-danger' : 'btn-success'"
              @click="toggleCategoryStatus(category)"
            >
              {{ category.is_active ? 'Disable' : 'Enable' }}
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="categories.length === 0" class="empty-state">
          <span>📂</span>
          <p>No categories found</p>
          <button class="btn btn-primary" @click="openCreateModal">Create First Category</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-content">
        <h2>{{ editingCategory ? '✏️ Edit Category' : '➕ Add Category' }}</h2>
        
        <form @submit.prevent="saveCategory" class="category-form">
          <div class="form-group">
            <label>Name *</label>
            <input 
              type="text" 
              v-model="form.name" 
              class="input" 
              placeholder="e.g., Photography"
              required
            />
          </div>

          <div class="form-group">
            <label>Icon (Emoji)</label>
            <input 
              type="text" 
              v-model="form.icon" 
              class="input" 
              placeholder="📷"
              maxlength="4"
            />
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea 
              v-model="form.description" 
              class="input" 
              rows="3"
              placeholder="Brief description of this category..."
            ></textarea>
          </div>

          <div class="form-group">
            <label>Sort Order</label>
            <input 
              type="number" 
              v-model.number="form.sort_order" 
              class="input" 
              placeholder="0"
            />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Saving...' : (editingCategory ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { RouterLink } from 'vue-router'
import { adminApi } from '../services/api'

const showToast = inject('showToast')

const loading = ref(true)
const saving = ref(false)
const categories = ref([])

const showModal = ref(false)
const editingCategory = ref(null)
const form = ref({
  name: '',
  icon: '',
  description: '',
  sort_order: 0
})

async function fetchCategories() {
  loading.value = true
  try {
    const response = await adminApi.getCategories()
    categories.value = response.data.data
  } catch (err) {
    console.error('Failed to fetch categories', err)
    showToast('Failed to load categories', 'error')
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingCategory.value = null
  form.value = {
    name: '',
    icon: '',
    description: '',
    sort_order: categories.value.length
  }
  showModal.value = true
}

function openEditModal(category) {
  editingCategory.value = category
  form.value = {
    name: category.name,
    icon: category.icon || '',
    description: category.description || '',
    sort_order: category.sort_order || 0
  }
  showModal.value = true
}

async function saveCategory() {
  saving.value = true
  try {
    if (editingCategory.value) {
      await adminApi.updateCategory(editingCategory.value.id, form.value)
      showToast('Category updated successfully', 'success')
    } else {
      await adminApi.createCategory(form.value)
      showToast('Category created successfully', 'success')
    }
    showModal.value = false
    fetchCategories()
  } catch (err) {
    console.error('Failed to save category', err)
    showToast(err.response?.data?.message || 'Failed to save category', 'error')
  } finally {
    saving.value = false
  }
}

async function toggleCategoryStatus(category) {
  try {
    await adminApi.updateCategory(category.id, { is_active: !category.is_active })
    showToast(`Category ${category.is_active ? 'disabled' : 'enabled'} successfully`, 'success')
    fetchCategories()
  } catch (err) {
    console.error('Failed to toggle category status', err)
    showToast('Failed to update category', 'error')
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
.manage-categories-page {
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

/* Categories Grid */
.categories-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.category-card {
  padding: 24px;
  transition: all var(--transition-fast);
}

.category-card.inactive {
  opacity: 0.6;
}

.category-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.category-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.category-icon {
  font-size: 2.5rem;
}

.category-info h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 2px;
}

.category-slug {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.category-description {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 16px;
  line-height: 1.5;
}

.category-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border-color);
}

.vendor-count {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.vendor-count strong {
  color: var(--text-primary);
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
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

.category-actions {
  display: flex;
  gap: 8px;
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

/* Empty State */
.empty-state {
  grid-column: 1 / -1;
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
  margin-bottom: 16px;
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
  max-width: 500px;
  width: 100%;
}

.modal-content h2 {
  font-size: 1.25rem;
  margin-bottom: 24px;
}

.category-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 6px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 8px;
}

@media (max-width: 1024px) {
  .categories-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .categories-grid {
    grid-template-columns: 1fr;
  }
}
</style>
