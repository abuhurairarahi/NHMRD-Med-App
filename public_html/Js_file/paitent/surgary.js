function handleTabFilter(selectedTab) {
  const tabs = document.querySelectorAll('.filter-tabs .tab-btn');
  tabs.forEach(tab => tab.classList.remove('active'));
  selectedTab.classList.add('active');

  const filterText = selectedTab.textContent.trim().toLowerCase();
  const procedureCards = document.querySelectorAll('.procedure-card');

  procedureCards.forEach(card => {
    const typeBadge = card.querySelector('.type-badge');
    const badgeText = typeBadge ? typeBadge.textContent.trim().toLowerCase() : '';

    if (filterText.includes('all')) {
      card.style.display = 'flex';
    } else if (filterText.includes('inpatient') && badgeText === 'inpatient') {
      card.style.display = 'flex';
    } else if (filterText.includes('outpatient') && badgeText === 'outpatient') {
      card.style.display = 'flex';
    } else if (filterText.includes('pre-op')) {
      card.style.display = 'none';
    } else {
      card.style.display = 'flex';
    }
  });
}

function handleGlobalSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const procedureCards = document.querySelectorAll('.procedure-card');

  procedureCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();
    card.style.display = cardText.includes(query) ? 'flex' : 'none';
  });
}

function handleDownloadDocument(fileName) {
  alert(`Initiating download for: ${fileName}`);
}

function handleViewDocument(fileName) {
  alert(`Opening viewer for: ${fileName}`);
}

function handleExportLog() {
  alert('Exporting surgical records log...');
}

function handleUploadReport() {
  alert('Opening upload modal for external report...');
}

function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}