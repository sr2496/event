<template>
  <RouterLink :to="`/vendors/${vendor.slug}`" class="vendor-card">
    <!-- Image -->
    <div class="vendor-image">
      <img 
        v-if="vendor.portfolio?.length" 
        :src="vendor.portfolio[0].image" 
        :alt="vendor.business_name"
      />
      <div v-else class="image-placeholder">
        <span>{{ getCategoryIcon(vendor.category) }}</span>
      </div>
      
      <!-- Badges -->
      <div class="card-badges">
        <span v-if="vendor.is_verified" class="badge verified">✓ Verified</span>
        <span v-if="vendor.backup_ready" class="badge backup">🛡️ Backup Ready</span>
      </div>
    </div>

    <!-- Content -->
    <div class="vendor-content">
      <div class="vendor-header">
        <h3>{{ vendor.business_name }}</h3>
        <span class="category-tag">{{ formatCategory(vendor.category) }}</span>
      </div>

      <div class="vendor-meta">
        <span class="location">📍 {{ vendor.city }}</span>
        <span class="experience">{{ vendor.experience_years }}+ years</span>
      </div>

      <!-- Reliability Score - Core Differentiator -->
      <div class="reliability-section">
        <div class="reliability-header">
          <span class="reliability-label">Reliability Score</span>
          <span :class="['reliability-score', `score-${vendor.reliability?.badge}`]">
            {{ vendor.reliability?.score }}
          </span>
        </div>
        <div class="reliability-bar">
          <div 
            class="reliability-fill"
            :class="`fill-${vendor.reliability?.badge}`"
            :style="{ width: `${(parseFloat(vendor.reliability?.score) / 5) * 100}%` }"
          ></div>
        </div>
        <div class="reliability-stats">
          <span>{{ vendor.reliability?.total_events }} events</span>
          <span>{{ vendor.reliability?.cancellations }} cancellations</span>
        </div>
      </div>

      <!-- Price & CTA -->
      <div class="vendor-footer">
        <div class="price-range">
          {{ vendor.price_range }}
        </div>
        <button class="btn btn-primary btn-sm">View Profile</button>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router'

const props = defineProps({
  vendor: {
    type: Object,
    required: true
  }
})

const categoryIcons = {
  'photographer': '📷',
  'videographer': '🎥',
  'decorator': '🎨',
  'caterer': '🍽️',
  'dj-music': '🎵',
  'makeup-artist': '💄',
  'florist': '💐',
  'event-planner': '📋',
}

function getCategoryIcon(category) {
  return categoryIcons[category] || '🎉'
}

function formatCategory(category) {
  return category?.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || ''
}
</script>

<style scoped>
.vendor-card {
  display: flex;
  flex-direction: column;
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  overflow: hidden;
  border: 1px solid var(--border-color);
  text-decoration: none;
  transition: all var(--transition-normal);
}

.vendor-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
  border-color: var(--primary-200);
}

/* Image */
.vendor-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.vendor-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-normal);
}

.vendor-card:hover .vendor-image img {
  transform: scale(1.05);
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
  font-size: 4rem;
}

.card-badges {
  position: absolute;
  top: 12px;
  left: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.badge {
  padding: 6px 10px;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
}

.badge.verified {
  background: rgba(16, 185, 129, 0.9);
  color: white;
}

.badge.backup {
  background: rgba(99, 102, 241, 0.9);
  color: white;
}

/* Content */
.vendor-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vendor-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.vendor-header h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  line-height: 1.3;
}

.category-tag {
  font-size: 0.7rem;
  padding: 4px 8px;
  background: var(--bg-secondary);
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  white-space: nowrap;
  text-transform: capitalize;
}

.vendor-meta {
  display: flex;
  gap: 16px;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

/* Reliability Section */
.reliability-section {
  padding: 12px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.reliability-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.reliability-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.reliability-score {
  font-size: 1.1rem;
  font-weight: 700;
}

.score-excellent { color: #10b981; }
.score-good { color: #22c55e; }
.score-average { color: #f59e0b; }
.score-poor { color: #ef4444; }

.reliability-bar {
  height: 6px;
  background: var(--gray-200);
  border-radius: var(--radius-full);
  overflow: hidden;
  margin-bottom: 8px;
}

.reliability-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: width var(--transition-slow);
}

.fill-excellent { background: linear-gradient(90deg, #10b981, #34d399); }
.fill-good { background: linear-gradient(90deg, #22c55e, #86efac); }
.fill-average { background: linear-gradient(90deg, #f59e0b, #fcd34d); }
.fill-poor { background: linear-gradient(90deg, #ef4444, #fca5a5); }

.reliability-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

/* Footer */
.vendor-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid var(--border-color);
}

.price-range {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
}
</style>
