import axios from 'axios'

const apiClient = axios.create({
  baseURL: 'http://localhost:8080',
  headers: {
    'Content-Type': 'application/json',
  },
})

// Attach the JWT to every request automatically, if we have one
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('mediminder_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default apiClient