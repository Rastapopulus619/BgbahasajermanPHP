(() => {
  const input = document.getElementById('studentInput');
  const dropdown = document.getElementById('studentDropdown');
  const loadingMessage = document.getElementById('loadingMessage');
  const searchStatus = document.getElementById('searchStatus');
  const inputError = document.getElementById('inputError');
  const showCardBtn = document.getElementById('showCardBtn');

  let ready = false;
  let selectedIndex = -1;
  let currentItems = [];

  const validPattern = /^[a-zA-ZÀ-ſ.\- ]*$/;

  window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      ready = true;
      input.disabled = false;
      loadingMessage.style.display = 'none';
    }, 300);
  });

  input.addEventListener('focus', () => {
    if (!ready || input.value !== '') return;
    input.dispatchEvent(new Event('input'));
  });

  input.addEventListener('input', () => {
    if (!ready) return;
    const term = input.value;

    if (term === '') {
      dropdown.innerHTML = '';
      dropdown.classList.add('hidden');
      inputError.textContent = '';
      searchStatus.textContent = '';
      return;
    }

    if (!validPattern.test(term)) {
      inputError.textContent = "Only letters, umlauts, '.', '-' and spaces are allowed.";
      dropdown.classList.add('hidden');
      return;
    } else {
      inputError.textContent = '';
    }

    searchStatus.textContent = "Searching...";

    fetch('../handlers/fetchStudentList.php?term=' + encodeURIComponent(term))
      .then(res => res.json())
      .then(data => {
        dropdown.innerHTML = '';
        selectedIndex = -1;
        currentItems = [];

        const lowerTerm = term.toLowerCase();
        const matches = data.filter(name => name.toLowerCase().includes(lowerTerm));

        if (matches.length === 0) {
          const div = document.createElement('div');
          div.className = 'dropdown-item';
          div.textContent = 'No match found.';
          dropdown.appendChild(div);
          dropdown.classList.remove('hidden');
          searchStatus.textContent = '';
          return;
        }

        matches.forEach((name) => {
          const div = document.createElement('div');
          div.className = 'dropdown-item';
          div.textContent = name;
          div.onclick = () => {
            input.value = name;
            dropdown.classList.add('hidden');
            searchStatus.textContent = '';
            showCardBtn.disabled = false;
          };
          dropdown.appendChild(div);
          currentItems.push(div);
        });

        dropdown.classList.remove('hidden');
        searchStatus.textContent = '';
      })
      .catch(err => {
        console.error(err);
        searchStatus.textContent = "Error fetching student list.";
      });

    showCardBtn.disabled = (term.trim() === '');
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
    if (!document.getElementById('studentSelector').contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });

  const wrapper = document.querySelector('#studentSelector .input-wrapper');
  if (wrapper) {
    wrapper.addEventListener('click', (e) => {
      const wrapperRect = wrapper.getBoundingClientRect();
      const clickX = e.clientX - wrapperRect.left;

      // ▼ triangle area = last 30px
      if (clickX > wrapperRect.width - 30) {
  console.log('▼ triangle clicked');
        if (!dropdown.classList.contains('hidden')) {
          dropdown.classList.add('hidden');
          searchStatus.textContent = '';
          return;
        }

        input.focus();
        selectedIndex = -1;
        currentItems = [];
        searchStatus.textContent = "Loading full list...";
        input.value = '';

        fetch('../handlers/fetchStudentList.php?term=')
          .then(res => res.json())
          .then(data => {
            dropdown.innerHTML = '';
            if (data.length === 0) {
              const div = document.createElement('div');
              div.className = 'dropdown-item';
              div.textContent = 'No students found.';
              dropdown.appendChild(div);
              dropdown.classList.remove('hidden');
              searchStatus.textContent = '';
              return;
            }

            data.forEach((name) => {
              const div = document.createElement('div');
              div.className = 'dropdown-item';
              div.textContent = name;
              div.onclick = () => {
                input.value = name;
                dropdown.classList.add('hidden');
                searchStatus.textContent = '';
                showCardBtn.disabled = false;
              };
              dropdown.appendChild(div);
              currentItems.push(div);
            });

            dropdown.classList.remove('hidden');
            searchStatus.textContent = '';
          })
          .catch(err => {
            console.error(err);
            searchStatus.textContent = "Error fetching student list.";
          });
      }
    });
  }
})();
