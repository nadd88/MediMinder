<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const dob = ref('')
const errorMessage = ref('')
const loading = ref(false)

async function handleRegister() {
  errorMessage.value = ''

  if (!name.value || !email.value || !password.value) {
    errorMessage.value = 'Please fill in all required fields'
    return
  }

  if (password.value.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters'
    return
  }

  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      role: 'Patient', // this screen is patient self-registration only
      dob: dob.value || null,
    })
    router.push('/login')
  } catch (err) {
    errorMessage.value = err.response?.data?.error || 'Registration failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-100 via-green-50 to-white px-4">
    <form
      @submit.prevent="handleRegister"
      class="bg-white w-full max-w-md p-8 rounded-2xl shadow-xl border border-green-100"
    >
      <h1 class="text-2xl font-bold text-center text-gray-800 mb-1">
        Patient Registration
      </h1>
      <p class="text-center text-gray-500 text-sm mb-6">
        Create your MediMinder account
      </p>

      <p v-if="errorMessage" class="text-red-500 text-sm text-center mb-4">{{ errorMessage }}</p>

      <input v-model="name" type="text" placeholder="Full Name"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <input v-model="email" type="email" placeholder="Email"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <input v-model="password" type="password" placeholder="Password (min 8 characters)"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <label class="block text-sm text-gray-500 mb-1">Date of Birth</label>
      <input v-model="dob" type="date"
        class="w-full border p-3 rounded-lg mb-6 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition disabled:opacity-50"
      >
        {{ loading ? 'Creating account...' : 'Register' }}
      </button>

      <p class="text-center text-sm text-gray-500 mt-4">
        Already have an account?
        <router-link to="/login" class="text-green-600 hover:underline">
          Sign in
        </router-link>
      </p>
    </form>
  </div>
</template>