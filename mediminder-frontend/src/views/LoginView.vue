<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'


const router = useRouter()
const auth = useAuthStore()

const role = ref('Patient')
const email = ref('')
const password = ref('')

function goToRegister() {
  router.push('/register')
}

function handleSubmit() {
  if (!email.value || !password.value) {
    alert('Please fill in both fields')
    return
  }
  auth.login(email.value, role.value)

  if (role.value === 'Patient') router.push('/patient')
  else if (role.value === 'Caregiver') router.push('/caregiver')
  else router.push('/admin')
}
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-100 via-green-50 to-white px-4"
  >
    <form
      @submit.prevent="handleSubmit"
      class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-sm border border-green-100"
    >
      <!-- Logo -->
      <div class="flex justify-center mb-6">
        <img
          src="@/assets/MediMinder_Logo.png"
          alt="MediMinder Logo"
          class="w-80 h-auto"
        />
      </div>

      <h2 class="text-2xl font-bold text-center text-gray-800 mb-1">
        Welcome Back
      </h2>

      <p class="text-center text-gray-500 text-sm mb-6">
        Sign in to continue
      </p>

      <!-- Role -->
      <select
        v-model="role"
        class="w-full border border-gray-300 p-3 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
      >
        <option>Patient</option>
        <option>Caregiver</option>
        <option>Admin</option>
      </select>

      <!-- Email -->
      <input
        v-model="email"
        type="email"
        placeholder="Email Address"
        class="w-full border border-gray-300 p-3 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
      />

      <!-- Password -->
      <input
        v-model="password"
        type="password"
        placeholder="Password"
        class="w-full border border-gray-300 p-3 rounded-lg mb-6 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
      />

      <!-- Button -->
      <button
        type="submit"
        class="w-full bg-green-600 hover:bg-green-700 transition duration-200 text-white py-3 rounded-lg font-medium shadow-md"
      >
        Sign In
      </button>

      <button
        type="button"
        @click="goToRegister"
        class="w-full mt-3 bg-white border border-green-600 text-green-600 py-2 rounded hover:bg-green-50"
      >
        Create new account
      </button>

      <!-- Optional Links -->
      <div class="mt-4 text-center">
        <a
          href="#"
          class="text-sm text-green-600 hover:text-green-700 hover:underline"
        >
          Forgot Password?
        </a>
      </div>
    </form>
  </div>
</template>