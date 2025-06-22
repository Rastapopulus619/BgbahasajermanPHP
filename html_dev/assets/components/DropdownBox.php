<div id="studentSelector">
  <label for="studentInput">Student Name</label><br>
  <div class="input-wrapper">
    <input
      id="studentInput"
      name="studentName"
      type="text"
      placeholder="Type to search..."
      autocomplete="off"
      disabled
    >
  </div>

  <div id="studentDropdown" class="dropdown hidden"></div>
  <div id="loadingMessage" style="color: gray; font-size: 0.9em;">Loading...</div>
  <div id="searchStatus" style="color: gray; font-size: 0.9em; margin-top: 4px;"></div>
  <div id="inputError" style="color: red; font-size: 0.9em; margin-top: 4px;"></div>
</div>
