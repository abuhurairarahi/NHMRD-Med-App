-- =====================================================================
-- NHMRD Seed Data (corrected to match schema column names)
-- =====================================================================
USE `nhmrd`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Divisions
INSERT INTO divisions (division_id, name) VALUES
(1, 'Dhaka'),(2, 'Chittagong'),(3, 'Rajshahi'),(4, 'Khulna'),
(5, 'Barisal'),(6, 'Sylhet'),(7, 'Rangpur'),(8, 'Mymensingh')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 2. Districts
INSERT INTO districts (district_id, division_id, name) VALUES
(1, 1, 'Dhaka'),(2, 1, 'Gazipur'),(3, 1, 'Narayanganj'),
(4, 2, 'Chittagong'),(5, 6, 'Sylhet')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 3. Upazilas
INSERT INTO upazilas (upazila_id, district_id, name) VALUES
(1, 1, 'Dhanmondi'),(2, 1, 'Shahbagh'),(3, 1, 'Mirpur'),
(4, 1, 'Uttara'),(5, 1, 'Gulshan')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 4. Specialties
INSERT INTO specialties (specialty_id, name) VALUES
(1, 'Internal Medicine'),(2, 'Cardiology'),(3, 'General Surgery'),
(4, 'Pediatrics'),(5, 'Orthopedics'),(6, 'Neurology'),
(7, 'Gynecology & Obstetrics')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 5. Clinical Departments
INSERT INTO clinical_departments (department_id, name) VALUES
(1, 'Emergency & Acute Care'),(2, 'Internal Medicine'),(3, 'Cardiology & CCU'),
(4, 'General & Laparoscopic Surgery'),(5, 'Pathology & Diagnostic Laboratory'),
(6, 'Pediatrics & NICU')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 6. Hospitals
INSERT INTO hospitals (hospital_id, division_id, district_id, upazila_id, dghs_code, legal_name, facility_type, ownership_category, classification, total_beds, icu_beds, nicu_picu_beds, license_status, is_24x7_er, er_hotline, official_email, superintendent_name) VALUES
(1, 1, 1, 2, 'DGHS-1001', 'Dhaka Medical College Hospital', 'tertiary_hospital', 'public', 'super_specialized', 2300, 60, 40, 'licensed', 1, '10655', 'director@dmch.gov.bd', 'Brig. Gen. Md. Nazmul Haque'),
(2, 1, 1, 2, 'DGHS-1002', 'Bangabandhu Sheikh Mujib Medical University', 'tertiary_hospital', 'public', 'super_specialized', 1900, 50, 30, 'licensed', 1, '10656', 'info@bsmmu.edu.bd', 'Prof. Dr. Sharfuddin Ahmed'),
(3, 1, 1, 1, 'DGHS-2001', 'Square Hospital Dhaka', 'specialized_hospital', 'private_for_profit', 'general', 400, 30, 20, 'licensed', 1, '10616', 'helpdesk@squarehospital.com', 'Dr. Faisal Ikram')
ON DUPLICATE KEY UPDATE legal_name=VALUES(legal_name);

