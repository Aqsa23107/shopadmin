/**
 * ShopAdmin Core Client JavaScript
 * Handles Theme Toggling, Sidebar interactions, Modals, and Toasts
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSidebar();
    initProfileDropdown();
    initModals();
});

/* Theme Management (Dark / Light) */
function initTheme() {
    const savedTheme = localStorage.getItem('shopadmin_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('shopadmin_theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Dispatch event for charts to adapt colors
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        });
    }
}

function updateThemeIcon(theme) {
    const icon = document.querySelector('#themeToggleBtn i');
    if (icon) {
        if (theme === 'light') {
            icon.className = 'fa-solid fa-moon';
        } else {
            icon.className = 'fa-solid fa-sun';
        }
    }
}

/* Sidebar Toggle for Responsive & Mobile */
function initSidebar() {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
}

/* User Profile Dropdown */
function initProfileDropdown() {
    const trigger = document.getElementById('profileDropdownTrigger');
    const menu = document.getElementById('profileDropdownMenu');

    if (trigger && menu) {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            menu.classList.remove('show');
        });
    }
}

/* Reusable Modal Handlers */
function initModals() {
    // Open modal triggers
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    // Close modal triggers
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // Click outside modal content to close
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/* Toast Notifications */
function showToast(message, type = 'info', duration = 3500) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check-circle';
    if (type === 'danger')  iconClass = 'fa-exclamation-circle';
    if (type === 'warning') iconClass = 'fa-triangle-exclamation';

    toast.innerHTML = `
        <i class="fa-solid ${iconClass}"></i>
        <span style="flex:1;">${message}</span>
        <button style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0 4px;" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}
