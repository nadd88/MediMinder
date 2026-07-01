import { defineStore } from 'pinia'
import { authApi } from '../api/authApi'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('mediminder_token') || null,
    userId: null,
    name: null,
    email: null,
    role: localStorage.getItem('mediminder_role') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
  },

  actions: {
    async login(email, password) {
      const response = await authApi.login(email, password)
      const { token, user } = response.data

      this.token = token
      this.userId = user.id
      this.name = user.name
      this.email = user.email
      this.role = user.role

      localStorage.setItem('mediminder_token', token)
      localStorage.setItem('mediminder_role', user.role)
    },

    async register(payload) {
      const response = await authApi.register(payload)
      return response.data
    },

    logout() {
      this.token = null
      this.userId = null
      this.name = null
      this.email = null
      this.role = null
      localStorage.removeItem('mediminder_token')
      localStorage.removeItem('mediminder_role')
    },
  },
})