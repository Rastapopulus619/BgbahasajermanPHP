/**
 * Extended Dropdown Box with built-in data loading and filtering
 * Extends the basic modularDropdownBox.js with common features
 */

export function setupDataDropdown(config) {
  const {
    inputId,
    dropdownId,
    statusId,
    errorId,
    buttonId,
    
    // Data source configuration
    dataSource = 'api', // 'api', 'static', 'dataFetcher'
    fetchUrl = null,
    dataFetcherQuery = null,
    staticData = [],
    
    // Display configuration
    displayField = 'name', // Field to display in dropdown
    valueField = 'name',   // Field to use as value
    filterFields = ['name'], // Fields to search in
    
    // Behavior configuration
    minChars = 1,
    showAllOnFocus = true,
    autoHighlight = false,
    highlightFunction = null, // Name of global function to call when item is selected
    
    // Validation
    validPattern = /^[a-zA-ZÀ-ſ.\- ]*$/,
    
    // Callbacks
    onSelect = null,
    onDataLoaded = null,
    onError = null
  } = config;

  const input = document.getElementById(inputId);
  const dropdown = document.getElementById(dropdownId);
  const status = document.getElementById(statusId);
  const errorEl = document.getElementById(errorId);
  const button = document.getElementById(buttonId);

  let allData = [];
  let currentItems = [];
  let selectedIndex = -1;
  let ready = false;

  // Initialize data loading
  async function loadData() {
    try {
      if (status) status.textContent = 'Loading...';
      
      let data = [];
      
      switch (dataSource) {
        case 'api':
          if (!fetchUrl) throw new Error('fetchUrl required for API data source');
          const response = await fetch(fetchUrl);
          data = await response.json();
          break;
          
        case 'dataFetcher':
          if (!dataFetcherQuery) throw new Error('dataFetcherQuery required for dataFetcher source');
          const dataFetcher = new DataFetcher();
          data = await dataFetcher.executeQuery(dataFetcherQuery);
          break;
          
        case 'static':
          data = staticData;
          break;
          
        default:
          throw new Error(`Unknown data source: ${dataSource}`);
      }
      
      allData = data;
      ready = true;
      
      if (input) input.disabled = false;
      if (status) status.textContent = '';
      if (onDataLoaded) onDataLoaded(data);
      
    } catch (error) {
      console.error('Error loading dropdown data:', error);
      if (errorEl) errorEl.textContent = 'Error loading data';
      if (onError) onError(error);
    }
  }

  // Filter and display matches
  function displayMatches(searchTerm = '') {
    if (!ready) return;
    
    dropdown.innerHTML = '';
    selectedIndex = -1;
    currentItems = [];
    
    let matches = allData;
    
    if (searchTerm && searchTerm.length >= minChars) {
      const lowerTerm = searchTerm.toLowerCase();
      matches = allData.filter(item => {
        return filterFields.some(field => {
          const value = getNestedValue(item, field);
          return value && value.toString().toLowerCase().includes(lowerTerm);
        });
      });
    }
    
    if (matches.length === 0) {
      const div = document.createElement('div');
      div.className = 'dropdownbox-item';
      div.textContent = searchTerm ? 'No matches found' : 'No data available';
      dropdown.appendChild(div);
      dropdown.classList.remove('hidden');
      return;
    }
    
    matches.forEach(item => {
      const div = document.createElement('div');
      div.className = 'dropdownbox-item';
      div.textContent = getNestedValue(item, displayField);
      
      div.onclick = () => {
        const value = getNestedValue(item, valueField);
        input.value = value;
        dropdown.classList.add('hidden');
        
        if (status) status.textContent = '';
        if (errorEl) errorEl.textContent = '';
        
        // Auto-highlight feature
        if (autoHighlight && config.highlightFunction) {
          const highlightFunc = window[config.highlightFunction];
          if (typeof highlightFunc === 'function') {
            highlightFunc(value, item);
          }
        }
        
        // Callback
        if (onSelect) onSelect(value, item);
        
        // Enable button if exists
        if (button) button.disabled = false;
      };
      
      dropdown.appendChild(div);
      currentItems.push(div);
    });
    
    dropdown.classList.remove('hidden');
  }

  // Helper function to get nested object values
  function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj);
  }

  // Input event handler
  input.addEventListener('input', () => {
    if (!ready) return;
    
    const term = input.value.trim();
    
    if (term === '') {
      if (showAllOnFocus) {
        displayMatches('');
      } else {
        dropdown.classList.add('hidden');
      }
      if (autoHighlight && highlightTarget) {
        highlightTarget('', null);
      }
      return;
    }
    
    if (!validPattern.test(term)) {
      if (errorEl) errorEl.textContent = "Invalid characters";
      dropdown.classList.add('hidden');
      return;
    } else {
      if (errorEl) errorEl.textContent = '';
    }
    
    if (status) status.textContent = 'Searching...';
    displayMatches(term);
    if (status) status.textContent = '';
  });

  // Focus handler
  input.addEventListener('focus', () => {
    if (showAllOnFocus && input.value.trim() === '') {
      displayMatches('');
    }
  });

  // Triangle button handler
  const inputWrapper = input.parentElement;
  if (inputWrapper) {
    inputWrapper.addEventListener('click', (e) => {
      if (e.target !== input) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!dropdown.classList.contains('hidden')) {
          dropdown.classList.add('hidden');
          return;
        }
        
        input.focus();
        input.value = '';
        displayMatches('');
      }
    });
  }

  // Hide dropdown when clicking outside
  document.addEventListener('click', (e) => {
    const wrapper = input.closest('.dropdownbox-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });

  // Keyboard navigation
  input.addEventListener('keydown', (e) => {
    if (dropdown.classList.contains('hidden') && e.key === 'ArrowDown') {
      input.dispatchEvent(new Event('input'));
      return;
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
      if (selectedIndex >= 0) {
        currentItems[selectedIndex].click();
      }
    }
    
    currentItems.forEach((item, index) => {
      item.classList.toggle('selected', index === selectedIndex);
      if (index === selectedIndex) {
        item.scrollIntoView({ block: 'nearest' });
      }
    });
  });

  // Initialize
  loadData();
  
  // Return API for external control
  return {
    refresh: loadData,
    setData: (newData) => {
      allData = newData;
      ready = true;
      if (input) input.disabled = false;
    },
    getData: () => allData,
    clear: () => {
      input.value = '';
      dropdown.classList.add('hidden');
      if (status) status.textContent = '';
      if (errorEl) errorEl.textContent = '';
    }
  };
}

// DataFetcher class for compatibility
class DataFetcher {
  async executeQuery(queryConfig) {
    const response = await fetch('../handlers/dataFetcher.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query: queryConfig })
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    
    if (!result.success) {
      throw new Error(result.error || 'Unknown error occurred');
    }
    
    return result.data;
  }
}