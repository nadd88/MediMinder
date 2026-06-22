<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const role = ref('Patient')
const email = ref('')
const password = ref('')

function handleSubmit() {
  if (!email.value || !password.value) {
    alert('Please fill in both fields')
    return
  }
  auth.login(email.value, role.value)

  if (role.value === 'Patient') router.push('/patient')
  else if (role.value === 'Caregiver') router.push('/caregiver')
  else router.push('/admin/prescriptions')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-green-50">
    <form @submit.prevent="handleSubmit" class="bg-white p-6 rounded-xl shadow-md w-80">
      <h1> <img src="@/assets/MediMinder_Logo.png" alt="Company Logo" width="1000" height="1000"></h1>
      <select v-model="role" class="w-full border p-2 rounded mb-3">
        <option>Patient</option>
        <option>Caregiver</option>
        <option>Admin</option>
      </select>

      <input v-model="email" type="email" placeholder="Email"
        class="w-full border p-2 rounded mb-3" />

      <input v-model="password" type="password" placeholder="Password"
        class="w-full border p-2 rounded mb-3" />

      <button type="submit" class="w-full bg-green-600 text-white py-2 rounded">
        Sign in
      </button>
    </form>
  </div>
</template>