/**
 * Template Service - Template-specific auto-fill logic
 * Works with StudentService and existing WATemplateCreator system
 */
class TemplateService {
  constructor(studentService, dataFetcher) {
    this.studentService = studentService;
    this.dataFetcher = dataFetcher;
  }

  /**
   * Get template-specific auto-fill data
   * @param {string} templateKey - Template identifier
   * @param {string} studentName - Selected student name
   * @returns {Promise<Object>} Auto-filled placeholder data
   */
  async getTemplateData(templateKey, studentName) {
    if (!studentName) {
      return {};
    }

    // Get the template-specific method
    const methodName = `fill_${templateKey}`;
    
    if (typeof this[methodName] === 'function') {
      console.log(`Using specific method: ${methodName}`);
      return await this[methodName](studentName);
    } else {
      console.log(`No specific method for ${templateKey}, using universal fetcher`);
      return await this.universalTemplateFetch(templateKey, studentName);
    }
  }

  /**
   * Fallback to universal template fetcher (your existing system)
   */
  async universalTemplateFetch(templateKey, studentName) {
    try {
      const queryConfig = {
        type: 'complex',
        handler: 'getTemplateAutoFetchData',
        params: {
          template_key: templateKey,
          student_name: studentName
        }
      };
      
      const response = await fetch('../handlers/dataFetcher.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: queryConfig })
      });
      
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      
      const result = await response.json();
      return result.success ? (result.data || {}) : {};
      
    } catch (error) {
      console.error('Universal template fetch failed:', error);
      return {};
    }
  }

  // ==========================================
  // TEMPLATE-SPECIFIC METHODS
  // ==========================================

  /**
   * Student reminder template - comprehensive student info
   */
  async fill_student_reminder(studentName) {
    try {
      const student = await this.studentService.getByName(studentName);
      if (!student) return {};

      // Get additional data using your existing complex queries
      const nextClassResult = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT DATE_FORMAT(MIN(ScheduleDate), "%W, %d.%m.%Y um %H:%i") as NextClass FROM schedules WHERE StudentID = ? AND ScheduleDate > NOW()',
        params: [student.StudentID],
        return_type: 'single'
      });

      const classCountResult = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT COUNT(*) as ClassesThisMonth FROM schedules WHERE StudentID = ? AND MONTH(ScheduleDate) = MONTH(NOW()) AND YEAR(ScheduleDate) = YEAR(NOW())',
        params: [student.StudentID],
        return_type: 'single'
      });

      return {
        NAME: student.Name,
        NUMBER: student.StudentNumber,
        LEVEL: student.Level || '',
        NEXT_CLASS: nextClassResult?.NextClass || 'Noch nicht geplant',
        CLASSES_THIS_MONTH: classCountResult?.ClassesThisMonth || '0'
      };
    } catch (error) {
      console.error('Error in student_reminder template:', error);
      return { NAME: studentName }; // Fallback to just name
    }
  }

  /**
   * Payment reminder - focus on financial data
   */
  async fill_payment_reminder(studentName) {
    try {
      const student = await this.studentService.getByName(studentName);
      if (!student) return {};

      // Get payment-specific data
      const paymentResult = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT Amount, DueDate FROM payments WHERE StudentID = ? AND Status = "pending" ORDER BY DueDate ASC LIMIT 1',
        params: [student.StudentID],
        return_type: 'single'
      });

      return {
        NAME: student.Name,
        NUMBER: student.StudentNumber,
        AMOUNT: paymentResult?.Amount || '0',
        DUE_DATE: paymentResult?.DueDate || 'N/A'
        // Leave PAYMENT_METHOD empty for manual input
      };
    } catch (error) {
      console.error('Error in payment_reminder template:', error);
      return { NAME: studentName };
    }
  }

  /**
   * Progress report - academic performance focus
   */
  async fill_progress_report(studentName) {
    try {
      const student = await this.studentService.getByName(studentName);
      if (!student) return {};

      // Get academic performance data
      const gradeResult = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT ROUND(AVG(Score), 1) as AverageScore FROM test_results WHERE StudentID = ? AND TestDate >= DATE_SUB(NOW(), INTERVAL 3 MONTH)',
        params: [student.StudentID],
        return_type: 'single'
      });

      const attendanceResult = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT COUNT(*) as TotalClasses, SUM(CASE WHEN Status = "present" THEN 1 ELSE 0 END) as PresentClasses FROM attendance WHERE StudentID = ? AND Date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
        params: [student.StudentID],
        return_type: 'single'
      });

      const attendanceRate = attendanceResult?.TotalClasses > 0 
        ? Math.round((attendanceResult.PresentClasses / attendanceResult.TotalClasses) * 100)
        : 0;

      return {
        NAME: student.Name,
        LEVEL: student.Level || '',
        AVERAGE_SCORE: gradeResult?.AverageScore || 'N/A',
        ATTENDANCE_RATE: `${attendanceRate}%`,
        // Leave TEACHER_NOTES empty for manual input
        // Leave NEXT_GOAL empty for manual input
      };
    } catch (error) {
      console.error('Error in progress_report template:', error);
      return { NAME: studentName };
    }
  }

  /**
   * Schedule update - timing and logistics focus
   */
  async fill_schedule_update(studentName) {
    try {
      const student = await this.studentService.getByName(studentName);
      if (!student) return {};

      const upcomingClasses = await this.dataFetcher.executeQuery({
        type: 'simple',
        sql: 'SELECT DATE_FORMAT(ScheduleDate, "%W, %d.%m.%Y um %H:%i") as ClassTime FROM schedules WHERE StudentID = ? AND ScheduleDate > NOW() ORDER BY ScheduleDate ASC LIMIT 3',
        params: [student.StudentID],
        return_type: 'multiple'
      });

      const nextClass = upcomingClasses?.[0]?.ClassTime || 'Noch nicht geplant';
      const classCount = upcomingClasses?.length || 0;

      return {
        NAME: student.Name,
        NEXT_CLASS: nextClass,
        UPCOMING_CLASSES: classCount.toString(),
        // Leave LOCATION empty for manual input
        // Leave SPECIAL_NOTES empty for manual input
      };
    } catch (error) {
      console.error('Error in schedule_update template:', error);
      return { NAME: studentName };
    }
  }

    /**
   * Pricelist template - auto-fill with current date and student info
   */
  async fill_pricelist(studentName) {
    try {
      const student = await this.studentService.getByName(studentName);
      if (!student) {
        console.log('❌ Student not found:', studentName);
        return {};
      }

      console.log('✅ Student found:', student.Name, 'ID:', student.StudentID);

      // Get current date in German format
      const currentDate = new Date().toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });

      const returnData = {
        NAME: student.Name,
        DATE: currentDate
      };

      console.log('📋 Returning pricelist data:', returnData);
      return returnData;

    } catch (error) {
      console.error('❌ Error in pricelist template:', error);
      return { 
        NAME: studentName,
        DATE: new Date().toLocaleDateString('de-DE', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        })
      };
    }
  }

}



// Make available globally
if (typeof window !== 'undefined') {
  window.TemplateService = TemplateService;
}