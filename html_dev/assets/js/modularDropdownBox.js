export function setupDropdown(config) {
  const {
    inputId,
    dropdownId,
    statusId,
    errorId,
    fetchUrl,
    validPattern = /^[a-zA-ZÀ-ſ0-9.\- ]*$/,
    minChars = 0
  } = config;

  const input = document.getElementById(inputId);
  const dropdown = document.getElementById(dropdownId);
  const searchStatus = document.getElementById(statusId);
  const inputError = document.getElementById(errorId);

  let ready = false;
  let selectedIndex = -1;
  let currentItems = [];
  let lastFetchedNames = []; // Store the last fetched results

  const inputWrapper = input.parentElement;

  setTimeout(() => {
    ready = true;
    input.disabled = false;
    if (searchStatus) searchStatus.textContent = '';
  }, 300);

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
      input.classList.remove('input-invalid');
      if (searchStatus) searchStatus.textContent = '';
      return;
    }

    if (!validPattern.test(term)) {
      input.classList.add('input-invalid');
      dropdown.classList.add('hidden');
      return;
    } else {
      input.classList.remove('input-invalid');
    }

    if (searchStatus) searchStatus.textContent = "Searching...";

    fetch(fetchUrl + '?term=' + encodeURIComponent(term))
      .then(res => res.json())
      .then(data => {
        lastFetchedNames = data; // Save latest names

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
            input.classList.remove('input-invalid');
            triggerConfirmedInput(input.value);
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
  });

  input.addEventListener('keydown', (e) => {
    if (dropdown.classList.contains('hidden') && e.key === 'ArrowDown') {
      input.dispatchEvent(new Event('input'));
      return;
    }

    if (currentItems.length === 0 && e.key !== 'Enter') return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (selectedIndex < currentItems.length - 1) selectedIndex++;
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (selectedIndex > 0) selectedIndex--;
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0) {
        currentItems[selectedIndex].click();
      } else {
        const entered = input.value.trim();
        const lowerEntered = entered.toLowerCase();
        const valid = lastFetchedNames.some(name => name.toLowerCase() === lowerEntered);

        if (valid) {
          dropdown.classList.add('hidden');
          input.classList.remove('input-invalid');
          triggerConfirmedInput(entered);
        } else {
          input.classList.add('input-invalid');
        }
      }
    }

    currentItems.forEach((item, index) => {
      item.classList.toggle('selected', index === selectedIndex);
      if (index === selectedIndex) item.scrollIntoView({ block: 'nearest' });
    });
  });

  document.addEventListener('click', (e) => {
    const wrapper = input.closest('.dropdownbox-wrapper');
    if (!wrapper || !wrapper.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });

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
            lastFetchedNames = data;

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
                input.classList.remove('input-invalid');
                triggerConfirmedInput(input.value);
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

  function triggerConfirmedInput(value) {
    console.log('Confirmed input:', value);
    // TODO: Replace with logic to fetch DB data and populate labels
  }
}
