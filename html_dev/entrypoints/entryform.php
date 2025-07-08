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
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    .form-section {
      margin: 15px 0;
    }
    
    .form-section label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .form-section input, .form-section select {
      width: 300px;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    
    #addStudentBtn {
      background: #007bff;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    
    #addStudentBtn:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
    
    #addStudentBtn:hover:not(:disabled) {
      background: #0056b3;
    }
    
    /* Table responsive styles */
    #studentsTableContainer {
      background: white;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    #studentsTable th {
      font-weight: 600;
      color: #495057;
      font-size: 0.85em;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    #studentsTable tbody tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    
    #studentsTable tbody tr.highlighted {
      background-color: #fff3cd !important;
      border: 2px solid #ffc107 !important;
    }
    
    #refreshTableBtn:hover {
      background: #218838 !important;
    }
    
    /* Enhanced table styling for new column order */
    #studentsTable td:first-child {
      font-weight: 600;
      color: #2c3e50;
    }
    
    #studentsTable td:last-child {
      font-weight: bold;
      color: #6c757d;
      font-size: 0.9em;
    }
  </style>
</head>
<body>

  <h1>Tambahkan Murid Baru</h1>
  <p>Harus ngisi semua untuk bisa pencet tombol</p>

  <br>

<!-- Two-column layout container (50:50) -->
<div style="display: flex; gap: 20px; margin-top: 30px; align-items: flex-start; min-height: 600px;">
  
  <!-- Left column: New Student Form (50%) -->
  <div style="flex: 1; min-width: 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
    <h3>Add New Student</h3>

    <!-- Move the new student form here -->
    <div class="form-section">
      <label for="titleSelect">Title:</label>
      <select id="titleSelect" name="title">
        <option value="">Select Title...</option>
        <option value="Herr">Herr</option>
        <option value="Frau">Frau</option>
        <option value="none">None</option>
      </select>
    </div>

    <div class="form-section">
      <label for="newStudentName">New Student Name:</label>
      <input type="text" id="newStudentName" name="studentName" placeholder="Enter new student name...">
      <div id="nameValidation" style="color: red; font-size: 0.9em; margin-top: 4px;"></div>
    </div>

    <div class="form-section">
      <label for="levelSelect">Level:</label>
      <select id="levelSelect" name="level">
        <option value="">Select Level...</option>
        <!-- Will be populated by JavaScript -->
      </select>
    </div>

    <div class="form-section">
      <button id="addStudentBtn" disabled>Add New Student</button>
      <div id="insertStatus" style="margin-top: 10px; font-weight: bold;"></div>
    </div>
  </div>
  
  <!-- Right column: Student Picker and Data Table (50%) -->
  <div style="flex: 1; min-width: 0; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
    <h3>Student Database Viewer</h3>
    
    <!-- Student Picker Section - Using Extended Dropdown -->
    <div class="form-section">
      <?php
        $inputId = 'viewerStudentInput';
        $dropdownId = 'viewerStudentDropdown';
        $statusId = 'viewerStudentStatus';
        $errorId = 'viewerStudentError';
        $buttonId = 'viewerStudentShowBtn';
        $label = 'Select Student to Highlight';
        $placeholder = 'Type student name...';
        $autoHighlight = true;
        $highlightFunction = 'highlightStudent';
        include '../assets/components/DropdownBox_StudentExtended.php';
      ?>
    </div>
    
    <!-- Students Data Table -->
    <div class="form-section" style="margin-top: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h4>All Students</h4>
        <button id="refreshTableBtn" style="padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer;">🔄 Refresh</button>
      </div>
      
      <div id="studentsTableContainer" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px;">
        <table id="studentsTable" style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
          <thead style="background: #f8f9fa; position: sticky; top: 0;">
            <tr>
              <th style="padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;">Name</th>
              <th style="padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;">Number</th>
              <th style="padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;">Title</th>
              <th style="padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;">ID</th>
            </tr>
          </thead>
          <tbody id="studentsTableBody">
            <tr>
              <td colspan="4" style="padding: 20px; text-align: center; color: #666;">Loading students...</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div id="tableStatus" style="margin-top: 10px; font-size: 0.9em; color: #666;"></div>
    </div>
  </div>
</div>

<br><br><br>

