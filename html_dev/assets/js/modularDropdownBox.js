// assets/js/modularDropdownBox.js
export function setupDropdown(config) {
  const {
    inputId,
    dropdownId,
    statusId,
    errorId,
    buttonId,
    fetchUrl,
    validPattern = /^[a-zA-ZÀ-ſ0-9.\- ]*$/,
    minChars = 0
  } = config;

  const input = document.getElementById(inputId);
  const dropdown = document.getElementById(dropdownId);
  const searchStatus = document.getElementById(statusId);
  const inputError = document.getElementById(errorId);
  const showCardBtn = buttonId ? document.getElementById(buttonId) : null;

  let ready = false;
  let selectedIndex = -1;
  let currentItems = [];

  const inputWrapper = input.parentElement; // reference to .input-wrapper

  // window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      ready = true;
      input.disabled = false;
      if (searchStatus) searchStatus.textContent = '';
    }, 300);
  // });

  input.addEventListener('focus', () => {
    if (!ready || input.value !== '') return;
    input.dispatchEvent(new Event('input'));
  });

  input.addEventListener('input', () => {
    if (!ready) return;
    const term = input.value;

    if (term.length < minChars) {
      dropdown.innerHTML = '';
      dropdown.classList.add('hidden');
      if (inputError) inputError.textContent = '';
      if (searchStatus) searchStatus.textContent = '';
      return;
    }

    if (!validPattern.test(term)) {
      if (inputError) inputError.textContent = "Only letters, numbers, umlauts, '.', '-' and spaces are allowed.";
      dropdown.classList.add('hidden');
      return;
    } else {
      if (inputError) inputError.textContent = '';
    }

    if (searchStatus) searchStatus.textContent = "Searching...";

    fetch(fetchUrl + '?term=' + encodeURIComponent(term))
      .then(res => res.json())
      .then(data => {
        dropdown.innerHTML = '';
        selectedIndex = -1;
        currentItems = [];

        const lowerTerm = term.toLowerCase();
        const matches = data.filter(name => name.toLowerCase().includes(lowerTerm));

        if (matches.length === 0) {
          const div = document.createElement('div');
          div.className = 'dropdownbox-item';
          div.textContent = 'No match found.';
          dropdown.appendChild(div);
          dropdown.classList.remove('hidden');
          if (searchStatus) searchStatus.textContent = '';
          return;
        }

        matches.forEach((name) => {
          const div = document.createElement('div');
          div.className = 'dropdownbox-item';
          div.textContent = name;
          div.onclick = () => {
            input.value = name;
            dropdown.classList.add('hidden');
            if (searchStatus) searchStatus.textContent = '';
            if (showCardBtn) showCardBtn.disabled = false;
          };
          dropdown.appendChild(div);
          currentItems.push(div);
        });

        dropdown.classList.remove('hidden');
        if (searchStatus) searchStatus.textContent = '';
      })
      .catch(err => {
        console.error(err);
        if (searchStatus) searchStatus.textContent = "Error fetching list.";
      });

    if (showCardBtn) showCardBtn.disabled = (term.trim() === '');
  });

  input.addEventListener('keydown', (e) => {
    if (dropdown.classList.contains('hidden')) {
      if (e.key === 'ArrowDown') {
        input.dispatchEvent(new Event('input'));
        return;
      }
    }

    if (currentItems.length === 0) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (selectedIndex < currentItems.length - 1) selectedIndex++;
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (selectedIndex > 0) selectedIndex--;
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0) currentItems[selectedIndex].click();
    }

    currentItems.forEach((item, index) => {
      if (index === selectedIndex) {
        item.classList.add('selected');
        item.scrollIntoView({ block: 'nearest' });
      } else {
        item.classList.remove('selected');
      }
    });
  });

  document.addEventListener('click', (e) => {
    const wrapper = input.closest('.dropdownbox-wrapper');
    if (!wrapper || !wrapper.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });

  // Use entire input-wrapper area for triangle click (including ::after)
  if (inputWrapper) {
    inputWrapper.addEventListener('click', (e) => {
      if (e.target !== input) {
        e.preventDefault();
        e.stopPropagation();

        if (!ready) return;

        if (!dropdown.classList.contains('hidden')) {
          dropdown.classList.add('hidden');
          if (searchStatus) searchStatus.textContent = '';
          return;
        }

        input.focus();
        selectedIndex = -1;
        currentItems = [];
        if (searchStatus) searchStatus.textContent = "Loading full list...";
        input.value = '';

        fetch(fetchUrl + '?term=')
          .then(res => res.json())
          .then(data => {
            dropdown.innerHTML = '';
            if (data.length === 0) {
              const div = document.createElement('div');
              div.className = 'dropdownbox-item';
              div.textContent = 'No results.';
              dropdown.appendChild(div);
              dropdown.classList.remove('hidden');
              if (searchStatus) searchStatus.textContent = '';
              return;
            }

            data.forEach((name) => {
              const div = document.createElement('div');
              div.className = 'dropdownbox-item';
              div.textContent = name;
              div.onclick = () => {
                input.value = name;
                dropdown.classList.add('hidden');
                if (searchStatus) searchStatus.textContent = '';
                if (showCardBtn) showCardBtn.disabled = false;
              };
              dropdown.appendChild(div);
              currentItems.push(div);
            });

            dropdown.classList.remove('hidden');
            if (searchStatus) searchStatus.textContent = '';
          })
          .catch(err => {
            console.error(err);
            if (searchStatus) searchStatus.textContent = "Error loading list.";
          });
      }
    });
  }
}
