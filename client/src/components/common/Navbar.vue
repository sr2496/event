<template>
  <nav class="navbar glass">
    <div class="navbar-container">
      <!-- Logo -->
      <RouterLink to="/" class="navbar-logo">
        <span class="logo-icon">🛡️</span>
        <span class="logo-text">
          <span class="logo-primary">Event</span>
          <span class="logo-accent">Reliability</span>
        </span>
      </RouterLink>

      <!-- Desktop Navigation -->
      <div class="navbar-links">
        <RouterLink to="/vendors" class="nav-link">Find Vendors</RouterLink>
        <RouterLink to="/vendors?category=photographer" class="nav-link">Photographers</RouterLink>
        <RouterLink to="/vendors?category=decorator" class="nav-link">Decorators</RouterLink>
      </div>

      <!-- Actions -->
      <div class="navbar-actions">
        <template v-if="!authStore.isAuthenticated">
          <RouterLink to="/login" class="btn btn-secondary btn-sm">Login</RouterLink>
          <RouterLink to="/register" class="btn btn-primary btn-sm">Get Started</RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/bookings" class="nav-link">
            <span class="nav-icon">📅</span>
            My Bookings
          </RouterLink>
          <div class="user-menu" @click="showUserMenu = !showUserMenu">
            <div class="user-avatar">
              {{ authStore.user?.name?.charAt(0) || 'U' }}
            </div>
            <span class="user-name">{{ authStore.user?.name }}</span>
            <span class="dropdown-arrow">▼</span>
            
            <!-- Dropdown -->
            <div v-if="showUserMenu" class="user-dropdown">
              <RouterLink to="/dashboard" class="dropdown-item" @click="showUserMenu = false">
                <span>📊</span> Dashboard
              </RouterLink>
              <RouterLink to="/bookings" class="dropdown-item" @click="showUserMenu = false">
                <span>📅</span> My Bookings
              </RouterLink>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item danger" @click="handleLogout">
                <span>🚪</span> Logout
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- Mobile Menu Button -->
      <button class="mobile-menu-btn" @click="showMobileMenu = !showMobileMenu">
        <span :class="{ 'open': showMobileMenu }"></span>
      </button>
    </div>

    <!-- Mobile Menu -->
    <transition name="slide">
      <div v-if="showMobileMenu" class="mobile-menu">
        <RouterLink to="/vendors" class="mobile-link" @click="showMobileMenu = false">Find Vendors</RouterLink>
        <RouterLink to="/vendors?category=photographer" class="mobile-link" @click="showMobileMenu = false">Photographers</RouterLink>
        <RouterLink to="/vendors?category=decorator" class="mobile-link" @click="showMobileMenu = false">Decorators</RouterLink>
        <div class="mobile-divider"></div>
        <template v-if="!authStore.isAuthenticated">
          <RouterLink to="/login" class="mobile-link" @click="showMobileMenu = false">Login</RouterLink>
          <RouterLink to="/register" class="btn btn-primary" @click="showMobileMenu = false">Get Started</RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/dashboard" class="mobile-link" @click="showMobileMenu = false">Dashboard</RouterLink>
          <RouterLink to="/bookings" class="mobile-link" @click="showMobileMenu = false">My Bookings</RouterLink>
          <button class="mobile-link danger" @click="handleLogout">Logout</button>
        </template>
      </div>
    </transition>
  </nav>
</template>

<script setup>
import { ref, inject } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const showToast = inject('showToast')

const showUserMenu = ref(false)
const showMobileMenu = ref(false)

async function handleLogout() {
  await authStore.logout()
  showUserMenu.value = false
  showMobileMenu.value = false
  showToast('Logged out successfully', 'success')
  router.push('/')
}

// Close dropdown on outside click
if (typeof window !== 'undefined') {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.user-menu')) {
      showUserMenu.value = false
    }
  })
}
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 0 24px;
  height: 80px;
}

.navbar-container {
  max-width: 1280px;
  margin: 0 auto;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 40px;
}

.navbar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  font-weight: 700;
}

.logo-icon {
  font-size: 28px;
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.logo-primary {
  font-size: 18px;
  color: var(--text-primary);
}

.logo-accent {
  font-size: 14px;
  color: var(--primary-600);
  font-weight: 600;
}

.navbar-links {
  display: flex;
  align-items: center;
  gap: 32px;
  flex: 1;
}

.nav-link {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: color var(--transition-fast);
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-link:hover {
  color: var(--primary-600);
}

.nav-link.router-link-active {
  color: var(--primary-600);
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

/* User Menu */
.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  border-radius: var(--radius-full);
  background: var(--bg-secondary);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.user-menu:hover {
  background: var(--bg-tertiary);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.user-name {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--text-primary);
}

.dropdown-arrow {
  font-size: 8px;
  color: var(--text-secondary);
}

.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  border: 1px solid var(--border-color);
  min-width: 200px;
  overflow: hidden;
  animation: fadeInUp 0.2s ease;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: var(--text-primary);
  text-decoration: none;
  font-size: 0.9rem;
  transition: background var(--transition-fast);
  border: none;
  background: none;
  width: 100%;
  cursor: pointer;
}

.dropdown-item:hover {
  background: var(--bg-secondary);
}

.dropdown-item.danger {
  color: var(--danger-600);
}

.dropdown-divider {
  height: 1px;
  background: var(--border-color);
  margin: 4px 0;
}

/* Mobile */
.mobile-menu-btn {
  display: none;
  width: 40px;
  height: 40px;
  background: none;
  border: none;
  cursor: pointer;
  position: relative;
}

.mobile-menu-btn span,
.mobile-menu-btn span::before,
.mobile-menu-btn span::after {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--text-primary);
  border-radius: 2px;
  transition: all 0.3s ease;
}

.mobile-menu-btn span::before,
.mobile-menu-btn span::after {
  content: '';
  position: absolute;
}

.mobile-menu-btn span::before {
  top: 12px;
}

.mobile-menu-btn span::after {
  bottom: 12px;
}

.mobile-menu-btn span.open {
  background: transparent;
}

.mobile-menu-btn span.open::before {
  transform: rotate(45deg);
  top: 19px;
}

.mobile-menu-btn span.open::after {
  transform: rotate(-45deg);
  bottom: 19px;
}

.mobile-menu {
  display: none;
  flex-direction: column;
  gap: 8px;
  padding: 20px;
  background: var(--bg-primary);
  border-top: 1px solid var(--border-color);
}

.mobile-link {
  padding: 12px 16px;
  color: var(--text-primary);
  text-decoration: none;
  font-weight: 500;
  border-radius: var(--radius-md);
  transition: background var(--transition-fast);
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  width: 100%;
}

.mobile-link:hover {
  background: var(--bg-secondary);
}

.mobile-link.danger {
  color: var(--danger-600);
}

.mobile-divider {
  height: 1px;
  background: var(--border-color);
  margin: 8px 0;
}

@media (max-width: 768px) {
  .navbar-links,
  .navbar-actions {
    display: none;
  }

  .mobile-menu-btn {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .mobile-menu {
    display: flex;
  }
}

/* Slide transition */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
