<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero gradient-mesh">
      <div class="hero-container">
        <div class="hero-content animate-fade-in-up">
          <div class="hero-badge">
            <span class="badge-icon">🛡️</span>
            <span>India's First Event Reliability Platform</span>
          </div>
          
          <h1 class="hero-title">
            Book Vendors with
            <span class="gradient-text">Guaranteed Backup</span>
          </h1>
          
          <p class="hero-subtitle">
            If your vendor cancels or fails, we guarantee a verified replacement. 
            Never stress about last-minute cancellations again.
          </p>

          <!-- Search Box -->
          <div class="hero-search glass">
            <div class="search-field">
              <label>Category</label>
              <select v-model="searchCategory" class="search-select">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">
                  {{ cat.icon }} {{ cat.name }}
                </option>
              </select>
            </div>
            <div class="search-divider"></div>
            <div class="search-field">
              <label>City</label>
              <select v-model="searchCity" class="search-select">
                <option value="">All Cities</option>
                <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
              </select>
            </div>
            <button class="btn btn-primary btn-lg search-btn" @click="searchVendors">
              <span>🔍</span> Find Vendors
            </button>
          </div>

          <!-- Trust Stats -->
          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">500+</span>
              <span class="stat-label">Verified Vendors</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">10,000+</span>
              <span class="stat-label">Events Covered</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">100%</span>
              <span class="stat-label">Backup Success</span>
            </div>
          </div>
        </div>

        <!-- Hero Visual -->
        <div class="hero-visual animate-fade-in-up stagger-2">
          <div class="visual-card glass">
            <div class="card-header">
              <div class="vendor-avatar">📷</div>
              <div>
                <h4>Rajesh Photography</h4>
                <div class="reliability-badge">
                  <span class="badge-dot"></span>
                  Backup Ready
                </div>
              </div>
            </div>
            <div class="reliability-display">
              <div class="reliability-score">4.8</div>
              <div class="reliability-label">Reliability Score</div>
              <div class="reliability-bar">
                <div class="reliability-fill" style="width: 96%"></div>
              </div>
            </div>
            <div class="card-footer">
              <span class="events-count">156 Events Completed</span>
              <span class="verified-badge">✓ Verified</span>
            </div>
          </div>

          <!-- Floating Elements -->
          <div class="floating-badge badge-1 animate-float">
            <span>🎉</span> Wedding
          </div>
          <div class="floating-badge badge-2 animate-float" style="animation-delay: 0.5s">
            <span>✓</span> Backup Assigned
          </div>
          <div class="floating-badge badge-3 animate-float" style="animation-delay: 1s">
            <span>⚡</span> Fast Response
          </div>
        </div>
      </div>

      <!-- Wave Divider -->
      <div class="wave-divider">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
          <path d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,60 1440,60 L1440,120 L0,120 Z" fill="var(--bg-primary)"/>
        </svg>
      </div>
    </section>

    <!-- How It Works -->
    <section class="section how-it-works">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">How Event Reliability Works</h2>
          <p class="section-subtitle">A simple 4-step process to stress-free events</p>
        </div>

        <div class="steps-grid">
          <div class="step-card" v-for="(step, index) in steps" :key="index">
            <div class="step-number">{{ index + 1 }}</div>
            <div class="step-icon">{{ step.icon }}</div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.description }}</p>
          </div>
        </div>

        <!-- The Guarantee -->
        <div class="guarantee-box glass">
          <div class="guarantee-icon">🛡️</div>
          <div class="guarantee-content">
            <h3>Our Iron-Clad Guarantee</h3>
            <p>If your booked vendor fails or cancels, we GUARANTEE a verified backup vendor within hours. Your event goes on, no matter what.</p>
          </div>
          <RouterLink to="/vendors" class="btn btn-primary">Book with Assurance</RouterLink>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="section categories-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Browse by Category</h2>
          <p class="section-subtitle">Find reliable vendors across all event services</p>
        </div>

        <div class="categories-grid">
          <RouterLink 
            v-for="category in categories" 
            :key="category.slug"
            :to="`/vendors?category=${category.slug}`"
            class="category-card"
          >
            <span class="category-icon">{{ category.icon }}</span>
            <h3>{{ category.name }}</h3>
            <span class="category-count">{{ category.vendors_count || 0 }} vendors</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Featured Vendors -->
    <section class="section featured-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Top Reliable Vendors</h2>
          <p class="section-subtitle">Handpicked vendors with exceptional reliability scores</p>
          <RouterLink to="/vendors?sort=reliability" class="view-all-link">
            View All <span>→</span>
          </RouterLink>
        </div>

        <div class="vendors-grid" v-if="!loading">
          <VendorCard 
            v-for="vendor in featuredVendors" 
            :key="vendor.id" 
            :vendor="vendor"
          />
        </div>

        <div v-else class="loading-grid">
          <div class="vendor-skeleton" v-for="i in 4" :key="i">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-line short"></div>
              <div class="skeleton-line"></div>
              <div class="skeleton-line medium"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section why-section gradient-mesh">
      <div class="container">
        <div class="section-header light">
          <h2 class="section-title">Why Event Reliability?</h2>
          <p class="section-subtitle">We're not just another marketplace. We're your event insurance.</p>
        </div>

        <div class="features-grid">
          <div class="feature-card glass" v-for="(feature, index) in features" :key="index">
            <div class="feature-icon">{{ feature.icon }}</div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="section cta-section">
      <div class="container">
        <div class="cta-box">
          <div class="cta-content">
            <h2>Ready for a Stress-Free Event?</h2>
            <p>Join thousands of clients who never worry about vendor cancellations.</p>
          </div>
          <div class="cta-actions">
            <RouterLink to="/vendors" class="btn btn-primary btn-lg">Find Vendors</RouterLink>
            <RouterLink to="/register?role=vendor" class="btn btn-secondary btn-lg">Join as Vendor</RouterLink>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useVendorStore } from '../stores/vendors'
