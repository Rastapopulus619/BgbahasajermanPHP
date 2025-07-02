<?php
// WATemplateCreator.php - WhatsApp Template Creator page

require_once '../config/db.php';  // Connect to the database:contentReference[oaicite:3]{index=3}

// (Optional) Fetch a default student record for testing (reuse index.php query):contentReference[oaicite:4]{index=4}
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
  
  <!-- Template selection combobox -->
  <label for="templateSelect"><strong>Pilih Template:</strong></label>
  <select id="templateSelect">
    <option value="">-- Choose Template --</option>
    <option value="reminder">Reminder Paket Habis</option>
    <option value="thanks">Terima Kasih untuk Pembayaran</option>
    <option value="pricelist">Pricelist A1</option>
  </select>
  
  <br><br>
  <!-- Student picker dropdown (reuse existing component) -->
  <label for="studentInput"><strong>Pilih Siswa:</strong></label><br>
  <?php include '../assets/components/DropdownBox_Student.php'; ?>  <!-- Student search box:contentReference[oaicite:5]{index=5} -->
  
  <br><br>
  <!-- Generated template text area and Copy button -->
  <label for="templateText"><strong>Generated Text:</strong></label><br>
  <textarea id="templateText" rows="6" cols="60" placeholder="Template text will appear here..."></textarea>
  <br>
  <button id="copyBtn">Copy to Clipboard</button>
  
  <!-- Script to handle template generation and clipboard copying -->
  <script type="module">
    import { setupDropdown } from '/assets/js/modularDropdownBox.js';
    // Initialize the student dropdown (fetch list as in entryform):contentReference[oaicite:6]{index=6}
    setupDropdown({
      inputId: 'studentInput',
      dropdownId: 'studentDropdown',
      statusId: 'studentStatus',
      errorId: 'studentError',
      buttonId: 'studentShowBtn',
      fetchUrl: '../handlers/fetchStudentList.php',
      minChars: 1
    });
  </script>
  <script>
    // Define template texts with placeholders
    const templates = {
      "reminder": "Halo [NAME], paket les Anda sudah *habis*. Silakan hubungi kami untuk perpanjangan paket.",
      "thanks":   "Halo [NAME], terima kasih atas *pembayaran* Anda. Semoga belajar Anda lancar!",
      "pricelist": "Halo [NAME], berikut pricelist terbaru untuk kursus A1. Silakan cek detail paketnya."
    };
    
    const templateSelect = document.getElementById('templateSelect');
    const textArea = document.getElementById('templateText');
    const studentInput = document.getElementById('studentInput');
    
    // Update the template text area whenever template or student selection changes
    function updateTemplateText() {
      const templateKey = templateSelect.value;
      if (!templateKey) {
        textArea.value = "";
        return;
      }
      let text = templates[templateKey] || "";
      // If a student is selected and placeholder exists, replace placeholders
      const studentName = studentInput.value.trim();
      if (studentName !== "") {
        text = text.replace(/\[NAME\]/g, studentName);
        // (Optional) If template includes [NUMBER], fetch student details via AJAX
        if (text.includes('[NUMBER]')) {
          fetch(`../handlers/fetchStudentDetails.php?name=${encodeURIComponent(studentName)}`)
            .then(res => res.json())
            .then(data => {
              if (data) {
                text = text.replace(/\[NUMBER\]/g, data.StudentNumber || "");
              }
              textArea.value = text;
            })
            .catch(err => {
              console.error("Error fetching student details:", err);
              textArea.value = text;
            });
          return;  // exit to wait for async replacement
        }
      }
      textArea.value = text;
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
  textArea.select();
  textArea.setSelectionRange(0, 99999); // For mobile devices

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

  // Optional: deselect the text afterward
  window.getSelection().removeAllRanges();
};
  </script>
</body>
</html>
