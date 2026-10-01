document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form') || document.querySelector('.prescription-form-container');
    if (form) {
        // Find a submit button or attach to form submit
        const submitBtn = document.querySelector('.btn-sign-rx, .btn-submit, button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                submitPrescription();
            });
        }
    }
});

function submitPrescription() {
    // Collect data (Using dummy selectors, adjust based on actual HTML)
    const title = document.querySelector('input[name="title"]')?.value || 'General Prescription';
    const chiefComplaint = document.querySelector('textarea[name="chief_complaint"]')?.value || 'Routine Checkup';
    
    // Mocking medication list from the form
    const medications = [
        {
            name: document.querySelector('input[name="med_name"]')?.value || 'Paracetamol',
            strength: '500mg',
            route: 'Oral (PO)',
            quantity: '10',
            refills: 0,
            instructions: 'Take 1 tablet every 8 hours as needed for pain/fever.'
        }
    ];

    const formData = new FormData();
    formData.append('doctor_id', 1);
    formData.append('patient_id', 1);
    formData.append('title', title);
    formData.append('chief_complaint', chiefComplaint);
    formData.append('medications', JSON.stringify(medications));

    fetch('/public_html/api/doctor-panel/create-prescription.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Prescription Signed & Saved Successfully! ID: ' + data.prescription_id);
            window.location.href = '/public_html/pages/doctor-panel/patient-prescription-records.html';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while saving.');
    });
}
