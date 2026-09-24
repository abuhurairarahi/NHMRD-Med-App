// 1. Filter appointments and medical activity lists based on search input
function filterDashboardContent(event) {
  const query = event.target.value.toLowerCase().trim();
  const cards = document.querySelectorAll('.appointment-item, .activity-item');
  
  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

// 2. Display notifications alert
function handleNotificationClick() {
  alert('You have no new notifications.');
}

// 3. Trigger browser print dialog for the patient card
function printPatientCard() {
  window.print();
}

// 4. Redirect user to update profile information
function navigateToUpdateProfile() {
  window.location.href = '/public_html/pages/Patient-panel/paitent-info.html';
}

// 5. Trigger external lab synchronization
function syncExternalLabs(event) {
  event.preventDefault();
  alert('Syncing records with DGHS External Labs... Refreshing panel.');
}

// 6. Confirm and process user logout
function handleUserLogout() {
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = '/login.html';
  }
}

// Initialize and bind events when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  
  // Search input binding
  const searchInput = document.querySelector('.search-bar input');
  if (searchInput) {
    searchInput.addEventListener('input', filterDashboardContent);
  }

  // Notification button binding
  const notificationBtn = document.querySelector('.header-right .icon-btn');
  if (notificationBtn) {
    notificationBtn.addEventListener('click', handleNotificationClick);
  }

  // Print card button binding
  const printCardBtn = document.querySelector('.btn-primary');
  if (printCardBtn) {
    printCardBtn.addEventListener('click', printPatientCard);
  }

  // Update data button binding
  const updateDataBtn = document.querySelector('.btn-secondary');
  if (updateDataBtn) {
    updateDataBtn.addEventListener('click', navigateToUpdateProfile);
  }

  // External labs sync link binding
  const syncLabsBtn = document.querySelector('.panel-footer-sync a');
  if (syncLabsBtn) {
    syncLabsBtn.addEventListener('click', syncExternalLabs);
  }

  // Logout button binding
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', handleUserLogout);
  }

});