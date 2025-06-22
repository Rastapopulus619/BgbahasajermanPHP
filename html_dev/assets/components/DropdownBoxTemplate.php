<div class="dropdownbox-wrapper">
  <label for="<?= $inputId ?>"><?= $label ?></label>
  <div class="input-wrapper">
    <input
      type="text"
      id="<?= $inputId ?>"
      autocomplete="off"
      placeholder="<?= $placeholder ?>"
    />
    <div class="dropdown-button-area"></div>
  </div>
  <div id="<?= $dropdownId ?>" class="dropdownbox-list hidden"></div>
  <div class="status-line">
    <span id="<?= $statusId ?>" class="status"></span>
    <span id="<?= $errorId ?>" class="input-error"></span>
  </div>
  <button id="<?= $buttonId ?>" disabled><?= $buttonLabel ?></button>
</div>
