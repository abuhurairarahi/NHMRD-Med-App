const API_URL = '../../pages/doctor-panel/doctor-profile.php';

// --- Modal Utility Functions ---
function closeModals() {
  document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.style.display = 'none';
  });
}

// Ensure clicking outside the modal box closes it
window.onclick = function(event) {
  if (event.target.classList.contains('modal-overlay')) {
    closeModals();
  }
}

// --- 1. Edit Profile Feature ---
function openEditProfileModal() {
  document.getElementById('modal-edit-profile').style.display = 'flex';
}

// 1. Save Profile (Title & Badge)
async function saveProfile() {
  const docTitle = document.getElementById('input-doc-title').value.trim();
  const tagBadge = document.getElementById('input-tag-badge').value.trim();

  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update_title_badge',
        doctor_title: docTitle,
        tag_badge: tagBadge
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error);

    if (docTitle) document.querySelector('.doctor-title').innerText = docTitle;
    if (tagBadge) document.querySelector('.badge-row .tag-badge').innerText = tagBadge;
    closeModals();
  } catch (err) {
    alert(err.message);
  }
}

// --- 2. Add Education Feature ---
function openEduModal() {
  document.getElementById('modal-add-edu').style.display = 'flex';
}

// 2. Add Verified Education
async function verifyAndAddEducation() {
  const title = document.getElementById('input-deg-title').value.trim();
  const desc  = document.getElementById('input-deg-desc').value.trim();
  const inst  = document.getElementById('input-inst-name').value.trim();
  const year  = document.getElementById('input-edu-year').value.trim();

  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'add_education',
        degree_title: title,
        degree_desc: desc,
        inst_name: inst,
        edu_year: year
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error);

    // Update DOM on success
    const eduList = document.querySelector('.education-list');
    const lastItem = eduList.querySelector('.edu-item:last-child');
    if (lastItem) lastItem.classList.remove('border-none');

    const newEduItem = document.createElement('div');
    newEduItem.className = 'edu-item border-none';
    newEduItem.innerHTML = `
      <div>
        <strong class="degree-title">${title} <i class="fa-solid fa-circle-check" style="color:var(--emerald-500); margin-left:4px;"></i></strong>
        <p class="degree-desc">${desc}</p>
      </div>
      <div class="edu-inst-col">
        <strong class="inst-name">${inst}</strong>
        <span class="edu-year">${year}</span>
      </div>
    `;
    eduList.appendChild(newEduItem);
    closeModals();
  } catch (err) {
    alert(err.message);
  }
}

// --- 3. Clinic Hours Feature ---
function openClinicModal() {
  // Update active tab styling
  document.querySelectorAll('.profile-tabs .tab-item').forEach(t => t.classList.remove('active'));
  event.currentTarget.classList.add('active');
  
  document.getElementById('modal-clinic-hours').style.display = 'flex';
}

// 3. Save Schedule (Conflict Handled via API & DOM)
async function saveClinicHours() {
  const day  = document.getElementById('input-day').value.trim();
  const dept = document.getElementById('input-dept').value.trim();
  const time = document.getElementById('input-time').value.trim();
  const loc  = document.getElementById('input-loc').value.trim();

  try {
    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_schedule',
        day_text: day,
        dept_text: dept,
        time_range: time,
        loc_text: loc
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error);

    const routineList = document.querySelector('.routine-list');
    const lastRoutine = routineList.querySelector('.routine-item:last-child');
    if (lastRoutine) lastRoutine.classList.remove('border-none');

    const newRoutine = document.createElement('div');
    newRoutine.className = 'routine-item border-none';
    newRoutine.innerHTML = `
      <div>
        <strong class="day-text">${day}</strong>
        <span class="dept-text">${dept}</span>
      </div>
      <div class="time-col">
        <span class="time-range">${time}</span>
        <span class="loc-text">${loc}</span>
      </div>
    `;
    routineList.appendChild(newRoutine);
    closeModals();
  } catch (err) {
    alert(err.message);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const currentPath = window.location.pathname.toLowerCase();
  
  const navLinks = document.querySelectorAll(".nav-menu .nav-item");

  navLinks.forEach(link => {
    link.classList.remove("active");
    
    const href = link.getAttribute("href");
    if (href) {
        // Strip out the relative prefix and match against the end of the current URL path
        const normalizedHref = href.replace('../../', '/').toLowerCase();
        if (currentPath.endsWith(normalizedHref)) {
            link.classList.add("active");
        }
    }
  });
});

window.handleLogout = function () {
  if (confirm('Are you sure you want to log out of the NHMRD Provider Portal?')) {
    fetch('../../api/logout.php')
      .then(res => res.json())
      .then(data => {
        window.location.href = data.redirect || '../../../index.php';
      })
      .catch(() => {
        window.location.href = '../../../index.php';
      });
  }
};
