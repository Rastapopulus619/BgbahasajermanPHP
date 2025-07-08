/**
 * Universal Data Fetcher JavaScript Library
 * 
 * This library provides a unified way to fetch data from the backend across all pages.
 * It supports simple queries, complex queries, auto-fetch triggers, and caching.
 * 
 * HOW TO USE:
 * 1. Include: <script src="/assets/js/dataFetcher.js"></script>
 * 2. Create instance: const dataFetcher = new DataFetcher();
 * 3. Execute queries: dataFetcher.executeQuery(queryConfig);
 * 4. Setup auto-fetch: dataFetcher.registerAutoFetch(config);
 * 
 * DEPENDENCIES:
 * - Requires dataFetcher.php on the backend
 * - Uses fetch() API (modern browsers)
 * 
 * EXTENDING:
 * - Add new trigger types in setupTrigger() method
 * - Add new target types in updateTarget() method
 * - Add custom transformation functions
 */

class DataFetcher {
    /**
     * Initialize DataFetcher with configuration options
     * 
     * @param {Object} options - Configuration options
     * @param {string} options.baseUrl - Base URL for data fetcher endpoint
     */
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '../handlers/dataFetcher.php';
        this.cache = new Map();
        this.listeners = new Map();
        this.autoFetchConfigs = new Map();
        this.debug = options.debug || false;
        
        if (this.debug) {
            console.log('DataFetcher: Initialized with options', options);
        }
    }
    
    // ==========================================
    // CORE QUERY EXECUTION METHODS
    // ==========================================
    
    /**
     * Execute a single query configuration
     * 
     * @param {Object} queryConfig - Query configuration object
     * @param {string} trigger - What triggered this query (manual, load, change, etc.)
     * @returns {Promise} - Promise resolving to query result data
     */
    async executeQuery(queryConfig, trigger = 'manual') {
        const cacheKey = queryConfig.cache_key;
        
        // Check cache first to avoid duplicate requests
        if (cacheKey && this.cache.has(cacheKey)) {
            if (this.debug) {
                console.log('DataFetcher: Using cached result for', cacheKey);
            }
            return this.cache.get(cacheKey);
        }
        
        try {
            if (this.debug) {
                console.log('DataFetcher: Executing query', queryConfig, 'triggered by', trigger);
            }
            
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: queryConfig,
                    trigger: trigger
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Cache result if cache key provided
                if (cacheKey) {
                    this.cache.set(cacheKey, result.data);
                    if (this.debug) {
                        console.log('DataFetcher: Cached result with key', cacheKey);
                    }
                }
                
                return result.data;
            } else {
                throw new Error(result.error || 'Query failed');
            }
            
        } catch (error) {
            console.error('DataFetcher Error:', error);
            throw error;
        }
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataFetcher;
}

// Also make available globally for non-module usage
if (typeof window !== 'undefined') {
    window.DataFetcher = DataFetcher;
}
