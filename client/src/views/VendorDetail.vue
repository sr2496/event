<template>
  <div class="vendor-detail" v-if="vendor">
    <!-- Cover Image -->
    <div class="cover-section">
      <div class="cover-image" v-if="vendor.profile?.cover_image">
        <img :src="vendor.profile.cover_image" :alt="vendor.business_name" />
      </div>
      <div class="cover-placeholder gradient-primary" v-else></div>
    </div>

    <div class="detail-container">
      <!-- Main Content -->
      <div class="detail-main">
        <!-- Profile Header -->
        <div class="profile-header card">
          <div class="profile-avatar">
            <img 
              v-if="vendor.profile?.profile_image" 
              :src="vendor.profile.profile_image" 
              :alt="vendor.business_name" 
            />
            <span v-else class="avatar-icon">{{ getCategoryIcon(vendor.category) }}</span>
          </div>
          
          <div class="profile-info">
            <div class="profile-badges">
              <span v-if="vendor.is_verified" class="badge badge-success">✓ Verified</span>
              <span v-if="vendor.backup_ready" class="badge badge-primary">🛡️ Backup Ready</span>
            </div>
            <h1>{{ vendor.business_name }}</h1>
            <p class="tagline">{{ formatCategory(vendor.category) }} • {{ vendor.city }}</p>
            
            <div class="profile-meta">
              <span>📍 {{ vendor.city }} ({{ vendor.service_radius_km }}km radius)</span>
              <span>⏱️ {{ vendor.experience_years }}+ years experience</span>
              <span>💰 {{ vendor.price_range }}</span>
            </div>
          </div>

          <div class="profile-actions">
            <RouterLink 
              :to="`/book/${vendor.slug}`" 
              class="btn btn-primary btn-lg"
            >
              Book with Assurance
            </RouterLink>
          </div>
        </div>

        <!-- Reliability Section - CORE DIFFERENTIATOR -->
        <div class="reliability-card card">
          <h2>
            <span class="section-icon">📊</span>
            Reliability Score
          </h2>
          
          <div class="reliability-content">
            <div class="score-display">
              <div :class="['score-circle', `score-${vendor.reliability?.badge}`]">
                {{ vendor.reliability?.score }}
              </div>
              <span class="score-label">out of 5.0</span>
            </div>

            <div class="reliability-stats">
              <div class="stat">
                <span class="stat-value">{{ vendor.reliability?.total_events }}</span>
                <span class="stat-label">Events Completed</span>
              </div>
              <div class="stat">
                <span class="stat-value">{{ vendor.reliability?.cancellations }}</span>
                <span class="stat-label">Cancellations</span>
              </div>
              <div class="stat">
                <span class="stat-value">{{ vendor.reliability?.no_shows }}</span>
                <span class="stat-label">No-Shows</span>
              </div>
              <div class="stat">
                <span class="stat-value">{{ vendor.reliability?.avg_response_minutes }}min</span>
                <span class="stat-label">Avg Response</span>
              </div>
              <div class="stat">
                <span class="stat-value">{{ vendor.reliability?.emergency_accepts }}</span>
                <span class="stat-label">Emergency Accepts</span>
              </div>
            </div>
          </div>

          <div class="trust-note">
            <span>🛡️</span>
            <p>This is NOT a review score. It's calculated from actual event history, response times, and reliability data.</p>
          </div>
        </div>

        <!-- Description -->
        <div class="description-card card" v-if="vendor.description">
          <h2>
            <span class="section-icon">📝</span>
            About
          </h2>
          <p>{{ vendor.description }}</p>
        </div>

        <!-- Portfolio -->
        <div class="portfolio-card card" v-if="vendor.portfolio?.length">
          <h2>
            <span class="section-icon">📷</span>
            Portfolio
          </h2>
          
          <div class="portfolio-grid">
            <div 
              v-for="(item, index) in vendor.portfolio" 
              :key="item.id"
              class="portfolio-item"
              @click="openLightbox(index)"
            >
              <img :src="item.image" :alt="item.title || 'Portfolio'" />
              <div class="portfolio-overlay">
                <span>🔍</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Services -->
        <div class="services-card card" v-if="vendor.profile?.services_offered?.length">
          <h2>
            <span class="section-icon">✨</span>
            Services Offered
          </h2>
          
          <div class="services-list">
            <div v-for="service in vendor.profile.services_offered" :key="service" class="service-item">
              <span class="check-icon">✓</span>
              {{ service }}
            </div>
          </div>
        </div>

        <!-- Trust Timeline -->
        <div class="timeline-card card" v-if="vendor.trust_timeline?.length">
          <h2>
            <span class="section-icon">📅</span>
            Trust Timeline
          </h2>
          <p class="timeline-note">Real event history - not fake reviews</p>
          
          <div class="timeline">
            <div 
              v-for="(event, index) in vendor.trust_timeline" 
              :key="index"
              :class="['timeline-item', event.score_change >= 0 ? 'positive' : 'negative']"
            >
              <div class="timeline-date">{{ event.date }}</div>
              <div class="timeline-content">
                <span class="timeline-action">{{ formatAction(event.action) }}</span>
                <span :class="['timeline-score', event.score_change >= 0 ? 'positive' : 'negative']">
                  {{ event.score_change >= 0 ? '+' : '' }}{{ event.score_change }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <aside class="detail-sidebar">
        <!-- Book Card -->
        <div class="book-card card sticky">
          <h3>Book This Vendor</h3>
          
          <div class="price-display">
            <span class="price">{{ vendor.price_range }}</span>
          </div>

          <!-- Availability Check -->
          <div class="availability-check">
            <label>Check Availability</label>
            <input 
              type="date" 
              v-model="checkDate" 
              :min="minDate"
              class="input"
            />
            <button 
              class="btn btn-secondary" 
              @click="checkAvailability"
              :disabled="!checkDate || checkingAvail"
            >
              {{ checkingAvail ? 'Checking...' : 'Check' }}
            </button>
          </div>

          <div v-if="availabilityResult !== null" :class="['availability-result', availabilityResult ? 'available' : 'unavailable']">
            <span>{{ availabilityResult ? '✓ Available' : '✕ Not Available' }}</span>
          </div>

          <RouterLink 
            :to="`/book/${vendor.slug}`" 
            class="btn btn-primary btn-lg book-btn"
          >
            <span>🛡️</span>
            Book with Assurance
          </RouterLink>

          <p class="assurance-note">
            Your booking includes our backup guarantee. If this vendor fails, we'll provide a verified replacement.
          </p>
        </div>

        <!-- Contact Card -->
        <div class="contact-card card" v-if="vendor.profile">
          <h3>Contact Information</h3>
          
          <div class="contact-item" v-if="vendor.profile.phone">
            <span class="contact-icon">📞</span>
            <span>{{ vendor.profile.phone }}</span>
          </div>
          
          <div class="contact-item" v-if="vendor.profile.whatsapp">
            <span class="contact-icon">💬</span>
            <a :href="`https://wa.me/${vendor.profile.whatsapp.replace(/\D/g, '')}`" target="_blank">
              WhatsApp
            </a>
          </div>
          
          <div class="contact-item" v-if="vendor.profile.email">
            <span class="contact-icon">✉️</span>
            <a :href="`mailto:${vendor.profile.email}`">{{ vendor.profile.email }}</a>
          </div>
          
          <div class="contact-item" v-if="vendor.profile.instagram">
            <span class="contact-icon">📸</span>
            <a :href="`https://instagram.com/${vendor.profile.instagram}`" target="_blank">
              @{{ vendor.profile.instagram }}
            </a>
          </div>
        </div>
      </aside>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else-if="loading" class="loading-page">
    <div class="spinner"></div>
    <p>Loading vendor details...</p>
  </div>

  <!-- Error State -->
  <div v-else class="error-page">
    <span class="error-icon">😕</span>
    <h2>Vendor Not Found</h2>
    <p>The vendor you're looking for doesn't exist or has been removed.</p>
    <RouterLink to="/vendors" class="btn btn-primary">Browse Vendors</RouterLink>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useVendorStore } from '../stores/vendors'

