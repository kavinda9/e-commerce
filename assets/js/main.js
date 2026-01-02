/**
 * Main JavaScript for Secure E-Commerce
 * Group 9 Project
 */

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    if (alert.classList.contains("alert-dismissible")) {
      setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }, 5000);
    }
  });

  // Form validation enhancement
  const forms = document.querySelectorAll('form[data-validate="true"]');
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll("[data-confirm-delete]");
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Are you sure you want to delete this item?")) {
        e.preventDefault();
      }
    });
  });

  // Add to cart with animation
  const addToCartForms = document.querySelectorAll('form[action*="cart/add"]');
  addToCartForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const button = form.querySelector('button[type="submit"]');
      if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        button.disabled = true;
      }
    });
  });

  // Quantity input validation
  const quantityInputs = document.querySelectorAll(
    'input[type="number"][name="quantity"]'
  );
  quantityInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const min = parseInt(this.getAttribute("min")) || 1;
      const max = parseInt(this.getAttribute("max")) || 999;
      let value = parseInt(this.value);

      if (value < min) {
        this.value = min;
      } else if (value > max) {
        this.value = max;
        alert(`Maximum quantity available is ${max}`);
      }
    });
  });

  // Search functionality enhancement
  const searchInput = document.querySelector('input[name="search"]');
  if (searchInput) {
    searchInput.addEventListener(
      "input",
      debounce(function () {
        // You can add live search functionality here
        console.log("Searching for:", this.value);
      }, 500)
    );
  }

  // Password strength indicator
  const passwordInputs = document.querySelectorAll(
    'input[type="password"][name="password"], input[type="password"][name="new_password"]'
  );
  passwordInputs.forEach((input) => {
    input.addEventListener("input", function () {
      const strength = checkPasswordStrength(this.value);
      // You can add UI feedback here
      console.log("Password strength:", strength);
    });
  });
});

// Utility Functions

// Debounce function for search
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Check password strength
function checkPasswordStrength(password) {
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^a-zA-Z0-9]/.test(password)) strength++;

  if (strength < 3) return "weak";
  if (strength < 5) return "medium";
  return "strong";
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
}

// Show loading spinner
function showLoading(element) {
  const spinner = document.createElement("div");
  spinner.className = "spinner-border spinner-border-sm";
  spinner.setAttribute("role", "status");
  element.appendChild(spinner);
}

// Hide loading spinner
function hideLoading(element) {
  const spinner = element.querySelector(".spinner-border");
  if (spinner) {
    spinner.remove();
  }
}

// Toast notifications (if needed)
function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
  toast.style.zIndex = "9999";
  toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 3000);
}

// Export functions for use in other scripts
if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    formatCurrency,
    checkPasswordStrength,
    showToast,
  };
}
