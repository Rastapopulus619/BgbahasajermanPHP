<?php
// WATemplateCreator.php - WhatsApp Template Creator page

require_once '../config/db.php';  // Connect to the database

// (Optional) Fetch a default student record for testing (reuse index.php query)
$defaultName = $defaultNumber = '';
$sql = "SELECT * FROM students WHERE StudentID = 5";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $defaultName   = $row['Name'];
    $defaultNumber = $row['StudentNumber'];
}
// Note: $defaultName/$defaultNumber could be used as a placeholder fill if needed.

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>WA Template Creator</title>
  <link rel="stylesheet" href="../assets/entryform.css">  <!-- Reuse existing styles -->
  <link rel="stylesheet" href="../assets/dropdownbox.css">
</head>
<body>
  <h1>WhatsApp Template Creator</h1>
  
<!-- Template selection combobox and manage button -->
<div style="display: flex; align-items: center; gap: 20px;">
  <div>
    <label for="templateSelect"><strong>Pilih Template:</strong></label>
    <select id="templateSelect">
      <option value="">-- Choose Template --</option>
    </select>
  </div>
  <div>
    <a href="templateManager.php" style="display: inline-block; padding: 7px 18px; background: #1976d2; color: #fff; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 1em; border: none; transition: background 0.2s;">Manage Templates</a>
  </div>
</div>
<br><br>
  
  <br><br>
  <div id="student-picker-section">
  <!-- Student picker dropdown (reuse existing component) -->
  <label for="studentInput"><strong>Pilih Siswa:</strong></label><br>
  <?php include '../assets/components/DropdownBox_Student.php'; ?>  <!-- Student search box -->
  </div>

  <br><br>
  <!-- Generated template text area and Copy button -->
  <!-- After -->
