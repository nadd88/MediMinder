<script setup>
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const auth = useAuthStore()
const router = useRouter()

const patients = [
  { id: 1, name: 'Ahmad Hamid', relation: 'Father · 68 yrs', adherence: 92, status: 'good', note: 'All doses taken today' },
  { id: 2, name: 'Rosnah Mat', relation: 'Mother · 65 yrs', adherence: 54, status: 'attention', note: '3 missed doses this week' },
  { id: 3, name: 'Zainab Ali', relation: 'Grandmother · 72 yrs', adherence: 78, status: 'good', note: '1 dose pending today' },
]

function initials(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Sidebar -->
    <AppSidebar :open="sidebarOpen" @close="closeSidebar">
      <template #nav-links>
        <router-link to="/caregiver"          @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/home-icon.png" width="20" class="inline-block mr-2" /> Dashboard
        </router-link>
        <router-link to="/caregiver/patients" @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/patient.png" width="20" class="inline-block mr-2" /> Patients
        </router-link>
        <router-link to="/caregiver/alerts"   @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/alert.png" width="20" class="inline-block mr-2" /> Alerts
        </router-link>
      </template>
    </AppSidebar>

    <div class="flex flex-col min-h-screen">
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
        <button @click="toggleSidebar" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600" aria-label="Toggle sidebar">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="17" y2="6"/><line x1="3" y1="11" x2="17" y2="11"/><line x1="3" y1="16" x2="17" y2="16"/></svg>
        </button>
        <div>
          <p class="text-sm text-gray-500">Caregiver</p>
          <h1 class="text-base font-semibold text-gray-800">{{ auth.name }}</h1>
        </div>
        <div class="ml-auto">
          <button class="text-sm text-gray-600 hover:text-gray-800" @click="handleLogout">Sign out</button>
        </div>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto">

      <!-- Summary cards -->
      <section class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm">
          <p class="text-2xl font-bold text-green-600">
            {{ patients.filter(p => p.status === 'good').length }}
          </p>
          <p class="text-xs text-gray-500">Good adherence</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm">
          <p class="text-2xl font-bold text-amber-600">
            {{ patients.filter(p => p.status === 'attention').length }}
          </p>
          <p class="text-xs text-gray-500">Needs attention</p>
        </div>
      </section>

      <!-- Patient list -->
      <section>
        <h2 class="font-semibold text-gray-800 mb-3">My patients ({{ patients.length }} linked)</h2>
        <div class="space-y-3">
          <div
            v-for="patient in patients"
            :key="patient.id"
            class="bg-white rounded-xl p-4 shadow-sm"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 text-green-700 font-semibold flex items-center justify-center">
                  {{ initials(patient.name) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ patient.name }}</p>
                  <p class="text-sm text-gray-500">{{ patient.relation }}</p>
                </div>
              </div>
              <p
                class="text-lg font-bold"
                :class="patient.status === 'attention' ? 'text-amber-600' : 'text-green-600'"
              >
                {{ patient.adherence }}%
              </p>
            </div>
            <p
              class="mt-2 text-xs font-medium px-2 py-1 rounded-md inline-block"
              :class="patient.status === 'attention' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
            >
              {{ patient.note }}
            </p>
          </div>
        </div>
      </section>

    </main>
  </div>
  </div>
</template>