-- 7. Demo Users
INSERT INTO users (user_id, uid, nid, password_hash, role, status, must_reset_password) VALUES
(1, '20421201', '19852691234560001', '$2y$10$0MLfNDLGj812eUHFBzi0XuWcLSRKz4YbAxZQhQ7rtQRA7mjQstcHa', 'admin', 'active', 0),
(2, '20421202', '19872691234560002', '$2y$10$TEZ14hTMwM.3K7ZrCO8GtuJDZGn9.QkQnd4GmtkrEekOGz/8p2/ES', 'executive', 'active', 0),
(3, '20421203', '19892691234560003', '$2y$10$xtoPCDNXwLGt1JFY9cEC3.gKwC8hJgNGO8JSe6rW2lF1J0MKck4DC', 'doctor', 'active', 0),
(4, '20421204', '19842691234560004', '$2y$10$XLXvgJj7JSyskVx3pRqYq.OaU1aiPTcMGE1JXkTt/6ua4LgqMQ/Z6', 'surgeon', 'active', 0),
(5, '2042122004', '19922691234560005', '$2y$10$nvJXHmperfaWi4Cqpl6xl.oUJZQkdPf8gRKBgKtZOL745ur.f2ij6', 'patient', 'active', 0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role=VALUES(role), status=VALUES(status);

-- 8. Admin
INSERT INTO admins (admin_id, user_id, full_name, designation, department, phone, email) VALUES
(1, 1, 'Dr. Sarah F.', 'National Super Administrator', 'DGHS', '+8801700000001', 'sarah.admin@dghs.gov.bd')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- 9. Medical Executive
INSERT INTO medical_executives (executive_id, user_id, full_name, designation, hospital_id, phone, email) VALUES
(1, 2, 'Rafiq Ahmed', 'Registrar & Licensing In-Charge', 1, '+8801700000002', 'rafiq.exec@dghs.gov.bd')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- 10. Doctors
INSERT INTO doctors (doctor_id, user_id, full_name, doctor_type, bmdc_registration_no, nid, dob, gender, primary_specialty_id, qualifications, hospital_id, designation, years_experience, shift_schedule, duty_status, phone, email, verification_status, status) VALUES
(1, 3, 'Dr. Tanvir Ahmed', 'physician', 'A-49821', '19892691234560003', '1985-06-12', 'male', 1, 'MBBS, FCPS (Internal Medicine), MD', 1, 'Senior Consultant Physician', 12, 'Morning Shift (08:00 - 15:00)', 'on_duty', '+8801711000003', 'tanvir.doc@dmch.gov.bd', 'verified', 'active'),
(2, 4, 'Dr. Mahmudul Hasan', 'surgeon', 'S-31204', '19842691234560004', '1980-02-18', 'male', 3, 'MBBS, MS (General Surgery)', 1, 'Chief of Laparoscopic Surgery', 18, 'Emergency Surgical Duty (07:00 - 15:30)', 'on_duty', '+8801711000004', 'mahmud.surgeon@dmch.gov.bd', 'verified', 'active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), doctor_type=VALUES(doctor_type);

-- 11. Patient
INSERT INTO patients (patient_id, user_id, full_name, nid, crvs_token, dob, gender, blood_group, health_card_no, birth_hospital_id, registering_hospital_id, phone, email, address, height_cm, weight_kg, blood_pressure, bmi, nid_verification_status, smart_health_id_issued, status) VALUES
(1, 5, 'Farhana Islam', '19922691234560005', 'CRVS-BGD-1992-449102', '1988-03-14', 'female', 'A+', 'SHID-88019-449102', 1, 1, '+8801812345678', 'farhana.islam@gmail.com', 'House 42, Road 9A, Dhanmondi R/A, Dhaka-1209', 165.0, 62.0, '120/80', 22.8, 'biometric_verified', 1, 'active')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name);

-- Patient Allergies
INSERT INTO patient_allergies (patient_id, allergy_name, severity) VALUES
(1, 'Sulfonamides', 'severe'),(1, 'Penicillin', 'moderate')
ON DUPLICATE KEY UPDATE severity=VALUES(severity);

-- Patient Chronic Conditions
INSERT INTO patient_chronic_conditions (patient_id, condition_name, diagnosed_date) VALUES
(1, 'Primary Hypertension', '2021-04-10'),(1, 'Mild Bronchial Asthma', '2019-11-20')
ON DUPLICATE KEY UPDATE condition_name=VALUES(condition_name);

-- Patient Contacts
INSERT INTO patient_contacts (patient_id, label, value) VALUES
(1, 'Emergency Contact (Spouse)', 'Tariqul Islam (+8801711223344)'),
(1, 'Home Landline', '+88029661234')
ON DUPLICATE KEY UPDATE value=VALUES(value);

-- 12. Lab Test Catalog (matches schema: test_id, test_name, category, price, test_code)
INSERT INTO lab_test_catalog (test_id, category, test_name, test_code, price) VALUES
(1, 'hematology', 'Complete Blood Count (CBC) with ESR', 'HAEM-CBC-01', 450.00),
(2, 'biochemistry', 'Fasting Blood Sugar (FBS)', 'BIO-FBS-02', 180.00),
(3, 'biochemistry', 'HbA1c (Glycated Hemoglobin)', 'BIO-HBA1C-03', 950.00),
(4, 'biochemistry', 'Lipid Profile Complete', 'BIO-LIPID-04', 1200.00),
(5, 'biochemistry', 'Serum Creatinine & eGFR', 'BIO-CREAT-05', 400.00),
(6, 'biochemistry', 'Troponin-I (High Sensitivity STAT)', 'CARD-TROP-06', 1600.00),
(7, 'radiology', 'Chest X-Ray (Digital P/A View)', 'IMG-CXR-07', 750.00),
(8, 'radiology', '12-Lead Electrocardiogram (ECG)', 'IMG-ECG-08', 350.00),
(9, 'radiology', 'MRI of Brain with Contrast', 'IMG-MRI-09', 8500.00)
ON DUPLICATE KEY UPDATE test_name=VALUES(test_name), price=VALUES(price);

-- 13. Vaccine Catalog (matches schema: vaccine_id, vaccine_name, batch_number, dose_ml, route)
INSERT INTO vaccine_catalog (vaccine_id, vaccine_name, batch_number, dose_ml, route) VALUES
(1, 'COVID-19 Pfizer-BioNTech Comirnaty', 'FL-8902', 0.3, 'IM'),
(2, 'Hepatitis B Recombinant Vaccine', 'HB-2024-01', 1.0, 'IM'),
(3, 'MMR (Measles, Mumps, Rubella)', 'MMR-904', 0.5, 'Subcutaneous'),
(4, 'Influenza Vaccine (Sanofi Quadrivalent)', 'IN24-912', 0.5, 'IM'),
(5, 'Tdap (Boostrix Tetanus, Diphtheria, Pertussis)', 'TD-4401', 0.5, 'IM')
ON DUPLICATE KEY UPDATE vaccine_name=VALUES(vaccine_name);

-- 14. Appointments
INSERT INTO appointments (appointment_id, patient_id, doctor_id, hospital_id, appointment_date, time_slot, status, reason) VALUES
(1, 1, 1, 1, CURDATE() + INTERVAL 2 DAY, '10:30 AM', 'booked', 'Routine Hypertension & Health Checkup Follow-up'),
(2, 1, 2, 1, CURDATE() + INTERVAL 7 DAY, '11:00 AM', 'booked', 'Post-operative Cholecystectomy Evaluation')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 15. Prescriptions
INSERT INTO prescriptions (prescription_id, patient_id, doctor_id, appointment_id, title, chief_complaint, symptoms, current_condition, doctors_statement, visit_type, is_long_term, status, next_visit_date) VALUES
(1, 1, 1, 1, 'Cardiovascular & General Health Maintenance', 'Mild intermittent headaches and fatigue', 'Elevated ambulatory BP, morning headache', 'Stage-1 Hypertension, stable', 'Low-sodium diet, aerobic exercise, adherence to antihypertensive therapy advised.', 'in_person', 1, 'active', CURDATE() + INTERVAL 60 DAY)
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- Prescription Medications
INSERT INTO prescription_medications (id, prescription_id, medication_name, dose_strength, route_frequency, dispense_quantity, refills, sig_instructions) VALUES
(1, 1, 'Tab. Amlodipine Besylate', '5 mg', 'Oral - Once Daily (Morning)', 60, 2, 'Take 1 tablet every morning after breakfast with water.'),
(2, 1, 'Tab. Montelukast Sodium', '10 mg', 'Oral - Once Daily (Night)', 30, 1, 'Take 1 tablet at night for mild asthma prevention.'),
(3, 1, 'Tab. Paracetamol', '500 mg', 'Oral - SOS as needed', 20, 0, 'Take 1 tablet every 6-8 hours for headache if needed.')
ON DUPLICATE KEY UPDATE medication_name=VALUES(medication_name);

-- Prescription Lab Tests
INSERT INTO prescription_lab_tests (id, prescription_id, test_name, test_code, instructions) VALUES
(1, 1, 'Fasting Blood Sugar (FBS)', 'BIO-FBS-02', '10-12 hours overnight fasting mandatory.'),
(2, 1, 'Serum Creatinine & eGFR', 'BIO-CREAT-05', 'Assess renal clearance before medication titration.')
ON DUPLICATE KEY UPDATE test_name=VALUES(test_name);

-- 16. Lab Test Orders & Items & Results
INSERT INTO lab_test_orders (order_id, patient_id, doctor_id, prescription_id, hospital_id, status, collection_fee, total_amount, ordered_at) VALUES
(1, 1, 1, 1, 1, 'completed', 0.00, 1650.00, NOW() - INTERVAL 15 DAY),
(2, 1, 1, 1, 1, 'requested', 200.00, 580.00, NOW() - INTERVAL 1 DAY)
ON DUPLICATE KEY UPDATE status=VALUES(status);

INSERT INTO lab_test_order_items (id, order_id, test_id, price, is_doctor_advised) VALUES
(1, 1, 1, 450.00, 1),(2, 1, 4, 1200.00, 1),(3, 2, 2, 180.00, 1),(4, 2, 5, 400.00, 1)
ON DUPLICATE KEY UPDATE price=VALUES(price);

INSERT INTO lab_test_results (result_id, order_item_id, result_date, remarks) VALUES
(1, 1, NOW() - INTERVAL 14 DAY, 'Hb: 13.2 g/dL, WBC: 7,400 /uL, Platelets: 280,000 /uL. Normal.'),
(2, 2, NOW() - INTERVAL 14 DAY, 'Total Cholesterol: 185 mg/dL, HDL: 52 mg/dL, LDL: 108 mg/dL, Triglycerides: 140 mg/dL.')
ON DUPLICATE KEY UPDATE remarks=VALUES(remarks);

-- 17. Vaccination Records
INSERT INTO vaccination_records (record_id, patient_id, vaccine_id, hospital_id, administered_by_doctor_id, dose_number, administered_date, serology_titer, certificate_no, source) VALUES
(1, 1, 1, 1, 1, 1, '2023-01-15', 'High Positive Antibody Spike', 'VAC-COVID-2023-00918', 'facility_administered'),
(2, 1, 1, 1, 1, 2, '2023-02-15', 'High Positive Antibody Spike', 'VAC-COVID-2023-01422', 'facility_administered'),
(3, 1, 4, 1, 1, 1, '2024-09-10', 'Protective Titer Documented', 'VAC-FLU-2024-99812', 'facility_administered'),
(4, 1, 5, 1, 1, 1, '2022-05-14', 'Valid State Record', 'VAC-TDAP-2022-33101', 'facility_administered')
ON DUPLICATE KEY UPDATE certificate_no=VALUES(certificate_no);

-- Vaccine Appointments (PK is 'id')
INSERT INTO vaccine_appointments (id, patient_id, vaccine_id, hospital_id, scheduled_date, time_slot, health_declaration_confirmed, status) VALUES
(1, 1, 4, 1, CURDATE() + INTERVAL 14 DAY, '09:30 AM', 1, 'scheduled')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- 18. Surgical Records
INSERT INTO surgical_records (surgery_id, patient_id, surgeon_id, anesthesiologist_name, hospital_id, procedure_title, primary_procedure, procedure_type, operation_datetime, status, estimated_blood_loss_ml, intra_op_complication_status) VALUES
(1, 1, 2, 'Prof. Dr. Anwar Hossain, DA', 1, 'Elective 4-Port Laparoscopic Cholecystectomy', 'Laparoscopic Cholecystectomy', 'inpatient', NOW() - INTERVAL 60 DAY, 'completed', 45, 'None noted. Gallbladder dissected smoothly.')
ON DUPLICATE KEY UPDATE procedure_title=VALUES(procedure_title);

-- 19. Notices (columns: notice_id, reference_code, title, description, category, target_audience, created_by_admin_id, status, dispatched_at)
INSERT INTO notices (notice_id, reference_code, title, description, category, target_audience, created_by_admin_id, dispatched_at, status) VALUES
(1, 'DGHS/SURV/2026/089', 'Enhanced Dengue Serotype-4 Surveillance & Surge Bed Allocation', 'All public and private tertiary healthcare facilities in Dhaka Division are mandated to reserve a minimum of 15% dedicated surge capacity beds for febrile inpatient admissions.', 'critical', 'All Licensed Hospitals (Dhaka Division)', 1, NOW() - INTERVAL 2 HOUR, 'active'),
(2, 'DGHS/CRVS/2026/042', 'Universal Smart Health ID & CRVS Digital Synchronisation Mandate', 'All medical executives and credentialed registrars are instructed to link newly registered newborn tokens with the National CRVS gateway.', 'routine', 'All Medical Executives & Registrars', 1, NOW() - INTERVAL 1 DAY, 'active')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- notice_delivery_logs recipient_type: ENUM('hospital','doctor') — use 'doctor' only
INSERT INTO notice_delivery_logs (id, notice_id, recipient_type, recipient_id, delivered, acknowledged) VALUES
(1, 1, 'doctor', 1, 1, 1),(2, 1, 'doctor', 2, 1, 1),(3, 2, 'doctor', 1, 1, 1)
ON DUPLICATE KEY UPDATE delivered=VALUES(delivered);

-- 20. Credential Requests
INSERT INTO credential_requests (request_id, reference_code, request_type, applicant_doctor_id, applicant_hospital_id, status, submitted_at, sla_deadline, verification_confidence) VALUES
(1, 'CREQ-DOC-2026-081', 'license_renewal', 1, NULL, 'pending', NOW() - INTERVAL 6 HOUR, NOW() + INTERVAL 18 HOUR, 96.00),
(2, 'CREQ-HOSP-2026-014', 'facility_expansion', NULL, 3, 'flagged', NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 6 HOUR, 82.00)
ON DUPLICATE KEY UPDATE reference_code=VALUES(reference_code);

-- 21. Profile Access Requests (PK is 'id'; no 'request_id' col)
INSERT INTO profile_access_requests (id, patient_id, patient_full_name, requesting_doctor_id, reason, status, requested_at, decided_at) VALUES
(1, 1, 'Farhana Islam', 1, 'Primary clinical encounter and prescription maintenance', 'approved', NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY),
(2, 1, 'Farhana Islam', 2, 'Pre-operative assessment for laparoscopic surgery', 'approved', NOW() - INTERVAL 65 DAY, NOW() - INTERVAL 65 DAY)
ON DUPLICATE KEY UPDATE reason=VALUES(reason);

SET FOREIGN_KEY_CHECKS = 1;
