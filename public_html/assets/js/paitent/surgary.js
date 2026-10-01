/**
 * NHMRD - Patient Surgery Record Module
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('Surgery Record module initialized.');
});

/**
 * Filter Surgery cards based on tab selection
 */
function handleTabFilter(buttonElement) {
    // 1. Update active state on tab buttons
    const tabs = document.querySelectorAll('.filter-tabs .tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));
    buttonElement.classList.add('active');

    // 2. Get the filter key
    const filter = buttonElement.getAttribute('data-filter');
    const cards = document.querySelectorAll('.procedure-card');

    cards.forEach(card => {
        const category = card.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Handle real-time filtering/search in surgery records
 */
function handleGlobalSearch(event) {
    const query = event.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.procedure-card');

    cards.forEach(card => {
        const textContent = card.innerText.toLowerCase();
        if (textContent.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Handle document downloads
 */
function handleDownloadDocument(fileUrl) {
    if (!fileUrl) {
        alert('File path not available.');
        return;
    }
    window.open(fileUrl, '_blank');
}

/**
 * Handle document viewing
 */
function handleViewDocument(fileUrl) {
    if (!fileUrl) {
        alert('Document preview unavailable.');
        return;
    }
    window.open(fileUrl, '_blank');
}

/**
 * Handle Log Export
 */
function handleExportLog() {
    window.print();
}

/**
 * Upload external report placeholder
 */
function handleUploadReport() {
    alert('External document upload portal opening...');
}

/**
 * Handle User Logout
 */
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/public_html/pages/logout.php';
    }
}