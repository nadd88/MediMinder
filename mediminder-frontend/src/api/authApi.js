import apiClient from './client'

export const authApi = {
  register(payload) {
    return apiClient.post('/auth/register', payload)
  },
  login(email, password) {
    return apiClient.post('/auth/login', { email, password })
  },
}