const route = useRoute()
const vendorStore = useVendorStore()

const vendor = ref(null)
const loading = ref(true)
const checkDate = ref('')
const checkingAvail = ref(false)
const availabilityResult = ref(null)

const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
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

function formatAction(action) {
  const actions = {
    'event_completed': '✓ Event Completed',
    'event_cancelled_by_vendor': '✕ Cancelled by Vendor',
    'event_cancelled_by_client': '○ Cancelled by Client',
    'no_show': '✕ No Show',
    'emergency_accepted': '⚡ Emergency Accepted',
    'emergency_rejected': '○ Emergency Declined',
    'positive_feedback': '⭐ Positive Feedback',
  }
  return actions[action] || action
}

async function checkAvailability() {
  if (!checkDate.value) return
  
  checkingAvail.value = true
  try {
    const result = await vendorStore.checkAvailability(vendor.value.slug, checkDate.value)
    availabilityResult.value = result.available
  } catch (err) {
    console.error('Failed to check availability', err)
  } finally {
    checkingAvail.value = false
  }
}

function openLightbox(index) {
  // TODO: Implement lightbox
  console.log('Open lightbox for image', index)
}

onMounted(async () => {
  loading.value = true
  try {
    await vendorStore.fetchVendorBySlug(route.params.slug)
    vendor.value = vendorStore.currentVendor
  } catch (err) {
    console.error('Failed to load vendor', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.vendor-detail {
  min-height: 100vh;
}

/* Cover Section */
.cover-section {
  height: 300px;
  overflow: hidden;
}

.cover-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-placeholder {
  width: 100%;
  height: 100%;
}

.detail-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 32px 60px;
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
  margin-top: -60px;
  position: relative;
}

/* Main Content */
.detail-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Profile Header */
.profile-header {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  padding: 28px;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 4px solid var(--bg-primary);
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-icon {
  font-size: 3rem;
}

.profile-info {
  flex: 1;
}

.profile-badges {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.profile-info h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.tagline {
  color: var(--text-secondary);
  font-size: 1rem;
  margin-bottom: 16px;
}

.profile-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.profile-actions {
  flex-shrink: 0;
}

/* Reliability Card */
.reliability-card,
.description-card,
.portfolio-card,
.services-card,
.timeline-card {
  padding: 28px;
}

.reliability-card h2,
.description-card h2,
.portfolio-card h2,
.services-card h2,
.timeline-card h2 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  margin-bottom: 24px;
  color: var(--text-primary);
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-color);
}

.section-icon {
  font-size: 1.5rem;
}

.reliability-content {
  display: flex;
  gap: 48px;
  align-items: center;
  margin-bottom: 24px;
  padding: 16px 0;
}

.score-display {
  text-align: center;
}

.score-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin-bottom: 8px;
}

.score-circle.score-excellent { background: linear-gradient(135deg, #10b981, #34d399); }
.score-circle.score-good { background: linear-gradient(135deg, #22c55e, #86efac); }
.score-circle.score-average { background: linear-gradient(135deg, #f59e0b, #fcd34d); }
.score-circle.score-poor { background: linear-gradient(135deg, #ef4444, #fca5a5); }

.score-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.reliability-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 32px;
  flex: 1;
  justify-content: space-around;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-width: 80px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.trust-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.trust-note span {
  font-size: 1.25rem;
}

/* Portfolio */
.portfolio-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.portfolio-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
}

.portfolio-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-normal);
}

.portfolio-item:hover img {
  transform: scale(1.1);
}

.portfolio-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.portfolio-item:hover .portfolio-overlay {
  opacity: 1;
}

.portfolio-overlay span {
  font-size: 2rem;
  color: white;
}

/* Services */
.services-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.service-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

.check-icon {
  color: var(--success-500);
  font-weight: 700;
}

/* Timeline */
.timeline-note {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.timeline-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  border-left: 4px solid;
}

.timeline-item.positive {
  border-color: var(--success-500);
}

.timeline-item.negative {
  border-color: var(--danger-500);
}

.timeline-date {
  font-size: 0.8rem;
  color: var(--text-secondary);
  min-width: 80px;
}

.timeline-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
}

.timeline-action {
  font-size: 0.9rem;
}

.timeline-score {
  font-weight: 600;
  font-size: 0.85rem;
}

.timeline-score.positive {
  color: var(--success-500);
}

.timeline-score.negative {
  color: var(--danger-500);
}

/* Sidebar */
.detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sticky {
  position: sticky;
  top: 100px;
}

.book-card,
.contact-card {
  padding: 28px;
}

.book-card h3,
.contact-card h3 {
  font-size: 1.15rem;
  margin-bottom: 24px;
  color: var(--text-primary);
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-color);
}

.price-display {
  text-align: center;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  margin-bottom: 20px;
}

.price {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.availability-check {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.availability-check label {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.availability-result {
  text-align: center;
  padding: 12px;
  border-radius: var(--radius-md);
  font-weight: 500;
  margin-bottom: 16px;
}

.availability-result.available {
  background: var(--success-50);
  color: var(--success-600);
}

.availability-result.unavailable {
  background: var(--danger-50);
  color: var(--danger-600);
}

.book-btn {
  width: 100%;
  margin-bottom: 16px;
}

.assurance-note {
  font-size: 0.8rem;
  color: var(--text-secondary);
  text-align: center;
  line-height: 1.5;
}

/* Contact Card */
.contact-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-color);
}

.contact-item:last-child {
  border-bottom: none;
}

.contact-icon {
  font-size: 1.25rem;
}

.contact-item a {
  color: var(--primary-600);
  text-decoration: none;
}

.contact-item a:hover {
  text-decoration: underline;
}

/* States */
.loading-page,
.error-page {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 24px;
}

.loading-page p {
  margin-top: 16px;
  color: var(--text-secondary);
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.error-page h2 {
  font-size: 1.5rem;
  margin-bottom: 8px;
}

.error-page p {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 1024px) {
  .detail-container {
    grid-template-columns: 1fr;
  }

  .detail-sidebar {
    order: -1;
  }

  .sticky {
    position: static;
  }
}

@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }

  .profile-badges {
    justify-content: center;
  }

  .profile-meta {
    justify-content: center;
  }

  .reliability-content {
    flex-direction: column;
    text-align: center;
  }

  .portfolio-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .services-list {
    grid-template-columns: 1fr;
  }
}
</style>
