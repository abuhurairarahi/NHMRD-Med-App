// 1. Dynamic Search Filter for Test Cards
function filterTestRecords(event) {
  const query = event.target.value.toLowerCase().trim();
  const testCards = document.querySelectorAll('.test-card');

  testCards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

// 2. Category Tab Filter (All, Biochemistry, Hematology, Radiology, Serology)
function filterByCategory(event) {
  const selectedBtn = event.currentTarget;
  const category = selectedBtn.textContent.toLowerCase();

  // Toggle active tab class
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  selectedBtn.classList.add('active');

  const testCards = document.querySelectorAll('.test-card');

  testCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();

    if (category.includes('all')) {
      card.style.display = 'flex';
    } else if (category.includes('biochemistry') && cardText.includes('liver function test')) {
      card.style.display = 'flex';
    } else if (category.includes('hematology') && cardText.includes('complete blood count')) {
      card.style.display = 'flex';
    } else if (category.includes('radiology') && cardText.includes('ultrasound')) {
      card.style.display = 'flex';
    } else if (category.includes('serology') && cardText.includes('hepatitis')) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
}

// 3. Export All Records to PDF
function exportAllRecordsPDF() {
  window.print();
}

// 4. Order New Diagnostic Test Redirect
function orderNewTest() {
  window.location.href = '/public_html/pages/Patient-panel/medical-test-req.html';
}

// 5. View Individual Test PDF Report
function viewTestReport(event) {
  const testTitle = event.currentTarget.closest('.test-card').querySelector('h3').textContent;
  alert(`Opening full diagnostic report for:\n"${testTitle}"`);
}

// 6. Notification Bell Click Action
function handleNotificationClick() {
  alert('You have no new notifications.');
}

// 7. User Logout Handling
function handleUserLogout() {
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = '/login.html';
  }
}

// Event Bindings on DOM Content Loaded
document.addEventListener('DOMContentLoaded', () => {
  
  // 1. Search Bar
  const searchInput = document.querySelector('.search-bar input');
  if (searchInput) {
    searchInput.addEventListener('input', filterTestRecords);
  }

  // 2. Category Tabs
  const tabButtons = document.querySelectorAll('.tab-btn');
  tabButtons.forEach(btn => {
    btn.addEventListener('click', filterByCategory);
  });

  // 3. Banner Buttons
  const exportBtn = document.querySelector('.banner-actions .btn-outline');
  if (exportBtn) {
    exportBtn.addEventListener('click', exportAllRecordsPDF);
  }

  const orderTestBtn = document.querySelector('.banner-actions .btn-primary-blue');
  if (orderTestBtn) {
    orderTestBtn.addEventListener('click', orderNewTest);
  }

  // 4. Individual Report View Buttons
  const viewReportBtns = document.querySelectorAll('.card-header .btn-primary-blue');
  viewReportBtns.forEach(btn => {
    btn.addEventListener('click', viewTestReport);
  });

  // 5. Notification Bell
  const notifBtn = document.querySelector('.header-right .icon-btn');
  if (notifBtn) {
    notifBtn.addEventListener('click', handleNotificationClick);
  }

  // 6. Logout Button
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', handleUserLogout);
  }

});