document.addEventListener("DOMContentLoaded", () => {
  // Target top profile buttons
  const editProfileBtn = document.querySelector(".btn-emerald");
  // Inject dynamic CSS modal styles into page head
  const modalStyle = document.createElement("style");
  modalStyle.textContent = `
    .nhmrd-modal-backdrop {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
      display: flex; align-items: center; justify-content: center;
      z-index: 9999;
    }
    .nhmrd-modal-card {
      background: #ffffff;
      width: 100%; max-width: 680px; max-height: 90vh;
      border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
      display: flex; flex-direction: column; overflow: hidden;
      font-family: inherit;
    }
    .nhmrd-modal-header {
      padding: 18px 24px; background: #0f172a; color: #ffffff;
      display: flex; justify-content: space-between; align-items: center;
    }
    .nhmrd-modal-header h2 { font-size: 1.15rem; margin: 0; font-weight: 600; }
    .nhmrd-modal-close {
      background: transparent; border: none; color: #94a3b8;
      font-size: 1.25rem; cursor: pointer; transition: color 0.2s;
    }
    .nhmrd-modal-close:hover { color: #ffffff; }
    .nhmrd-modal-body { padding: 24px; overflow-y: auto; }
    .nhmrd-form-section { margin-bottom: 20px; }
    .nhmrd-form-section h3 {
      font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;
      color: #059669; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;
    }
    .nhmrd-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .nhmrd-field-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
    .nhmrd-field-group.full-width { grid-column: span 2; }
    .nhmrd-field-group label { font-size: 0.825rem; font-weight: 600; color: #334155; }
    .nhmrd-field-group input, .nhmrd-field-group select {
      padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;
      font-size: 0.875rem; outline: none; transition: border-color 0.2s;
    }
    .nhmrd-field-group input:focus, .nhmrd-field-group select:focus { border-color: #059669; }
    .nhmrd-modal-footer {
      padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0;
      display: flex; justify-content: flex-end; gap: 12px;
    }
    .nhmrd-btn-save {
      background: #059669; color: white; border: none; padding: 8px 18px;
      border-radius: 6px; font-weight: 600; cursor: pointer;
    }
    .nhmrd-btn-cancel {
      background: #e2e8f0; color: #475569; border: none; padding: 8px 18px;
      border-radius: 6px; font-weight: 600; cursor: pointer;
    }
    .nhmrd-status-box {
      background: #f1f5f9; border-left: 4px solid #0284c7; padding: 12px;
      border-radius: 4px; font-size: 0.85rem; margin-bottom: 12px;
    }
  `;
  document.head.appendChild(modalStyle);

  // Helper method to create dynamic modals
  function createModal(titleHTML, bodyHTML, onSave) {
    const backdrop = document.createElement("div");
    backdrop.className = "nhmrd-modal-backdrop";

    backdrop.innerHTML = `
      <div class="nhmrd-modal-card">
        <div class="nhmrd-modal-header">
          <h2>${titleHTML}</h2>
          <button class="nhmrd-modal-close">&times;</button>
        </div>
        <div class="nhmrd-modal-body">${bodyHTML}</div>
        <div class="nhmrd-modal-footer">
          <button class="nhmrd-btn-cancel">Cancel</button>
          <button class="nhmrd-btn-save">Save & Apply</button>
        </div>
      </div>
    `;

    document.body.appendChild(backdrop);

    const closeBtn = backdrop.querySelector(".nhmrd-modal-close");
    const cancelBtn = backdrop.querySelector(".nhmrd-btn-cancel");
    const saveBtn = backdrop.querySelector(".nhmrd-btn-save");

    const closeModal = () => backdrop.remove();

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    backdrop.addEventListener("click", (e) => {
      if (e.target === backdrop) closeModal();
    });

    saveBtn.addEventListener("click", () => {
      if (onSave) onSave(backdrop);
      closeModal();
    });
  }

  // 1. EDIT PROFILE DIALOG WITH INPUT FIELDS
  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", () => {
      const modalBody = `
        <form id="edit-profile-form">
          <!-- Personal & Contact Details -->
          <div class="nhmrd-form-section">
            <h3>1. Personal & Contact Details</h3>
            <div class="nhmrd-field-grid">
              <div class="nhmrd-field-group">
                <label>Display Name</label>
                <input type="text" name="displayName" value="Dr. Nusrat Jahan">
              </div>
              <div class="nhmrd-field-group">
                <label>Contact Phone Number</label>
                <input type="tel" name="phone" value="+880 1700-000000">
              </div>
              <div class="nhmrd-field-group full-width">
                <label>Profile Picture URL / Upload File</label>
                <input type="text" name="profileImg" value="https://i.pravatar.cc/150?img=47">
              </div>
            </div>
          </div>

          <!-- Qualifications & Titles -->
          <div class="nhmrd-form-section">
            <h3>2. Medical Qualifications & Titles</h3>
            <div class="nhmrd-field-grid">
              <div class="nhmrd-field-group">
                <label>Credentials</label>
                <input type="text" name="credentials" value="FCPS, MRCP">
              </div>
              <div class="nhmrd-field-group">
                <label>Administrative Designation</label>
                <select name="designation">
                  <option selected>Attending Physician</option>
                  <option>Faculty Fellow</option>
                  <option>Consultant</option>
                  <option>Senior Registrar</option>
                </select>
              </div>
              <div class="nhmrd-field-group full-width">
                <label>Clinical Specialty Title</label>
                <input type="text" name="specialty" value="Board Certified Internal Medicine & Clinical Pharmacology">
              </div>
            </div>
          </div>
        </form>
      `;

      createModal(
        '<i class="fa-solid fa-sliders"></i> Edit Doctor Profile',
        modalBody,
        (container) => {
          const form = container.querySelector("#edit-profile-form");
          const formData = new FormData(form);

          // Update profile card UI dynamically
          const nameElem = document.querySelector(".doctor-title");
          const subElem = document.querySelector(".doctor-subtitle");
          if (nameElem) nameElem.textContent = `${formData.get("displayName")}, ${formData.get("credentials")}`;
          if (subElem) subElem.textContent = formData.get("specialty");

          alert("Profile updated successfully!");
        }
      );
    });
  }
});