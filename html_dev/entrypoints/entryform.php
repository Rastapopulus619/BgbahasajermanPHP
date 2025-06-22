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
  <link rel="stylesheet" href="../assets/dropdownbox.css"> <!-- NEW -->
  <!-- <script src="../assets/dropdownbox.js" defer></script> -->
</head>
<body>

  <h1>Welcome to the New Entry Page</h1>
  <p>This will be the new control center for real-time DB interaction.</p>

  <br>
  <h2>Select Student</h2>



  <!-- Confirm Button -->
  <br>
  <button id="showCardBtn" disabled>Show Lesson Package</button>

  <!-- Placeholder for dynamically inserted lesson card -->
  <div id="lessonCardContainer" style="margin-top: 40px;"></div>



<!-- 🔽 DropdownBox component (modularized) -->
  <?php include '../assets/components/DropdownBox_Course.php'; ?>

  <!-- 🔽 DropdownBox component (modularized) -->
  <?php include '../assets/components/DropdownBox_Student.php'; ?>
  <!-- Card rendering logic -->
  <script>
    const input = document.getElementById('studentInput');
    const showCardBtn = document.getElementById('showCardBtn');
    const lessonCardContainer = document.getElementById('lessonCardContainer');
    const dropdown = document.getElementById('studentDropdown');

    // Show placeholder card
    showCardBtn.addEventListener('click', () => {
      const selectedStudent = input.value.trim();
      if (!selectedStudent) return;

      lessonCardContainer.innerHTML = `
        <div class="cardBox" id="cardBox">
          <div class="titleBox" id="titleBox">Deutsch Unterricht</div>
          <div class="StudentDataBox">Showing last package for <strong>${selectedStudent}</strong>...</div>
        </div>
      `;

      // Optional: Load script for colors/fonts (from card style project)
      const script = document.createElement('script');
      script.src = '../assets/script.js';
      document.body.appendChild(script);
    });
  </script>
  <script type="module">
    import { setupDropdown } from '/assets/js/modularDropdownBox.js';

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

  <!-- JS for DropdownBox (modularized) -->
</body>
</html>
