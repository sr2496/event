<template>
  <div class="create-booking-page" v-if="vendor">
    <div class="container">
      <RouterLink to="/vendors" class="back-link">← Back to Vendors</RouterLink>

      <div class="booking-grid">
        <!-- Form -->
        <div class="booking-form-section">
          <div class="card">
            <h1>Book {{ vendor.business_name }}</h1>
            <p class="form-subtitle">Fill in your event details to book this vendor with backup guarantee</p>

            <form @submit.prevent="handleSubmit" class="booking-form">
              <div class="form-group">
                <label for="title">Event Title *</label>
                <input type="text" id="title" v-model="form.title" class="input" placeholder="e.g., John & Sarah's Wedding" required />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="type">Event Type *</label>
                  <select id="type" v-model="form.type" class="input" required>
                    <option value="">Select type...</option>
                    <option value="wedding">Wedding</option>
                    <option value="pre_wedding">Pre-Wedding Shoot</option>
                    <option value="corporate">Corporate Event</option>
                    <option value="influencer_shoot">Influencer Shoot</option>
                    <option value="birthday">Birthday Party</option>
                    <option value="anniversary">Anniversary</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="event_date">Event Date *</label>
                  <input type="date" id="event_date" v-model="form.event_date" class="input" :min="minDate" required />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="start_time">Start Time</label>
                  <input type="time" id="start_time" v-model="form.start_time" class="input" />
                </div>
                <div class="form-group">
                  <label for="end_time">End Time</label>
                  <input type="time" id="end_time" v-model="form.end_time" class="input" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="venue">Venue</label>
                  <input type="text" id="venue" v-model="form.venue" class="input" placeholder="Venue name/address" />
                </div>
                <div class="form-group">
                  <label for="city">City *</label>
                  <input type="text" id="city" v-model="form.city" class="input" placeholder="City" required />
                </div>
              </div>

              <div class="form-group">
                <label for="expected_guests">Expected Guests</label>
                <input type="number" id="expected_guests" v-model="form.expected_guests" class="input" placeholder="Number of guests" min="1" />
              </div>

              <div class="form-group">
                <label for="description">Event Description</label>
                <textarea id="description" v-model="form.description" class="input" rows="3" placeholder="Any additional details about your event..."></textarea>
              </div>

              <div class="form-group">
                <label for="special_requirements">Special Requirements for Vendor</label>
                <textarea id="special_requirements" v-model="form.special_requirements" class="input" rows="2" placeholder="Any specific requirements..."></textarea>
              </div>

              <div v-if="error" class="error-message">{{ error }}</div>

              <button type="submit" class="btn btn-primary btn-lg submit-btn" :disabled="loading">
                <span v-if="loading" class="spinner small"></span>
                <span v-else>Continue to Payment</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="booking-summary">
          <div class="card sticky">
            <h3>Booking Summary</h3>

            <!-- Vendor Info -->
            <div class="vendor-summary">
              <div class="vendor-avatar">
                {{ getCategoryIcon(vendor.category) }}
              </div>
              <div class="vendor-info">
                <h4>{{ vendor.business_name }}</h4>
                <span class="vendor-category">{{ formatCategory(vendor.category) }}</span>
                <div class="vendor-reliability">
                  ⭐ {{ vendor.reliability?.score }} Reliability Score
                </div>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="price-breakdown">
              <div class="price-row">
                <span>Vendor Charges</span>
                <span>{{ vendor.price_range }}</span>
              </div>
              <div class="price-row assurance">
                <span>
                  Assurance Fee
                  <span class="info-icon" title="Non-refundable fee that guarantees backup vendor">ℹ️</span>
                </span>
                <span>~5% of booking</span>
              </div>
              <div class="price-note">
                Exact pricing will be confirmed after vendor accepts
              </div>
            </div>

            <!-- Guarantee -->
            <div class="guarantee-section">
              <h4>🛡️ What's Included</h4>
              <ul>
                <li>✓ Verified vendor booking</li>
                <li>✓ 3 backup vendors assigned</li>
                <li>✓ Fast replacement if vendor fails</li>
                <li>✓ 100% event guarantee</li>
              </ul>
            </div>

            <!-- Trust Badges -->
            <div class="trust-badges">
              <span class="trust-badge">🔒 Secure Booking</span>
              <span class="trust-badge">🛡️ Backup Guaranteed</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading -->
  <div v-else-if="loading" class="loading-page">
    <div class="spinner"></div>
    <p>Loading vendor details...</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useVendorStore } from '../stores/vendors'
