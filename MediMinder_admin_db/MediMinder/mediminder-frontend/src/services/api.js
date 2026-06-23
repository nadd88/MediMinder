// src/services/api.js
// Central axios client for the MediMinder API.
// Attaches the JWT from the auth store to every request and exposes
// typed helper methods used by the admin views.

import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
})

// Attach Bearer token on every request.
api.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

// On 401, clear the session so the router guard bounces to login.
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response && err.response.status === 401) {
      useAuthStore().logout()
    }
    return Promise.reject(err)
  },
)

export default {
  // ---- auth ----
  login: (email, password) => api.post('/auth/login', { email, password }),

  // ---- patients ----
  listPatients: () => api.get('/admin/patients'),
  getPatient: (id) => api.get(`/admin/patients/${id}`),
  createPatient: (payload) => api.post('/admin/patients', payload),

  // ---- medications ----
  searchMedications: (q = '') => api.get('/admin/medications', { params: { q } }),

  // ---- prescriptions ----
  listPrescriptions: (patientId) => api.get(`/admin/patients/${patientId}/prescriptions`),
  createPrescription: (patientId, payload) =>
    api.post(`/admin/patients/${patientId}/prescriptions`, payload),
  updatePrescription: (id, payload) => api.put(`/admin/prescriptions/${id}`, payload),
  checkInteractions: (patientId, medicationId) =>
    api.get(`/admin/patients/${patientId}/interaction-check`, {
      params: { medication_id: medicationId },
    }),

  // ---- reports ----
  getReport: (patientId, from, to) =>
    api.get(`/admin/patients/${patientId}/report`, { params: { from, to } }),
  reportCsvUrl: (patientId, from, to) =>
    `${api.defaults.baseURL}/admin/patients/${patientId}/report.csv?from=${from}&to=${to}`,

  // ---- audit ----
  listAudit: (patientId = null, limit = 50) =>
    api.get('/admin/audit', { params: { patient_id: patientId, limit } }),
}
