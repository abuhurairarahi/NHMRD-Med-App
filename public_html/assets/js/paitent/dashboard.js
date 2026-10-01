document.addEventListener('DOMContentLoaded', () => {

  // 1. Dynamic Search Filtering for Appointments & Activities
  const searchInput = document.getElementById('dashboardSearch');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();
      const items = document.querySelectorAll('.appointment-item, .activity-item');

      items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
      });
    });
  }

  // 2. Notification Button Trigger
  const notificationBtn = document.getElementById('notificationBtn');
  if (notificationBtn) {
    notificationBtn.addEventListener('click', () => {
      alert('You have no new notifications.');
    });
  }

  // 3. Print Card Action
  const printCardBtn = document.getElementById('printCardBtn');
  if (printCardBtn) {
    printCardBtn.addEventListener('click', () => {
      window.print();
    });
  }

  // 4. Update Profile Action
  const updateDataBtn = document.getElementById('updateDataBtn');
  if (updateDataBtn) {
    updateDataBtn.addEventListener('click', () => {
      window.location.href = '/public_html/pages/Patient-panel/paitent-info.php';
    });
  }

  // 5. External Lab Sync Trigger
  const syncLabsBtn = document.getElementById('syncLabsBtn');
  if (syncLabsBtn) {
    syncLabsBtn.addEventListener('click', (e) => {
      e.preventDefault();
      alert('Syncing records with DGHS External Labs... Updated.');
    });
  }

  // 6. Action Buttons in Medical Activity Cards
  document.querySelectorAll('.action-download').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      alert(`Downloading record #${id}...`);
    });
  });

  document.querySelectorAll('.action-view').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      alert(`Viewing details for record #${id}...`);
    });
  });

  // 7. Logout Action Confirmation
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/logout.php';
      }
    });
  }

});