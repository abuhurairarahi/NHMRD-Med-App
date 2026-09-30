let timeLeft = 28;
let timerInterval = null;

// Copy MRN to Clipboard
window.copyMRN = function () {
  const mrnText = "MRN-449102";
  navigator.clipboard.writeText(mrnText).then(() => {
    alert(`Copied ${mrnText} to clipboard!`);
  });
};

// Resend OTP via WhatsApp API
window.resendOTP = function () {
  if (timeLeft <= 0) {
    const resendBtn = document.querySelector(".btn-link");
    if (resendBtn) resendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending WhatsApp...';

    fetch('/api/whatsapp-otp.php?action=send_otp')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("A new WhatsApp OTP authorization code has been dispatched to Farhana Rahman (+880 1711-304829).");
          document.querySelectorAll(".otp-box").forEach(input => input.value = "");
          document.querySelectorAll(".otp-box")[0].focus();
          startCountdown();
        } else {
          alert("Failed to send WhatsApp message: " + data.message);
        }
      })
      .catch(err => {
        alert("Server error connecting to WhatsApp service.");
      })
      .finally(() => {
        if (resendBtn) resendBtn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Resend WhatsApp Code';
      });
  } else {
    alert(`Please wait ${timeLeft} seconds before requesting a new WhatsApp code.`);
  }
};

// Verify OTP via PHP Session Check
window.verifyAndUnlock = function () {
  const otpBoxes = document.querySelectorAll(".otp-box");
  let enteredCode = "";
  otpBoxes.forEach(box => enteredCode += box.value);

  if (enteredCode.length < 6) {
    alert("Please enter the complete 6-digit WhatsApp OTP code.");
    return;
  }

  const accessBtn = document.querySelector(".btn-primary");
  accessBtn.disabled = true;
  accessBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Verifying Code...`;

  const formData = new FormData();
  formData.append('otp', enteredCode);

  fetch('/api/whatsapp-otp.php?action=verify_otp', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Authorization Verified! Unlocking 2-Hour Audit-Logged Clinical Record for Farhana Rahman.");
      window.location.href = "/public_html/pages/Doctor-panel/doctor-clinical-service-records.html";
    } else {
      alert("Verification Failed: " + data.message);
      accessBtn.disabled = false;
      accessBtn.innerHTML = `<i class="fa-solid fa-lock-open"></i> Access Profile / Verify & Unlock`;
    }
  })
  .catch(err => {
    alert("Server error verifying OTP.");
    accessBtn.disabled = false;
    accessBtn.innerHTML = `<i class="fa-solid fa-lock-open"></i> Access Profile / Verify & Unlock`;
  });
};

// Cancel Request
window.cancelAccess = function () {
  if (confirm("Are you sure you want to cancel the authorization process?")) {
    window.location.href = "/public_html/pages/Doctor-panel/patient-appointments.html";
  }
};

// Countdown Timer Handler
function startCountdown() {
  clearInterval(timerInterval);
  timeLeft = 28;
  const timerDisplay = document.querySelector(".timer-text strong");
  const resendBtn = document.querySelector(".btn-link");

  if (resendBtn) {
    resendBtn.style.opacity = "0.5";
    resendBtn.style.cursor = "not-allowed";
  }

  timerInterval = setInterval(() => {
    timeLeft--;
    if (timerDisplay) {
      timerDisplay.textContent = `00:${timeLeft < 10 ? "0" : ""}${timeLeft}`;
    }

    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      if (timerDisplay) timerDisplay.textContent = "00:00";
      if (resendBtn) {
        resendBtn.style.opacity = "1";
        resendBtn.style.cursor = "pointer";
      }
    }
  }, 1000);
}

document.addEventListener("DOMContentLoaded", () => {
  startCountdown();

  // Auto-focus logic for 6-digit OTP fields
  const otpInputs = document.querySelectorAll(".otp-box");
  otpInputs.forEach((input, index) => {
    input.addEventListener("input", () => {
      input.value = input.value.replace(/[^0-9]/g, "");
      if (input.value && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && !input.value && index > 0) {
        otpInputs[index - 1].focus();
      }
    });
  });
});