import { useBookingStore } from '../stores/bookings'

const route = useRoute()
const router = useRouter()
const vendorStore = useVendorStore()
const bookingStore = useBookingStore()
const showToast = inject('showToast')

const vendor = ref(null)
const loading = ref(true)
const error = ref('')

const form = ref({
  title: '',
  type: '',
  event_date: '',
  start_time: '',
  end_time: '',
  venue: '',
  city: '',
  expected_guests: '',
  description: '',
  special_requirements: '',
})

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

async function handleSubmit() {
  loading.value = true
  error.value = ''

  try {
    const bookingData = {
      vendor_id: vendor.value.id,
      title: form.value.title,
      type: form.value.type,
      event_date: form.value.event_date,
      start_time: form.value.start_time || null,
      end_time: form.value.end_time || null,
      venue: form.value.venue || null,
      city: form.value.city,
      expected_guests: form.value.expected_guests || null,
      description: form.value.description || null,
      special_requirements: form.value.special_requirements || null,
    }

    const result = await bookingStore.createBooking(bookingData)
    
    showToast('Booking created! Please complete the payment.', 'success')
    router.push(`/bookings/${result.event.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create booking. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await vendorStore.fetchVendorBySlug(route.params.vendorSlug)
    vendor.value = vendorStore.currentVendor
    form.value.city = vendor.value.city
  } catch (err) {
    console.error('Failed to load vendor', err)
    router.push('/vendors')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.create-booking-page {
  padding: 40px 0 80px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
  margin-bottom: 24px;
}

.back-link:hover {
  color: var(--primary-600);
}

.booking-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 32px;
  align-items: flex-start;
}

/* Form Section */
.booking-form-section h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.form-subtitle {
  color: var(--text-secondary);
  margin-bottom: 32px;
}

.booking-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group select,
.form-group textarea {
  width: 100%;
}

.error-message {
  padding: 12px 16px;
  background: var(--danger-50);
  color: var(--danger-600);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

.submit-btn {
  width: 100%;
  margin-top: 12px;
}

.spinner.small {
  width: 20px;
  height: 20px;
  border-width: 2px;
}

/* Sidebar */
.booking-summary .sticky {
  position: sticky;
  top: 100px;
}

.booking-summary h3 {
  font-size: 1.1rem;
  margin-bottom: 20px;
}

.vendor-summary {
  display: flex;
  gap: 16px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 20px;
}

.vendor-avatar {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-lg);
  background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
}

.vendor-info h4 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.vendor-category {
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-transform: capitalize;
}

.vendor-reliability {
  font-size: 0.85rem;
  color: var(--success-600);
  margin-top: 4px;
}

/* Price Breakdown */
.price-breakdown {
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 20px;
}

.price-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 0.9rem;
}

.price-row.assurance {
  color: var(--primary-600);
}

.info-icon {
  cursor: help;
  font-size: 0.8rem;
}

.price-note {
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-style: italic;
}

/* Guarantee */
.guarantee-section {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
  border-radius: var(--radius-lg);
  padding: 16px;
  margin-bottom: 20px;
}

.guarantee-section h4 {
  font-size: 0.95rem;
  color: var(--success-700);
  margin-bottom: 12px;
}

.guarantee-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.guarantee-section li {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

/* Trust Badges */
.trust-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.trust-badge {
  font-size: 0.75rem;
  padding: 6px 10px;
  background: var(--bg-secondary);
  border-radius: var(--radius-full);
  color: var(--text-secondary);
}

/* Loading */
.loading-page {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.loading-page p {
  color: var(--text-secondary);
  margin-top: 16px;
}

/* Responsive */
@media (max-width: 1024px) {
  .booking-grid {
    grid-template-columns: 1fr;
  }

  .booking-summary .sticky {
    position: static;
  }
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
