document.addEventListener('DOMContentLoaded', () => {
    let currentPatientId = null;

    // --- AUTO-LOAD FROM LOCALSTORAGE ---
    const storedData = localStorage.getItem('accessRequestData');
    if (storedData) {
        try {
            const data = JSON.parse(storedData);
            
            // Populate Verification Card
            const nameInput = document.getElementById('patientNameInput');
            if (nameInput) nameInput.value = data.name;

            const mrnValue = document.getElementById('mrnValue');
            if (mrnValue) mrnValue.innerText = data.mrn;

            const dobInputs = document.querySelectorAll('.patient-fields-grid input');
            if (dobInputs.length >= 2) {
                dobInputs[1].value = data.dob;
            }
            
            // For the OTP request
            const mrnHidden = document.querySelector('input[name="patient_mrn"]');
            if (mrnHidden) {
                mrnHidden.value = data.mrn.replace('MRN-', '');
            } else {
                // If not hidden, use the global variable or a new hidden input
                currentPatientId = data.mrn.replace('MRN-', '').trim();
            }

            // Populate Request Summary Card (Right Panel)
            const reqCard = document.querySelector('.side-panel .request-card');
            if (reqCard) {
                const reqImg = reqCard.querySelector('.patient-avatar');
                if (reqImg && data.img) reqImg.src = data.img;

                const reqName = reqCard.querySelector('.patient-name');
                if (reqName) reqName.innerText = data.name;

                const reqDetails = reqCard.querySelector('.patient-details');
                if (reqDetails) reqDetails.innerText = `DOB: ${data.dob}`;

                const reqEncounter = reqCard.querySelector('.encounter-desc');
                if (reqEncounter) reqEncounter.innerText = data.condition;
            }
        } catch(e) { console.error("Error parsing localStorage data", e); }
    }

    const requestBtn = document.querySelector('.btn-request, #send-otp-btn');
    const verifyBtn = document.querySelector('.btn-verify, #verify-otp-btn');
    // We already pulled MRN above, but if there's a specific input:
    const mrnInput = document.querySelector('input[name="patient_mrn"], .mrn-input') || document.getElementById('mrnValue');
    const otpInput = document.querySelector('input[name="otp"], .otp-input');
    
    // Fallback if there are 6 boxes for OTP
    const otpBoxes = document.querySelectorAll('.otp-box');
    function getOtpValue() {
        if (otpInput && otpInput.value) return otpInput.value;
        if (otpBoxes.length === 6) {
            let val = '';
            otpBoxes.forEach(b => val += b.value);
            return val;
        }
        return '';
    }

    if (requestBtn) {
        requestBtn.addEventListener('click', (e) => {
            e.preventDefault();
            let mrn = '';
            if (mrnInput && mrnInput.tagName === 'INPUT') mrn = mrnInput.value;
            else if (mrnInput) mrn = mrnInput.innerText;
            
            if (mrn) mrn = mrn.replace('MRN-', '').trim();
            
            if (!mrn && currentPatientId) mrn = currentPatientId;

            if (!mrn) {
                alert('Please enter Patient MRN or UID');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send_otp');
            formData.append('patient_mrn', mrn);

            fetch('../../api/doctor-panel/request-access.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentPatientId = data.patient_id;
                    alert(data.message + ' (Demo OTP: ' + data.demo_otp + ')');
                    // Show OTP input UI
                    if(document.getElementById('otpSection')) document.getElementById('otpSection').style.display = 'block';
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => console.error(err));
        });
    }

    if (verifyBtn || document.getElementById('verifyOtpBtn')) {
        const btnToUse = document.getElementById('verifyOtpBtn') || verifyBtn;
        btnToUse.addEventListener('click', (e) => {
            e.preventDefault();
            const otp = getOtpValue();
            if (!otp || !currentPatientId) {
                alert('Please enter OTP and request access first.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('patient_id', currentPatientId);
            formData.append('otp', otp);

            fetch('../../api/doctor-panel/request-access.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Access Granted! Redirecting to Patient Medical Profile...');
                    window.location.href = `../../pages/doctor-panel/patient-medical-profile.php?patient_id=${currentPatientId}`;
                } else {
                    alert('Verification Failed: ' + data.error);
                }
            })
            .catch(err => console.error(err));
        });
    }
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
