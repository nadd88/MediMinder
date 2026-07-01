<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'
import { caregiverApi } from '../../api/caregiverApi'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const auth = useAuthStore()
const router = useRouter()

const search = ref('')
const patients = ref([])
const loading = ref(true)
const error = ref('')

const filtered = computed(() =>
  patients.value.filter((p) =>
    p.name.toLowerCase().includes(search.value.toLowerCase()) ||
    p.relation.toLowerCase().includes(search.value.toLowerCase())
  )
)

function initials(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}

async function loadPatients() {
  loading.value = true
  error.value = ''
  try {
    const meds = await caregiverApi.getPatients()
    const grouped = new Map()

    for (const med of meds || []) {
      const patientId = med.patient_id ?? med.patientId
      const patientName = med.patient_name || 'Linked patient'
      if (!grouped.has(patientId)) {
        grouped.set(patientId, {
          id: patientId,
          name: patientName,
          relation: 'Linked patient',
          age: '—',
          adherence: 0,
          status: 'good',
          note: 'Loaded from backend',
          medications: [],
          lastSeen: 'Available',
        })
      }
    }

    patients.value = Array.from(grouped.values())
  } catch (err) {
    console.error('Failed to load patient list:', err)
    error.value = 'Unable to load patient list.'
  } finally {
    loading.value = false
  }
}

onMounted(loadPatients)
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Sidebar -->
    <AppSidebar :open="sidebarOpen" @close="closeSidebar">
      <template #nav-links>
        <router-link to="/caregiver"           @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/home-icon.png" width="20" class="inline-block mr-2" /> Dashboard
        </router-link>
        <router-link to="/caregiver/patients"  @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/patient.png" width="20" class="inline-block mr-2" /> Patients
        </router-link>
        <router-link to="/caregiver/alerts"    @click="closeSidebar" class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900" active-class="font-bold text-green-700 bg-green-50">
          <img src="@/assets/alert.png" width="20" class="inline-block mr-2" /> Alerts
        </router-link>
      </template>
    </AppSidebar>

    <div class="flex flex-col min-h-screen">

      <!-- Header -->
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
        <button @click="toggleSidebar" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600" aria-label="Toggle sidebar">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="17" y2="6"/><line x1="3" y1="11" x2="17" y2="11"/><line x1="3" y1="16" x2="17" y2="16"/></svg>
        </button>
        <div>
          <p class="text-sm text-gray-500">Caregiver</p>
          <h1 class="text-base font-semibold text-gray-800">My Patients</h1>
        </div>
        <div class="ml-auto">
          <button class="text-sm text-gray-600 hover:text-gray-800" @click="handleLogout">Sign out</button>
        </div>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto w-full">

        <!-- Search -->
        <div class="mb-4 relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="7" cy="7" r="5"/><line x1="11" y1="11" x2="15" y2="15"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Search patients…"
            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-300"
          />
        </div>

        <!-- Count -->
        <p class="text-sm text-gray-500 mb-3">{{ filtered.length }} patient{{ filtered.length !== 1 ? 's' : '' }} linked</p>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-600">{{ error }}</p>

        <!-- Patient cards -->
        <div v-if="loading" class="rounded-xl bg-white p-4 text-sm text-gray-500 shadow-sm">Loading linked patients…</div>
        <div v-else class="space-y-3">
          <div
            v-for="patient in filtered"
            :key="patient.id"
            class="bg-white rounded-xl p-4 shadow-sm"
          >
            <!-- Top row -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-green-100 text-green-700 font-semibold flex items-center justify-center text-sm">
                  {{ initials(patient.name) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ patient.name }}</p>
                  <p class="text-xs text-gray-500">{{ patient.relation }} · {{ patient.age }} yrs</p>
                </div>
              </div>
              <!-- Adherence badge -->
              <div class="text-right">
                <p
                  class="text-xl font-bold"
                  :class="patient.status === 'attention' ? 'text-amber-600' : 'text-green-600'"
                >{{ patient.adherence }}%</p>
                <p class="text-xs text-gray-400">adherence</p>
              </div>
            </div>

            <!-- Status note -->
            <p
              class="text-xs font-medium px-2 py-1 rounded-md inline-block mb-3"
              :class="patient.status === 'attention' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
            >{{ patient.note }}</p>

            <!-- Medications -->
            <div class="border-t border-gray-100 pt-3">
              <p class="text-xs text-gray-400 mb-1.5">Medications</p>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="med in patient.medications"
                  :key="med"
                  class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"
                >{{ med }}</span>
              </div>
            </div>

            <!-- Last seen -->
            <p class="text-xs text-gray-400 mt-2">Last check-in: {{ patient.lastSeen }}</p>
          </div>

          <!-- Empty state -->
          <div v-if="filtered.length === 0" class="text-center py-12 text-gray-400">
            <p class="text-sm">No patients match "{{ search }}"</p>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>
