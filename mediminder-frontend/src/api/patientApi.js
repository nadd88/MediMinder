import apiClient from './client'
import { mockApi } from './mockClient'
import { useDemoApi } from './runtime'

const unwrap = (response) => response.data

export const patientApi = {
  async getDashboardData() {
    if (useDemoApi) return mockApi.getDashboardData()
    return unwrap(await apiClient.get('/patient/dashboard'))
  },
  async getDoses() {
    if (useDemoApi) return mockApi.getDoses()
    return unwrap(await apiClient.get('/patient/doses'))
  },
  async markDose(doseId, status) {
    if (useDemoApi) return mockApi.markDose(doseId, status)
    return unwrap(await apiClient.post(`/patient/doses/${doseId}/status`, { status }))
  },
  async getAdherence(range = '7') {
    if (useDemoApi) return mockApi.getAdherence(range)
    return unwrap(await apiClient.get('/patient/adherence', { params: { range } }))
  },
  async getSupply() {
    if (useDemoApi) return mockApi.getSupply()
    return unwrap(await apiClient.get('/patient/supply'))
  },
  async refillSupply(medicationId, amount) {
    if (useDemoApi) return mockApi.refillSupply(medicationId, amount)
    return unwrap(await apiClient.post(`/patient/supply/${medicationId}/refill`, { amount }))
  },
}
