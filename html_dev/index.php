<?php
echo "<h1>Hello, World from new PHP!</h1>";

// Database config
$host = 'mysql-container'; // Or your actual MySQL hostname/IP
$user = 'rasta';
$password = 'Burungnuri1212';
$database = 'bgbahasajerman';

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

// Query
$sql = "SELECT * FROM students WHERE StudentID = 5";
$result = $conn->query($sql);

$studentName = '';
$studentID = '';
$studentNumber = '';
$title = '';

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentID = $row['StudentID'];
    $studentNumber = $row['StudentNumber'];
    $studentName = $row['Name'];
    $title = $row['Title'];

    echo "<p><strong>Student Found:</strong></p><ul>";
    echo "<li><strong>StudentID:</strong> $studentID</li>";
    echo "<li><strong>StudentNumber:</strong> $studentNumber</li>";
    echo "<li><strong>Name:</strong> $studentName</li>";
    echo "<li><strong>Title:</strong> $title</li>";
    echo "</ul>";
} else {
    echo "<p>No student found with ID 5.</p>";
}

$conn->close();
?>

<!-- HTML FORM STARTS HERE -->
<?php
// Escape the value for safety
$escapedName = htmlspecialchars($studentName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pembuatan Kartu Les</title>
  <!-- you can paste your full <style> block here or keep it external -->
<style>
    body {
      font-family: sans-serif;
      margin: 0;
      padding: 2em;
      transition: background-color 0.3s;
    }

    h1 {
      font-size: 1.8em;
      margin-bottom: 1em;
      text-align: left;
    }

    #formWrapper {
      max-width: 720px;
      margin: auto;
      padding: 1em;
      border-radius: 8px;
      transition: background-color 0.3s;
    }

    .form-grid {
      display: grid;
      grid-template-columns: auto 1fr auto 1fr;
      gap: 0.6em 1em;
      align-items: center;
    }

    .form-grid label {
      font-weight: bold;
      text-align: right;
    }

    .form-grid input.short,
    .form-grid select.short,
    .form-grid textarea {
      width: 100%;
    }

    .dynamic-section {
      margin: 1.5em 0;
    }

    #tablesContainer {
      background-color: #f0f0f0;
      border-radius: 6px;
      padding: 1em;
      transition: background-color 0.3s;
    }

    .slots-control {
      border: 1px solid #ccc;
      margin-bottom: 1.5em;
      padding: 1em;
      border-radius: 6px;
      transition: background-color 0.3s;
    }

    .td-entries {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1em;
    }

    .td-group {
      border: 1px dashed #999;
      padding: 1em;
      background: #fff;
      transition: background-color 0.3s;
    }

    .replacement {
      display: none;
    }

    .button {
      padding: 0.6em 1.2em;
      font-weight: bold;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s;
    }
	#hariContainer select {
		display: block;
  		margin-bottom: 0.4em;
	}

  </style>
