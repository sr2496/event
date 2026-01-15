<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card glass">
        <div class="auth-header">
          <RouterLink to="/" class="auth-logo">
            <span>🛡️</span>
            <span>Event Reliability</span>
          </RouterLink>
          <h1>Welcome Back</h1>
          <p>Sign in to manage your bookings and events</p>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input 
              type="email" 
              id="email" 
              v-model="form.email" 
              class="input"
              placeholder="your@email.com"
              required
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-input">
              <input 
                :type="showPassword ? 'text' : 'password'" 
                id="password" 
                v-model="form.password" 
                class="input"
                placeholder="••••••••"
                required
                :disabled="loading"
              />
              <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                {{ showPassword ? '👁️' : '👁️‍🗨️' }}
              </button>
            </div>
          </div>

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" v-model="form.remember" />
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button type="submit" class="btn btn-primary btn-lg" :disabled="loading">
            <span v-if="loading" class="spinner small"></span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <div class="auth-footer">
          <p>Don't have an account? <RouterLink to="/register">Create one</RouterLink></p>
        </div>
      </div>
    </div>

    <!-- Background Elements -->
    <div class="auth-bg gradient-mesh">
      <div class="floating-element e1">🎉</div>
      <div class="floating-element e2">📷</div>
      <div class="floating-element e3">🎨</div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const showToast = inject('showToast')

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password,
    })

    showToast('Welcome back!', 'success')

    // Redirect to intended page or dashboard
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid credentials. Please try again.'
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

.floating-element {
  position: absolute;
  font-size: 4rem;
  opacity: 0.2;
  animation: float 6s ease-in-out infinite;
}

.e1 { top: 10%; left: 10%; animation-delay: 0s; }
.e2 { top: 20%; right: 15%; animation-delay: 2s; }
.e3 { bottom: 20%; left: 20%; animation-delay: 4s; }

.auth-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
}

.auth-card {
  padding: 40px;
  border-radius: var(--radius-2xl);
}

.auth-header {
  text-align: center;
  margin-bottom: 32px;
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

.auth-form {
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

.password-input {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  color: var(--text-secondary);
  cursor: pointer;
}

.remember-me input {
  accent-color: var(--primary-600);
}

.forgot-link {
  font-size: 0.9rem;
  color: var(--primary-600);
  text-decoration: none;
}

.forgot-link:hover {
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

.auth-footer a:hover {
  text-decoration: underline;
}
</style>
