<template>
  <div class="app" :class="{ 'dark': isDark }">
    <Navbar />
    <main class="main-content">
      <RouterView v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </RouterView>
    </main>
    <Footer />
    
    <!-- Toast Notifications -->
    <div class="toast-container">
      <transition-group name="toast">
        <div 
          v-for="toast in toasts" 
          :key="toast.id" 
          :class="['toast', `toast-${toast.type}`]"
        >
          <span class="toast-icon">{{ toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : 'ℹ' }}</span>
          <span>{{ toast.message }}</span>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, provide, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Navbar from './components/common/Navbar.vue'
import Footer from './components/common/Footer.vue'

const isDark = ref(false)
const toasts = ref([])
let toastId = 0

const authStore = useAuthStore()

// Toast system
function showToast(message, type = 'info', duration = 4000) {
  const id = ++toastId
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, duration)
}

provide('showToast', showToast)
provide('isDark', isDark)

// Toggle dark mode
function toggleDark() {
  isDark.value = !isDark.value
  localStorage.setItem('dark-mode', isDark.value)
}
provide('toggleDark', toggleDark)

onMounted(() => {
  // Load dark mode preference
  isDark.value = localStorage.getItem('dark-mode') === 'true'
  
  // Fetch user if token exists
  if (authStore.token) {
    authStore.fetchUser().catch(() => {})
  }
})
</script>

<style>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding-top: 80px;
}

/* Page Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Toast Notifications */
.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.toast {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border-left: 4px solid;
  min-width: 300px;
  max-width: 400px;
}

.toast-success {
  border-color: var(--success-500);
}

.toast-error {
  border-color: var(--danger-500);
}

.toast-info {
  border-color: var(--primary-500);
}

.toast-icon {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: white;
}

.toast-success .toast-icon {
  background: var(--success-500);
}

.toast-error .toast-icon {
  background: var(--danger-500);
}

.toast-info .toast-icon {
  background: var(--primary-500);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>
