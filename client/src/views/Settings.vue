<template>
  <div class="settings-page">
    <div class="container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <RouterLink to="/dashboard" class="back-link">← Back to Dashboard</RouterLink>
          <h1>⚙️ Platform Settings</h1>
          <p>Configure your platform settings</p>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading settings...</p>
      </div>

      <template v-else>
        <!-- Settings Sections -->
        <div class="settings-grid">
          <!-- General Settings -->
          <div class="settings-section card">
            <h2>🏢 General Settings</h2>
            <div class="settings-form">
              <div class="form-group">
                <label>Platform Name</label>
                <input type="text" v-model="settings.platform_name" class="input" />
              </div>
              <div class="form-group">
                <label>Support Email</label>
                <input type="email" v-model="settings.support_email" class="input" />
              </div>
              <div class="form-group">
                <label>Support Phone</label>
                <input type="tel" v-model="settings.support_phone" class="input" />
              </div>
            </div>
          </div>

          <!-- Financial Settings -->
          <div class="settings-section card">
            <h2>💰 Financial Settings</h2>
            <div class="settings-form">
              <div class="form-group">
                <label>Assurance Fee (%)</label>
                <input type="number" v-model.number="settings.assurance_fee_percent" class="input" min="0" max="100" step="0.1" />
                <span class="helper-text">Percentage of total amount charged as assurance fee</span>
              </div>
              <div class="form-group">
                <label>Platform Commission (%)</label>
                <input type="number" v-model.number="settings.commission_percent" class="input" min="0" max="100" step="0.1" />
                <span class="helper-text">Commission charged on each successful booking</span>
              </div>
              <div class="form-group">
                <label>Advance Payment (%)</label>
                <input type="number" v-model.number="settings.advance_percent" class="input" min="0" max="100" step="1" />
                <span class="helper-text">Advance payment percentage required from clients</span>
              </div>
            </div>
          </div>

          <!-- Emergency Settings -->
          <div class="settings-section card">
            <h2>🚨 Emergency Settings</h2>
            <div class="settings-form">
              <div class="form-group">
                <label>Emergency Bonus Multiplier</label>
                <input type="number" v-model.number="settings.emergency_bonus_multiplier" class="input" min="1" max="5" step="0.1" />
                <span class="helper-text">Bonus multiplier for vendors accepting emergency requests</span>
              </div>
              <div class="form-group">
                <label>Emergency Response Window (hours)</label>
                <input type="number" v-model.number="settings.emergency_window_hours" class="input" min="1" max="48" />
                <span class="helper-text">Time window for vendors to respond to emergency requests</span>
              </div>
              <div class="form-group">
                <label>Max Backup Assignments</label>
                <input type="number" v-model.number="settings.max_backup_assignments" class="input" min="1" max="10" />
                <span class="helper-text">Maximum backup vendors to assign per booking</span>
              </div>
            </div>
          </div>

          <!-- Vendor Settings -->
          <div class="settings-section card">
            <h2>🏪 Vendor Settings</h2>
            <div class="settings-form">
              <div class="form-group">
                <label>Default Reliability Score</label>
                <input type="number" v-model.number="settings.default_reliability_score" class="input" min="1" max="5" step="0.1" />
                <span class="helper-text">Starting reliability score for new vendors</span>
              </div>
              <div class="form-group">
                <label>Min Score for Backup Eligibility</label>
                <input type="number" v-model.number="settings.min_backup_score" class="input" min="1" max="5" step="0.1" />
                <span class="helper-text">Minimum score required to be a backup vendor</span>
              </div>
              <div class="form-group toggle-group">
                <label>Auto-verify Vendors</label>
                <label class="toggle">
                  <input type="checkbox" v-model="settings.auto_verify_vendors" />
                  <span class="toggle-slider"></span>
                </label>
                <span class="helper-text">Automatically verify new vendor registrations</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="settings-actions">
          <button class="btn btn-secondary" @click="resetSettings">Reset to Defaults</button>
          <button class="btn btn-primary" @click="saveSettings" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { RouterLink } from 'vue-router'
import { adminApi } from '../services/api'
import { showDangerConfirm } from '../utils/sweetalert'

const showToast = inject('showToast')

const loading = ref(true)
const saving = ref(false)

const defaultSettings = {
  platform_name: 'Event Reliability Platform',
  support_email: 'support@eventreliability.com',
  support_phone: '+91 1800-123-4567',
  assurance_fee_percent: 5,
  commission_percent: 10,
  advance_percent: 30,
  emergency_bonus_multiplier: 1.5,
  emergency_window_hours: 4,
  max_backup_assignments: 3,
  default_reliability_score: 5.0,
  min_backup_score: 4.0,
  auto_verify_vendors: false
}

const settings = ref({ ...defaultSettings })

async function loadSettings() {
  loading.value = true
  try {
    const response = await adminApi.getSettings()
    settings.value = { ...defaultSettings, ...response.data.settings }
  } catch (err) {
    console.error('Failed to load settings', err)
    showToast('Failed to load settings', 'error')
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    const response = await adminApi.updateSettings(settings.value)
    settings.value = { ...defaultSettings, ...response.data.settings }
    showToast('Settings saved successfully!', 'success')
  } catch (err) {
    console.error('Failed to save settings', err)
    showToast(err.response?.data?.message || 'Failed to save settings', 'error')
  } finally {
    saving.value = false
  }
}

async function resetSettings() {
  const confirmed = await showDangerConfirm(
    'Reset All Settings?', 
    'This will reset all settings to their default values. This action cannot be undone.',
    'Reset',
    'Cancel'
  )
  if (!confirmed) return
  
  saving.value = true
  try {
    await adminApi.updateSettings(defaultSettings)
    settings.value = { ...defaultSettings }
    showToast('Settings reset to defaults', 'success')
  } catch (err) {
    console.error('Failed to reset settings', err)
    showToast('Failed to reset settings', 'error')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>

<style scoped>
.settings-page {
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

/* Loading */
.loading-state {
  text-align: center;
  padding: 80px 24px;
}

.loading-state p {
  color: var(--text-secondary);
  margin-top: 16px;
}

/* Settings Grid */
.settings-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 32px;
}

.settings-section {
  padding: 24px;
}

.settings-section h2 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-color);
}

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary);
}

.helper-text {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

/* Toggle */
.toggle-group {
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.toggle-group > label:first-child {
  flex: 1;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--gray-300);
  transition: 0.3s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle input:checked + .toggle-slider {
  background-color: var(--primary-600);
}

.toggle input:checked + .toggle-slider:before {
  transform: translateX(24px);
}

.toggle-group .helper-text {
  width: 100%;
}

/* Actions */
.settings-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-bottom: 32px;
}

@media (max-width: 1024px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
}
</style>
