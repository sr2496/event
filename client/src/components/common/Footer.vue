<template>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-brand">
          <div class="footer-logo">
            <span class="logo-icon">🛡️</span>
            <span class="logo-text">Event Reliability</span>
          </div>
          <p class="footer-tagline">
            Never worry about vendor cancellations again. We guarantee verified backup vendors for every booking.
          </p>
          <div class="social-links">
            <a href="#" class="social-link">📘</a>
            <a href="#" class="social-link">📸</a>
            <a href="#" class="social-link">🐦</a>
            <a href="#" class="social-link">💼</a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section">
          <h4>Quick Links</h4>
          <ul>
            <li><RouterLink to="/vendors">Find Vendors</RouterLink></li>
            <li><RouterLink to="/vendors?category=photographer">Photographers</RouterLink></li>
            <li><RouterLink to="/vendors?category=decorator">Decorators</RouterLink></li>
            <li><RouterLink to="/vendors?category=caterer">Caterers</RouterLink></li>
          </ul>
        </div>

        <!-- For Vendors -->
        <div class="footer-section">
          <h4>For Vendors</h4>
          <ul>
            <li><RouterLink to="/register?role=vendor">Join as Vendor</RouterLink></li>
            <li><a href="#">Vendor Guidelines</a></li>
            <li><a href="#">Reliability Program</a></li>
            <li><a href="#">Emergency Assignments</a></li>
          </ul>
        </div>

        <!-- Support -->
        <div class="footer-section">
          <h4>Support</h4>
          <ul>
            <li><a href="#">Help Center</a></li>
            <li><a href="#">How It Works</a></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">Terms of Service</a></li>
          </ul>
        </div>

        <!-- Newsletter -->
        <div class="footer-section newsletter">
          <h4>Stay Updated</h4>
          <p>Get tips for stress-free event planning.</p>
          <form class="newsletter-form" @submit.prevent>
            <input type="email" placeholder="Enter your email" class="newsletter-input" />
            <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
          </form>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <p>&copy; {{ new Date().getFullYear() }} Event Reliability Platform. All rights reserved.</p>
        <div class="footer-legal">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Use</a>
          <a href="#">Cookie Policy</a>
        </div>
      </div>
    </div>

    <!-- Trust Badges -->
    <div class="trust-section">
      <div class="trust-badges">
        <div class="trust-badge-item">
          <span class="trust-icon">✓</span>
          <span>Verified Vendors</span>
        </div>
        <div class="trust-badge-item">
          <span class="trust-icon">🛡️</span>
          <span>Backup Guaranteed</span>
        </div>
        <div class="trust-badge-item">
          <span class="trust-icon">⚡</span>
          <span>Fast Replacement</span>
        </div>
        <div class="trust-badge-item">
          <span class="trust-icon">💯</span>
          <span>100% Assurance</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.footer {
  background: var(--gray-900);
  color: var(--gray-300);
  margin-top: auto;
}

.footer-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 60px 24px 30px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr repeat(4, 1fr);
  gap: 40px;
  margin-bottom: 40px;
}

/* Brand */
.footer-brand {
  padding-right: 40px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.logo-icon {
  font-size: 32px;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
}

.footer-tagline {
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 20px;
  color: var(--gray-400);
}

.social-links {
  display: flex;
  gap: 12px;
}

.social-link {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--gray-800);
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 18px;
  transition: all var(--transition-fast);
}

.social-link:hover {
  background: var(--primary-600);
  transform: translateY(-3px);
}

/* Sections */
.footer-section h4 {
  color: white;
  font-size: 1rem;
  margin-bottom: 20px;
  font-weight: 600;
}

.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section li {
  margin-bottom: 12px;
}

.footer-section a {
  color: var(--gray-400);
  text-decoration: none;
  font-size: 0.9rem;
  transition: color var(--transition-fast);
}

.footer-section a:hover {
  color: white;
}

/* Newsletter */
.newsletter p {
  font-size: 0.9rem;
  margin-bottom: 16px;
  color: var(--gray-400);
}

.newsletter-form {
  display: flex;
  gap: 8px;
}

.newsletter-input {
  flex: 1;
  padding: 10px 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--gray-700);
  background: var(--gray-800);
  color: white;
  font-size: 0.9rem;
}

.newsletter-input::placeholder {
  color: var(--gray-500);
}

.newsletter-input:focus {
  outline: none;
  border-color: var(--primary-500);
}

/* Footer Bottom */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 30px;
  border-top: 1px solid var(--gray-800);
  font-size: 0.85rem;
}

.footer-legal {
  display: flex;
  gap: 24px;
}

.footer-legal a {
  color: var(--gray-500);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.footer-legal a:hover {
  color: white;
}

/* Trust Section */
.trust-section {
  background: var(--gray-950);
  padding: 20px 24px;
}

.trust-badges {
  max-width: 1280px;
  margin: 0 auto;
  display: flex;
  justify-content: center;
  gap: 48px;
}

.trust-badge-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--gray-400);
  font-size: 0.9rem;
}

.trust-icon {
  font-size: 1.25rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .footer-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .footer-brand {
    grid-column: 1 / -1;
    padding-right: 0;
    text-align: center;
  }

  .social-links {
    justify-content: center;
  }

  .newsletter {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .footer-bottom {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .trust-badges {
    flex-wrap: wrap;
    gap: 24px;
  }
}

@media (max-width: 480px) {
  .footer-grid {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .footer-section ul {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px 16px;
  }

  .footer-section li {
    margin-bottom: 0;
  }

  .newsletter-form {
    flex-direction: column;
  }
}
</style>
