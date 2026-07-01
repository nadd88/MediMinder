import apiClient from './client'

const unwrap = (response) => response.data

export const patientApi = {
  async getDashboardData() {
    return unwrap(await apiClient.get('/patient/dashboard'))
  },
  async getDoses() {
    return unwrap(await apiClient.get('/patient/doses'))
  },
  async markDose(doseId, status) {
    return unwrap(await apiClient.post(`/patient/doses/${doseId}/status`, { status }))
  },
  async getAdherence(range = '7') {
    return unwrap(await apiClient.get('/patient/adherence', { params: { range } }))
  },
  async getSupply() {
    return unwrap(await apiClient.get('/patient/supply'))
  },
  async refillSupply(medicationId, amount) {
    return unwrap(await apiClient.post(`/patient/supply/${medicationId}/refill`, { amount }))
  },
}