<!-- New Student Entry JavaScript -->
<script>
  // Global variables
  let existingStudents = [];
  let availableLevels = [];
  let allStudents = [];
  let selectedStudentForHighlight = '';
  
  // Initialize the universal data fetcher
  class DataFetcher {
    async executeQuery(queryConfig) {
      try {
        console.log('Sending query:', queryConfig);
        
        const response = await fetch('../handlers/dataFetcher.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ query: queryConfig })
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get the raw text first to debug
        const rawText = await response.text();
        console.log('Raw response:', rawText);
        
        // Try to parse JSON
        let result;
        try {
          result = JSON.parse(rawText);
        } catch (parseError) {
          console.error('JSON parse error:', parseError);
          console.error('Raw response that failed to parse:', rawText);
          throw new Error(`Server returned invalid JSON: ${parseError.message}`);
        }
        
        if (!result.success) {
          throw new Error(result.error || 'Unknown error occurred');
        }
        
        return result.data;
      } catch (error) {
        console.error('DataFetcher error:', error);
        throw error;
      }
    }
  }
  const dataFetcher = new DataFetcher();

  // Load existing students and levels when page loads
  document.addEventListener('DOMContentLoaded', async () => {
    try {
      // Load existing student names
      const studentResult = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT Name FROM students',
        return_type: 'array'
      });
      
      existingStudents = studentResult.map(row => row.Name.toLowerCase());
      console.log('Loaded existing students:', existingStudents);

      // Load available levels
      const levelResult = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT Level FROM levels',
        return_type: 'array'
      });
      
      availableLevels = levelResult;
      populateLevelDropdown(availableLevels);
      console.log('Loaded levels:', availableLevels);

    } catch (error) {
      console.error('Error loading initial data:', error);
      document.getElementById('insertStatus').innerHTML = '<span style="color: red;">Error loading initial data</span>';
    }
  });

  // Populate level dropdown
  function populateLevelDropdown(levels) {
    const levelSelect = document.getElementById('levelSelect');
    
    levels.forEach(levelRow => {
      const option = document.createElement('option');
      option.value = levelRow.Level;
      option.textContent = levelRow.Level;
      levelSelect.appendChild(option);
    });
  }

  // Validate student name in real-time
  document.getElementById('newStudentName').addEventListener('input', (e) => {
    const name = e.target.value.trim();
    const validation = document.getElementById('nameValidation');
    
    if (name === '') {
      validation.textContent = '';
      checkFormValidity();
      return;
    }
    
    if (existingStudents.includes(name.toLowerCase())) {
      validation.textContent = 'Student already exists!';
      validation.style.color = 'red';
    } else {
      validation.textContent = 'Student name available';
      validation.style.color = 'green';
    }
    
    checkFormValidity();
  });

  // Check if all form fields are valid
  function checkFormValidity() {
    const name = document.getElementById('newStudentName').value.trim();
    const title = document.getElementById('titleSelect').value;
    const level = document.getElementById('levelSelect').value;
    const button = document.getElementById('addStudentBtn');
    
    const nameValid = name !== '' && !existingStudents.includes(name.toLowerCase());
    const titleValid = title !== '';
    const levelValid = level !== '';
    
    button.disabled = !(nameValid && titleValid && levelValid);
  }

  // Add event listeners for form validation
  document.getElementById('titleSelect').addEventListener('change', checkFormValidity);
  document.getElementById('levelSelect').addEventListener('change', checkFormValidity);

  // Handle new student insertion
  document.getElementById('addStudentBtn').addEventListener('click', async () => {
    const button = document.getElementById('addStudentBtn');
    const status = document.getElementById('insertStatus');
    
    button.disabled = true;
    status.innerHTML = '<span style="color: blue;">Adding student...</span>';
    
    try {
      const name = document.getElementById('newStudentName').value.trim();
      const title = document.getElementById('titleSelect').value;
      const level = document.getElementById('levelSelect').value;
      
      // Step 1: Get next StudentNumber and StudentID
      console.log('Getting next StudentNumber...');
      const numberResult = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT MAX(StudentNumber)+1 as NextNumber FROM students',
        return_type: 'single'
      });
      console.log('Number result:', numberResult);
      
      console.log('Getting next StudentID...');
      const idResult = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT MAX(StudentID)+1 as NextID FROM students',
        return_type: 'single'
      });
      console.log('ID result:', idResult);
      
      const studentNumber = numberResult.NextNumber || 1;
      const studentID = idResult.NextID || 1;
      
      console.log('Final values - StudentNumber:', studentNumber, 'StudentID:', studentID);
      console.log('Insertion values - Name:', name, 'Title:', title, 'TitleValue:', title === 'none' ? null : title);
      
      // Step 2: Insert new student
      const titleValue = title === 'none' ? null : title;
      
      console.log('Attempting to insert student with params:', [studentID, studentNumber, name, titleValue]);
      
      const insertResult = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'INSERT INTO students (StudentID, StudentNumber, Name, Title) VALUES (?, ?, ?, ?)',
        params: [studentID, studentNumber, name, titleValue]
      });
      
      console.log('Insert result:', insertResult);
      
      status.innerHTML = '<span style="color: blue;">Student inserted, updating level...</span>';
      
      // Step 3: Wait briefly, then update level
      setTimeout(async () => {
        try {
          await dataFetcher.executeQuery({
            type: 'simple',
            sql: 'UPDATE studydetails SET Level = ? WHERE StudentID = ?',
            params: [level, studentID]
          });
          
          // Success!
          status.innerHTML = '<span style="color: green;">✅ Student successfully added!</span>';
          
          // Add to existing students list and clear form
          existingStudents.push(name.toLowerCase());
          clearForm();
          
          setTimeout(() => {
            status.innerHTML = '';
          }, 3000);
          
        } catch (error) {
          console.error('Error updating level:', error);
          status.innerHTML = '<span style="color: orange;">⚠️ Student added but level update failed</span>';
        }
      }, 500);
      
    } catch (error) {
      console.error('Error adding student:', error);
      status.innerHTML = '<span style="color: red;">❌ Error adding student: ' + error.message + '</span>';
      button.disabled = false;
    }
  });

  // Clear the form
  function clearForm() {
    document.getElementById('newStudentName').value = '';
    document.getElementById('titleSelect').value = '';
    document.getElementById('levelSelect').value = '';
    document.getElementById('nameValidation').textContent = '';
    checkFormValidity();
    
    // Refresh the students table after adding a new student
    loadStudentsTable();
  }

  // ==========================================
  // TABLE FUNCTIONALITY (simplified)
  // ==========================================

  // Highlight selected student in table (global for auto-system)
  window.highlightStudent = function(studentName, studentData) {
    console.log('highlightStudent called with:', studentName, studentData);
    selectedStudentForHighlight = studentName;
    
    // Remove previous highlights
    document.querySelectorAll('#studentsTableBody tr').forEach(row => {
      row.classList.remove('highlighted');
      row.style.backgroundColor = '';
    });
    
    if (!studentName) {
      console.log('No student name provided, clearing highlights');
      return;
    }
    
    // If studentData is not provided, find it in allStudents
    let student = studentData;
    if (!student) {
      student = allStudents.find(s => s.Name === studentName);
      console.log('Found student data:', student);
    }
    
    if (!student) {
      console.log('Student not found in table data:', studentName);
      return;
    }
    
    // Find and highlight the student
    const row = document.getElementById(`student-row-${student.StudentID}`);
    if (row) {
      row.classList.add('highlighted');
      row.style.backgroundColor = '#fff3cd';
      row.style.border = '2px solid #ffc107';
      
      // Scroll to the highlighted row
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      
      console.log('Successfully highlighted student:', studentName, 'ID:', student.StudentID);
    } else {
      console.log('Table row not found for StudentID:', student.StudentID);
    }
  };
  
  // Load and display students table
  async function loadStudentsTable() {
    const tableBody = document.getElementById('studentsTableBody');
    const tableStatus = document.getElementById('tableStatus');
    
    try {
      tableStatus.textContent = 'Loading students...';
      
      const students = await dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT StudentID, StudentNumber, Name, Title FROM students ORDER BY StudentID DESC',
        return_type: 'multiple'
      });
      
            // Make students data globally available
      allStudents = students;
      window.allStudents = students; // Also available to dropdown system
      
      console.log('Loaded students data:', students.length, 'students');
      
      if (students.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #666;">No students found</td></tr>';
        tableStatus.textContent = 'No students in database';
        return;
      }
      
      tableBody.innerHTML = '';
      
      students.forEach(student => {
        const row = document.createElement('tr');
        row.id = `student-row-${student.StudentID}`;
        row.style.borderBottom = '1px solid #eee';
        row.style.transition = 'background-color 0.2s';
        
        row.addEventListener('mouseenter', () => {
          if (!row.classList.contains('highlighted')) {
            row.style.backgroundColor = '#f8f9fa';
          }
        });
        
        row.addEventListener('mouseleave', () => {
          if (!row.classList.contains('highlighted')) {
            row.style.backgroundColor = '';
          }
        });
        
        row.innerHTML = `
          <td style="padding: 8px; font-weight: 500;">${student.Name}</td>
          <td style="padding: 8px;">${student.StudentNumber || ''}</td>
          <td style="padding: 8px;">${student.Title || ''}</td>
          <td style="padding: 8px; font-weight: bold;">${student.StudentID}</td>
        `;
        
        tableBody.appendChild(row);
      });
      
            tableStatus.textContent = `${students.length} students loaded`;
      
      console.log('Table populated with', students.length, 'students');
      console.log('Sample student data:', students[0]);
      
      // Re-highlight if there was a selected student
      if (selectedStudentForHighlight) {
        const student = allStudents.find(s => s.Name === selectedStudentForHighlight);
        if (student) {
          console.log('Re-highlighting selected student:', selectedStudentForHighlight);
          highlightStudent(selectedStudentForHighlight, student);
        }
      }
      
    } catch (error) {
      console.error('Error loading students table:', error);
      tableBody.innerHTML = '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #dc3545;">Error loading students</td></tr>';
      tableStatus.textContent = 'Error: ' + error.message;
    }
  }
  
  // Refresh table button
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('refreshTableBtn').addEventListener('click', () => {
      loadStudentsTable();
    });
    
    // Just load the table - dropdown is auto-configured
    setTimeout(() => {
      loadStudentsTable();
    }, 500);
  });
</script>

</body>
</html>