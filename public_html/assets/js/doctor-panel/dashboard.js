document.addEventListener('DOMContentLoaded', () => {
    fetchDashboardData();
});

function fetchDashboardData() {
    fetch('/public_html/api/doctor-panel/dashboard.php?doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardUI(data);
            } else {
                console.error('Error fetching dashboard data:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateDashboardUI(data) {
    // 1. Doctor Info
    document.querySelectorAll('.doctor-name').forEach(el => el.textContent = data.doctor.name);
    document.querySelectorAll('.doctor-dept').forEach(el => el.textContent = data.doctor.department);
    document.querySelectorAll('.doctor-avatar img').forEach(el => el.src = data.doctor.avatar);

    // 2. Metrics
    const metricValues = document.querySelectorAll('.metric-value');
    if (metricValues.length >= 4) {
        metricValues[0].textContent = data.metrics.consultations_today;
        metricValues[1].textContent = data.metrics.rx_pending;
        metricValues[2].textContent = data.metrics.critical_alerts;
        metricValues[3].textContent = data.metrics.care_index;
    }

    // 3. Queue List
    const queueContainer = document.querySelector('.card .queue-item')?.parentElement;
    if (queueContainer && data.schedule.length > 0) {
        // Remove static items except header
        const items = queueContainer.querySelectorAll('.queue-item');
        items.forEach(item => item.remove());

        data.schedule.forEach(appt => {
            const timeParts = appt.appointment_time.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = timeParts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;

            const dob = new Date(appt.dob);
            const ageDifMs = Date.now() - dob.getTime();
            const ageDate = new Date(ageDifMs);
            const age = Math.abs(ageDate.getUTCFullYear() - 1970);
            const genderChar = appt.gender ? appt.gender.charAt(0).toUpperCase() : 'U';

            const statusClass = appt.status === 'confirmed' ? 'green' : 'amber';

            const itemHTML = `
                <div class="queue-item highlight-border">
                    <div class="time-col">
                        <span class="time-text">${displayHours}:${minutes}</span>
                        <span class="ampm-text">${ampm}</span>
                    </div>
                    <div class="patient-info-col">
                        <div class="patient-head">
                            <strong class="patient-name">${appt.full_name}</strong>
                            <span class="patient-age-gender">${age}${genderChar}</span>
                        </div>
                        <div class="patient-sub">
                            <span><i class="fa-solid fa-droplet text-red"></i> ${appt.blood_group || 'N/A'}</span>
                            <span class="meta-dot">&bull;</span>
                            <span>${appt.phone || 'No phone'}</span>
                        </div>
                    </div>
                    <div class="status-col">
                        <span class="status-badge ${statusClass}">${appt.status.toUpperCase()}</span>
                    </div>
                    <div class="action-col">
                        <button class="btn-icon" title="Open Chart"><i class="fa-solid fa-folder-open"></i></button>
                    </div>
                </div>
            `;
            queueContainer.insertAdjacentHTML('beforeend', itemHTML);
        });
    }
}
