document.addEventListener('DOMContentLoaded', () => {
  // Search Filter Implementation
  const searchInput = document.getElementById('vaccineSearch');
  const recordRows = document.querySelectorAll('.vaccine-record-row');

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();
      recordRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? 'flex' : 'none';
      });
    });
  }

  // Logout Handler
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/logout.php';
      }
    });
  }

  // Certificate PDF Download / View Trigger
  const printCertBtn = document.getElementById('printCertBtn');
  if (printCertBtn) {
    printCertBtn.addEventListener('click', () => {
      window.print();
    });
  }
});