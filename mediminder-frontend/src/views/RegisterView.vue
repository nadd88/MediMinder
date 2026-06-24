<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const age = ref(null)
const gender = ref('Male')

// caregiver linkage
const linkCaregiver = ref(false)
const caregiverEmail = ref('')

function handleRegister() {
  if (!name.value || !email.value || !password.value) {
    alert('Please fill in required fields')
    return
  }

  const payload = {
    name: name.value,
    email: email.value,
    password: password.value,
    age: age.value,
    gender: gender.value,
    caregiverEmail: linkCaregiver.value ? caregiverEmail.value : null
  }

  console.log('Register payload:', payload)

  // TODO: send to backend API
  router.push('/')
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

      <!-- Name -->
      <input v-model="name" type="text" placeholder="Full Name"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <!-- Email -->
      <input v-model="email" type="email" placeholder="Email"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <!-- Password -->
      <input v-model="password" type="password" placeholder="Password"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <!-- Age -->
      <input v-model="age" type="number" placeholder="Age"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none" />

      <!-- Gender -->
      <select v-model="gender"
        class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-green-500 focus:outline-none">
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>

      <!-- Link caregiver toggle -->
      <div class="flex items-center gap-2 mb-3">
        <input type="checkbox" v-model="linkCaregiver" />
        <label class="text-sm text-gray-600">
          Link to caregiver
        </label>
      </div>

      <!-- Conditional caregiver field -->
      <input
        v-if="linkCaregiver"
        v-model="caregiverEmail"
        type="email"
        placeholder="Caregiver Email"
        class="w-full border p-3 rounded-lg mb-6 focus:ring-2 focus:ring-green-500 focus:outline-none"
      />

      <!-- Submit -->
      <button
        type="submit"
        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition"
      >
        Register
      </button>

      <p class="text-center text-sm text-gray-500 mt-4">
        Already have an account?
        <router-link to="/" class="text-green-600 hover:underline">
          Sign in
        </router-link>
      </p>
    </form>

  </div>
</template>