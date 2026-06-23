// src/stores/auth.js
// Holds the authenticated session (JWT + user) and persists it to
// localStorage so a page refresh keeps the user logged in.

import { defineStore } from 'pinia'

const STORAGE_KEY = 'mediminder_auth'

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : { token: null, user: null }
  } catch (e) {
    return { token: null, user: null }
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => {
    const saved = loadFromStorage()
    return {
      token: saved.token,
      user: saved.user, // { id, name, role }
    }
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role ?? null,
    name: (state) => state.user?.name ?? null,
  },

  actions: {
    setSession(token, user) {
      this.token = token
      this.user = user
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ token, user }))
    },

    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem(STORAGE_KEY)
    },
  },
})