<div style="display: flex; gap: 30px; align-items: stretch; max-width: 1200px; width: 100%; margin: 40px 0; padding: 0; box-sizing: border-box;">
      <div style="flex: 1 1 0; min-width: 0; display: flex; flex-direction: column;">
        <label for="templateText"><strong>Generated Text:</strong></label><br>
        <textarea id="templateText" rows="6" style="width: 100%; flex: 1;" placeholder="Template text will appear here..."></textarea>
        <br>
        <button id="copyBtn">Copy to Clipboard</button>
      </div>
      <!-- Right side: manual input fields -->
      <div id="manualInputs" style="flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; justify-content: flex-start; padding: 10px; border: 1px solid #ccc; border-radius: 8px; background: #f9f9f9;">
        <strong>Manual Placeholder Overrides</strong>
        <p style="font-size: 0.9em; margin-top: 5px; color: #777;">(Fields appear based on selected template)</p>
      </div>
  </div>

  <!-- Load required services -->
  <script src="../assets/js/dataFetcher.js"></script>
  <script src="../services/StudentService.js"></script>
  <script src="../services/TemplateService.js"></script>

  <!-- Include the dropdown box script -->
  <script type="module">
    import { setupDropdown } from '/assets/js/modularDropdownBox.js';
    // Initialize the student dropdown (fetch list as in entryform)
    setupDropdown({
      inputId: 'studentInput',
      dropdownId: 'studentDropdown',
      statusId: 'studentStatus',
      errorId: 'studentError',
      buttonId: 'studentShowBtn',
      fetchUrl: '../handlers/fetchStudentList.php',
      minChars: 1,
        onSelect: (selectedValue) => {
          const nameField = document.getElementById('input_STUDENTNAME');
          if (nameField) {
            nameField.value = selectedValue;
            // 🔥 Trigger the input event so the listener updates the template text
            nameField.dispatchEvent(new Event('input', { bubbles: true }));
          }
        }
    });

    async function loadTemplateOptions() {
      try {
        const res = await fetch('../handlers/listTemplates.php');
        const templates = await res.json();

        const select = document.getElementById('templateSelect');
        templates.forEach(name => {
          const option = document.createElement('option');
          option.value = name;
          option.textContent = name.charAt(0).toUpperCase() + name.slice(1); // optional: capitalize
          select.appendChild(option);
        });
      } catch (err) {
        console.error("Error loading template list:", err);
      }
    }

    // ==========================================
    // UNIVERSAL DATA FETCHING SYSTEM
    // ==========================================
    
    // REPLACE the existing fetchTemplateData function with this:

    /**
     * Fetch template-specific data using the new TemplateService
     * Falls back to universal fetcher if no specific method exists
     * 
     * @param {string} templateKey - The template identifier
     * @param {string} studentName - The selected student name
     * @returns {Object} - Object with placeholder data
     */
    async function fetchTemplateData(templateKey, studentName) {
      try {
        console.log(`🔍 Fetching template data for: ${templateKey}, student: ${studentName}`);
        
        // Use the new template service (this will check for specific methods first)
        const templateData = await templateService.getTemplateData(templateKey, studentName);
        
        console.log('📋 Template data received:', templateData);
        return templateData;
        
      } catch (error) {
        console.error('❌ Template data fetch failed:', error);
        // Fallback to universal system
        return await templateService.universalTemplateFetch(templateKey, studentName);
      }
    }

    // Load templates on page load
    loadTemplateOptions();

    // 🚀 Initialize the new services
    const dataFetcher = new DataFetcher({ debug: true });
    const studentService = new StudentService(dataFetcher);
    const templateService = new TemplateService(studentService, dataFetcher);

    console.log('✅ Services initialized successfully');
  </script>
  
  <!-- Script to handle template text generation and manual input fields -->
  <script>
    
    const templateSelect = document.getElementById('templateSelect');
    const textArea = document.getElementById('templateText');
    const studentInput = document.getElementById('studentInput');
    const manualInputsContainer = document.getElementById('manualInputs');

    let originalTemplate = "";  // Add this at top-level near lastValue

    function generateInputFields(keys, valueMap) {
      manualInputsContainer.querySelectorAll('input, label.placeholder-label').forEach(el => el.remove());

      const currentValues = { ...valueMap };  // Clone value map

      keys.forEach(ph => {
        const label = document.createElement('label');
        label.textContent = ph;
        label.className = 'placeholder-label';
        label.style.display = 'block';
        label.style.marginTop = '10px';
        label.htmlFor = `input_${ph}`;

        const input = document.createElement('input');
        input.type = 'text';
        input.id = `input_${ph}`;
        input.placeholder = `Enter ${ph}...`;
        input.value = currentValues[ph] || "";
        input.style.width = '100%';

        input.addEventListener('input', () => {
          currentValues[ph] = input.value;

          // 🎨 Rebuild with SMART placeholder display
          let rebuilt = originalTemplate;

          keys.forEach(k => {
            const val = currentValues[k] || "";
            const regex = new RegExp(`\\[${k}\\]`, "g");
            
            if (val.trim() !== '') {
              // Show user input: [UserInput]
              rebuilt = rebuilt.replace(regex, `[${val}]`);
            } else {
              // Show descriptive placeholder: [PLACEHOLDER_NAME]
              rebuilt = rebuilt.replace(regex, `[${k}]`);
            }
          });

          textArea.value = rebuilt;
          lastValue = rebuilt;
        });

        manualInputsContainer.appendChild(label);
        manualInputsContainer.appendChild(input);
      });
    }


    // Update the template text area whenever template or student selection changes
    async function updateTemplateText() {
      const templateKey = templateSelect.value;
      if (!templateKey) {
        textArea.value = "";
        return;
      }

      try {
        const res = await fetch(`../handlers/generateTemplate.php?template=${templateKey}`);
        const result = await res.json();

        if (result.error) {
          textArea.value = "Template not found.";
          return;
        }

    // Initial message
    originalTemplate = result.text; // Keep original with [NAME], [DATE] etc.
    let text = result.text; // Working copy for display
    const placeholders = result.placeholders;

    // Get student name but DON'T replace in text yet
    const studentName = studentInput.value.trim();

    // 🎯 Use template service for intelligent auto-filling
    if (studentName) {
      try {
        const autoFetchData = await fetchTemplateData(templateKey, studentName);
        
        console.log('📊 Auto-fetched data:', autoFetchData);
        
        // Store the auto-fetched values in placeholders object
        // but DON'T replace them in the text yet
        Object.keys(autoFetchData).forEach(key => {
          const value = autoFetchData[key];
          if (value !== undefined && value !== null && value !== '') {
            placeholders[key] = value; // Store for later use
            console.log(`✅ Auto-filled ${key}: ${value}`);
          }
        });
        
        // 📋 Show summary of what was auto-filled vs manual
        const autoFilledKeys = Object.keys(autoFetchData).filter(key => 
          autoFetchData[key] !== undefined && autoFetchData[key] !== null && autoFetchData[key] !== ''
        );
        
        const manualKeys = Object.keys(placeholders).filter(key => 
          !autoFilledKeys.includes(key)
        );
        
        if (autoFilledKeys.length > 0) {
          console.log(`🤖 Auto-filled placeholders: ${autoFilledKeys.join(', ')}`);
        }
        
        if (manualKeys.length > 0) {
          console.log(`✏️ Manual placeholders: ${manualKeys.join(', ')}`);
        }
        
      } catch (error) {
        console.error("❌ Error auto-fetching template data:", error);
        // Fallback logic can go here if needed
      }
    }

    // 🎨 Create SMART VISUAL preview with dynamic placeholder display
    // Shows filled values OR descriptive placeholders when empty
    let displayText = originalTemplate;
    Object.keys(placeholders).forEach(key => {
      const value = placeholders[key];
      
      if (value && value.trim() !== '') {
        // Show the filled value: [StudentName] or [11.07.2025]
        displayText = displayText.replaceAll(`[${key}]`, `[${value}]`);
      } else {
        // Keep the descriptive placeholder: [STUDENTNAME] or [DATE]
        // This way user always sees what should go where
        displayText = displayText.replaceAll(`[${key}]`, `[${key}]`);
      }
    });

    // Update the textarea with the VISUAL preview
    textArea.value = displayText;
    lastValue = displayText; // for bracket protection
    generateInputFields(Object.keys(placeholders), placeholders);

      } catch (err) {
        console.error("Error generating template:", err);
        textArea.value = "Error loading template.";
      }
    }

    
    // Event listeners for changes
    templateSelect.addEventListener('change', updateTemplateText);
    // Update when student selection is finalized:
    studentInput.addEventListener('blur', updateTemplateText);
    studentInput.addEventListener('keydown', (e) => {
      if (e.key === "Enter") {
        // Small delay to allow selection to finalize
        setTimeout(updateTemplateText, 100);
      }
    });
    // Regenerate template when student is typed/changed
    studentInput.addEventListener('input', () => {
    if (templateSelect.value) updateTemplateText();
    });

    document.getElementById('copyBtn').onclick = function() {
      const textArea = document.getElementById('templateText');
      const rawText = textArea.value;

      // Remove square brackets, e.g., [Luna] → Luna
      const cleanedText = rawText.replace(/\[([^\]]*)\]/g, '$1');


      // Create a temporary textarea to copy clean text
      const temp = document.createElement('textarea');
      temp.value = cleanedText;
      document.body.appendChild(temp);
      temp.select();
      temp.setSelectionRange(0, 99999);

      try {
        const successful = document.execCommand('copy');
        if (successful) {
          alert('Text copied to clipboard!');
        } else {
          alert('Copy failed. Please copy manually.');
        }
      } catch (err) {
        alert('Copy not supported in this browser.');
      }

      document.body.removeChild(temp); // Clean up
    };
  </script>

  <!-- Prevent deletion of brackets in the template textarea -->
  <script>
    const templateTextarea = document.getElementById("templateText");
    let lastValue = templateTextarea.value;

    templateTextarea.addEventListener("input", function (e) {
      const currentValue = templateTextarea.value;

      // Count brackets in last and current value
      const count = (str, ch) => (str.match(new RegExp(`\\${ch}`, "g")) || []).length;

      const missingLeft = count(lastValue, "[") > count(currentValue, "[");
      const missingRight = count(lastValue, "]") > count(currentValue, "]");

      if (missingLeft || missingRight) {
        templateTextarea.value = lastValue; // revert change
        const msg = missingLeft && missingRight ? "`[` and `]`" : (missingLeft ? "`[`" : "`]`");
        alert("You can't delete " + msg + " symbols. They are needed for placeholder tracking.");
      } else {
        lastValue = currentValue;
      }
    });
</script>


</body>
</html>