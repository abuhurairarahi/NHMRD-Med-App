document.addEventListener('DOMContentLoaded', () => {
    fetchProfileData();
});

function fetchProfileData() {
    fetch('/public_html/api/doctor-panel/profile.php?doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProfileUI(data.profile);
            } else {
                console.error('Error fetching profile data:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateProfileUI(profile) {
    document.querySelectorAll('.doctor-name, .doctor-title').forEach(el => el.textContent = profile.full_name);
    document.querySelectorAll('.doctor-dept, .doctor-subtitle').forEach(el => el.textContent = profile.specialty || profile.designation);
    
    if (profile.photo_url) {
        document.querySelectorAll('.doctor-avatar img, .profile-img').forEach(el => el.src = profile.photo_url);
    }

    const docMeta = document.querySelector('.doctor-meta');
    if (docMeta) {
        docMeta.innerHTML = `
            <span><i class="fa-solid fa-id-card"></i> BMDC Reg: ${profile.bmdc_registration_no}</span>
            <span class="meta-divider">&bull;</span>
            <span><i class="fa-solid fa-award"></i> NID/License: ${profile.nid}</span>
            <span class="meta-divider">&bull;</span>
            <span><i class="fa-solid fa-hospital"></i> ${profile.hospital_name || 'Unassigned Hospital'}</span>
        `;
    }
}
