document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('prescriptionSearch');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const rxCards = document.querySelectorAll('.rx-card');
    const modal = document.getElementById('rxModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalRxTitle = document.getElementById('modalRxTitle');
    const modalRxBody = document.getElementById('modalRxBody');
    const printCardBtn = document.getElementById('printCardBtn');
    const logoutBtn = document.getElementById('logoutBtn');

    // 1. Tab Navigation Filter (All, Active, Chronic, Completed)
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            rxCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 2. Real-Time Search Filter
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();

            rxCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 3. View Full Prescription Modal Handler
    document.addEventListener('click', function (e) {
        if (e.target.closest('.view-rx-detail-btn')) {
            const btn = e.target.closest('.view-rx-detail-btn');
            const rxId = btn.getAttribute('data-rx-id');

            const rxData = (window.PRESCRIPTIONS_DATA || []).find(item => item.prescription_id == rxId);

            if (rxData) {
                modalRxTitle.textContent = rxData.title || 'Prescription Details';
                
                let html = `
                    <p><strong>Doctor:</strong> Dr. ${rxData.doctor_name} (${rxData.specialty_name || 'General'})</p>
                    <p><strong>BMDC Reg No:</strong> ${rxData.bmdc_registration_no || 'N/A'}</p>
                    <p><strong>Facility:</strong> ${rxData.hospital_name || 'N/A'}</p>
                    <hr style="border:0; border-top: 1px solid #e2e8f0; margin: 10px 0;">
                    ${rxData.symptoms ? `<p><strong>Symptoms:</strong> ${rxData.symptoms}</p>` : ''}
                    ${rxData.doctors_statement ? `<p><strong>Doctor's Notes:</strong> ${rxData.doctors_statement}</p>` : ''}
                `;

                if (rxData.medications && rxData.medications.length > 0) {
                    html += `
                        <h4 style="margin: 12px 0 6px 0; color: #1e293b;">Prescribed Medications:</h4>
                        <ul style="padding-left: 20px; margin: 0;">
                    `;
                    rxData.medications.forEach(med => {
                        html += `<li><strong>${med.medication_name}</strong> - ${med.dose_strength || ''} (${med.route_frequency || ''}) ${med.sig_instructions ? `<br><small><em>Instructions: ${med.sig_instructions}</em></small>` : ''}</li>`;
                    });
                    html += `</ul>`;
                }

                if (rxData.lab_tests && rxData.lab_tests.length > 0) {
                    html += `
                        <h4 style="margin: 12px 0 6px 0; color: #1e293b;">Advised Lab Tests:</h4>
                        <ul style="padding-left: 20px; margin: 0;">
                    `;
                    rxData.lab_tests.forEach(test => {
                        html += `<li>${test.test_name} (${test.test_code || 'Standard'})</li>`;
                    });
                    html += `</ul>`;
                }

                modalRxBody.innerHTML = html;
                modal.style.display = 'flex';
            }
        }

        // Single Card Printing Handler
        if (e.target.closest('.print-rx-btn')) {
            window.print();
        }
    });

    // Modal Close Events
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // 4. Header Print Action
    if (printCardBtn) {
        printCardBtn.addEventListener('click', function () {
            window.print();
        });
    }

    // 5. Logout Action
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            if (confirm('Are you sure you want to log out of NHMRD?')) {
                window.location.href = '/public_html/pages/login.html';
            }
        });
    }
});