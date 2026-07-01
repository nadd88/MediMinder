import apiClient from './client'

const unwrap = (response) => response.data.data

export const caregiverApi = {
  async getPatients() {
    return unwrap(await apiClient.get('/medications'))
  },
  async getPatientSummary(patientId) {
    return unwrap(await apiClient.get(`/patients/${patientId}/adherence`))
  },
  async getPatientWeekly(patientId) {
    return unwrap(await apiClient.get(`/patients/${patientId}/adherence/weekly`))
  },
}
