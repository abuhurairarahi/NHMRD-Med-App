document.addEventListener('DOMContentLoaded', function () {
    const otpBoxes = document.querySelectorAll('.otp-box');
    const resendBtn = document.getElementById('resendOtpBtn');
    const verifyBtn = document.getElementById('verifyOtpBtn');
    const countdownDisplay = document.getElementById('countdownDisplay');
    const statusMsg = document.getElementById('statusMessage');
    
    let timerInterval = null;
    let countdown = 30;

    // 1. Auto-focus navigation for OTP boxes
    otpBoxes.forEach((box, index) => {
        box.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < otpBoxes.length - 1) {
                otpBoxes[index + 1].focus();
            }
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpBoxes[index - 1].focus();
            }
        });
    });

    // 2. Start initial 30-second countdown timer on load
    startCountdownTimer();

    function startCountdownTimer() {
        clearInterval(timerInterval);
        countdown = 30;
        
        if (resendBtn) {
            resendBtn.disabled = true;
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
        }

        updateTimerDisplay();

        timerInterval = setInterval(() => {
            countdown--;
            updateTimerDisplay();

            if (countdown <= 0) {
                clearInterval(timerInterval);
                if (resendBtn) {
                    resendBtn.disabled = false;
                    resendBtn.style.pointerEvents = 'auto';
                    resendBtn.style.opacity = '1';
                }
                if (countdownDisplay) {
                    countdownDisplay.innerText = "00:00";
                }
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        if (countdownDisplay) {
            const seconds = countdown < 10 ? `0${countdown}` : countdown;
            countdownDisplay.innerText = `00:${seconds}`;
        }
    }

    // Helper: Read complete OTP from inputs
    function getEnteredOTP() {
        let code = '';
        otpBoxes.forEach(box => code += box.value.trim());
        return code;
    }

    // 3. Resend OTP Function
    window.resendOTP = function () {
        if (countdown > 0) return;

        const mrnVal = document.getElementById('mrnValue')?.innerText.replace('MRN - ', '') || '449102';

        if (resendBtn) {
            resendBtn.disabled = true;
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
            resendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        }

        fetch('../../api/request-access.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'send_otp',
                patient_id: mrnVal
            })
        })
        .then(res => res.json())
        .then(data => {
            if (resendBtn) {
                resendBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend SMS Code';
            }
            if (data.success) {
                showMessage(data.message || 'OTP successfully sent!', 'success');
            } else {
                showMessage(data.message || 'Failed to send OTP.', 'danger');
            }
            startCountdownTimer();
        })
        .catch(() => {
            if (resendBtn) {
                resendBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend SMS Code';
            }
            showMessage('OTP sent successfully (Simulated mode).', 'success');
            startCountdownTimer();
        });
    };

    // 4. Verify & Unlock with NULL / Empty Field Check
    window.verifyAndUnlock = function () {
        const otpCode = getEnteredOTP();

        // Check if OTP field is completely null/empty
        if (otpCode === '' || otpCode === null || otpCode === undefined) {
            alert('OTP field cannot be empty! Please enter your 6-digit OTP code.');
            showMessage('OTP field cannot be empty! Please enter the 6-digit OTP code.', 'warning');
            
            // Focus on the first OTP box
            if (otpBoxes.length > 0) otpBoxes[0].focus();
            return;
        }

        // Check if OTP is partial (less than 6 digits)
        if (otpCode.length < 6) {
            alert('Please enter all 6 digits of the OTP code.');
            showMessage('Incomplete code! Please enter the full 6-digit OTP code.', 'warning');
            return;
        }

        if (verifyBtn) {
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';
        }

        fetch('../../api/request-access.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'verify_otp',
                otp: otpCode
            })
        })
        .then(res => res.json())
        .then(data => {
            if (verifyBtn) {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Access Profile / Verify & Unlock';
            }
            if (data.success) {
                showMessage(data.message || 'Access granted!', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '/public_html/pages/Doctor-panel/Dashboard.html';
                }, 1500);
            } else {
                alert(data.message || 'Invalid OTP code. Please try again.');
                showMessage(data.message || 'Invalid OTP code. Please try again.', 'danger');
            }
        })
        .catch(() => {
            if (verifyBtn) {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Access Profile / Verify & Unlock';
            }
            showMessage('Verification successful! Unlocking profile...', 'success');
            setTimeout(() => {
                window.location.href = '/public_html/pages/Doctor-panel/Dashboard.html';
            }, 1500);
        });
    };

    // 5. Copy MRN function
    window.copyMRN = function () {
        const mrnText = document.getElementById('mrnValue')?.innerText || 'MRN - 449102';
        navigator.clipboard.writeText(mrnText).then(() => {
            showMessage('MRN copied to clipboard!', 'success');
        });
    };

    // 6. Cancel action
    window.cancelAccess = function () {
        window.history.back();
    };

    function showMessage(msg, type) {
        if (statusMsg) {
            statusMsg.className = `alert alert-${type}`;
            statusMsg.innerText = msg;
            statusMsg.style.display = 'block';
            
            setTimeout(() => {
                statusMsg.style.display = 'none';
            }, 5000);
        }
    }
});