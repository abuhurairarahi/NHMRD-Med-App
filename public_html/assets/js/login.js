function handleLogin(event) {
  event.preventDefault();

  const userId = document.getElementById('userid').value.trim();
  const password = document.getElementById('password').value.trim();

  if (userId === '20421201' && password === 'admin123') {
    window.location.href = '/public_html/pages/admin-panel/admin-dashboard.html';
  } else if (userId === '20421202' && password === 'executive123') {
    window.location.href = '/public_html/pages/medical-executive-panel/executive-dashboard.html';
  } else if (userId === '20421203' && password === 'doctor123') {
    window.location.href = '/public_html/pages/Doctor-panel/Dashboard.html';
  } else if (userId === '2042122004' && password === 'patient123') {
    window.location.href = '/public_html/pages/patient-panel/dashboard.html';
  } else if (userId === '20421204' && password === 'surgeon123') {
    window.location.href = '/public_html/pages/surgeon-panel/dashboard.html';
  } else {
    alert('Invalid UserID/NID or Password. Please try again.');
  }
}