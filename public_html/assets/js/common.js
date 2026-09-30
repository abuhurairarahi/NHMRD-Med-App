/**
 * NHMRD Common Client-side Runtime & Session Sync
 */
const NHMRD = {
    user: null,

    async init() {
        try {
            // Determine panel role from current URL path
            let panelRole = null;
            const path = window.location.pathname.toLowerCase();
            if (path.includes('patient-panel')) panelRole = 'patient';
            else if (path.includes('doctor-panel')) panelRole = 'doctor';
            else if (path.includes('surgeon-panel')) panelRole = 'surgeon';
            else if (path.includes('medical-executive-panel')) panelRole = 'executive';
            else if (path.includes('admin-panel')) panelRole = 'admin';

            const url = panelRole ? `/public_html/api/auth/session.php?as=${panelRole}` : '/public_html/api/auth/session.php';
            const res = await fetch(url);
            const data = await res.json();

            if (data.logged_in && data.user) {
                NHMRD.user = data.user;
                NHMRD.syncHeader(data.user);
            }
        } catch (e) {
            console.warn('Session sync warning:', e);
        }

        NHMRD.bindLogout();
    },

    syncHeader(user) {
        // Patient Name & Initials
        if (user.full_name) {
            // Header doctor/user names
            document.querySelectorAll('.doctor-name, .user-name, .exec-name').forEach(el => {
                el.textContent = (user.role === 'doctor' || user.role === 'surgeon' ? 'Dr. ' : '') + user.full_name;
            });

            // Patient strip / card names
            const pNameEl = document.querySelector('.patient-details h2, .patient-strip-name');
            if (pNameEl && user.role === 'patient') {
                pNameEl.childNodes[0].nodeValue = user.full_name + ' ';
            }

            // Initials avatar
            const initials = user.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            document.querySelectorAll('.avatar-badge, .avatar span, .user-avatar-initials').forEach(el => {
                el.textContent = initials;
            });
        }

        // Roles & Speciality
        if (user.specialty_name) {
            document.querySelectorAll('.doctor-dept').forEach(el => el.textContent = user.specialty_name);
        }
        if (user.designation) {
            document.querySelectorAll('.user-role').forEach(el => el.textContent = user.designation);
        }

        // Health Card / MRN
        if (user.health_card_no) {
            document.querySelectorAll('.health-card-num, .p-mrn').forEach(el => el.textContent = user.health_card_no);
        }
    },

    bindLogout() {
        document.querySelectorAll('.logout-btn, #logout-btn, #logoutBtn').forEach(btn => {
            btn.onclick = (e) => {
                e.preventDefault();
                NHMRD.logout();
            };
        });
    },

    async logout() {
        if (confirm('Are you sure you want to log out of the NHMRD system?')) {
            try {
                await fetch('/public_html/api/auth/logout.php');
            } catch(e) {}
            window.location.href = '/public_html/pages/login.html';
        }
    },

    formatCurrency(amount) {
        return '৳' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
};

// Global functions for inline onclick handlers in existing HTML
function handleLogout() { NHMRD.logout(); }
function handleUserLogout() { NHMRD.logout(); }

document.addEventListener('DOMContentLoaded', () => {
    NHMRD.init();
});
