import mockData from '../assets/mockData.json';

// Simulate network delay
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

export const mockApi = {
  async getDashboardData() {
    await delay(500);
    return {
      success: true,
      data: {
        summary: mockData.summary,
        medications: mockData.medications,
        patient: mockData.patient
      }
    };
  },

  async markDose(medicationId, status) {
    await delay(300);
    
    // Find and update the medication status
    const medication = mockData.medications.find(m => m.id === medicationId);
    if (medication) {
      medication.status = status;
    }
    
    return {
      success: true,
      message: `Dose marked as ${status}`,
      data: { medicationId, status }
    };
  }
};