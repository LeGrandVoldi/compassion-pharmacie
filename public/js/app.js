/* =============================================
   COMPASSION PHARMACIE - App Shared JS
   ============================================= */

// Sidebar toggle for mobile
function initSidebar() {
  const toggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('active');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }
}

// Animate stat cards on scroll
function initAnimations() {
  const cards = document.querySelectorAll('.stat-card');
  const rows = document.querySelectorAll('.data-table tbody tr');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('visible');
          entry.target.style.animationDelay = '0s';
        }, i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  cards.forEach((card, i) => {
    card.style.opacity = '0';
    setTimeout(() => observer.observe(card), i * 50);
  });

  rows.forEach((row, i) => {
    row.style.opacity = '0';
    setTimeout(() => {
      row.style.opacity = '1';
      row.style.animation = `fadeInUp 0.3s ease forwards`;
    }, 200 + i * 60);
  });
}

// Tab filtering
function initTabs() {
  const tabBtns = document.querySelectorAll('[data-tab]');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('[data-tab-group]') || btn.closest('.tab-filters') || btn.closest('.tab-bar');
      if (group) {
        group.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
      } else {
        document.querySelectorAll('[data-tab]').forEach(b => {
          if (b.dataset.tab === btn.dataset.tab || b.closest('.tab-filters') === btn.closest('.tab-filters')) {
            b.classList.remove('active');
          }
        });
      }
      btn.classList.add('active');

      const target = btn.dataset.tab;
      if (target !== 'all') {
        document.querySelectorAll('[data-tab-content]').forEach(row => {
          row.style.display = row.dataset.tabContent === target ? '' : 'none';
        });
      } else {
        document.querySelectorAll('[data-tab-content]').forEach(row => {
          row.style.display = '';
        });
      }
    });
  });
}

// Number counter animation
function animateCounters() {
  const counters = document.querySelectorAll('[data-count]');
  counters.forEach(el => {
    const target = parseFloat(el.dataset.count.replace(/[^0-9.]/g, ''));
    const suffix = el.dataset.count.replace(/[0-9.,]/g, '');
    const duration = 1200;
    const start = performance.now();

    function update(time) {
      const elapsed = time - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(target * eased * 100) / 100;
      el.textContent = current.toLocaleString('fr-FR') + suffix;
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  });
}

// Search filter for tables
function initSearch(inputSel, rowSel) {
  const input = document.querySelector(inputSel);
  if (!input) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    document.querySelectorAll(rowSel).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// Logout handler
function initLogout() {
  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      Swal.fire({
        title: "Êtes-vous sûr ?",
        text: "Voulez-vous vraiment vous déconnecter ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Oui, me déconnecter",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {

            Swal.fire({
                title: "Déconnecté !",
                text: "Vous avez été déconnecté avec succès.",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            }).then(() => {

                window.location.href = '/deconnexion';
            });

        }
    });
    
    });

  });
}

// Toast notification
function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast-notification toast-${type}`;
  toast.innerHTML = `
    <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
    <span>${msg}</span>
  `;
  Object.assign(toast.style, {
    position: 'fixed', bottom: '24px', right: '24px', zIndex: '9999',
    background: type === 'success' ? '#22c55e' : '#ef4444',
    color: 'white', padding: '12px 20px', borderRadius: '8px',
    display: 'flex', alignItems: 'center', gap: '10px',
    boxShadow: '0 4px 16px rgba(0,0,0,0.15)', fontSize: '14px',
    animation: 'fadeInUp 0.3s ease'
  });
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'fadeIn 0.3s ease reverse forwards';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Init all on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initAnimations();
  initTabs();
  initLogout();
  setTimeout(animateCounters, 400);
});