import VendorCard from '../components/vendor/VendorCard.vue'

const router = useRouter()
const vendorStore = useVendorStore()

const searchCategory = ref('')
const searchCity = ref('')
const loading = ref(true)

const categories = ref([])
const cities = ref([])
const featuredVendors = ref([])

const steps = [
  {
    icon: '🔍',
    title: 'Browse Vendors',
    description: 'Search verified vendors by category, city, and reliability score.'
  },
  {
    icon: '📋',
    title: 'Book with Assurance',
    description: 'Select your vendor and pay the assurance fee for backup guarantee.'
  },
  {
    icon: '🛡️',
    title: 'Backup Assigned',
    description: 'We silently assign verified backup vendors for your event date.'
  },
  {
    icon: '🎉',
    title: 'Event Secured',
    description: 'Enjoy your event! If anything goes wrong, backup activates instantly.'
  }
]

const features = [
  {
    icon: '⭐',
    title: 'Reliability Scores',
    description: 'Not reviews - actual performance data. See cancellation rates, response times, and event history.'
  },
  {
    icon: '🔄',
    title: 'Automatic Backup',
    description: 'Every booking gets silent backup vendors assigned. You never need to search if something fails.'
  },
  {
    icon: '⚡',
    title: 'Fast Replacement',
    description: 'One click triggers our emergency system. Backup vendor assigned within hours, not days.'
  },
  {
    icon: '💰',
    title: 'Fair Pricing',
    description: 'Assurance fee is non-refundable but ensures you\'re covered. Worth every rupee for peace of mind.'
  },
  {
    icon: '✓',
    title: 'Verified Vendors',
    description: 'Every vendor is manually verified. Identity, portfolio, and track record checked.'
  },
  {
    icon: '📊',
    title: 'Trust Timeline',
    description: 'See real event history - completed, rescheduled, cancelled. No fake testimonials.'
  }
]

function searchVendors() {
  const query = {}
  if (searchCategory.value) query.category = searchCategory.value
  if (searchCity.value) query.city = searchCity.value
  router.push({ path: '/vendors', query })
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      vendorStore.fetchCategories(),
      vendorStore.fetchCities(),
      vendorStore.fetchFeatured()
    ])
    categories.value = vendorStore.categories
    cities.value = vendorStore.cities
    featuredVendors.value = vendorStore.featuredVendors
  } catch (err) {
    console.error('Failed to load home data', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Hero Section */
.hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 120px 24px 160px;
  position: relative;
  overflow: hidden;
}

.hero-container {
  max-width: 1280px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.hero-content {
  color: white;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-full);
  font-size: 0.9rem;
  margin-bottom: 24px;
  backdrop-filter: blur(10px);
}

.badge-icon {
  font-size: 1.2rem;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 24px;
}

.gradient-text {
  background: linear-gradient(135deg, #38ef7d 0%, #11998e 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 32px;
  line-height: 1.6;
}

/* Hero Search */
.hero-search {
  display: flex;
  align-items: center;
  padding: 8px;
  border-radius: var(--radius-xl);
  margin-bottom: 40px;
}

.search-field {
  flex: 1;
  padding: 12px 20px;
}

.search-field label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-bottom: 4px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.search-select {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 1rem;
  color: var(--text-primary);
  cursor: pointer;
  outline: none;
}

.search-divider {
  width: 1px;
  height: 50px;
  background: var(--border-color);
}

.search-btn {
  margin: 8px;
  white-space: nowrap;
}

/* Hero Stats */
.hero-stats {
  display: flex;
  gap: 48px;
}

.stat-item {
  display: flex;
  flex-direction: column;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
}

.stat-label {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

/* Hero Visual */
.hero-visual {
  position: relative;
  display: flex;
  justify-content: center;
}

.visual-card {
  background: var(--bg-primary);
  border-radius: var(--radius-2xl);
  padding: 24px;
  width: 300px;
  box-shadow: var(--shadow-xl);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.vendor-avatar {
  width: 50px;
  height: 50px;
  border-radius: var(--radius-lg);
  background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.card-header h4 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.reliability-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--success-600);
}

.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success-500);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.reliability-display {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 16px;
  text-align: center;
  margin-bottom: 16px;
}

.reliability-score {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--success-600);
}

.reliability-label {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 12px;
}

.reliability-bar {
  height: 8px;
  background: var(--gray-200);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.reliability-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--success-500), var(--success-400));
  border-radius: var(--radius-full);
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
}