</head>
<body>
  <div id="formWrapper">
    <h1>Pembuatan Kartu Les</h1>

        <!-- Redirect Button to New Entry Page -->
    <form action="/entrypoints/entryform.php" method="get">
      <button type="submit">Start New Entry</button>
    </form>

    <div class="form-grid" id="studentDataInputs">
      <label for="name">Nama Siswa</label>
      <input id="name" class="student-data short" data-key="Name" value="<?= $escapedName ?>">

      <label for="dauer">Dauer</label>
      <select id="dauer" class="student-data short" data-key="Dauer">
        <option>60MIN/UE</option>
        <option selected>90MIN/UE</option>
      </select>

      <label for="stufe">Stufe</label>
      <select id="stufeInput" class="student-data short" data-key="Stufe" onchange="updateColorTheme()">
        <option>A1</option>
        <option>A2</option>
        <option>B1</option>
        <option>B2</option>
        <option>Gespräch</option>
        <option>A1 Prüfungstraining</option>
        <option>A2 Prüfungstraining</option>
        <option>B1 Prüfungstraining</option>
        <option>B2 Prüfungstraining</option>
        <option>C1 Prüfungstraining</option>
        <option>C2 Prüfungstraining</option>
        <option>TestDaF Prüfungstraining</option>
      </select>

      <label for="intensitaet">Intensität</label>
      <input id="intensitaet" type="number" step="4" min="0" value="4" class="student-data short" data-key="Intensitaet">

      <label>Hari</label>
      <div id="hariContainer" class="student-data" data-key="Tage">
        <select onchange="handleComboSelect(this, 'hariContainer', ['Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'])">
          <option value="">Tambahkan Hari</option>
          <option>Mittwoch</option>
          <option>Donnerstag</option>
          <option>Freitag</option>
          <option>Samstag</option>
          <option>Sonntag</option>
        </select>
      </div>

      <label>Waktu</label>
      <div id="zeitContainer" class="student-data" data-key="Zeit">
        <select onchange="handleComboSelect(this, 'zeitContainer', [
          '07:00-08:00','07:00-08:30','08:00-09:00','08:30-10:00','09:00-10:00',
          '10:00-11:00','10:00-11:30','13:00-14:00','13:00-14:30','14:00-15:00',
          '14:30-16:00','15:00-16:00','16:00-17:30','16:00-17:00','17:00-18:00',
          '17:30-19:00','18:00-19:00','19:00-20:00'
        ])">
          <option value="">Tambahkan Waktu</option>
          <option>07:00-08:00</option>

          <option>07:00-08:30</option>
          <option>08:00-09:00</option>
          <option>08:30-10:00</option>
          <option>09:00-10:00</option>
          <option>10:00-11:00</option>
          <option>10:00-11:30</option>
          <option>13:00-14:00</option>
          <option>13:00-14:30</option>
          <option>14:00-15:00</option>
          <option>14:30-16:00</option>
          <option>15:00-16:00</option>
          <option>16:00-17:30</option>
          <option>16:00-17:00</option>
          <option>17:00-18:00</option>
          <option>17:30-19:00</option>
          <option>18:00-19:00</option>
          <option>19:00-20:00</option>
        </select>
      </div>
    </div>

    <div class="dynamic-section">
      <label>Berapa banyak pasangan Separator + Table?</label>
      <input type="number" id="sectionCount" min="1" value="1" oninput="updateSections()">
    </div>

    <div id="tablesContainer"></div>

    <button class="button" onclick="generateHtml()">Buat File HTML</button>
  </div>

  <script>
    const germanMonths = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];

    function formatGermanDate(dateStr) {
      const date = new Date(dateStr);
      if (isNaN(date)) return '';
      return `${String(date.getDate()).padStart(2, '0')} ${germanMonths[date.getMonth()]} ${date.getFullYear()}`;
    }
    const colors = {
      A1:       { background: "rgb(252, 162, 162)", card: "rgb(255, 106, 106)", sections: "rgb(238, 75, 75)" },
      A2:       { background: "rgb(187, 225, 206)", card: "rgb(106, 241, 169)", sections: "rgb(0, 213, 110)" },
      B1:       { background: "rgb(243, 208, 169)", card: "rgb(249, 180, 101)", sections: "rgb(242, 133, 24)" },
      B2:       { background: "rgb(178, 194, 238)", card: "rgb(102, 137, 236)", sections: "rgb(13, 65, 208)" },
      Gespräch: { background: "rgb(178, 194, 238)", card: "rgb(102, 137, 236)", sections: "rgb(13, 65, 208)" }
    };

    function normalizeStufe(value) {
      if (!value) return '';
      value = value.trim();
      if (value.includes("A1")) return "A1";
      if (value.includes("A2")) return "A2";
      if (value.includes("B1")) return "B1";
      if (value.includes("B2")) return "B2";
      if (value.includes("C1") || value.includes("C2") || value.includes("Gespräch") || value.includes("DaF")) return "Gespräch";
      return "";
    }

    function updateColorTheme() {
      const key = normalizeStufe(document.getElementById("stufeInput").value);
      const theme = colors[key];
      if (!theme) return;
      document.body.style.backgroundColor = theme.sections;
      document.getElementById("formWrapper").style.backgroundColor = theme.sections;
      document.getElementById("tablesContainer").style.backgroundColor = theme.card;
      document.querySelectorAll(".slots-control").forEach(el => el.style.backgroundColor = theme.card);
      document.querySelectorAll(".td-group").forEach(el => el.style.backgroundColor = theme.card);
    }
	  
    function handleComboSelect(select, containerId, options) {
      const container = document.getElementById(containerId);
      const selects = container.querySelectorAll('select');
      const last = selects[selects.length - 1];
      if (select === last && select.value !== '') {
        const newSelect = document.createElement('select');
        newSelect.innerHTML = `<option value="">Tambahkan ${containerId.includes("hari") ? "Hari" : "Waktu"}</option>` +
          options.map(opt => `<option>${opt}</option>`).join('');
        newSelect.onchange = () => handleComboSelect(newSelect, containerId, options);
        container.appendChild(newSelect);
      } else if (select.value === '') {
        container.removeChild(select);
      }
    }

    function updateSections() {
      const count = parseInt(document.getElementById('sectionCount').value || '0');
      const container = document.getElementById('tablesContainer');
      container.innerHTML = '';
      for (let i = 0; i < count; i++) {
        const wrapper = document.createElement('div');
        wrapper.className = 'slots-control';
        wrapper.innerHTML = `
          <label>Judul Paket ${i + 1}</label>
          <input type="text" class="section-title" value=""><br><br>
          <label>Jumlah kolom (TD)</label>
          <input type="number" class="td-count" value="4" min="1" onchange="renderTDInputs(this)">
          <div class="td-entries"></div>`;
        container.appendChild(wrapper);
        renderTDInputs(wrapper.querySelector('.td-count'));
      }
      updateColorTheme();
    }

    function renderTDInputs(input) {
      const count = parseInt(input.value);
      const container = input.parentElement.querySelector('.td-entries');
      container.innerHTML = '';
      for (let i = 0; i < count; i++) {
        const group = document.createElement('div');
        group.className = 'td-group';
        group.innerHTML = `
          <label>TD ${i + 1} Tanggal:</label>
          <input type="date" class="td-date"><br>
          <label><input type="checkbox" class="td-attended"> Attended</label><br>
          <label><input type="checkbox" class="td-diganti"> Diganti</label>
          <input type="date" class="replacement td-replace-date">`;
        const diganti = group.querySelector('.td-diganti');
        const replacement = group.querySelector('.td-replace-date');
        diganti.addEventListener('change', () => {
          replacement.style.display = diganti.checked ? 'inline-block' : 'none';
        });
        container.appendChild(group);
      }
      updateColorTheme();
    }

