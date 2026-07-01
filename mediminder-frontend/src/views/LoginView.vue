<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const loading = ref(false)

function goToRegister() {
  router.push('/register')
}

async function handleSubmit() {
  errorMessage.value = ''

  if (!email.value || !password.value) {
    errorMessage.value = 'Please fill in both fields'
    return
  }

  loading.value = true
  try {
    await auth.login(email.value, password.value)

    if (auth.role === 'Patient') router.push('/patient')
    else if (auth.role === 'Caregiver') router.push('/caregiver')
    else router.push('/admin')
  } catch (err) {
    errorMessage.value = err.response?.data?.error || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-100 via-green-50 to-white px-4">
    <form
      @submit.prevent="handleSubmit"
      class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm border border-green-100"
    >
      <div class="flex justify-center mb-6">
        <img src="@/assets/MediMinder_Logo.png" alt="MediMinder Logo" class="w-80 h-auto" />
      </div>

      <h2 class="text-2xl font-bold text-center text-gray-800 mb-1">Welcome Back</h2>
      <p class="text-center text-gray-500 text-sm mb-6">Sign in to continue</p>

      <p v-if="errorMessage" class="text-red-500 text-sm text-center mb-4">{{ errorMessage }}</p>

      <input
        v-model="email"
        type="email"
        placeholder="Email Address"
        class="w-full border border-gray-300 p-3 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
      />

      <input
        v-model="password"
        type="password"
        placeholder="Password"
        class="w-full border border-gray-300 p-3 rounded-lg mb-6 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
      />

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-green-600 hover:bg-green-700 transition duration-200 text-white py-3 rounded-lg font-medium shadow-md disabled:opacity-50"
      >
        {{ loading ? 'Signing in...' : 'Sign In' }}
      </button>

      <button
        type="button"
        @click="goToRegister"
        class="w-full mt-3 bg-white border border-green-600 text-green-600 py-2 rounded hover:bg-green-50"
      >
        Create new account
      </button>

      <div class="mt-4 text-center">
        <a href="#" class="text-sm text-green-600 hover:text-green-700 hover:underline">Forgot Password?</a>
      </div>
    </form>
  </div>
</template>