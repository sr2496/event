<template>
  <div class="dashboard-page">
    <!-- Role-based Dashboard -->
    <AdminDashboard v-if="userRole === 'admin'" />
    <VendorDashboard v-else-if="userRole === 'vendor'" />
    <ClientDashboard v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import AdminDashboard from '../components/dashboard/AdminDashboard.vue'
import VendorDashboard from '../components/dashboard/VendorDashboard.vue'
import ClientDashboard from '../components/dashboard/ClientDashboard.vue'

const authStore = useAuthStore()
const userRole = computed(() => authStore.user?.role || 'client')
</script>

<style scoped>
.dashboard-page {
  min-height: calc(100vh - 80px);
}
</style>