function generateHtml() {
      const nama = document.getElementById('name').value.trim().replace(/\s+/g, '_');
      const now = new Date();
      const pad = n => n.toString().padStart(2, '0');
      const timestamp = `${pad(now.getDate())}${pad(now.getMonth() + 1)}${now.getFullYear()}_${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
      const filename = `${timestamp}_${nama}_Zahlungsbestätigung.html`;

      let html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Karte</title>
      <link rel="stylesheet" href="http://bgbj-php:80/assets/styles.css"></head><body>
      <div class="cardWrapper">
        <div class="cardBox" id="cardBox">
      <div class="titleBox" id="titleBox">Deutsch Unterricht</div>
      <div class="StudentDataBox"><table class="std_Table">`;

      html += `<tr><td class="StudentTableTitleCell">NAME </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell">${document.getElementById('name').value}</td>`;
      html += `<td class="StudentTableTitleCell">DAUER </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell">${document.getElementById('dauer').value}</td></tr>`;

      html += `<tr><td class="StudentTableTitleCell">STUFE </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell" id="stufe">${document.getElementById('stufeInput').value}</td>`;
      html += `<td class="StudentTableTitleCell">INTENSITÄT </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell">${document.getElementById('intensitaet').value}</td></tr>`;

      const hari = Array.from(document.querySelectorAll('#hariContainer select')).map(s => s.value).filter(v => v).join('<br>');
      const waktu = Array.from(document.querySelectorAll('#zeitContainer select')).map(s => s.value).filter(v => v).join('<br>');
      html += `<tr><td class="StudentTableTitleCell">TAGE </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell" id="tageCell">${hari}</td>`;
      html += `<td class="StudentTableTitleCell">ZEIT </td><td class="StudentTableDotsCells">:</td><td class="StudentTableDataCell" id="zeitenCell">${waktu}</td></tr></table></div>`;

      document.querySelectorAll('.slots-control').forEach(section => {
        const title = section.querySelector('.section-title').value;
        const displayTitle = title.trim() !== "" ? title : "&nbsp;";
		html += `<div class="separatorBox">${displayTitle}</div><table class="slotsTable">`;


        const tds = section.querySelectorAll('.td-group');
        for (let i = 0; i < tds.length; i += 2) {
          html += `<tr>`;
          for (let j = i; j < i + 2 && j < tds.length; j++) {
            const td = tds[j];
            const date = formatGermanDate(td.querySelector('.td-date').value);
            const diganti = td.querySelector('.td-diganti').checked;
            const replace = formatGermanDate(td.querySelector('.td-replace-date').value);
            const attended = td.querySelector('.td-attended').checked;
            let content = date;
            if (diganti && replace) content += `<br><span class="lesPengganti">↪ ${replace}</span>`;
            html += `<td attended="${attended ? 'y' : 'n'}" diganti="${diganti ? 'y' : 'n'}">${content}</td>`;
          }
          html += `</tr>`;
        }
        html += `</table>`;
      });

      html += `</div></div><script>
        window.addEventListener("DOMContentLoaded", function () {
          var s = document.getElementById("stufe")?.textContent?.trim();
          var stufeScript = document.createElement('script');
          stufeScript.src = "http://bgbj-php:80/assets/script.js";
          document.body.appendChild(stufeScript);
        });
      <`+`/script></body></html>`;

      // Wrap this whole section at the end of generateHtml()
      fetch("download-png.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ html: html, width: 1200, height: 800 })
      })
      .then(response => {
        if (!response.ok) throw new Error("Failed to render image");
        return response.blob();
      })
      .then(blob => {
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = `${timestamp}_${nama}_Zahlungsbestaetigung.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
      })
      .catch(error => {
        alert("Error during rendering: " + error.message);
      });

      const DEBUG = true;
      if (DEBUG) {
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
      }

    }
	window.onload = () => {
      updateSections();
      updateColorTheme();
    };
  </script>
</body>
</html>
