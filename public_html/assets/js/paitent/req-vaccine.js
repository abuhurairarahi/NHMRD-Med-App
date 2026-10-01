// public_html/assets/js/patient/req-vaccine.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reqVaccineForm');
    const submitBtn = document.getElementById('submitBtn');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting Request...';

            const formData = new FormData(form);

            try {
                const response = await fetch('/public_html/handlers/patient/reqVaccineHandler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Submission Error:', error);
                alert('An unexpected error occurred. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Vaccine Request';
            }
        });
    }

    // Logout handling
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '/public_html/pages/logout.php';
            }
        });
    }
});