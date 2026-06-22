import { defineStore} from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        role: null,
        name: null,
}),

actions: {
    login(name, role) {
        this.name = name
        this.role = role
    },

    logout() {
        this.name = null
        this.role = null
    },
},
})