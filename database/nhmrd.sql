-- =====================================================================
--  NHMRD  (National Health & Medical Record Directory)
--  Database Schema
--
--  Reverse-engineered from the NHMRD_GitHub front-end prototype
--  (login.html + Admin / Executive / Doctor / Surgeon / Patient panels).
--  The repo shipped with no backend/DB, so this schema was designed to
--  back every screen, form, table and workflow found in the HTML/JS:
--     - Unified login by UserID/NID (public_html/js/login.js)
--     - Admin: doctor/hospital/patient registries, credentialing &
--       licensing request queue, notice/broadcast center, disease
--       surveillance analytics
--     - Medical Executive: patient intake, doctor/hospital registration,
--       death certificates, vaccine allocation requests
--     - Doctor / Surgeon: e-prescriptions, lab test orders, surgical
--       records, vaccine records, profile access requests
--     - Patient: appointments, prescriptions, lab tests, vaccines,
--       surgical history, personal health profile
--
--  Engine   : MySQL 5.7+/8.0  OR  MariaDB 10.3+   (InnoDB, utf8mb4)
--             Tested on MySQL 8.0.46 and MariaDB 10.11.
--             JSON arguments are parsed with JSON_EXTRACT (no JSON_TABLE), so
--             MariaDB versions older than 10.6 are supported.
--  Author   : Generated for NHMRD_GitHub project
--
--  CONTENTS
--     Sections  1-17 : Tables
--     Section  18    : INDEXES       (query-driven composite / FULLTEXT / integrity UNIQUE)
--     Section  19    : VIEWS         (dashboard + registry read models per role panel)
--     Section  20    : TRANSACTIONS  (stored procedures: START TRANSACTION / COMMIT / ROLLBACK)
--     Section  21    : TRIGGERS      (BMI, deceased-patient guard, audit trail)
--
--  Load with the `mysql` client (Sections 20-21 use DELIMITER, a client directive):
--     mysql -u root -p < 01_schema.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `nhmrd`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nhmrd`;

-- =====================================================================
-- SECTION 1: GEOGRAPHIC / REFERENCE (LOOKUP) TABLES
-- =====================================================================

CREATE TABLE divisions (
  division_id   INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE districts (
  district_id   INT AUTO_INCREMENT PRIMARY KEY,
  division_id   INT NOT NULL,
  name          VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_district (division_id, name),
  CONSTRAINT fk_district_division FOREIGN KEY (division_id)
    REFERENCES divisions(division_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE upazilas (
  upazila_id    INT AUTO_INCREMENT PRIMARY KEY,
  district_id   INT NOT NULL,
  name          VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_upazila (district_id, name),
  CONSTRAINT fk_upazila_district FOREIGN KEY (district_id)
    REFERENCES districts(district_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE specialties (
  specialty_id  INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL UNIQUE          -- e.g. Cardiology, General Surgery
) ENGINE=InnoDB;

CREATE TABLE clinical_departments (
  department_id INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL UNIQUE           -- e.g. Obs & Gynae, Nephrology/Dialysis
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 2: IDENTITY & AUTHENTICATION
--   One unified login table backs the single login.html for all 5 roles
--   (login by UserID/NID + Password, per hello.md demo credentials)
-- =====================================================================

CREATE TABLE users (
  user_id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  uid             VARCHAR(30)  NOT NULL UNIQUE,        -- login UserID, e.g. 20421201
  nid             VARCHAR(30)  NULL UNIQUE,             -- National ID (Smart NID), may double as login
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','executive','doctor','surgeon','patient') NOT NULL,
  status          ENUM('active','suspended','pending','deactivated') NOT NULL DEFAULT 'active',
  must_reset_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at   DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 3: HOSPITALS / FACILITIES
--   Backs admin-hospital-records, licence-new-hospital,
--   executive-register-new-hospital
-- =====================================================================

CREATE TABLE hospitals (
  hospital_id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  dghs_code               VARCHAR(30) UNIQUE,                       -- e.g. DGHS-HOSP-0012
  legal_name              VARCHAR(255) NOT NULL,
  facility_type           ENUM('tertiary','general','clinic','specialized','diagnostic') NOT NULL,
  ownership_category      ENUM('public','private','nonprofit') NOT NULL,
  classification          VARCHAR(100),                             -- Govt Tertiary / Private Tier-1 / Upazila Complex ...
  total_beds              INT DEFAULT 0,
  icu_beds                INT DEFAULT 0,
  nicu_picu_beds          INT DEFAULT 0,
  year_established        YEAR NULL,
  division_id             INT NOT NULL,
  district_id             INT NOT NULL,
  upazila_id              INT NULL,
  full_address            VARCHAR(500) NOT NULL,
  er_hotline              VARCHAR(30) NOT NULL,
  director_phone          VARCHAR(30),
  official_email          VARCHAR(150) NOT NULL,
  superintendent_name     VARCHAR(150) NOT NULL,
  superintendent_bmdc_no  VARCHAR(50)  NOT NULL,
  cao_name                VARCHAR(150),
  cao_contact             VARCHAR(30),
  inspection_date         DATE NULL,
  license_issue_date      DATE NULL,
  license_status          ENUM('pending','licensed','suspended','revoked') NOT NULL DEFAULT 'pending',
  is_24x7_er              TINYINT(1) DEFAULT 0,
  photo_url               VARCHAR(500),
  created_by_user_id      BIGINT NULL,
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_hosp_division FOREIGN KEY (division_id) REFERENCES divisions(division_id),
  CONSTRAINT fk_hosp_district FOREIGN KEY (district_id) REFERENCES districts(district_id),
  CONSTRAINT fk_hosp_upazila  FOREIGN KEY (upazila_id)  REFERENCES upazilas(upazila_id),
  CONSTRAINT fk_hosp_creator  FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Checkbox list of clinical departments/wings a hospital offers
CREATE TABLE hospital_departments (
  hospital_id    BIGINT NOT NULL,
  department_id  INT NOT NULL,
  PRIMARY KEY (hospital_id, department_id),
  CONSTRAINT fk_hd_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
  CONSTRAINT fk_hd_department FOREIGN KEY (department_id) REFERENCES clinical_departments(department_id)
) ENGINE=InnoDB;

-- Statutory NOCs / regulatory certifications attached to a hospital application
CREATE TABLE hospital_documents (
  document_id     BIGINT AUTO_INCREMENT PRIMARY KEY,
  hospital_id     BIGINT NOT NULL,
  document_name   VARCHAR(255) NOT NULL,
  file_url        VARCHAR(500) NOT NULL,
  file_size_bytes BIGINT,
  sha256_hash     VARCHAR(64),
  verified        TINYINT(1) DEFAULT 0,
  uploaded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hdoc_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 4: STAFF PROFILES  (Admin / Executive / Doctor / Surgeon)
-- =====================================================================

CREATE TABLE admins (
  admin_id        BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT NOT NULL UNIQUE,
  full_name       VARCHAR(150) NOT NULL,
  designation     VARCHAR(150) DEFAULT 'Super Admin',      -- Dr. Sarah F. / Super Admin
  department      VARCHAR(150),
  photo_url       VARCHAR(500),
  phone           VARCHAR(30),
  email           VARCHAR(150),
  CONSTRAINT fk_admin_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE medical_executives (
  executive_id    BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT NOT NULL UNIQUE,
  full_name       VARCHAR(150) NOT NULL,
  designation     VARCHAR(150),                             -- e.g. Registrar, RPIC
  hospital_id     BIGINT NULL,
  phone           VARCHAR(30),
  email           VARCHAR(150),
  photo_url       VARCHAR(500),
  CONSTRAINT fk_exec_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_exec_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
) ENGINE=InnoDB;

-- Doctors AND Surgeons share one clinical-staff table (doctor_type
-- distinguishes the two separate front-end panels/roles).
CREATE TABLE doctors (
  doctor_id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id                   BIGINT NOT NULL UNIQUE,
  full_name                 VARCHAR(150) NOT NULL,
  doctor_type               ENUM('physician','surgeon','intern') NOT NULL DEFAULT 'physician',
  bmdc_registration_no      VARCHAR(50)  UNIQUE,             -- e.g. A-49821
  nid                       VARCHAR(30)  UNIQUE,
  dob                       DATE,
  gender                    ENUM('male','female','other'),
  primary_specialty_id      INT NULL,
  qualifications            VARCHAR(255),                    -- MBBS, FCPS (Cardiology), MD
  graduating_institution    VARCHAR(255),
  affiliated_university     VARCHAR(255),
  final_exam_session        VARCHAR(100),                    -- e.g. November 2024 Session
  mbbs_roll_number          VARCHAR(50),
  student_bmdc_id           VARCHAR(50),
  license_issue_date        DATE,
  license_expiry_date       DATE,
  practicing_scope          VARCHAR(150),                    -- Full Registration / Provisional / Internship Rotational
  authorizing_registrar     VARCHAR(150),
  hospital_id               BIGINT NULL,                     -- current posting
  designation               VARCHAR(150),                    -- Head of Interventional Cardiology, Registrar...
  years_experience          SMALLINT DEFAULT 0,
  shift_schedule            VARCHAR(150),                    -- Morning Shift (08:00-15:00)
  duty_status               ENUM('on_duty','off_duty','leave') DEFAULT 'off_duty',
  leave_return_date         DATE NULL,
  phone                     VARCHAR(30),
  email                     VARCHAR(150),
  photo_url                 VARCHAR(500),
  biometric_verified        TINYINT(1) DEFAULT 0,
  verification_status       ENUM('pending','verified','flagged') DEFAULT 'pending',
  status                    ENUM('active','inactive') DEFAULT 'active',
  created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctor_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_doctor_specialty FOREIGN KEY (primary_specialty_id) REFERENCES specialties(specialty_id),
  CONSTRAINT fk_doctor_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
  KEY idx_doctor_type (doctor_type)
) ENGINE=InnoDB;

-- 52-week rotational internship roster (register-new-doctor.html)
CREATE TABLE internship_rotations (
  rotation_id      BIGINT AUTO_INCREMENT PRIMARY KEY,
  doctor_id        BIGINT NOT NULL,
  hospital_id      BIGINT NOT NULL,
  department_id    INT NOT NULL,
  duration_weeks   TINYINT NOT NULL,
  timeline_phase   VARCHAR(100),                              -- e.g. Post Core Term - Phase 6
  batch_label      VARCHAR(100),                              -- Batch 2025-A (January Induction)
  start_date       DATE,
  hostel_room      VARCHAR(50),
  CONSTRAINT fk_rot_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
  CONSTRAINT fk_rot_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_rot_department FOREIGN KEY (department_id) REFERENCES clinical_departments(department_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 5: PATIENTS  (civilian registry / health profile)
-- =====================================================================

CREATE TABLE patients (
  patient_id                BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id                   BIGINT NULL UNIQUE,               -- NULL until portal account activated
  full_name                 VARCHAR(150) NOT NULL,
  nid                       VARCHAR(30)  UNIQUE,              -- NULL for newborns
  crvs_token                VARCHAR(50)  UNIQUE,              -- CRVS-BGD-2026-90812 (birth-registered, no NID yet)
  dob                       DATE NOT NULL,
  gender                    ENUM('male','female','intersex_other') NOT NULL,
  blood_group               ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
  health_card_no            VARCHAR(50) UNIQUE,               -- Smart Health ID
  birth_hospital_id         BIGINT NULL,
  registering_hospital_id   BIGINT NULL,                      -- facility patient is primarily attached to
  phone                     VARCHAR(30),
  alt_phone                 VARCHAR(30),
  email                     VARCHAR(150),
  address                   VARCHAR(500),
  province                  VARCHAR(150),
  postal_code               VARCHAR(20),
  emergency_contact_name    VARCHAR(150),
  emergency_contact_relation VARCHAR(100),
  emergency_contact_phone   VARCHAR(30),
  height_cm                 DECIMAL(5,1),
  weight_kg                 DECIMAL(5,1),
  blood_pressure            VARCHAR(20),                      -- "120/80"
  bmi                       DECIMAL(4,1),
  vitals_last_synced_at     DATETIME NULL,
  nid_verification_status   ENUM('unverified','biometric_verified','crvs_certified') DEFAULT 'unverified',
  smart_health_id_issued    TINYINT(1) DEFAULT 0,
  status                    ENUM('active','inactive','deceased') DEFAULT 'active',
  registered_by_executive_id BIGINT NULL,
  created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT fk_patient_birth_hosp FOREIGN KEY (birth_hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_patient_reg_hosp FOREIGN KEY (registering_hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_patient_executive FOREIGN KEY (registered_by_executive_id) REFERENCES medical_executives(executive_id),
  KEY idx_patient_blood (blood_group),
  KEY idx_patient_status (status)
) ENGINE=InnoDB;

CREATE TABLE patient_allergies (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id    BIGINT NOT NULL,
  allergy_name  VARCHAR(150) NOT NULL,
  severity      ENUM('mild','moderate','severe') DEFAULT 'moderate',
  CONSTRAINT fk_allergy_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE patient_chronic_conditions (
  id              BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id      BIGINT NOT NULL,
  condition_name  VARCHAR(150) NOT NULL,
  diagnosed_date  DATE NULL,
  CONSTRAINT fk_condition_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Ad-hoc extra contact entries (patient-info.html "Add Contact Entry")
CREATE TABLE patient_contacts (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id   BIGINT NOT NULL,
  label        VARCHAR(100) NOT NULL,      -- e.g. "Secondary Phone"
  value        VARCHAR(255) NOT NULL,
  CONSTRAINT fk_contact_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Organ & Blood Donor registry linked from a patient's profile page
CREATE TABLE patient_blood_donors (
  donor_id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id           BIGINT NOT NULL,     -- profile the donor was added under
  donor_name           VARCHAR(150) NOT NULL,
  blood_group          ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  phone                VARCHAR(30) NOT NULL,
  verification_status  ENUM('pending','verified') DEFAULT 'pending',
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_donor_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 6: APPOINTMENTS
-- =====================================================================

CREATE TABLE appointments (
  appointment_id   BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id       BIGINT NOT NULL,
  doctor_id        BIGINT NOT NULL,
  hospital_id      BIGINT NULL,
  appointment_date DATE NOT NULL,
  time_slot        VARCHAR(50),
  reason           VARCHAR(255),
  status           ENUM('booked','completed','cancelled','rescheduled') DEFAULT 'booked',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Non-NULL only while the slot is actually held. Cancelled/completed rows become NULL,
  -- so the UNIQUE key below blocks double-booking but still allows re-booking a freed slot
  -- (MySQL UNIQUE permits many NULLs).
  slot_lock        VARCHAR(50) GENERATED ALWAYS AS
                   (IF(status IN ('booked','rescheduled'), time_slot, NULL)) VIRTUAL,
  UNIQUE KEY uq_appt_doctor_slot (doctor_id, appointment_date, slot_lock),
  CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_appt_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
  KEY idx_appt_date (appointment_date)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 7: PRESCRIPTIONS (e-Rx)  -- write-prescription.html
-- =====================================================================

CREATE TABLE prescriptions (
  prescription_id     BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id           BIGINT NOT NULL,
  doctor_id            BIGINT NOT NULL,
  appointment_id       BIGINT NULL,
  title                VARCHAR(255) NOT NULL,               -- "Acute Migraine Treatment & Prophylactic Management Protocol"
  chief_complaint      TEXT,
  symptoms             TEXT,                                 -- onset, severity, triggers
  current_condition    TEXT,
  referred_surgeon_id  BIGINT NULL,
  referred_facility    VARCHAR(255),
  doctors_statement    TEXT,
  next_visit_date      DATE NULL,
  visit_type           ENUM('in_person','telehealth') DEFAULT 'in_person',
  status               ENUM('active','completed','ongoing','cancelled') DEFAULT 'active',
  is_long_term         TINYINT(1) DEFAULT 0,                 -- drives "Chronic" tab filter
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rx_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_rx_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_rx_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
  CONSTRAINT fk_rx_referred_surgeon FOREIGN KEY (referred_surgeon_id) REFERENCES doctors(doctor_id),
  KEY idx_rx_status (status)
) ENGINE=InnoDB;

CREATE TABLE prescription_medications (
  id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
  prescription_id    BIGINT NOT NULL,
  medication_name    VARCHAR(255) NOT NULL,                 -- generic / brand
  dose_strength      VARCHAR(100),
  route_frequency    VARCHAR(150),                          -- "Oral (PO) - Daily at Bedtime"
  dispense_quantity  VARCHAR(100),
  refills            TINYINT DEFAULT 0,
  sig_instructions   VARCHAR(500),
  CONSTRAINT fk_rxmed_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE prescription_lab_tests (
  id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
  prescription_id    BIGINT NOT NULL,
  test_name          VARCHAR(255) NOT NULL,
  test_code          VARCHAR(100),                          -- LOINC / CPT
  instructions        VARCHAR(500),
  CONSTRAINT fk_rxlab_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 8: LAB / DIAGNOSTIC TESTS -- medical-test-req.html, lab-test.html
-- =====================================================================

CREATE TABLE lab_test_catalog (
  test_id       INT AUTO_INCREMENT PRIMARY KEY,
  test_name     VARCHAR(255) NOT NULL,
  category      ENUM('biochemistry','hematology','radiology','serology','other') DEFAULT 'other',
  price         DECIMAL(10,2) NOT NULL DEFAULT 0,
  test_code     VARCHAR(100)
) ENGINE=InnoDB;

CREATE TABLE lab_test_orders (
  order_id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id           BIGINT NOT NULL,
  doctor_id            BIGINT NULL,                          -- advising doctor, if any
  prescription_id      BIGINT NULL,
  hospital_id          BIGINT NULL,
  collection_fee       DECIMAL(10,2) DEFAULT 200.00,
  total_amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
  uploaded_prescription_file VARCHAR(500),
  status               ENUM('requested','sample_collected','processing','completed','cancelled') DEFAULT 'requested',
  ordered_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_labord_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_labord_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_labord_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id),
  CONSTRAINT fk_labord_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
) ENGINE=InnoDB;

CREATE TABLE lab_test_order_items (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id     BIGINT NOT NULL,
  test_id      INT NOT NULL,
  price        DECIMAL(10,2) NOT NULL,
  is_doctor_advised TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_labitem_order FOREIGN KEY (order_id) REFERENCES lab_test_orders(order_id) ON DELETE CASCADE,
  CONSTRAINT fk_labitem_test FOREIGN KEY (test_id) REFERENCES lab_test_catalog(test_id)
) ENGINE=InnoDB;

CREATE TABLE lab_test_results (
  result_id       BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_item_id   BIGINT NOT NULL,
  result_file_url VARCHAR(500),
  result_date     DATE,
  remarks         TEXT,
  CONSTRAINT fk_labresult_item FOREIGN KEY (order_item_id) REFERENCES lab_test_order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 9: VACCINES / IMMUNIZATION
-- =====================================================================

CREATE TABLE vaccine_catalog (
  vaccine_id     INT AUTO_INCREMENT PRIMARY KEY,
  vaccine_name   VARCHAR(150) NOT NULL,                     -- e.g. "Dengue Vector Vaccine"
  batch_number   VARCHAR(50),
  dose_ml        DECIMAL(3,2) DEFAULT 0.5,
  route          VARCHAR(50) DEFAULT 'IM'
) ENGINE=InnoDB;

CREATE TABLE vaccination_records (
  record_id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id            BIGINT NOT NULL,
  vaccine_id            INT NOT NULL,
  hospital_id           BIGINT NULL,
  administered_by_doctor_id BIGINT NULL,
  dose_number           TINYINT DEFAULT 1,
  administered_date     DATE NOT NULL,
  serology_titer        VARCHAR(100),                        -- serology / titer result
  certificate_no        VARCHAR(100) UNIQUE,
  source                ENUM('facility_administered','external_certificate') DEFAULT 'facility_administered',
  CONSTRAINT fk_vrec_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_vrec_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccine_catalog(vaccine_id),
  CONSTRAINT fk_vrec_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_vrec_doctor FOREIGN KEY (administered_by_doctor_id) REFERENCES doctors(doctor_id)
) ENGINE=InnoDB;

-- Patient-initiated vaccine appointment request (req-vaccine.html)
CREATE TABLE vaccine_appointments (
  id                    BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id            BIGINT NOT NULL,
  vaccine_id            INT NOT NULL,
  hospital_id           BIGINT NOT NULL,
  scheduled_date        DATE NOT NULL,
  time_slot             VARCHAR(50),
  health_declaration_confirmed TINYINT(1) DEFAULT 0,
  status                ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vappt_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_vappt_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccine_catalog(vaccine_id),
  CONSTRAINT fk_vappt_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
) ENGINE=InnoDB;

-- Facility -> national depot vaccine allocation request (executive-vaccination-request.html)
CREATE TABLE vaccine_allocation_requests (
  request_id              BIGINT AUTO_INCREMENT PRIMARY KEY,
  requesting_hospital_id  BIGINT NOT NULL,
  requesting_executive_id BIGINT NOT NULL,
  clinical_department     VARCHAR(150),
  urgency_classification  ENUM('routine','urgent','emergency') DEFAULT 'routine',
  requested_delivery_date DATE,
  rpic_name               VARCHAR(150),                       -- Receiving Pharmacist in Charge
  target_population_notes VARCHAR(500),
  clinical_justification  TEXT,
  status                  ENUM('pending','approved','declined','fulfilled') DEFAULT 'pending',
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_valloc_hospital FOREIGN KEY (requesting_hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_valloc_executive FOREIGN KEY (requesting_executive_id) REFERENCES medical_executives(executive_id)
) ENGINE=InnoDB;

CREATE TABLE vaccine_allocation_items (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id   BIGINT NOT NULL,
  vaccine_id   INT NOT NULL,
  quantity     INT NOT NULL,
  CONSTRAINT fk_vallocitem_request FOREIGN KEY (request_id) REFERENCES vaccine_allocation_requests(request_id) ON DELETE CASCADE,
  CONSTRAINT fk_vallocitem_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccine_catalog(vaccine_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 10: SURGICAL RECORDS -- surgical-rec.html
-- =====================================================================

CREATE TABLE surgical_records (
  surgery_id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id           BIGINT NOT NULL,
  surgeon_id           BIGINT NOT NULL,
  anesthesiologist_name VARCHAR(150),
  hospital_id          BIGINT NULL,
  procedure_title      VARCHAR(255) NOT NULL,
  primary_procedure    VARCHAR(255),
  procedure_type       ENUM('inpatient','outpatient','pre_op') DEFAULT 'inpatient',
  operation_datetime   DATETIME NOT NULL,
  medications_used     TEXT,                                 -- medicine type/name/dose/frequency (list, JSON or free text)
  contraindication_notes TEXT,                                -- COMPLICY / risk notes
  estimated_blood_loss_ml INT,
  intra_op_complication_status VARCHAR(255),
  specimen_pathology   TEXT,
  status               ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  report_file_url      VARCHAR(500),
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_surg_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
  CONSTRAINT fk_surg_surgeon FOREIGN KEY (surgeon_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_surg_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 11: PROFILE ACCESS REQUESTS -- resquest-pfp-access.html
--   (doctor/surgeon requesting authorization to view a patient dossier)
-- =====================================================================

CREATE TABLE profile_access_requests (
  id                    BIGINT AUTO_INCREMENT PRIMARY KEY,
  requesting_doctor_id  BIGINT NOT NULL,
  patient_full_name     VARCHAR(150) NOT NULL,   -- as typed on the request form
  patient_dob           DATE,
  patient_id            BIGINT NULL,             -- resolved match, once verified
  reason                VARCHAR(500),
  status                ENUM('pending','approved','denied') DEFAULT 'pending',
  requested_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at            DATETIME NULL,
  decided_by_user_id    BIGINT NULL,
  CONSTRAINT fk_par_doctor FOREIGN KEY (requesting_doctor_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_par_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  CONSTRAINT fk_par_decider FOREIGN KEY (decided_by_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 12: DEATH CERTIFICATES -- executive-issue-death-certificate.html
-- =====================================================================

CREATE TABLE death_certificates (
  certificate_id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  patient_id                BIGINT NOT NULL,
  date_of_pronouncement     DATE NOT NULL,
  time_of_death             TIME NOT NULL,
  place_of_death            ENUM('hospital_inpatient','residential','transit_ambulance','other') DEFAULT 'hospital_inpatient',
  facility_name             VARCHAR(255),
  municipality              VARCHAR(150),
  jurisdiction              VARCHAR(150),
  cause_immediate           VARCHAR(500),
  cause_immediate_icd       VARCHAR(20),
  cause_immediate_onset     VARCHAR(50),
  cause_due_to              VARCHAR(500),
  cause_due_to_icd          VARCHAR(20),
  cause_due_to_onset        VARCHAR(50),
  cause_underlying          VARCHAR(500),
  cause_underlying_icd      VARCHAR(20),
  cause_underlying_onset    VARCHAR(50),
  comorbidities             VARCHAR(500),
  comorbidity_codes         VARCHAR(100),
  manner_of_death           ENUM('natural','accident','suicide','homicide','undetermined') DEFAULT 'natural',
  attending_physician_mcrn  VARCHAR(50),
  coroner_license_id        VARCHAR(50),
  autopsy_performed         TINYINT(1) DEFAULT 0,
  disposal_authorization    VARCHAR(255),
  issued_by_executive_id    BIGINT NOT NULL,
  issued_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_death_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  CONSTRAINT fk_death_executive FOREIGN KEY (issued_by_executive_id) REFERENCES medical_executives(executive_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 13: BIRTH / CRVS REGISTRATION
--   (referenced in admin dashboard "CRVS Birth Registration" queue item
--    and patient rows like "Baby of Rahima Begum")
-- =====================================================================

CREATE TABLE birth_registrations (
  crvs_token       VARCHAR(50) PRIMARY KEY,             -- e.g. CRVS-BGD-2026-90812
  baby_patient_id  BIGINT NULL,                          -- linked once a `patients` row is created for the baby
  mother_patient_id BIGINT NULL,
  baby_name        VARCHAR(150),
  gender           ENUM('male','female','intersex_other'),
  birth_weight_kg  DECIMAL(4,2),
  hospital_id      BIGINT NOT NULL,
  registered_by_executive_id BIGINT NULL,
  registered_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status           ENUM('verified_issued','pending') DEFAULT 'pending',
  CONSTRAINT fk_birth_baby FOREIGN KEY (baby_patient_id) REFERENCES patients(patient_id),
  CONSTRAINT fk_birth_mother FOREIGN KEY (mother_patient_id) REFERENCES patients(patient_id),
  CONSTRAINT fk_birth_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_birth_executive FOREIGN KEY (registered_by_executive_id) REFERENCES medical_executives(executive_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 14: CREDENTIALING / LICENSING / REQUEST APPROVAL CENTER
--   Backs admin-request-management.html (BMDC credentialing, facility
--   expansion, license renewal, emergency broadcast authorization) as
--   well as register-new-doctor.html / licence-new-hospital.html intake
-- =====================================================================

CREATE TABLE credential_requests (
  request_id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  reference_code        VARCHAR(50) UNIQUE NOT NULL,          -- e.g. REQ-9941-DMCH, LIC-2026-EVR-09
  request_type          ENUM('doctor_registration','license_renewal','facility_expansion',
                              'broadcast_authorization','foreign_degree_recognition',
                              'specialty_accreditation_upgrade') NOT NULL,
  applicant_doctor_id   BIGINT NULL,
  applicant_hospital_id BIGINT NULL,
  submitted_by_user_id  BIGINT NULL,
  submitted_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verification_confidence DECIMAL(5,2),                       -- e.g. 98.40 (%)
  sla_deadline          DATETIME NULL,
  status                ENUM('pending','approved','declined','flagged') DEFAULT 'pending',
  decision_notes        TEXT,
  decided_by_admin_id   BIGINT NULL,
  decided_at            DATETIME NULL,
  CONSTRAINT fk_creq_doctor FOREIGN KEY (applicant_doctor_id) REFERENCES doctors(doctor_id),
  CONSTRAINT fk_creq_hospital FOREIGN KEY (applicant_hospital_id) REFERENCES hospitals(hospital_id),
  CONSTRAINT fk_creq_submitter FOREIGN KEY (submitted_by_user_id) REFERENCES users(user_id),
  CONSTRAINT fk_creq_admin FOREIGN KEY (decided_by_admin_id) REFERENCES admins(admin_id)
) ENGINE=InnoDB;

CREATE TABLE credential_request_documents (
  id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id     BIGINT NOT NULL,
  document_name  VARCHAR(255) NOT NULL,          -- e.g. BMDC_Full_Reg_Certificate_signed.pdf
  file_url       VARCHAR(500),
  file_size_bytes BIGINT,
  sha256_hash    VARCHAR(64),
  verified       TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_creqdoc_request FOREIGN KEY (request_id) REFERENCES credential_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 15: NOTICE / BROADCAST CENTER -- admin-notice-broadcast.html
-- =====================================================================

CREATE TABLE notices (
  notice_id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  reference_code      VARCHAR(50) UNIQUE,                   -- DGHS/CIRCULAR/2026/089
  title               VARCHAR(255) NOT NULL,
  description         TEXT,
  category            ENUM('routine','priority','critical') DEFAULT 'routine',
  target_audience     VARCHAR(255),                          -- e.g. "All Licensed Hospitals (13,447)"
  channel_portal_banner TINYINT(1) DEFAULT 1,
  channel_sms_flash     TINYINT(1) DEFAULT 0,
  channel_email         TINYINT(1) DEFAULT 0,
  channel_dghs_webhook   TINYINT(1) DEFAULT 0,
  attachment_url      VARCHAR(500),
  created_by_admin_id BIGINT NOT NULL,
  status              ENUM('draft','scheduled','active','archived') DEFAULT 'draft',
  dispatched_at       DATETIME NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notice_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins(admin_id),
  KEY idx_notice_status (status)
) ENGINE=InnoDB;

-- Per-recipient delivery/acknowledgement tracking (feeds the progress bars)
CREATE TABLE notice_delivery_logs (
  id               BIGINT AUTO_INCREMENT PRIMARY KEY,
  notice_id        BIGINT NOT NULL,
  recipient_type   ENUM('hospital','doctor') NOT NULL,
  recipient_id     BIGINT NOT NULL,                          -- hospital_id or doctor_id depending on recipient_type
  delivered        TINYINT(1) DEFAULT 0,
  acknowledged     TINYINT(1) DEFAULT 0,
  delivered_at     DATETIME NULL,
  acknowledged_at  DATETIME NULL,
  CONSTRAINT fk_ndl_notice FOREIGN KEY (notice_id) REFERENCES notices(notice_id) ON DELETE CASCADE,
  KEY idx_ndl_recipient (recipient_type, recipient_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 16: DISEASE SURVEILLANCE / ANALYTICS -- admin-analytics.html
-- =====================================================================

CREATE TABLE disease_catalog (
  disease_id    INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL UNIQUE           -- Dengue (DENV-2), Measles, Influenza/H1N1, HIV ...
) ENGINE=InnoDB;

CREATE TABLE disease_surveillance_reports (
  report_id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  disease_id          INT NOT NULL,
  report_date         DATE NOT NULL,
  division_id         INT NULL,
  district_id         INT NULL,
  active_cases        INT DEFAULT 0,
  deaths_last_24h     INT DEFAULT 0,
  severity_percentage DECIMAL(5,2),                     -- threat index e.g. 85.00
  r0_value            DECIMAL(4,2),
  mean_incubation_days DECIMAL(4,1),
  genomic_match_pct   DECIMAL(5,2),
  notes               VARCHAR(500),
  submitted_by_hospital_id BIGINT NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dsr_disease FOREIGN KEY (disease_id) REFERENCES disease_catalog(disease_id),
  CONSTRAINT fk_dsr_division FOREIGN KEY (division_id) REFERENCES divisions(division_id),
  CONSTRAINT fk_dsr_district FOREIGN KEY (district_id) REFERENCES districts(district_id),
  CONSTRAINT fk_dsr_hospital FOREIGN KEY (submitted_by_hospital_id) REFERENCES hospitals(hospital_id),
  KEY idx_dsr_date (report_date)
) ENGINE=InnoDB;

-- =====================================================================
-- SECTION 17: AUDIT LOG (cross-cutting; every role's write actions)
-- =====================================================================

CREATE TABLE audit_logs (
  log_id       BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT NULL,
  action       VARCHAR(100) NOT NULL,        -- e.g. 'APPROVE_REQUEST', 'ISSUE_CERTIFICATE'
  entity_type  VARCHAR(100),                 -- e.g. 'credential_requests'
  entity_id    BIGINT,
  details      JSON NULL,
  ip_address   VARCHAR(45),
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id),
  KEY idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SECTION 18: INDEXES
--   InnoDB already indexes PKs, UNIQUE keys and (implicitly) FK columns.
--   The indexes below are the ADDITIONAL ones driven by real screen
--   queries: list filters, sorted timelines, dashboard counters, search
--   boxes, and integrity rules. Column order = equality filters first,
--   then range/sort column last. A composite index also serves queries
--   on its leftmost prefix, so no single-column duplicates are created.
-- =====================================================================

-- ---- users / login ---------------------------------------------------
-- Login is `WHERE uid = ?` (UNIQUE already). Admin user lists filter role+status.
CREATE INDEX idx_users_role_status        ON users (role, status);
CREATE INDEX idx_users_last_login         ON users (last_login_at);

-- ---- hospitals (admin-hospital-records, licence-new-hospital) --------
CREATE INDEX idx_hosp_geo_license         ON hospitals (division_id, district_id, license_status);
CREATE INDEX idx_hosp_license_type        ON hospitals (license_status, facility_type);
CREATE INDEX idx_hosp_ownership           ON hospitals (ownership_category, facility_type);
CREATE FULLTEXT INDEX ftx_hosp_name       ON hospitals (legal_name);
CREATE INDEX idx_hdoc_hospital_verified   ON hospital_documents (hospital_id, verified);

-- ---- doctors / surgeons (admin-doctor-records, appointment picker) ---
CREATE INDEX idx_doctor_hosp_duty         ON doctors (hospital_id, duty_status);
CREATE INDEX idx_doctor_spec_verif        ON doctors (primary_specialty_id, verification_status, status);
CREATE INDEX idx_doctor_type_status       ON doctors (doctor_type, status, verification_status);
CREATE INDEX idx_doctor_license_expiry    ON doctors (license_expiry_date);
CREATE FULLTEXT INDEX ftx_doctor_name     ON doctors (full_name);
CREATE INDEX idx_rot_doctor_start         ON internship_rotations (doctor_id, start_date);
CREATE INDEX idx_rot_hosp_dept            ON internship_rotations (hospital_id, department_id);

-- ---- patients (admin-patient-records, doctor lookup, search bars) ----
CREATE INDEX idx_patient_hosp_status      ON patients (registering_hospital_id, status);
CREATE INDEX idx_patient_dob              ON patients (dob);
CREATE INDEX idx_patient_phone            ON patients (phone);
CREATE FULLTEXT INDEX ftx_patient_name    ON patients (full_name);
CREATE UNIQUE INDEX uq_allergy_patient    ON patient_allergies (patient_id, allergy_name);
CREATE UNIQUE INDEX uq_condition_patient  ON patient_chronic_conditions (patient_id, condition_name);
CREATE INDEX idx_contact_patient          ON patient_contacts (patient_id);
CREATE INDEX idx_donor_group_status       ON patient_blood_donors (blood_group, verification_status);

-- ---- appointments (req-appointment, doctor dashboard) ----------------
-- (uq_appt_doctor_slot is defined inline on the table: no double-booking.)
CREATE INDEX idx_appt_doctor_date_status  ON appointments (doctor_id, appointment_date, status);
CREATE INDEX idx_appt_patient_date        ON appointments (patient_id, appointment_date);
CREATE INDEX idx_appt_hosp_date           ON appointments (hospital_id, appointment_date);

-- ---- prescriptions (prescription-record, "Chronic" tab) --------------
CREATE INDEX idx_rx_patient_created       ON prescriptions (patient_id, created_at);
CREATE INDEX idx_rx_patient_status_lt     ON prescriptions (patient_id, status, is_long_term);
CREATE INDEX idx_rx_doctor_created        ON prescriptions (doctor_id, created_at);
CREATE INDEX idx_rx_next_visit            ON prescriptions (next_visit_date);
CREATE INDEX idx_rxmed_name               ON prescription_medications (medication_name);
CREATE INDEX idx_rxlab_prescription_name  ON prescription_lab_tests (prescription_id, test_name);

-- ---- lab tests (medical-test-req, lab-test) --------------------------
CREATE INDEX idx_labcat_category_name     ON lab_test_catalog (category, test_name);
CREATE UNIQUE INDEX uq_labcat_code        ON lab_test_catalog (test_code);
CREATE FULLTEXT INDEX ftx_labcat_name     ON lab_test_catalog (test_name);
CREATE INDEX idx_labord_patient_status    ON lab_test_orders (patient_id, status, ordered_at);
CREATE INDEX idx_labord_hosp_status       ON lab_test_orders (hospital_id, status);
CREATE UNIQUE INDEX uq_labitem_order_test ON lab_test_order_items (order_id, test_id);   -- a test appears once per order
CREATE INDEX idx_labitem_test             ON lab_test_order_items (test_id);
CREATE INDEX idx_labres_date              ON lab_test_results (result_date);

-- ---- vaccines (vaccine-panel, req-vaccine, executive-vaccination-request)
CREATE INDEX idx_vaccat_name              ON vaccine_catalog (vaccine_name);
CREATE INDEX idx_vrec_patient_date        ON vaccination_records (patient_id, administered_date);
CREATE INDEX idx_vrec_vaccine_hosp_date   ON vaccination_records (vaccine_id, hospital_id, administered_date);
CREATE INDEX idx_vappt_hosp_date_status   ON vaccine_appointments (hospital_id, scheduled_date, status);
CREATE INDEX idx_vappt_patient_status     ON vaccine_appointments (patient_id, status);
CREATE INDEX idx_valloc_status_urgency    ON vaccine_allocation_requests (status, urgency_classification, created_at);
CREATE INDEX idx_valloc_hosp_status       ON vaccine_allocation_requests (requesting_hospital_id, status);
CREATE UNIQUE INDEX uq_vallocitem_req_vac ON vaccine_allocation_items (request_id, vaccine_id);

-- ---- surgical records (surgical-rec, surgary-record) -----------------
CREATE INDEX idx_surg_patient_dt          ON surgical_records (patient_id, operation_datetime);
CREATE INDEX idx_surg_surgeon_dt          ON surgical_records (surgeon_id, operation_datetime);
CREATE INDEX idx_surg_status_dt           ON surgical_records (status, operation_datetime);

-- ---- profile access requests (req-profile-access) --------------------
CREATE INDEX idx_par_doctor_status        ON profile_access_requests (requesting_doctor_id, status);
CREATE INDEX idx_par_patient_status       ON profile_access_requests (patient_id, status);
CREATE INDEX idx_par_status_requested     ON profile_access_requests (status, requested_at);

-- ---- death certificates / births -------------------------------------
CREATE UNIQUE INDEX uq_death_patient      ON death_certificates (patient_id);            -- one certificate per person
CREATE INDEX idx_death_issued             ON death_certificates (issued_at);
CREATE INDEX idx_birth_hosp_registered    ON birth_registrations (hospital_id, registered_at);
CREATE INDEX idx_birth_status             ON birth_registrations (status);

-- ---- credential / licensing queue (admin-request-management) ---------
CREATE INDEX idx_creq_status_type_sla     ON credential_requests (status, request_type, sla_deadline);
CREATE INDEX idx_creq_submitted           ON credential_requests (submitted_at);
CREATE INDEX idx_creqdoc_req_verified     ON credential_request_documents (request_id, verified);

-- ---- notices (admin-notice-broadcast) --------------------------------
CREATE INDEX idx_notice_status_cat        ON notices (status, category, created_at);
CREATE UNIQUE INDEX uq_ndl_notice_recipient ON notice_delivery_logs (notice_id, recipient_type, recipient_id);
CREATE INDEX idx_ndl_notice_flags         ON notice_delivery_logs (notice_id, delivered, acknowledged);

-- ---- disease surveillance (admin-analytics) --------------------------
CREATE INDEX idx_dsr_disease_date         ON disease_surveillance_reports (disease_id, report_date);
CREATE INDEX idx_dsr_division_date        ON disease_surveillance_reports (division_id, report_date);
CREATE INDEX idx_dsr_district_disease_date ON disease_surveillance_reports (district_id, disease_id, report_date);

-- ---- audit log --------------------------------------------------------
CREATE INDEX idx_audit_user_created       ON audit_logs (user_id, created_at);
CREATE INDEX idx_audit_action_created     ON audit_logs (action, created_at);
CREATE INDEX idx_audit_created            ON audit_logs (created_at);


-- =====================================================================
-- SECTION 19: VIEWS
--   Read models used by each panel. They centralise joins so the
--   application never re-implements them, and give a single place to
--   restrict what each DB account may see (GRANT SELECT on views only).
--   Counts use correlated subqueries (not JOIN + GROUP BY) so one-to-many
--   tables can never multiply rows.
-- =====================================================================

-- Admin > Hospital Records
CREATE OR REPLACE VIEW v_hospital_directory AS
SELECT h.hospital_id, h.dghs_code, h.legal_name, h.facility_type, h.ownership_category,
       h.classification, h.total_beds, h.icu_beds, h.nicu_picu_beds,
       dv.name AS division, dt.name AS district, up.name AS upazila,
       h.license_status, h.license_issue_date, h.is_24x7_er, h.er_hotline, h.official_email,
       h.superintendent_name,
       (SELECT COUNT(*) FROM doctors d
         WHERE d.hospital_id = h.hospital_id AND d.status = 'active') AS active_doctors,
       (SELECT GROUP_CONCAT(cd.name ORDER BY cd.name SEPARATOR ', ')
          FROM hospital_departments hd
          JOIN clinical_departments cd ON cd.department_id = hd.department_id
         WHERE hd.hospital_id = h.hospital_id) AS departments
FROM hospitals h
JOIN divisions dv      ON dv.division_id = h.division_id
JOIN districts dt      ON dt.district_id = h.district_id
LEFT JOIN upazilas up  ON up.upazila_id  = h.upazila_id;

-- Admin > Doctor Records ; Patient > Request Appointment (doctor picker)
CREATE OR REPLACE VIEW v_doctor_directory AS
SELECT d.doctor_id, u.uid, u.status AS account_status,
       d.full_name, d.doctor_type, d.bmdc_registration_no,
       sp.name AS specialty, d.qualifications, d.designation, d.years_experience,
       d.hospital_id, h.legal_name AS hospital_name,
       d.duty_status, d.shift_schedule,
       d.verification_status, d.status,
       d.license_expiry_date, d.phone, d.email, d.photo_url
FROM doctors d
JOIN users u            ON u.user_id = d.user_id
LEFT JOIN specialties sp ON sp.specialty_id = d.primary_specialty_id
LEFT JOIN hospitals h    ON h.hospital_id = d.hospital_id;

-- Admin > alerts: licences expired / expiring within 90 days
CREATE OR REPLACE VIEW v_doctor_license_watch AS
SELECT d.doctor_id, d.full_name, d.bmdc_registration_no, d.doctor_type,
       d.hospital_id, h.legal_name AS hospital_name,
       d.license_expiry_date,
       DATEDIFF(d.license_expiry_date, CURDATE()) AS days_remaining,
       CASE WHEN d.license_expiry_date < CURDATE() THEN 'expired' ELSE 'expiring_soon' END AS license_state
FROM doctors d
LEFT JOIN hospitals h ON h.hospital_id = d.hospital_id
WHERE d.status = 'active'
  AND d.license_expiry_date IS NOT NULL
  AND d.license_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY);

-- Admin > Patient Records ; Doctor > Patient Medical Profile ; Patient > Info
CREATE OR REPLACE VIEW v_patient_overview AS
SELECT p.patient_id, u.uid, p.full_name, p.nid, p.crvs_token, p.health_card_no,
       p.dob, TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age_years,
       p.gender, p.blood_group, p.phone, p.email,
       p.height_cm, p.weight_kg, p.bmi, p.blood_pressure,
       p.status, p.nid_verification_status,
       p.registering_hospital_id, h.legal_name AS registering_hospital,
       (SELECT GROUP_CONCAT(a.allergy_name ORDER BY a.allergy_name SEPARATOR ', ')
          FROM patient_allergies a WHERE a.patient_id = p.patient_id)            AS allergies,
       (SELECT GROUP_CONCAT(c.condition_name ORDER BY c.condition_name SEPARATOR ', ')
          FROM patient_chronic_conditions c WHERE c.patient_id = p.patient_id)   AS chronic_conditions,
       (SELECT COUNT(*) FROM prescriptions x       WHERE x.patient_id = p.patient_id) AS prescription_count,
       (SELECT COUNT(*) FROM vaccination_records v WHERE v.patient_id = p.patient_id) AS vaccination_count,
       (SELECT COUNT(*) FROM surgical_records s    WHERE s.patient_id = p.patient_id) AS surgery_count
FROM patients p
LEFT JOIN users u     ON u.user_id = p.user_id
LEFT JOIN hospitals h ON h.hospital_id = p.registering_hospital_id;

-- Patient/Doctor dashboards: upcoming appointment cards
CREATE OR REPLACE VIEW v_upcoming_appointments AS
SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.reason,
       a.patient_id, p.full_name AS patient_name,
       a.doctor_id,  d.full_name AS doctor_name, sp.name AS specialty,
       a.hospital_id, h.legal_name AS hospital_name
FROM appointments a
JOIN patients p          ON p.patient_id = a.patient_id
JOIN doctors  d          ON d.doctor_id  = a.doctor_id
LEFT JOIN specialties sp ON sp.specialty_id = d.primary_specialty_id
LEFT JOIN hospitals h    ON h.hospital_id = a.hospital_id
WHERE a.status IN ('booked','rescheduled')
  AND a.appointment_date >= CURDATE();

-- Patient > Prescription Record ; Doctor > Prescription Record
CREATE OR REPLACE VIEW v_prescription_summary AS
SELECT rx.prescription_id, rx.patient_id, p.full_name AS patient_name,
       rx.doctor_id, d.full_name AS doctor_name,
       rx.title, rx.status, rx.is_long_term, rx.visit_type,
       rx.next_visit_date, rx.created_at,
       sg.full_name AS referred_surgeon, rx.referred_facility,
       (SELECT COUNT(*) FROM prescription_medications m  WHERE m.prescription_id = rx.prescription_id) AS medication_count,
       (SELECT COUNT(*) FROM prescription_lab_tests t    WHERE t.prescription_id = rx.prescription_id) AS lab_test_count
FROM prescriptions rx
JOIN patients p          ON p.patient_id = rx.patient_id
JOIN doctors  d          ON d.doctor_id  = rx.doctor_id
LEFT JOIN doctors sg     ON sg.doctor_id = rx.referred_surgeon_id;

-- Patient > Lab Tests ; Doctor > Test Records
CREATE OR REPLACE VIEW v_lab_order_summary AS
SELECT o.order_id, o.patient_id, p.full_name AS patient_name,
       o.doctor_id, d.full_name AS advising_doctor,
       o.hospital_id, h.legal_name AS hospital_name,
       o.status, o.ordered_at,
       (SELECT COUNT(*) FROM lab_test_order_items i WHERE i.order_id = o.order_id) AS test_count,
       (SELECT COUNT(*) FROM lab_test_order_items i
          JOIN lab_test_results r ON r.order_item_id = i.id
         WHERE i.order_id = o.order_id)                                            AS results_ready,
       (SELECT COALESCE(SUM(i.price),0) FROM lab_test_order_items i WHERE i.order_id = o.order_id) AS tests_subtotal,
       o.collection_fee, o.total_amount
FROM lab_test_orders o
JOIN patients p       ON p.patient_id = o.patient_id
LEFT JOIN doctors d   ON d.doctor_id  = o.doctor_id
LEFT JOIN hospitals h ON h.hospital_id = o.hospital_id;

-- Patient > Vaccine Panel ; Doctor > Vaccine Record
CREATE OR REPLACE VIEW v_vaccination_history AS
SELECT vr.record_id, vr.patient_id, p.full_name AS patient_name,
       vc.vaccine_name, vc.batch_number, vr.dose_number, vr.administered_date,
       vr.serology_titer, vr.certificate_no, vr.source,
       vr.hospital_id, h.legal_name AS hospital_name,
       d.full_name AS administered_by
FROM vaccination_records vr
JOIN patients p          ON p.patient_id = vr.patient_id
JOIN vaccine_catalog vc  ON vc.vaccine_id = vr.vaccine_id
LEFT JOIN hospitals h    ON h.hospital_id = vr.hospital_id
LEFT JOIN doctors d      ON d.doctor_id   = vr.administered_by_doctor_id;

-- Patient > Surgery Record ; Surgeon > Surgical Record
CREATE OR REPLACE VIEW v_surgical_history AS
SELECT s.surgery_id, s.patient_id, p.full_name AS patient_name,
       s.surgeon_id, d.full_name AS surgeon_name, s.anesthesiologist_name,
       s.hospital_id, h.legal_name AS hospital_name,
       s.procedure_title, s.primary_procedure, s.procedure_type,
       s.operation_datetime, s.status,
       s.estimated_blood_loss_ml, s.intra_op_complication_status, s.report_file_url
FROM surgical_records s
JOIN patients p       ON p.patient_id = s.patient_id
JOIN doctors  d       ON d.doctor_id  = s.surgeon_id
LEFT JOIN hospitals h ON h.hospital_id = s.hospital_id;

-- Admin > Request Management: open queue with SLA clock
CREATE OR REPLACE VIEW v_pending_credential_queue AS
SELECT cr.request_id, cr.reference_code, cr.request_type, cr.status,
       cr.submitted_at, cr.sla_deadline, cr.verification_confidence,
       COALESCE(d.full_name, h.legal_name) AS applicant_name,
       cr.applicant_doctor_id, cr.applicant_hospital_id,
       TIMESTAMPDIFF(HOUR, NOW(), cr.sla_deadline)                     AS hours_to_sla,
       (cr.sla_deadline IS NOT NULL AND cr.sla_deadline < NOW())        AS sla_breached,
       (SELECT COUNT(*) FROM credential_request_documents x WHERE x.request_id = cr.request_id)                  AS documents_total,
       (SELECT COUNT(*) FROM credential_request_documents x WHERE x.request_id = cr.request_id AND x.verified=1) AS documents_verified
FROM credential_requests cr
LEFT JOIN doctors d   ON d.doctor_id   = cr.applicant_doctor_id
LEFT JOIN hospitals h ON h.hospital_id = cr.applicant_hospital_id
WHERE cr.status IN ('pending','flagged');

-- Admin > Notice Broadcast: delivery / acknowledgement progress bars
CREATE OR REPLACE VIEW v_notice_delivery_stats AS
SELECT n.notice_id, n.reference_code, n.title, n.category, n.status, n.dispatched_at,
       COUNT(l.id)                                            AS recipients,
       COALESCE(SUM(l.delivered),0)                           AS delivered,
       COALESCE(SUM(l.acknowledged),0)                        AS acknowledged,
       ROUND(100 * COALESCE(SUM(l.delivered),0)    / NULLIF(COUNT(l.id),0), 1) AS delivered_pct,
       ROUND(100 * COALESCE(SUM(l.acknowledged),0) / NULLIF(COUNT(l.id),0), 1) AS acknowledged_pct
FROM notices n
LEFT JOIN notice_delivery_logs l ON l.notice_id = n.notice_id
GROUP BY n.notice_id, n.reference_code, n.title, n.category, n.status, n.dispatched_at;

-- Admin > Analytics: most recent surveillance report per disease per division
CREATE OR REPLACE VIEW v_disease_latest_status AS
SELECT t.disease_id, dc.name AS disease, t.division_id, dv.name AS division,
       t.report_date, t.active_cases, t.deaths_last_24h,
       t.severity_percentage, t.r0_value, t.mean_incubation_days, t.genomic_match_pct
FROM (
  SELECT r.*, ROW_NUMBER() OVER (PARTITION BY r.disease_id, r.division_id
                                 ORDER BY r.report_date DESC, r.report_id DESC) AS rn
  FROM disease_surveillance_reports r
) t
JOIN disease_catalog dc  ON dc.disease_id  = t.disease_id
LEFT JOIN divisions dv   ON dv.division_id = t.division_id
WHERE t.rn = 1;

-- Executive > Vaccination Request list
CREATE OR REPLACE VIEW v_vaccine_allocation_summary AS
SELECT r.request_id, r.requesting_hospital_id, h.legal_name AS hospital_name,
       r.requesting_executive_id, e.full_name AS executive_name,
       r.urgency_classification, r.status, r.requested_delivery_date, r.created_at,
       (SELECT COUNT(*)                FROM vaccine_allocation_items i WHERE i.request_id = r.request_id) AS vaccine_lines,
       (SELECT COALESCE(SUM(quantity),0) FROM vaccine_allocation_items i WHERE i.request_id = r.request_id) AS total_doses
FROM vaccine_allocation_requests r
JOIN hospitals h           ON h.hospital_id  = r.requesting_hospital_id
JOIN medical_executives e  ON e.executive_id = r.requesting_executive_id;

-- Doctor/Surgeon > Patient profiles they are currently authorised to open
CREATE OR REPLACE VIEW v_doctor_patient_access AS
SELECT par.requesting_doctor_id AS doctor_id, par.patient_id,
       p.full_name AS patient_name, par.decided_at AS approved_at, par.reason
FROM profile_access_requests par
JOIN patients p ON p.patient_id = par.patient_id
WHERE par.status = 'approved';

-- Admin Dashboard: one-row KPI strip
CREATE OR REPLACE VIEW v_admin_dashboard_kpis AS
SELECT
  (SELECT COUNT(*) FROM patients  WHERE status = 'active')                                   AS active_patients,
  (SELECT COUNT(*) FROM doctors   WHERE status = 'active' AND verification_status='verified') AS verified_doctors,
  (SELECT COUNT(*) FROM hospitals WHERE license_status = 'licensed')                          AS licensed_hospitals,
  (SELECT COUNT(*) FROM credential_requests WHERE status IN ('pending','flagged'))            AS open_credential_requests,
  (SELECT COUNT(*) FROM credential_requests
     WHERE status IN ('pending','flagged') AND sla_deadline < NOW())                          AS sla_breached_requests,
  (SELECT COUNT(*) FROM profile_access_requests WHERE status = 'pending')                     AS pending_access_requests,
  (SELECT COUNT(*) FROM vaccine_allocation_requests WHERE status = 'pending')                 AS pending_vaccine_requests,
  (SELECT COUNT(*) FROM birth_registrations WHERE status = 'pending')                         AS pending_birth_registrations,
  (SELECT COUNT(*) FROM notices WHERE status = 'active')                                      AS active_notices,
  (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()
     AND status IN ('booked','rescheduled'))                                                  AS appointments_today;

-- =====================================================================
-- SECTION 20: TRANSACTIONS  (stored procedures)
--   Every multi-table workflow in the UI is wrapped in ONE atomic unit:
--   START TRANSACTION ... COMMIT, with an EXIT HANDLER that ROLLBACKs
--   and RESIGNALs the original error to the caller on ANY failure
--   (constraint violation, deadlock, or a business rule raised with
--   SIGNAL SQLSTATE '45000').
--
--   Conventions
--     * Rows that a decision depends on are locked with SELECT ... FOR UPDATE
--       so two admins / two patients cannot race each other.
--     * Isolation level is InnoDB's default REPEATABLE READ.
--     * Callers should retry once on MySQL error 1213 (deadlock) / 1205 (lock wait).
--     * Every procedure writes to audit_logs INSIDE the same transaction, so
--       an action can never exist without its audit row (or vice-versa).
--     * `SET @app_user_id = <users.user_id>` is set by procedures so that the
--       audit triggers in Section 21 can attribute changes to the actor.
--   Enum-valued parameters are VARCHAR (stored-routine params cannot be ENUM);
--   invalid values are rejected by the column ENUM under strict sql_mode.
-- =====================================================================

DELIMITER $$

-- ---------------------------------------------------------------------
-- Executive/Admin > Register New Doctor
--   users + doctors + credential_request (+ audit) : all or nothing.
--   Account starts 'pending' and must reset its password; it becomes
--   'active' only when sp_decide_credential_request approves it.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_register_doctor$$
CREATE PROCEDURE sp_register_doctor(
  IN  p_uid                 VARCHAR(30),
  IN  p_nid                 VARCHAR(30),
  IN  p_password_hash       VARCHAR(255),
  IN  p_full_name           VARCHAR(150),
  IN  p_doctor_type         VARCHAR(10),            -- physician | surgeon | intern
  IN  p_bmdc_no             VARCHAR(50),
  IN  p_specialty_id        INT,
  IN  p_hospital_id         BIGINT,
  IN  p_qualifications      VARCHAR(255),
  IN  p_license_issue       DATE,
  IN  p_license_expiry      DATE,
  IN  p_submitted_by_user   BIGINT,
  OUT p_doctor_id           BIGINT,
  OUT p_request_ref         VARCHAR(50)
)
BEGIN
  DECLARE v_user_id    BIGINT;
  DECLARE v_request_id BIGINT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_license_issue IS NOT NULL AND p_license_expiry IS NOT NULL AND p_license_expiry < p_license_issue THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'License expiry date precedes license issue date';
  END IF;

  START TRANSACTION;

  INSERT INTO users (uid, nid, password_hash, role, status, must_reset_password)
  VALUES (p_uid, p_nid, p_password_hash,
          IF(p_doctor_type = 'surgeon', 'surgeon', 'doctor'), 'pending', 1);
  SET v_user_id = LAST_INSERT_ID();

  INSERT INTO doctors (user_id, full_name, doctor_type, bmdc_registration_no, nid,
                       primary_specialty_id, hospital_id, qualifications,
                       license_issue_date, license_expiry_date,
                       verification_status, status)
  VALUES (v_user_id, p_full_name, p_doctor_type, p_bmdc_no, p_nid,
          p_specialty_id, p_hospital_id, p_qualifications,
          p_license_issue, p_license_expiry, 'pending', 'inactive');
  SET p_doctor_id = LAST_INSERT_ID();

  SET p_request_ref = CONCAT('REQ-', DATE_FORMAT(NOW(), '%Y'), '-',
                             UPPER(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 8)));
  INSERT INTO credential_requests (reference_code, request_type, applicant_doctor_id,
                                   applicant_hospital_id, submitted_by_user_id,
                                   sla_deadline, status)
  VALUES (p_request_ref, 'doctor_registration', p_doctor_id,
          p_hospital_id, p_submitted_by_user,
          DATE_ADD(NOW(), INTERVAL 72 HOUR), 'pending');
  SET v_request_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (p_submitted_by_user, 'REGISTER_DOCTOR', 'doctors', p_doctor_id,
          JSON_OBJECT('request_id', v_request_id, 'reference', p_request_ref,
                      'bmdc', p_bmdc_no, 'type', p_doctor_type));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Executive > Add New Patient
--   Optional portal login (users) + patients row + audit.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_register_patient$$
CREATE PROCEDURE sp_register_patient(
  IN  p_uid            VARCHAR(30),      -- NULL => no portal account yet
  IN  p_password_hash  VARCHAR(255),
  IN  p_nid            VARCHAR(30),
  IN  p_full_name      VARCHAR(150),
  IN  p_dob            DATE,
  IN  p_gender         VARCHAR(20),      -- male | female | intersex_other
  IN  p_blood_group    VARCHAR(3),
  IN  p_phone          VARCHAR(30),
  IN  p_address        VARCHAR(500),
  IN  p_hospital_id    BIGINT,
  IN  p_executive_id   BIGINT,
  OUT p_patient_id     BIGINT
)
BEGIN
  DECLARE v_user_id BIGINT DEFAULT NULL;
  DECLARE v_actor   BIGINT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_dob > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Date of birth cannot be in the future';
  END IF;

  START TRANSACTION;

  SELECT user_id INTO v_actor FROM medical_executives WHERE executive_id = p_executive_id;
  SET @app_user_id = v_actor;

  IF p_uid IS NOT NULL THEN
    INSERT INTO users (uid, nid, password_hash, role, status, must_reset_password)
    VALUES (p_uid, p_nid, p_password_hash, 'patient', 'active', 1);
    SET v_user_id = LAST_INSERT_ID();
  END IF;

  INSERT INTO patients (user_id, full_name, nid, dob, gender, blood_group, phone, address,
                        registering_hospital_id, registered_by_executive_id)
  VALUES (v_user_id, p_full_name, p_nid, p_dob, p_gender, p_blood_group, p_phone, p_address,
          p_hospital_id, p_executive_id);
  SET p_patient_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_actor, 'REGISTER_PATIENT', 'patients', p_patient_id,
          JSON_OBJECT('hospital_id', p_hospital_id, 'portal_account', v_user_id IS NOT NULL));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Executive > Register birth (CRVS)
--   Baby gets a patient record with a CRVS token (no NID yet); the
--   birth registration is linked to it. Both rows or neither.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_register_birth$$
CREATE PROCEDURE sp_register_birth(
  IN  p_crvs_token         VARCHAR(50),
  IN  p_mother_patient_id  BIGINT,           -- NULL if mother is not in the registry
  IN  p_baby_name          VARCHAR(150),
  IN  p_gender             VARCHAR(20),
  IN  p_birth_date         DATE,
  IN  p_birth_weight_kg    DECIMAL(4,2),
  IN  p_hospital_id        BIGINT,
  IN  p_executive_id       BIGINT,
  OUT p_baby_patient_id    BIGINT
)
BEGIN
  DECLARE v_actor  BIGINT DEFAULT NULL;
  DECLARE v_mother BIGINT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT user_id INTO v_actor FROM medical_executives WHERE executive_id = p_executive_id;
  SET @app_user_id = v_actor;

  IF p_mother_patient_id IS NOT NULL THEN
    SELECT patient_id INTO v_mother FROM patients WHERE patient_id = p_mother_patient_id LOCK IN SHARE MODE;
    IF v_mother IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mother patient record not found';
    END IF;
  END IF;

  INSERT INTO patients (full_name, crvs_token, dob, gender, birth_hospital_id,
                        registering_hospital_id, nid_verification_status,
                        registered_by_executive_id, weight_kg)
  VALUES (p_baby_name, p_crvs_token, p_birth_date, p_gender, p_hospital_id,
          p_hospital_id, 'crvs_certified', p_executive_id, p_birth_weight_kg);
  SET p_baby_patient_id = LAST_INSERT_ID();

  INSERT INTO birth_registrations (crvs_token, baby_patient_id, mother_patient_id, baby_name,
                                   gender, birth_weight_kg, hospital_id,
                                   registered_by_executive_id, status)
  VALUES (p_crvs_token, p_baby_patient_id, v_mother, p_baby_name,
          p_gender, p_birth_weight_kg, p_hospital_id, p_executive_id, 'verified_issued');

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_actor, 'REGISTER_BIRTH', 'birth_registrations', p_baby_patient_id,
          JSON_OBJECT('crvs_token', p_crvs_token, 'hospital_id', p_hospital_id));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Patient > Request Appointment
--   Locks the doctor row so concurrent bookings queue up, verifies the
--   slot is free, then inserts. uq_appt_doctor_slot is the safety net.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_book_appointment$$
CREATE PROCEDURE sp_book_appointment(
  IN  p_patient_id  BIGINT,
  IN  p_doctor_id   BIGINT,
  IN  p_hospital_id BIGINT,
  IN  p_date        DATE,
  IN  p_time_slot   VARCHAR(50),
  IN  p_reason      VARCHAR(255),
  OUT p_appointment_id BIGINT
)
BEGIN
  DECLARE v_patient_status VARCHAR(20) DEFAULT NULL;
  DECLARE v_doc_status     VARCHAR(20) DEFAULT NULL;
  DECLARE v_doc_verified   VARCHAR(20) DEFAULT NULL;
  DECLARE v_taken          INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_date < CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot book an appointment in the past';
  END IF;

  START TRANSACTION;

  SELECT status INTO v_patient_status FROM patients WHERE patient_id = p_patient_id LOCK IN SHARE MODE;
  IF v_patient_status IS NULL OR v_patient_status <> 'active' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient not found or not active';
  END IF;

  SELECT status, verification_status INTO v_doc_status, v_doc_verified
    FROM doctors WHERE doctor_id = p_doctor_id FOR UPDATE;          -- serialise bookings per doctor
  IF v_doc_status IS NULL OR v_doc_status <> 'active' OR v_doc_verified <> 'verified' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doctor not found, inactive or not verified';
  END IF;

  SELECT COUNT(*) INTO v_taken FROM appointments
   WHERE doctor_id = p_doctor_id AND appointment_date = p_date
     AND time_slot = p_time_slot AND status IN ('booked','rescheduled');
  IF v_taken > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Selected time slot is already booked';
  END IF;

  INSERT INTO appointments (patient_id, doctor_id, hospital_id, appointment_date, time_slot, reason)
  VALUES (p_patient_id, p_doctor_id, p_hospital_id, p_date, p_time_slot, p_reason);
  SET p_appointment_id = LAST_INSERT_ID();

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (NULL, 'BOOK_APPOINTMENT', 'appointments', p_appointment_id,
          JSON_OBJECT('patient_id', p_patient_id, 'doctor_id', p_doctor_id,
                      'date', p_date, 'slot', p_time_slot));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Doctor > Write Prescription
--   Header + N medications + N lab tests (+ completes the appointment).
--   p_medications : [{"name":"..","dose":"..","route_frequency":"..",
--                     "quantity":"..","refills":0,"sig":".."}, ...]
--   p_lab_tests   : [{"name":"..","code":"..","instructions":".."}, ...]
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_create_prescription$$
CREATE PROCEDURE sp_create_prescription(
  IN  p_patient_id        BIGINT,
  IN  p_doctor_id         BIGINT,
  IN  p_appointment_id    BIGINT,
  IN  p_title             VARCHAR(255),
  IN  p_chief_complaint   TEXT,
  IN  p_symptoms          TEXT,
  IN  p_current_condition TEXT,
  IN  p_doctors_statement TEXT,
  IN  p_referred_surgeon  BIGINT,
  IN  p_next_visit_date   DATE,
  IN  p_visit_type        VARCHAR(12),        -- in_person | telehealth
  IN  p_is_long_term      TINYINT,
  IN  p_medications       JSON,
  IN  p_lab_tests         JSON,
  OUT p_prescription_id   BIGINT
)
BEGIN
  DECLARE v_doc_user BIGINT DEFAULT NULL;
  DECLARE v_doc_ok   INT    DEFAULT 0;
  DECLARE v_i        INT    DEFAULT 0;
  DECLARE v_n        INT    DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF JSON_LENGTH(COALESCE(p_medications, JSON_ARRAY())) + JSON_LENGTH(COALESCE(p_lab_tests, JSON_ARRAY())) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A prescription needs at least one medication or lab test';
  END IF;

  START TRANSACTION;

  SELECT user_id, (status = 'active' AND verification_status = 'verified')
    INTO v_doc_user, v_doc_ok
    FROM doctors WHERE doctor_id = p_doctor_id LOCK IN SHARE MODE;
  IF v_doc_ok IS NULL OR v_doc_ok = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prescriber is not an active, verified doctor';
  END IF;
  SET @app_user_id = v_doc_user;

  -- trg_prescriptions_bi additionally rejects deceased patients
  INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, title, chief_complaint, symptoms,
                             current_condition, referred_surgeon_id, doctors_statement,
                             next_visit_date, visit_type, is_long_term)
  VALUES (p_patient_id, p_doctor_id, p_appointment_id, p_title, p_chief_complaint, p_symptoms,
          p_current_condition, p_referred_surgeon, p_doctors_statement,
          p_next_visit_date, COALESCE(p_visit_type, 'in_person'), COALESCE(p_is_long_term, 0));
  SET p_prescription_id = LAST_INSERT_ID();

  -- JSON array is walked with JSON_EXTRACT (portable: no JSON_TABLE needed)
  IF p_medications IS NOT NULL THEN
    SET v_n = JSON_LENGTH(p_medications), v_i = 0;
    WHILE v_i < v_n DO
      INSERT INTO prescription_medications (prescription_id, medication_name, dose_strength,
                                            route_frequency, dispense_quantity, refills, sig_instructions)
      VALUES (p_prescription_id,
              JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].name'))),
              JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].dose'))),
              JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].route_frequency'))),
              JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].quantity'))),
              COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].refills'))) AS SIGNED), 0),
              JSON_UNQUOTE(JSON_EXTRACT(p_medications, CONCAT('$[', v_i, '].sig'))));
      SET v_i = v_i + 1;
    END WHILE;
  END IF;

  IF p_lab_tests IS NOT NULL THEN
    SET v_n = JSON_LENGTH(p_lab_tests), v_i = 0;
    WHILE v_i < v_n DO
      INSERT INTO prescription_lab_tests (prescription_id, test_name, test_code, instructions)
      VALUES (p_prescription_id,
              JSON_UNQUOTE(JSON_EXTRACT(p_lab_tests, CONCAT('$[', v_i, '].name'))),
              JSON_UNQUOTE(JSON_EXTRACT(p_lab_tests, CONCAT('$[', v_i, '].code'))),
              JSON_UNQUOTE(JSON_EXTRACT(p_lab_tests, CONCAT('$[', v_i, '].instructions'))));
      SET v_i = v_i + 1;
    END WHILE;
  END IF;

  IF p_appointment_id IS NOT NULL THEN
    UPDATE appointments SET status = 'completed'
     WHERE appointment_id = p_appointment_id AND patient_id = p_patient_id AND doctor_id = p_doctor_id;
  END IF;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_doc_user, 'CREATE_PRESCRIPTION', 'prescriptions', p_prescription_id,
          JSON_OBJECT('patient_id', p_patient_id, 'medications', JSON_LENGTH(COALESCE(p_medications, JSON_ARRAY())),
                      'lab_tests', JSON_LENGTH(COALESCE(p_lab_tests, JSON_ARRAY()))));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Patient > Medical Test Request
--   Prices are SNAPSHOTTED from the catalog into the order items, and
--   total = sum(items) + collection fee is computed server-side (the JS
--   only previews it). p_items: [{"test_id":1,"advised":true}, ...]
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_place_lab_test_order$$
CREATE PROCEDURE sp_place_lab_test_order(
  IN  p_patient_id      BIGINT,
  IN  p_doctor_id       BIGINT,
  IN  p_prescription_id BIGINT,
  IN  p_hospital_id     BIGINT,
  IN  p_uploaded_file   VARCHAR(500),
  IN  p_items           JSON,
  OUT p_order_id        BIGINT,
  OUT p_total_amount    DECIMAL(10,2)
)
BEGIN
  DECLARE v_requested INT DEFAULT 0;
  DECLARE v_i         INT DEFAULT 0;
  DECLARE v_tid       INT DEFAULT NULL;
  DECLARE v_exists    INT DEFAULT 0;
  DECLARE v_adv       TINYINT DEFAULT 0;
  DECLARE v_subtotal  DECIMAL(10,2) DEFAULT 0;
  DECLARE v_fee       DECIMAL(10,2) DEFAULT 200.00;      -- same base fee the UI shows
  DECLARE v_pstatus   VARCHAR(20) DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_items IS NULL OR JSON_LENGTH(p_items) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Select at least one test';
  END IF;

  START TRANSACTION;

  SELECT status INTO v_pstatus FROM patients WHERE patient_id = p_patient_id LOCK IN SHARE MODE;
  IF v_pstatus IS NULL OR v_pstatus <> 'active' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient not found or not active';
  END IF;

  -- validate every requested test exists BEFORE creating the order
  SET v_requested = JSON_LENGTH(p_items), v_i = 0;
  WHILE v_i < v_requested DO
    SET v_tid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].test_id'))) AS SIGNED);
    SELECT COUNT(*) INTO v_exists FROM lab_test_catalog WHERE test_id = v_tid;
    IF v_tid IS NULL OR v_exists = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'One or more requested tests do not exist in the catalog';
    END IF;
    SET v_i = v_i + 1;
  END WHILE;

  INSERT INTO lab_test_orders (patient_id, doctor_id, prescription_id, hospital_id,
                               collection_fee, total_amount, uploaded_prescription_file)
  VALUES (p_patient_id, p_doctor_id, p_prescription_id, p_hospital_id, v_fee, 0, p_uploaded_file);
  SET p_order_id = LAST_INSERT_ID();

  SET v_i = 0;
  WHILE v_i < v_requested DO
    SET v_tid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].test_id'))) AS SIGNED);
    SET v_adv = IF(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].advised'))) IN ('true','1'), 1, 0);
    INSERT INTO lab_test_order_items (order_id, test_id, price, is_doctor_advised)
    SELECT p_order_id, c.test_id, c.price, v_adv FROM lab_test_catalog c WHERE c.test_id = v_tid;
    SET v_i = v_i + 1;
  END WHILE;

  SELECT COALESCE(SUM(price), 0) INTO v_subtotal FROM lab_test_order_items WHERE order_id = p_order_id;
  SET p_total_amount = v_subtotal + v_fee;
  UPDATE lab_test_orders SET total_amount = p_total_amount WHERE order_id = p_order_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (NULL, 'PLACE_LAB_ORDER', 'lab_test_orders', p_order_id,
          JSON_OBJECT('patient_id', p_patient_id, 'tests', v_requested, 'total', p_total_amount));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Patient > Request Vaccine ; Doctor/Executive > record dose
--   Inserts the dose record and closes the linked vaccine appointment.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_record_vaccination$$
CREATE PROCEDURE sp_record_vaccination(
  IN  p_patient_id           BIGINT,
  IN  p_vaccine_id           INT,
  IN  p_hospital_id          BIGINT,
  IN  p_doctor_id            BIGINT,
  IN  p_dose_number          TINYINT,
  IN  p_administered_date    DATE,
  IN  p_serology_titer       VARCHAR(100),
  IN  p_certificate_no       VARCHAR(100),
  IN  p_vaccine_appointment  BIGINT,          -- NULL for walk-ins
  OUT p_record_id            BIGINT
)
BEGIN
  DECLARE v_dup INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_administered_date > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administered date cannot be in the future';
  END IF;

  START TRANSACTION;

  SELECT COUNT(*) INTO v_dup FROM vaccination_records
   WHERE patient_id = p_patient_id AND vaccine_id = p_vaccine_id
     AND dose_number = COALESCE(p_dose_number, 1) FOR UPDATE;
  IF v_dup > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This dose is already recorded for the patient';
  END IF;

  INSERT INTO vaccination_records (patient_id, vaccine_id, hospital_id, administered_by_doctor_id,
                                   dose_number, administered_date, serology_titer, certificate_no)
  VALUES (p_patient_id, p_vaccine_id, p_hospital_id, p_doctor_id,
          COALESCE(p_dose_number, 1), p_administered_date, p_serology_titer, p_certificate_no);
  SET p_record_id = LAST_INSERT_ID();

  IF p_vaccine_appointment IS NOT NULL THEN
    UPDATE vaccine_appointments SET status = 'completed'
     WHERE id = p_vaccine_appointment AND patient_id = p_patient_id AND status = 'scheduled';
  END IF;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (NULL, 'RECORD_VACCINATION', 'vaccination_records', p_record_id,
          JSON_OBJECT('patient_id', p_patient_id, 'vaccine_id', p_vaccine_id, 'dose', COALESCE(p_dose_number, 1)));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Executive > Vaccination Request  (facility -> national depot)
--   p_items: [{"vaccine_id":1,"quantity":500}, ...]
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_submit_vaccine_allocation$$
CREATE PROCEDURE sp_submit_vaccine_allocation(
  IN  p_hospital_id     BIGINT,
  IN  p_executive_id    BIGINT,
  IN  p_department      VARCHAR(150),
  IN  p_urgency         VARCHAR(10),        -- routine | urgent | emergency
  IN  p_delivery_date   DATE,
  IN  p_rpic_name       VARCHAR(150),
  IN  p_target_notes    VARCHAR(500),
  IN  p_justification   TEXT,
  IN  p_items           JSON,
  OUT p_request_id      BIGINT
)
BEGIN
  DECLARE v_actor     BIGINT DEFAULT NULL;
  DECLARE v_lines     INT DEFAULT 0;
  DECLARE v_i         INT DEFAULT 0;
  DECLARE v_vid       INT DEFAULT NULL;
  DECLARE v_qty       INT DEFAULT NULL;
  DECLARE v_exists    INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_items IS NULL OR JSON_LENGTH(p_items) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Add at least one vaccine line';
  END IF;

  START TRANSACTION;

  SET v_lines = JSON_LENGTH(p_items), v_i = 0;
  WHILE v_i < v_lines DO
    SET v_vid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].vaccine_id'))) AS SIGNED);
    SET v_qty = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity'))) AS SIGNED);
    SELECT COUNT(*) INTO v_exists FROM vaccine_catalog WHERE vaccine_id = v_vid;
    IF v_vid IS NULL OR v_exists = 0 OR v_qty IS NULL OR v_qty <= 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Every line needs an existing vaccine and a quantity above zero';
    END IF;
    SET v_i = v_i + 1;
  END WHILE;

  SELECT user_id INTO v_actor FROM medical_executives WHERE executive_id = p_executive_id;

  INSERT INTO vaccine_allocation_requests (requesting_hospital_id, requesting_executive_id, clinical_department,
                                           urgency_classification, requested_delivery_date, rpic_name,
                                           target_population_notes, clinical_justification)
  VALUES (p_hospital_id, p_executive_id, p_department, COALESCE(p_urgency, 'routine'),
          p_delivery_date, p_rpic_name, p_target_notes, p_justification);
  SET p_request_id = LAST_INSERT_ID();

  SET v_i = 0;
  WHILE v_i < v_lines DO
    INSERT INTO vaccine_allocation_items (request_id, vaccine_id, quantity)
    VALUES (p_request_id,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].vaccine_id'))) AS SIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity'))) AS SIGNED));
    SET v_i = v_i + 1;
  END WHILE;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_actor, 'SUBMIT_VACCINE_ALLOCATION', 'vaccine_allocation_requests', p_request_id,
          JSON_OBJECT('hospital_id', p_hospital_id, 'urgency', COALESCE(p_urgency, 'routine'), 'lines', v_lines));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Admin > Request Management : approve / decline / flag
--   The request row is locked, so two admins cannot decide it twice.
--   Approving a doctor registration activates the doctor AND the login
--   account in the same transaction.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_decide_credential_request$$
CREATE PROCEDURE sp_decide_credential_request(
  IN p_request_id BIGINT,
  IN p_admin_id   BIGINT,
  IN p_decision   VARCHAR(10),              -- approved | declined | flagged
  IN p_notes      TEXT
)
BEGIN
  DECLARE v_status      VARCHAR(10) DEFAULT NULL;
  DECLARE v_type        VARCHAR(40) DEFAULT NULL;
  DECLARE v_doctor_id   BIGINT DEFAULT NULL;
  DECLARE v_admin_user  BIGINT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_decision NOT IN ('approved','declined','flagged') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Decision must be approved, declined or flagged';
  END IF;

  START TRANSACTION;

  SELECT user_id INTO v_admin_user FROM admins WHERE admin_id = p_admin_id;
  IF v_admin_user IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown admin';
  END IF;
  SET @app_user_id = v_admin_user;

  SELECT status, request_type, applicant_doctor_id
    INTO v_status, v_type, v_doctor_id
    FROM credential_requests WHERE request_id = p_request_id FOR UPDATE;
  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Request not found';
  END IF;
  IF v_status IN ('approved','declined') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Request has already been finally decided';
  END IF;

  UPDATE credential_requests
     SET status = p_decision, decision_notes = p_notes,
         decided_by_admin_id = p_admin_id, decided_at = NOW()
   WHERE request_id = p_request_id;

  IF v_type = 'doctor_registration' AND v_doctor_id IS NOT NULL THEN
    IF p_decision = 'approved' THEN
      UPDATE doctors SET verification_status = 'verified', status = 'active' WHERE doctor_id = v_doctor_id;
      UPDATE users u JOIN doctors d ON d.user_id = u.user_id
         SET u.status = 'active' WHERE d.doctor_id = v_doctor_id;
    ELSEIF p_decision = 'declined' THEN
      UPDATE doctors SET status = 'inactive' WHERE doctor_id = v_doctor_id;
      UPDATE users u JOIN doctors d ON d.user_id = u.user_id
         SET u.status = 'deactivated' WHERE d.doctor_id = v_doctor_id;
    ELSE
      UPDATE doctors SET verification_status = 'flagged' WHERE doctor_id = v_doctor_id;
    END IF;
  END IF;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_admin_user, 'DECIDE_CREDENTIAL_REQUEST', 'credential_requests', p_request_id,
          JSON_OBJECT('decision', p_decision, 'type', v_type, 'doctor_id', v_doctor_id));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Admin > Licence New Hospital
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_license_hospital$$
CREATE PROCEDURE sp_license_hospital(
  IN p_hospital_id      BIGINT,
  IN p_admin_id         BIGINT,
  IN p_decision         VARCHAR(10),         -- licensed | suspended | revoked
  IN p_inspection_date  DATE
)
BEGIN
  DECLARE v_current    VARCHAR(10) DEFAULT NULL;
  DECLARE v_admin_user BIGINT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_decision NOT IN ('licensed','suspended','revoked') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Decision must be licensed, suspended or revoked';
  END IF;

  START TRANSACTION;

  SELECT user_id INTO v_admin_user FROM admins WHERE admin_id = p_admin_id;
  IF v_admin_user IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown admin';
  END IF;

  SELECT license_status INTO v_current FROM hospitals WHERE hospital_id = p_hospital_id FOR UPDATE;
  IF v_current IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hospital not found';
  END IF;
  IF v_current = 'revoked' AND p_decision <> 'revoked' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A revoked licence cannot be reinstated; submit a new application';
  END IF;

  UPDATE hospitals
     SET license_status     = p_decision,
         inspection_date    = COALESCE(p_inspection_date, inspection_date),
         license_issue_date = IF(p_decision = 'licensed' AND license_issue_date IS NULL, CURDATE(), license_issue_date)
   WHERE hospital_id = p_hospital_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_admin_user, 'LICENSE_HOSPITAL', 'hospitals', p_hospital_id,
          JSON_OBJECT('from', v_current, 'to', p_decision));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Doctor > Request Profile Access  (admin/patient decision)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_decide_profile_access$$
CREATE PROCEDURE sp_decide_profile_access(
  IN p_request_id      BIGINT,
  IN p_decider_user_id BIGINT,
  IN p_decision        VARCHAR(10),          -- approved | denied
  IN p_patient_id      BIGINT                -- resolved match; NULL keeps the stored one
)
BEGIN
  DECLARE v_status  VARCHAR(10) DEFAULT NULL;
  DECLARE v_patient BIGINT DEFAULT NULL;
  DECLARE v_exists  INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_decision NOT IN ('approved','denied') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Decision must be approved or denied';
  END IF;

  START TRANSACTION;
  SET @app_user_id = p_decider_user_id;

  SELECT status, patient_id INTO v_status, v_patient
    FROM profile_access_requests WHERE id = p_request_id FOR UPDATE;
  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Access request not found';
  END IF;
  IF v_status <> 'pending' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Access request was already decided';
  END IF;

  SET v_patient = COALESCE(p_patient_id, v_patient);
  IF p_decision = 'approved' THEN
    SELECT COUNT(*) INTO v_exists FROM patients WHERE patient_id = v_patient;
    IF v_patient IS NULL OR v_exists = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot approve: request is not matched to a patient record';
    END IF;
  END IF;

  UPDATE profile_access_requests
     SET status = p_decision, patient_id = v_patient,
         decided_at = NOW(), decided_by_user_id = p_decider_user_id
   WHERE id = p_request_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (p_decider_user_id, 'DECIDE_PROFILE_ACCESS', 'profile_access_requests', p_request_id,
          JSON_OBJECT('decision', p_decision, 'patient_id', v_patient));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Executive > Issue Death Certificate
--   The most cross-cutting workflow: certificate + patient status +
--   login deactivation + cancellation of everything still pending.
--   Any failure leaves the person 'active' and no certificate behind.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_issue_death_certificate$$
CREATE PROCEDURE sp_issue_death_certificate(
  IN  p_patient_id            BIGINT,
  IN  p_executive_id          BIGINT,
  IN  p_date_of_death         DATE,
  IN  p_time_of_death         TIME,
  IN  p_place_of_death        VARCHAR(30),   -- hospital_inpatient | residential | transit_ambulance | other
  IN  p_facility_name         VARCHAR(255),
  IN  p_cause_immediate       VARCHAR(500),
  IN  p_cause_immediate_icd   VARCHAR(20),
  IN  p_cause_underlying      VARCHAR(500),
  IN  p_cause_underlying_icd  VARCHAR(20),
  IN  p_manner                VARCHAR(20),   -- natural | accident | suicide | homicide | undetermined
  IN  p_attending_mcrn        VARCHAR(50),
  IN  p_autopsy_performed     TINYINT,
  OUT p_certificate_id        BIGINT
)
BEGIN
  DECLARE v_status VARCHAR(10) DEFAULT NULL;
  DECLARE v_dob    DATE DEFAULT NULL;
  DECLARE v_puser  BIGINT DEFAULT NULL;
  DECLARE v_actor  BIGINT DEFAULT NULL;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT user_id INTO v_actor FROM medical_executives WHERE executive_id = p_executive_id;
  IF v_actor IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unknown medical executive';
  END IF;
  SET @app_user_id = v_actor;

  SELECT status, dob, user_id INTO v_status, v_dob, v_puser
    FROM patients WHERE patient_id = p_patient_id FOR UPDATE;
  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient not found';
  END IF;
  IF v_status = 'deceased' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A death certificate has already been issued for this patient';
  END IF;
  IF p_date_of_death > CURDATE() OR p_date_of_death < v_dob THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Date of death must fall between date of birth and today';
  END IF;

  INSERT INTO death_certificates (patient_id, date_of_pronouncement, time_of_death, place_of_death,
                                  facility_name, cause_immediate, cause_immediate_icd,
                                  cause_underlying, cause_underlying_icd, manner_of_death,
                                  attending_physician_mcrn, autopsy_performed, issued_by_executive_id)
  VALUES (p_patient_id, p_date_of_death, p_time_of_death, COALESCE(p_place_of_death, 'hospital_inpatient'),
          p_facility_name, p_cause_immediate, p_cause_immediate_icd,
          p_cause_underlying, p_cause_underlying_icd, COALESCE(p_manner, 'natural'),
          p_attending_mcrn, COALESCE(p_autopsy_performed, 0), p_executive_id);
  SET p_certificate_id = LAST_INSERT_ID();

  UPDATE patients SET status = 'deceased' WHERE patient_id = p_patient_id;

  IF v_puser IS NOT NULL THEN
    UPDATE users SET status = 'deactivated' WHERE user_id = v_puser;
  END IF;

  UPDATE appointments SET status = 'cancelled'
   WHERE patient_id = p_patient_id AND status IN ('booked','rescheduled') AND appointment_date >= p_date_of_death;
  UPDATE vaccine_appointments SET status = 'cancelled'
   WHERE patient_id = p_patient_id AND status = 'scheduled';
  UPDATE lab_test_orders SET status = 'cancelled'
   WHERE patient_id = p_patient_id AND status = 'requested';

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_actor, 'ISSUE_DEATH_CERTIFICATE', 'death_certificates', p_certificate_id,
          JSON_OBJECT('patient_id', p_patient_id, 'manner', COALESCE(p_manner, 'natural')));

  COMMIT;
END$$

-- ---------------------------------------------------------------------
-- Admin > New Broadcast : dispatch
--   Flips the notice to 'active' and fans out one delivery-log row per
--   recipient. Delivery/ack flags are updated later by the sender/worker.
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_dispatch_notice$$
CREATE PROCEDURE sp_dispatch_notice(
  IN  p_notice_id   BIGINT,
  IN  p_admin_id    BIGINT,
  IN  p_audience    VARCHAR(10),            -- hospitals | doctors | both
  OUT p_recipients  INT
)
BEGIN
  DECLARE v_status     VARCHAR(10) DEFAULT NULL;
  DECLARE v_admin_user BIGINT DEFAULT NULL;
  DECLARE v_h          INT DEFAULT 0;
  DECLARE v_d          INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_audience NOT IN ('hospitals','doctors','both') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audience must be hospitals, doctors or both';
  END IF;

  START TRANSACTION;

  SELECT user_id INTO v_admin_user FROM admins WHERE admin_id = p_admin_id;

  SELECT status INTO v_status FROM notices WHERE notice_id = p_notice_id FOR UPDATE;
  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Notice not found';
  END IF;
  IF v_status NOT IN ('draft','scheduled') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only draft or scheduled notices can be dispatched';
  END IF;

  IF p_audience IN ('hospitals','both') THEN
    INSERT INTO notice_delivery_logs (notice_id, recipient_type, recipient_id)
    SELECT p_notice_id, 'hospital', hospital_id FROM hospitals WHERE license_status = 'licensed';
    SET v_h = ROW_COUNT();
  END IF;
  IF p_audience IN ('doctors','both') THEN
    INSERT INTO notice_delivery_logs (notice_id, recipient_type, recipient_id)
    SELECT p_notice_id, 'doctor', doctor_id FROM doctors
     WHERE status = 'active' AND verification_status = 'verified';
    SET v_d = ROW_COUNT();
  END IF;
  SET p_recipients = v_h + v_d;

  UPDATE notices SET status = 'active', dispatched_at = NOW() WHERE notice_id = p_notice_id;

  INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
  VALUES (v_admin_user, 'DISPATCH_NOTICE', 'notices', p_notice_id,
          JSON_OBJECT('audience', p_audience, 'recipients', p_recipients));

  COMMIT;
END$$


-- =====================================================================
-- SECTION 21: TRIGGERS
--   Guard rails that must hold no matter which code path writes the row
--   (procedure, admin SQL console, future API).
-- =====================================================================

-- Keep BMI consistent with height/weight (patient-info.html vitals card)
DROP TRIGGER IF EXISTS trg_patients_bi_bmi$$
CREATE TRIGGER trg_patients_bi_bmi BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
  IF NEW.height_cm > 0 AND NEW.weight_kg > 0 THEN
    SET NEW.bmi = ROUND(NEW.weight_kg / POW(NEW.height_cm / 100, 2), 1);
  END IF;
END$$

DROP TRIGGER IF EXISTS trg_patients_bu_bmi$$
CREATE TRIGGER trg_patients_bu_bmi BEFORE UPDATE ON patients
FOR EACH ROW
BEGIN
  IF NEW.height_cm > 0 AND NEW.weight_kg > 0
     AND (NOT (NEW.height_cm <=> OLD.height_cm) OR NOT (NEW.weight_kg <=> OLD.weight_kg)) THEN
    SET NEW.bmi = ROUND(NEW.weight_kg / POW(NEW.height_cm / 100, 2), 1);
  END IF;
END$$

-- No new clinical activity for a deceased patient
DROP TRIGGER IF EXISTS trg_prescriptions_bi_alive$$
CREATE TRIGGER trg_prescriptions_bi_alive BEFORE INSERT ON prescriptions
FOR EACH ROW
BEGIN
  IF (SELECT status FROM patients WHERE patient_id = NEW.patient_id) = 'deceased' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot create a prescription for a deceased patient';
  END IF;
END$$

DROP TRIGGER IF EXISTS trg_appointments_bi_alive$$
CREATE TRIGGER trg_appointments_bi_alive BEFORE INSERT ON appointments
FOR EACH ROW
BEGIN
  IF (SELECT status FROM patients WHERE patient_id = NEW.patient_id) = 'deceased' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot book an appointment for a deceased patient';
  END IF;
END$$

-- Account role/status changes are always audited, whoever makes them
DROP TRIGGER IF EXISTS trg_users_au_audit$$
CREATE TRIGGER trg_users_au_audit AFTER UPDATE ON users
FOR EACH ROW
BEGIN
  IF NOT (NEW.status <=> OLD.status) OR NOT (NEW.role <=> OLD.role) THEN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (@app_user_id, 'USER_ACCOUNT_CHANGED', 'users', NEW.user_id,
            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status,
                        'old_role', OLD.role, 'new_role', NEW.role));
  END IF;
END$$

-- Doctor verification / duty-status changes are always audited
DROP TRIGGER IF EXISTS trg_doctors_au_audit$$
CREATE TRIGGER trg_doctors_au_audit AFTER UPDATE ON doctors
FOR EACH ROW
BEGIN
  IF NOT (NEW.verification_status <=> OLD.verification_status) OR NOT (NEW.status <=> OLD.status) THEN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
    VALUES (@app_user_id, 'DOCTOR_STATUS_CHANGED', 'doctors', NEW.doctor_id,
            JSON_OBJECT('old_verification', OLD.verification_status, 'new_verification', NEW.verification_status,
                        'old_status', OLD.status, 'new_status', NEW.status));
  END IF;
END$$

DELIMITER ;
