<template>
  <div class="booking-detail-page" v-if="booking">
    <div class="container">
      <!-- Header -->
      <div class="detail-header">
        <RouterLink to="/bookings" class="back-link">← Back to Bookings</RouterLink>
        <div class="header-content">
          <div class="header-info">
            <h1>{{ booking.title }}</h1>
            <div class="header-meta">
              <span :class="['status-badge', `status-${booking.status}`]">{{ booking.status }}</span>
              <span>{{ booking.event_date_formatted }}</span>
              <span>{{ formatEventType(booking.type) }}</span>
            </div>
          </div>
          <div class="header-actions" v-if="booking.can_trigger_emergency">
            <button class="btn btn-danger" @click="showEmergencyModal = true">
              🚨 Vendor Failed
            </button>
          </div>
        </div>
      </div>

      <div class="detail-grid">
        <!-- Main Content -->
        <div class="detail-main">
          <!-- Payment Pending Action -->
          <div class="card payment-action-card" v-if="booking.status === 'pending'">
            <div class="payment-action-header">
              <div>
                <h2>💳 Complete Your Booking</h2>
                <p>Payment is required to confirm <strong>{{ booking.title }}</strong></p>
              </div>
              <div class="payment-total">
                <span class="label">Total to Pay</span>
                <span class="amount">₹{{ formatPrice(totalAmount) }}</span>
              </div>
            </div>
            <button class="btn btn-primary btn-lg pay-btn" @click="processPayment" :disabled="processingPayment">
              {{ processingPayment ? 'Processing Payment...' : 'Securely Pay Now' }}
            </button>
            <p class="payment-secure-note">🔒 Payments are secured. Assurance fee included.</p>
          </div>

          <!-- Event Details -->
          <div class="card">
            <h2>Event Details</h2>
            <div class="detail-list">
              <div class="detail-item">
                <span class="detail-label">📅 Date</span>
                <span class="detail-value">{{ booking.event_date_formatted }}</span>
              </div>
              <div class="detail-item" v-if="booking.start_time">
                <span class="detail-label">🕐 Time</span>
                <span class="detail-value">{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">📍 Venue</span>
                <span class="detail-value">{{ booking.venue || booking.city }}</span>
              </div>
              <div class="detail-item" v-if="booking.expected_guests">
                <span class="detail-label">👥 Expected Guests</span>
                <span class="detail-value">{{ booking.expected_guests }}</span>
              </div>
              <div class="detail-item" v-if="booking.description">
                <span class="detail-label">📝 Description</span>
                <span class="detail-value">{{ booking.description }}</span>
              </div>
            </div>
          </div>

          <!-- Vendors -->
          <div class="card">
            <h2>Assigned Vendors</h2>
            <div class="vendors-list">
              <div 
                v-for="ev in booking.vendors" 
                :key="ev.id" 
                :class="['vendor-card-item', `role-${ev.role}`]"
              >
                <div class="vendor-card-header">
                  <span :class="['role-badge', `role-${ev.role}`]">{{ ev.role }}</span>
                  <span :class="['vendor-status', `vs-${ev.status}`]">{{ ev.status }}</span>
                </div>
                <div class="vendor-card-body">
                  <RouterLink 
                    v-if="ev.vendor" 
                    :to="`/vendors/${ev.vendor.slug}`"
                    class="vendor-name-link"
                  >
                    {{ ev.vendor.business_name }}
                  </RouterLink>
                  <span class="vendor-category">{{ ev.vendor?.category }}</span>
                </div>
                <div class="vendor-card-footer">
                  <span class="vendor-price">₹{{ formatPrice(ev.agreed_price) }}</span>
                  <span v-if="ev.vendor?.reliability_score" class="vendor-score">
                    ⭐ {{ ev.vendor.reliability_score }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Backup Guarantee -->
            <div class="backup-notice" v-if="!booking.has_emergency">
              <span class="backup-icon">🛡️</span>
              <div>
                <strong>Backup Vendors Assigned</strong>
                <p>If your primary vendor fails, verified backup vendors are ready to take over.</p>
              </div>
            </div>
          </div>

          <!-- Emergency Section -->
          <div class="card emergency-card" v-if="booking.has_emergency">
            <h2>🚨 Emergency Status</h2>
            <div v-for="er in booking.emergency_requests" :key="er.id" class="emergency-item">
              <div class="emergency-header">
                <span :class="['emergency-status', `es-${er.status}`]">{{ er.status }}</span>
                <span class="emergency-time">{{ formatDate(er.created_at) }}</span>
              </div>
              <p class="emergency-reason">{{ er.failure_reason }}</p>
              <div v-if="er.assigned_backup" class="emergency-replacement">
                <span>✓ Replacement Assigned:</span>
                <strong>{{ er.assigned_backup.business_name }}</strong>
              </div>
              <div v-if="er.resolution_time_minutes" class="emergency-resolution">
                Resolved in {{ er.resolution_time_minutes }} minutes
              </div>
            </div>
          </div>

          <!-- Payments -->
          <div class="card" v-if="booking.payments?.length">
            <h2>Payment History</h2>
            <div class="payments-list">
              <div v-for="payment in booking.payments" :key="payment.id" class="payment-item">
                <div class="payment-info">
                  <span class="payment-type">{{ formatPaymentType(payment.type) }}</span>
                  <span :class="['payment-status', `ps-${payment.status}`]">{{ payment.status }}</span>
                </div>
                <span class="payment-amount">₹{{ formatPrice(payment.amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="card actions-card" v-if="booking.status === 'confirmed' && !booking.is_upcoming">
            <button class="btn btn-success btn-lg" @click="markCompleted" :disabled="completing">
              {{ completing ? 'Processing...' : '✓ Mark Event as Completed' }}
            </button>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="detail-sidebar">
          <!-- Payment Summary -->
          <div class="card">
            <h3>Payment Summary</h3>
            <div class="payment-summary">
              <div class="summary-row">
                <span>Vendor Charges</span>
                <span>₹{{ formatPrice(booking.financials?.total_amount) }}</span>
              </div>
              <div class="summary-row">
                <span>Assurance Fee</span>
                <span>₹{{ formatPrice(booking.financials?.assurance_fee) }}</span>
              </div>
              <div class="summary-row total">
                <span>Total</span>
                <span>₹{{ formatPrice(parseFloat(booking.financials?.total_amount || 0) + parseFloat(booking.financials?.assurance_fee || 0)) }}</span>
              </div>
            </div>
          </div>

          <!-- Need Help -->
          <div class="card help-card">
            <h3>Need Help?</h3>
            <p>Having issues with your booking or vendor?</p>
            <button class="btn btn-secondary">Contact Support</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Emergency Modal -->
    <div v-if="showEmergencyModal" class="modal-overlay" @click.self="showEmergencyModal = false">
      <div class="modal-content">
        <h2>🚨 Report Vendor Failure</h2>
        <p>If your vendor has cancelled or is unable to perform, we'll find a verified backup immediately.</p>
        
        <div class="form-group">
          <label>Select Failed Vendor</label>
          <select v-model="emergencyForm.event_vendor_id" class="input">
            <option value="">Select vendor...</option>
            <option v-for="ev in booking.vendors" :key="ev.id" :value="ev.id">
              {{ ev.vendor?.business_name }} ({{ ev.role }})
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Reason for Failure</label>
          <textarea v-model="emergencyForm.failure_reason" class="input" rows="3" placeholder="Describe what happened..."></textarea>
        </div>

        <div class="form-group">
          <label>Upload Proof (Optional)</label>
          <input type="file" @change="handleProofFile" accept="image/*,.pdf" />
        </div>

        <div class="modal-actions">
          <button class="btn btn-secondary" @click="showEmergencyModal = false">Cancel</button>
          <button class="btn btn-danger" @click="submitEmergency" :disabled="submittingEmergency">
            {{ submittingEmergency ? 'Submitting...' : 'Trigger Emergency' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading -->
  <div v-else-if="loading" class="loading-page">
    <div class="spinner"></div>
    <p>Loading booking details...</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useBookingStore } from '../stores/bookings'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()
const showToast = inject('showToast')

const booking = ref(null)
const loading = ref(true)
const completing = ref(false)
const processingPayment = ref(false)
const showEmergencyModal = ref(false)
const submittingEmergency = ref(false)

const emergencyForm = ref({
  event_vendor_id: '',
  failure_reason: '',
  proof_file: null,
})

const totalAmount = computed(() => {
  if (!booking.value?.financials) return 0
  return parseFloat(booking.value.financials.total_amount || 0) + parseFloat(booking.value.financials.assurance_fee || 0)
})

async function processPayment() {
  processingPayment.value = true
  try {
    // Simulate payment gateway delay
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    await bookingStore.confirmPayment(booking.value.id, {
      payment_method: 'credit_card', // Mock method
      transaction_id: 'tx_mock_' + Date.now() // Mock transaction ID
    })
    
    showToast('Payment successful! Booking confirmed.', 'success')
    
    // Refresh booking
    await bookingStore.fetchBookingById(route.params.id)
    booking.value = bookingStore.currentBooking
  } catch (err) {
     console.error(err)
     showToast('Payment failed. Please try again.', 'error')
  } finally {
     processingPayment.value = false
  }
}

function formatEventType(type) {
  return type?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || ''
}

function formatPrice(amount) {
  if (!amount) return '0'
  return new Intl.NumberFormat('en-IN').format(amount)
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en', { 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  // Check if valid date
  if (isNaN(date.getTime())) return dateStr

  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

function formatPaymentType(type) {
  const types = {
    'advance': 'Advance Payment',
    'assurance_fee': 'Assurance Fee',
    'final_payment': 'Final Payment',
    'refund': 'Refund',
    'emergency_commission': 'Emergency Commission',
  }
  return types[type] || type
}

function handleProofFile(e) {
  emergencyForm.value.proof_file = e.target.files[0]
}

async function markCompleted() {
  completing.value = true
  try {
    await bookingStore.markCompleted(booking.value.id)
    showToast('Event marked as completed!', 'success')
    booking.value = bookingStore.currentBooking
  } catch (err) {
    showToast('Failed to mark as completed', 'error')
  } finally {
    completing.value = false
  }
}

async function submitEmergency() {
  if (!emergencyForm.value.event_vendor_id || !emergencyForm.value.failure_reason) {
    showToast('Please fill in all required fields', 'error')
    return
  }

  submittingEmergency.value = true
  try {
    const formData = new FormData()
    formData.append('event_vendor_id', emergencyForm.value.event_vendor_id)
    formData.append('failure_reason', emergencyForm.value.failure_reason)
    if (emergencyForm.value.proof_file) {
      formData.append('proof_file', emergencyForm.value.proof_file)
    }

    await bookingStore.triggerEmergency(booking.value.id, formData)
    showToast('Emergency request submitted! We are searching for a backup.', 'success')
    showEmergencyModal.value = false
    
    // Refresh booking
    await bookingStore.fetchBookingById(route.params.id)
    booking.value = bookingStore.currentBooking
  } catch (err) {
    showToast('Failed to submit emergency request', 'error')
  } finally {
    submittingEmergency.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await bookingStore.fetchBookingById(route.params.id)
    booking.value = bookingStore.currentBooking
  } catch (err) {
    console.error('Failed to load booking', err)
    router.push('/bookings')
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.booking-detail-page {
  padding: 40px 0 80px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
  margin-bottom: 16px;
}

.back-link:hover {
  color: var(--primary-600);
}

.detail-header {
  margin-bottom: 32px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-info h1 {
  font-size: 2rem;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.header-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--text-secondary);
}

.status-badge {
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-pending { background: var(--warning-50); color: var(--warning-600); }
.status-confirmed { background: var(--success-50); color: var(--success-600); }
.status-completed { background: var(--gray-100); color: var(--gray-600); }
.status-cancelled { background: var(--danger-50); color: var(--danger-600); }
.status-emergency { background: var(--danger-50); color: var(--danger-600); }

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 32px;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.card h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 20px;
}

.detail-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-item {
  display: flex;
  gap: 16px;
}

.detail-label {
  min-width: 140px;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.detail-value {
  color: var(--text-primary);
  font-weight: 500;
}

/* Payment Action Card */
.payment-action-card {
  background: linear-gradient(135deg, var(--primary-50), white);
  border: 1px solid var(--primary-200);
}

.payment-action-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.payment-action-header h2 {
  font-size: 1.25rem;
  color: var(--primary-700);
  margin-bottom: 4px;
}

.payment-total {
  text-align: right;
}

.payment-total .label {
  display: block;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.payment-total .amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.pay-btn {
  width: 100%;
}

.payment-secure-note {
  text-align: center;
  margin-top: 12px;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

/* Vendors */
.vendors-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.vendor-card-item {
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  border-left: 4px solid;
}

.vendor-card-item.role-primary { border-color: var(--primary-500); }
.vendor-card-item.role-backup { border-color: var(--gray-400); }
.vendor-card-item.role-emergency_replacement { border-color: var(--warning-500); }

.vendor-card-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.role-badge {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  text-transform: uppercase;
  font-weight: 600;
}

.role-badge.role-primary { background: var(--primary-100); color: var(--primary-700); }
.role-badge.role-backup { background: var(--gray-200); color: var(--gray-700); }
.role-badge.role-emergency_replacement { background: var(--warning-100); color: var(--warning-700); }

.vendor-status {
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
}

.vs-confirmed { background: var(--success-50); color: var(--success-600); }
.vs-pending { background: var(--warning-50); color: var(--warning-600); }
.vs-failed { background: var(--danger-50); color: var(--danger-600); }
.vs-completed { background: var(--gray-100); color: var(--gray-600); }

.vendor-name-link {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
}

.vendor-name-link:hover {
  color: var(--primary-600);
}

.vendor-category {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-left: 8px;
  text-transform: capitalize;
}

.vendor-card-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 0.9rem;
}

.vendor-price {
  font-weight: 600;
  color: var(--text-primary);
}

.vendor-score {
  color: var(--text-secondary);
}

.backup-notice {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
  border-radius: var(--radius-lg);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.backup-icon {
  font-size: 2rem;
}

.backup-notice strong {
  color: var(--success-700);
}

.backup-notice p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-top: 4px;
}

/* Emergency */
.emergency-card {
  background: var(--danger-50);
  border-color: var(--danger-200);
}

.emergency-item {
  padding: 16px;
  background: white;
  border-radius: var(--radius-lg);
  margin-bottom: 12px;
}

.emergency-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.emergency-status {
  font-size: 0.75rem;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-weight: 600;
}

.es-pending { background: var(--warning-100); color: var(--warning-700); }
.es-searching { background: var(--primary-100); color: var(--primary-700); }
.es-backup_found { background: var(--success-100); color: var(--success-700); }
.es-resolved { background: var(--success-100); color: var(--success-700); }

.emergency-reason {
  color: var(--text-primary);
  margin-bottom: 12px;
}

.emergency-replacement {
  padding: 8px 12px;
  background: var(--success-50);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

/* Payments */
.payments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-color);
}

.payment-item:last-child {
  border-bottom: none;
}

.payment-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.payment-type {
  font-weight: 500;
}

.payment-status {
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
}

.ps-completed { background: var(--success-50); color: var(--success-600); }
.ps-pending { background: var(--warning-50); color: var(--warning-600); }

.payment-amount {
  font-weight: 600;
}

/* Sidebar */
.detail-sidebar .card {
  margin-bottom: 20px;
}

.detail-sidebar h3 {
  font-size: 1rem;
  margin-bottom: 16px;
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.summary-row.total {
  margin-top: 8px;
  padding-top: 12px;
  border-top: 1px solid var(--border-color);
  font-weight: 700;
  font-size: 1rem;
}

.help-card p {
  color: var(--text-secondary);
  font-size: 0.9rem;
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
  margin-bottom: 12px;
}

.modal-content > p {
  color: var(--text-secondary);
  margin-bottom: 24px;
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

.form-group textarea {
  resize: vertical;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
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
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
  }

  .header-meta {
    flex-wrap: wrap;
  }
}
</style>
