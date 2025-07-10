/**
 * Student Service - Centralized Student Operations
 * Uses the existing DataFetcher system but provides organized, reusable methods
 */
class StudentService {
  constructor(dataFetcher) {
    this.dataFetcher = dataFetcher;
    
    // Query definitions - organized and documented
    this.queries = {
      // Basic student queries
      GET_ALL_NAMES: 'SELECT Name FROM students',
      GET_ALL_FOR_TABLE: 'SELECT StudentID, StudentNumber, Name, Title FROM students ORDER BY StudentID DESC',
      GET_BY_ID: 'SELECT * FROM students WHERE StudentID = ?',
      GET_BY_NAME: 'SELECT * FROM students WHERE Name = ?',
      
      // ID generation queries
      GET_NEXT_NUMBER: 'SELECT MAX(StudentNumber)+1 as NextNumber FROM students',
      GET_NEXT_ID: 'SELECT MAX(StudentID)+1 as NextID FROM students',
      
      // Modification queries
      INSERT_STUDENT: 'INSERT INTO students (StudentID, StudentNumber, Name, Title) VALUES (?, ?, ?, ?)',
      UPDATE_STUDENT: 'UPDATE students SET Name = ?, Title = ? WHERE StudentID = ?',
      DELETE_STUDENT: 'DELETE FROM students WHERE StudentID = ?',
      
      // Level queries
      GET_ALL_LEVELS: 'SELECT Level FROM levels',
      UPDATE_STUDENT_LEVEL: 'UPDATE studydetails SET Level = ? WHERE StudentID = ?'
    };
  }

  // ==========================================
  // READ OPERATIONS
  // ==========================================

  /**
   * Get all student names for validation/autocomplete
   * @returns {Promise<Array>} Array of student names
   */
  async getAllNames() {
    const result = await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_ALL_NAMES,
      return_type: 'array'
    });
    return result.map(row => row.Name);
  }

  /**
   * Get all students for table display
   * @returns {Promise<Array>} Array of student objects
   */
  async getAllForTable() {
    return await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_ALL_FOR_TABLE,
      return_type: 'multiple'
    });
  }

  /**
   * Get student by name
   * @param {string} name - Student name
   * @returns {Promise<Object|null>} Student object or null
   */
  async getByName(name) {
    return await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_BY_NAME,
      params: [name],
      return_type: 'single'
    });
  }

  /**
   * Get all available levels
   * @returns {Promise<Array>} Array of level objects
   */
  async getAllLevels() {
    return await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_ALL_LEVELS,
      return_type: 'array'
    });
  }

  /**
   * Get next available student IDs
   * @returns {Promise<Object>} Object with studentNumber and studentID
   */
  async getNextIds() {
    const numberResult = await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_NEXT_NUMBER,
      return_type: 'single'
    });
    
    const idResult = await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.GET_NEXT_ID,
      return_type: 'single'
    });
    
    return {
      studentNumber: numberResult.NextNumber || 1,
      studentID: idResult.NextID || 1
    };
  }

  // ==========================================
  // CREATE OPERATIONS
  // ==========================================

  /**
   * Create a new student with automatic ID generation
   * @param {Object} studentData - {name, title, level}
   * @returns {Promise<Object>} Created student with IDs
   */
  async createStudent(studentData) {
    const { name, title, level } = studentData;
    
    // Step 1: Get next IDs
    const { studentNumber, studentID } = await this.getNextIds();
    const titleValue = title === 'none' ? null : title;
    
    console.log('StudentService: Creating student with IDs:', { studentNumber, studentID });
    
    // Step 2: Insert student
    await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.INSERT_STUDENT,
      params: [studentID, studentNumber, name, titleValue]
    });
    
    // Step 3: Update level if provided
    if (level) {
      await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: this.queries.UPDATE_STUDENT_LEVEL,
        params: [level, studentID]
      });
    }
    
    return {
      StudentID: studentID,
      StudentNumber: studentNumber,
      Name: name,
      Title: titleValue,
      Level: level
    };
  }

  // ==========================================
  // DELETE OPERATIONS
  // ==========================================

  /**
   * Delete a student by ID
   * @param {number} studentID - Student ID to delete
   * @returns {Promise<boolean>} Success status
   */
  async deleteStudent(studentID) {
    console.log('StudentService: Deleting student ID:', studentID);
    
    const result = await this.dataFetcher.executeQuery({
      type: 'simple',
      sql: this.queries.DELETE_STUDENT,
      params: [studentID]
    });
    
    return true; // If no error thrown, deletion succeeded
  }

  // ==========================================
  // UTILITY OPERATIONS
  // ==========================================

  /**
   * Search students with fuzzy matching
   * @param {string} searchTerm - Search term
   * @param {Array} allStudents - Array of students to search
   * @returns {Object|null} Best matching student
   */
  findClosestMatch(searchTerm, allStudents) {
    if (!allStudents || allStudents.length === 0) return null;
    
    const lowerSearchTerm = searchTerm.toLowerCase();
    
    // Priority 1: Exact match from start
    let startsWith = allStudents.find(student => 
      student.Name.toLowerCase().startsWith(lowerSearchTerm)
    );
    if (startsWith) return startsWith;
    
    // Priority 2: Contains the search term
    let contains = allStudents.find(student => 
      student.Name.toLowerCase().includes(lowerSearchTerm)
    );
    if (contains) return contains;
    
    // Priority 3: Fuzzy match (word starts)
    let fuzzyMatch = allStudents.find(student => {
      const words = student.Name.toLowerCase().split(' ');
      return words.some(word => word.startsWith(lowerSearchTerm));
    });
    
    return fuzzyMatch || null;
  }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = StudentService;
}

// Also make available globally for non-module usage
if (typeof window !== 'undefined') {
  window.StudentService = StudentService;
}
