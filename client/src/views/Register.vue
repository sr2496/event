<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card glass">
        <div class="auth-header">
          <RouterLink to="/" class="auth-logo">
            <span>🛡️</span>
            <span>Event Reliability</span>
          </RouterLink>
          <h1>Create Account</h1>
          <p>Join us and never worry about vendor cancellations</p>
        </div>

        <!-- Role Tabs -->
        <div class="role-tabs">
          <button :class="['role-tab', form.role === 'client' && 'active']" @click="form.role = 'client'">
            <span>🎉</span>
            <span>I'm a Client</span>
          </button>
          <button :class="['role-tab', form.role === 'vendor' && 'active']" @click="form.role = 'vendor'">
            <span>📷</span>
            <span>I'm a Vendor</span>
          </button>
        </div>

        <form @submit.prevent="handleRegister" class="auth-form">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" v-model="form.name" class="input" placeholder="John Doe" required
              :disabled="loading" />
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" v-model="form.email" class="input" placeholder="your@email.com" required
              :disabled="loading" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" v-model="form.phone" class="input" placeholder="+91 9876543210"
                :disabled="loading" />
            </div>
            <div class="form-group">
              <label for="city">City</label>
              <input type="text" id="city" v-model="form.city" class="input" placeholder="Mumbai" :disabled="loading" />
            </div>
          </div>

          <!-- Vendor-specific fields -->
          <template v-if="form.role === 'vendor'">
            <div class="form-group">
              <label for="business_name">Business Name *</label>
              <input type="text" id="business_name" v-model="form.business_name" class="input"
                placeholder="Your Photography Studio" required :disabled="loading" />
            </div>

            <div class="form-group">
              <label for="category">Category *</label>
              <select id="category" v-model="form.category" class="input" required :disabled="loading">
                <option value="">Select your category...</option>
                <option value="photographer">Photographer</option>
                <option value="videographer">Videographer</option>
                <option value="decorator">Decorator</option>
                <option value="caterer">Caterer</option>
                <option value="dj-music">DJ / Music</option>
                <option value="makeup-artist">Makeup Artist</option>
                <option value="florist">Florist</option>
                <option value="event-planner">Event Planner</option>
              </select>
            </div>
          </template>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" v-model="form.password" class="input" placeholder="Min 8 characters"
              required :disabled="loading" />
          </div>

          <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" v-model="form.password_confirmation" class="input"
              placeholder="Confirm your password" required :disabled="loading" />
          </div>

          <div class="form-options">
            <label class="terms-checkbox">
              <input type="checkbox" v-model="form.terms" required />
              <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
            </label>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button type="submit" class="btn btn-primary btn-lg" :disabled="loading || !form.terms">
            <span v-if="loading" class="spinner small"></span>
            <span v-else>Create Account</span>
          </button>
        </form>

        <div class="auth-footer">
          <p>Already have an account? <RouterLink to="/login">Sign in</RouterLink>
          </p>
        </div>
      </div>
    </div>

    <!-- Background -->
    <div class="auth-bg gradient-mesh"></div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const showToast = inject('showToast')

const form = ref({
  name: '',
  email: '',
  phone: '',
  city: '',
  password: '',
  password_confirmation: '',
  role: 'client',
  terms: false,
  // Vendor-specific
  business_name: '',
  category: '',
})

const loading = ref(false)
const error = ref('')

onMounted(() => {
  // Check for role in URL
  if (route.query.role === 'vendor') {
    form.value.role = 'vendor'
  }
})

async function handleRegister() {
  loading.value = true
  error.value = ''

  // Validation
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match'
    loading.value = false
    return
  }

  if (form.value.password.length < 8) {
    error.value = 'Password must be at least 8 characters'
    loading.value = false
    return
  }

  try {
    const registerData = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      city: form.value.city,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      role: form.value.role,
    }

    // Add vendor fields if registering as vendor
    if (form.value.role === 'vendor') {
      registerData.business_name = form.value.business_name
      registerData.category = form.value.category
    }

    await authStore.register(registerData)

    showToast('Account created successfully!', 'success')
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  position: relative;
  overflow: hidden;
}

.auth-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.auth-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
}

.auth-card {
  padding: 40px;
  border-radius: var(--radius-2xl);
}

.auth-header {
  text-align: center;
  margin-bottom: 24px;
}

.auth-logo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  text-decoration: none;
  margin-bottom: 24px;
}

.auth-header h1 {
  font-size: 1.75rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.auth-header p {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

/* Role Tabs */
.role-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.role-tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  border: 2px solid var(--border-color);
  border-radius: var(--radius-lg);
  background: var(--bg-primary);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.role-tab:hover {
  border-color: var(--primary-300);
}

.role-tab.active {
  border-color: var(--primary-600);
  background: var(--primary-50);
}

.role-tab span:first-child {
  font-size: 1.5rem;
}

.role-tab span:last-child {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-primary);
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.terms-checkbox {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.85rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.terms-checkbox input {
  margin-top: 3px;
  accent-color: var(--primary-600);
}

.terms-checkbox a {
  color: var(--primary-600);
  text-decoration: none;
}

.terms-checkbox a:hover {
  text-decoration: underline;
}

.error-message {
  padding: 12px 16px;
  background: var(--danger-50);
  color: var(--danger-600);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

.auth-form .btn-primary {
  width: 100%;
  margin-top: 8px;
}

.spinner.small {
  width: 20px;
  height: 20px;
  border-width: 2px;
}

.auth-footer {
  text-align: center;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border-color);
}

.auth-footer p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.auth-footer a {
  color: var(--primary-600);
  font-weight: 500;
  text-decoration: none;
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
