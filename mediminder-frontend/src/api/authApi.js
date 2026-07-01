import apiClient from './client'
import { useDemoApi } from './runtime'

const defaultDemoUsers = [
  {
    id: 1,
    name: 'Sarah Tan',
    email: 'patient@email.com',
    password: 'password123',
    role: 'Patient',
  },
]

function savedDemoUsers() {
  const saved = localStorage.getItem('mediminder_demo_users')
  return saved ? JSON.parse(saved) : []
}

function allDemoUsers() {
  return [...defaultDemoUsers, ...savedDemoUsers()]
}

function demoLogin(email, password) {
  const normalizedEmail = email.trim().toLowerCase()
  const user = allDemoUsers().find(
    (item) => item.email === normalizedEmail && item.password === password
  )

  if (!user) {
    return Promise.reject({
      response: { data: { error: 'Invalid email or password' } },
    })
  }

  return Promise.resolve({
    data: {
      message: 'Login successful',
      token: 'demo-patient-token',
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      },
    },
  })
}

function demoRegister(payload) {
  const users = savedDemoUsers()
  const normalizedEmail = payload.email.trim().toLowerCase()

  if (allDemoUsers().some((user) => user.email === normalizedEmail)) {
    return Promise.reject({
      response: { data: { error: 'Email already registered' } },
    })
  }

  const user = {
    id: Date.now(),
    name: payload.name,
    email: normalizedEmail,
    password: payload.password,
    role: payload.role,
  }

  localStorage.setItem('mediminder_demo_users', JSON.stringify([...users, user]))

  return Promise.resolve({
    data: {
      message: 'User registered successfully',
      user_id: user.id,
    },
  })
}

export const authApi = {
  register(payload) {
    if (useDemoApi) return demoRegister(payload)
    return apiClient.post('/auth/register', payload)
  },
  login(email, password) {
    if (useDemoApi) return demoLogin(email, password)
    return apiClient.post('/auth/login', { email, password })
  },
}
