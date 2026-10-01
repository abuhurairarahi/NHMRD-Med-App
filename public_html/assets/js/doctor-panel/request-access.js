document.addEventListener('DOMContentLoaded', () => {
    let currentPatientId = null;

    const requestBtn = document.querySelector('.btn-request, #send-otp-btn');
    const verifyBtn = document.querySelector('.btn-verify, #verify-otp-btn');
    const mrnInput = document.querySelector('input[name="patient_mrn"], .mrn-input');
    const otpInput = document.querySelector('input[name="otp"], .otp-input');

    if (requestBtn) {
        requestBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const mrn = mrnInput ? mrnInput.value : '';
            if (!mrn) {
                alert('Please enter Patient MRN or UID');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send_otp');
            formData.append('patient_mrn', mrn);

            fetch('/public_html/api/doctor-panel/request-access.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentPatientId = data.patient_id;
                    alert(data.message + ' (Demo OTP: ' + data.demo_otp + ')');
                    // Show OTP input UI
                    if(otpInput) otpInput.style.display = 'block';
                    if(verifyBtn) verifyBtn.style.display = 'inline-block';
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => console.error(err));
        });
    }

    if (verifyBtn) {
        verifyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const otp = otpInput ? otpInput.value : '';
            if (!otp || !currentPatientId) {
                alert('Please enter OTP');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('patient_id', currentPatientId);
            formData.append('otp', otp);

            fetch('/public_html/api/doctor-panel/request-access.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Access Granted! Redirecting to Patient Medical Profile...');
                    window.location.href = `/public_html/pages/doctor-panel/patient-medical-profile.html?patient_id=${currentPatientId}`;
                } else {
                    alert('Verification Failed: ' + data.error);
                }
            })
            .catch(err => console.error(err));
        });
    }
});