.events-count {
  color: var(--text-secondary);
}

.verified-badge {
  color: var(--primary-600);
  font-weight: 600;
}

/* Floating Badges */
.floating-badge {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--bg-primary);
  border-radius: var(--radius-full);
  box-shadow: var(--shadow-lg);
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary);
}

.badge-1 { top: 0; right: 0; }
.badge-2 { bottom: 20%; left: -20px; }
.badge-3 { bottom: -10px; right: 20px; }

/* Wave Divider */
.wave-divider {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 120px;
}

.wave-divider svg {
  width: 100%;
  height: 100%;
}

/* Section Styles */
.section-header {
  text-align: center;
  margin-bottom: 48px;
}

.section-header.light {
  color: white;
}

.section-header.light .section-subtitle {
  color: rgba(255, 255, 255, 0.8);
}

.section-title {
  font-size: 2.5rem;
  margin-bottom: 16px;
  color: var(--text-primary);
}

.section-header.light .section-title {
  color: white;
}

.section-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
}

.view-all-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--primary-600);
  text-decoration: none;
  font-weight: 500;
  margin-top: 16px;
  transition: gap var(--transition-fast);
}

.view-all-link:hover {
  gap: 12px;
}

/* Steps */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
  margin-bottom: 48px;
}

.step-card {
  text-align: center;
  padding: 32px 24px;
  position: relative;
}

.step-number {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 32px;
  height: 32px;
  background: var(--primary-600);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
}

.step-icon {
  font-size: 3rem;
  margin: 24px 0 16px;
}

.step-card h3 {
  font-size: 1.1rem;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.step-card p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  line-height: 1.5;
}

/* Guarantee Box */
.guarantee-box {
  display: flex;
  align-items: center;
  gap: 32px;
  padding: 32px 40px;
  border-radius: var(--radius-2xl);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.guarantee-icon {
  font-size: 4rem;
}

.guarantee-content {
  flex: 1;
}

.guarantee-content h3 {
  font-size: 1.25rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.guarantee-content p {
  color: var(--text-secondary);
  line-height: 1.6;
}

/* Categories */
.categories-section {
  background: var(--bg-secondary);
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 24px;
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  text-decoration: none;
  transition: all var(--transition-normal);
  border: 1px solid var(--border-color);
}

.category-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
  border-color: var(--primary-200);
}

.category-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.category-card h3 {
  font-size: 1rem;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.category-count {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

/* Vendors Grid */
.vendors-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

/* Features */
.why-section {
  padding: 80px 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.feature-card {
  padding: 32px;
  border-radius: var(--radius-xl);
  text-align: center;
}

.feature-icon {
  font-size: 2.5rem;
  margin-bottom: 16px;
}

.feature-card h3 {
  font-size: 1.1rem;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.feature-card p {
  font-size: 0.9rem;
  color: var(--text-secondary);
  line-height: 1.6;
}

/* CTA */
.cta-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 48px 64px;
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%);
  border-radius: var(--radius-2xl);
  color: white;
}

.cta-content h2 {
  font-size: 1.75rem;
  margin-bottom: 8px;
}

.cta-content p {
  opacity: 0.9;
}

.cta-actions {
  display: flex;
  gap: 16px;
}

.cta-actions .btn-primary {
  background: white;
  color: var(--primary-700);
  box-shadow: none;
}

.cta-actions .btn-secondary {
  background: transparent;
  color: white;
  border-color: rgba(255, 255, 255, 0.3);
}

/* Skeleton Loading */
.loading-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.vendor-skeleton {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.skeleton-image {
  height: 200px;
  background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-content {
  padding: 20px;
}

.skeleton-line {
  height: 16px;
  background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: var(--radius-sm);
  margin-bottom: 12px;
}

.skeleton-line.short { width: 40%; }
.skeleton-line.medium { width: 70%; }

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Responsive */
@media (max-width: 1024px) {
  .hero-container {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .hero-visual {
    display: none;
  }

  .hero-stats {
    justify-content: center;
  }

  .steps-grid,
  .categories-grid,
  .vendors-grid,
  .loading-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .guarantee-box {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 2.5rem;
  }

  .hero-search {
    flex-direction: column;
    gap: 0;
  }

  .search-divider {
    width: 100%;
    height: 1px;
  }

  .search-btn {
    width: 100%;
    margin: 16px 0 0;
  }

  .hero-stats {
    flex-direction: column;
    gap: 24px;
  }

  .steps-grid,
  .categories-grid {
    grid-template-columns: 1fr;
  }

  .vendors-grid,
  .loading-grid {
    grid-template-columns: 1fr;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .cta-box {
    flex-direction: column;
    text-align: center;
    padding: 32px;
    gap: 24px;
  }

  .cta-actions {
    flex-direction: column;
    width: 100%;
  }
}
</style>
