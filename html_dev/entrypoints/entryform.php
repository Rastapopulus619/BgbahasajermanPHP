<?php
// entryform.php
require_once '../config/db.php'; // database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Entry Form</title>
  <link rel="stylesheet" href="../assets/entryform.css">
  <link rel="stylesheet" href="../assets/dropdownbox.css">
</head>
<body>

  <h1>Welcome to the New Entry Page</h1>
  <p>This will be the new control center for real-time DB interaction.</p>

  <br>
  <h2>Select Student</h2>

  <!-- 🔽 Always-visible dropdowns -->
  <?php include '../assets/components/DropdownBox_Course.php'; ?>
  <?php include '../assets/components/DropdownBox_Student.php'; ?>

  <br><br><br>

  <!-- 🔄 Dynamic Label → AJAX Dropdown -->
  <div id="dynamicFieldWrapper">
    <label id="fieldLabel" style="cursor: pointer; text-decoration: underline; color: blue;">
      Click me to select another student
    </label>
  </div>

  <!-- Slot where dropdown will appear -->
  <div id="dynamicDropdownBox" style="display: none;"></div>

  <!-- Hidden pre-rendered modular dropdown -->
  <div id="templateTestDropdown" style="display: none;">
    <?php
      $inputId = 'testStudentInput';
      $dropdownId = 'testStudentDropdown';
      $statusId = 'testStudentStatus';
      $errorId = 'testStudentError';
      $buttonId = 'testStudentShowBtn';
      $label = 'Student auswählen';
      $placeholder = 'Gib den Namen ein …';
      $buttonLabel = 'Paket anzeigen';
      include '../assets/components/DropdownBoxTemplate.php';
    ?>
  </div>

  <!-- JavaScript for setting up dropdowns -->
  <script type="module">
  import { setupDropdown } from '/assets/js/modularDropdownBox.js';

  // Setup original dropdowns
  setupDropdown({
    inputId: 'studentInput',
    dropdownId: 'studentDropdown',
    statusId: 'studentStatus',
    errorId: 'studentError',
    buttonId: 'studentShowBtn',
    fetchUrl: '../handlers/fetchStudentList.php',
    minChars: 1
  });

  setupDropdown({
    inputId: 'courseInput',
    dropdownId: 'courseDropdown',
    statusId: 'courseStatus',
    errorId: 'courseError',
    buttonId: 'courseShowBtn',
    fetchUrl: '../handlers/fetchLevelsList.php',
    minChars: 1
  });
</script>
<script type="module">
  import { setupDropdown } from '/assets/js/modularDropdownBox.js';

  const wrapper = document.getElementById('dynamicFieldWrapper');
  const label = document.getElementById('fieldLabel');
  const template = document.getElementById('templateTestDropdown');

  label.addEventListener('click', () => {
    // Generate unique ID suffix
    const suffix = 'dyn' + Date.now();

    // Clone template and show
    const clone = template.cloneNode(true);
    clone.style.display = 'block';
    wrapper.innerHTML = '';
    wrapper.appendChild(clone);

    // Dynamically assign new IDs
    const oldToNew = {
      testStudentInput: `input_${suffix}`,
      testStudentDropdown: `dropdown_${suffix}`,
      testStudentStatus: `status_${suffix}`,
      testStudentError: `error_${suffix}`,
      testStudentShowBtn: `button_${suffix}`
    };

    for (const [oldId, newId] of Object.entries(oldToNew)) {
      const el = clone.querySelector(`#${oldId}`);
      if (el) el.id = newId;
    }

    // ⚠️ Wait until DOM update completes
      setTimeout(() => {
        const input = document.getElementById(oldToNew.testStudentInput);
        const dropdown = document.getElementById(oldToNew.testStudentDropdown);
        const status = document.getElementById(oldToNew.testStudentStatus);
        const error = document.getElementById(oldToNew.testStudentError);
        const button = document.getElementById(oldToNew.testStudentShowBtn);

        console.log('[DEBUG] Input found?', input);
        console.log('[DEBUG] Dropdown found?', dropdown);
        console.log('[DEBUG] Status found?', status);
        console.log('[DEBUG] Error found?', error);
        console.log('[DEBUG] Button found?', button);

        if (input && dropdown && status && error && button) {
          setupDropdown({
            inputId: oldToNew.testStudentInput,
            dropdownId: oldToNew.testStudentDropdown,
            statusId: oldToNew.testStudentStatus,
            errorId: oldToNew.testStudentError,
            buttonId: oldToNew.testStudentShowBtn,
            fetchUrl: '../handlers/fetchStudentList.php',
            minChars: 1
          });

          button.addEventListener('click', () => {
            const val = input?.value?.trim();
            if (val) {
              label.textContent = val;
              wrapper.innerHTML = '';
              wrapper.appendChild(label);
            }
          });
        } else {
          console.error('[ERROR] ❌ One or more dynamic elements not found, aborting setupDropdown');
        }
      }, 0);


      // Confirm button click → label switch
      const confirmBtn = document.getElementById(oldToNew.testStudentShowBtn);
      confirmBtn?.addEventListener('click', () => {
        const input = document.getElementById(oldToNew.testStudentInput);
        const val = input?.value?.trim();
        if (val) {
          label.textContent = val;
          wrapper.innerHTML = '';
          wrapper.appendChild(label);
        }
      });
    }, 0); // Let DOM update complete before wiring
  
</script>




</body>
</html>
