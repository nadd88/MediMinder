<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'
import { patientApi } from '../../api/patientApi'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()
const auth = useAuthStore()

// Loading and error states
const loading = ref(true)
const error = ref(null)

// Data from API
const summary = ref({
  adherence7day: 87,
  dueToday: 3,
  missedToday: 1,
})

const medications = ref([])

// Load data from API
async function loadDashboardData() {
  loading.value = true
  error.value = null
  
  try {
    const response = await patientApi.getDashboardData()
    if (response.success) {
      summary.value = response.data.summary
      medications.value = response.data.medications
    }
  } catch (err) {
    console.error('Failed to load dashboard:', err)
    error.value = 'Failed to load your medication data. Please try again.'
  } finally {
    loading.value = false
  }
}

// Mark dose as taken
async function markDoseTaken(medicationId) {
  try {
    const response = await patientApi.markDose(medicationId, 'taken')
    if (response.success) {
      // Update the local state
      const med = medications.value.find(m => m.id === medicationId)
      if (med) {
        med.status = 'taken'
        // Update summary counts
        summary.value.dueToday = medications.value.filter(m => m.status === 'pending').length
        summary.value.missedToday = medications.value.filter(m => m.status === 'missed').length
      }
    }
  } catch (err) {
    console.error('Failed to mark dose:', err)
    error.value = 'Failed to mark dose. Please try again.'
  }
}

onMounted(loadDashboardData)

// Helper functions (unchanged)
function statusColor(status) {
  if (status === 'taken') return 'bg-green-100 text-green-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  if (status === 'missed') return 'bg-red-100 text-red-700'
  return 'bg-gray-100 text-gray-500'
}

function statusLabel(status) {
  if (status === 'taken') return 'Taken'
  if (status === 'pending') return 'Due now'
  if (status === 'missed') return 'Missed'
  return 'Upcoming'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
        <p class="text-gray-500 mt-4">Loading your medications...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center p-6 bg-red-50 rounded-xl max-w-md">
        <p class="text-red-600">{{ error }}</p>
        <button 
          @click="loadDashboardData" 
          class="mt-4 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
          Try Again
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Sidebar -->
      <AppSidebar :open="sidebarOpen" @close="closeSidebar">
        <template #nav-links>
          <router-link
            to="/patient"
            @click="closeSidebar"
            class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
            active-class="font-bold text-green-700 bg-green-50"
          >
            Dashboard
          </router-link>

          <router-link
            to="/patient/doses"
            @click="closeSidebar"
            class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
            active-class="font-bold text-green-700 bg-green-50"
          >
            Doses
          </router-link>

          <router-link
            to="/patient/adherence"
            @click="closeSidebar"
            class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
            active-class="font-bold text-green-700 bg-green-50"
          >
            Adherence
          </router-link>
          <router-link
          to="/patient/medicine-supply"
          @click="closeSidebar"
          class="block font-bold text-gray-700 px-3 py-2 rounded-lg transition-colors hover:bg-gray-100 hover:text-gray-900"
          active-class="font-bold text-green-700 bg-green-50"
        >
          Supply
        </router-link>
        </template>
      </AppSidebar>

      <!-- Main content -->
      <div class="flex flex-col min-h-screen">

        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 sticky top-0 z-10">
          <button
            @click="toggleSidebar"
            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-600"
            aria-label="Toggle sidebar"
          >
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <line x1="3" y1="6" x2="17" y2="6"/>
              <line x1="3" y1="11" x2="17" y2="11"/>
              <line x1="3" y1="16" x2="17" y2="16"/>
            </svg>
          </button>
          <h2 class="text-base font-semibold text-gray-800">Dashboard</h2>
          <p class="hidden md:block text-sm text-gray-400 ml-auto">
            {{ new Date().toLocaleDateString('en-MY', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
          </p>
        </header>

        <!-- Mobile greeting -->
        <div class="md:hidden bg-green-700 px-4 pt-4 pb-8">
          <p class="text-sm text-white/80">Good Morning,</p>
          <h1 class="text-xl font-bold text-white">{{ auth.name }}</h1>
        </div>

        <!-- Page content -->
        <main class="px-4 md:px-8 py-4 md:py-6 -mt-4 md:mt-0 max-w-4xl w-full pb-24 md:pb-8">

          <!-- Stat cards (data now from API) -->
          <section class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-green-700 text-white rounded-xl p-3 text-center">
              <p class="text-2xl font-bold">{{ summary.adherence7day }}%</p>
              <p class="text-xs opacity-80">This week</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
              <p class="text-2xl font-bold text-gray-800">{{ summary.dueToday }}</p>
              <p class="text-xs text-gray-500">Due today</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
              <p class="text-2xl font-bold text-red-600">{{ summary.missedToday }}</p>
              <p class="text-xs text-gray-500">Missed</p>
            </div>
          </section>

          <!-- Medications list -->
          <section>
            <h2 class="font-semibold text-gray-800 mb-3">Today's medications</h2>
            <div class="space-y-2">
              <div
                v-for="med in medications"
                :key="med.id"
                class="bg-white rounded-xl px-4 py-3 shadow-sm flex justify-between items-center"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ med.name }}</p>
                  <p class="text-sm text-gray-500">{{ med.dose }} - {{ med.time }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium px-2 py-1 rounded-full" :class="statusColor(med.status)">
                    {{ statusLabel(med.status) }}
                  </span>
                  
                  <button
                    v-if="med.status === 'pending'"
                    @click="markDoseTaken(med.id)"
                    class="text-xs bg-green-500 text-white px-3 py-1.5 rounded-lg hover:bg-green-600 transition-colors"
                  >
                    Take
                  </button>
                </div>
              </div>
            </div>
          </section>

        </main>
      </div>

    <!-- Bottom nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex">
        <router-link
            to="/patient"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/home-icon.png" alt="Home" width="24" height="24">
            Dashboard
        </router-link>
        <router-link
            to="/patient/doses"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/pill-icon.png" alt="Doses" width="24" height="24">
            Doses
        </router-link>
        <router-link
            to="/patient/adherence"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/adherence-icon.png" alt="Adherence" width="24" height="24">
            Adherence
        </router-link>
        <router-link
            to="/patient/medicine-supply"
            class="flex-1 flex flex-col items-center py-3 text-xs text-gray-500"
            active-class="text-green-700 font-semibold"
        >
            <img src="@/assets/supply.png" alt="Supply" width="24" height="24">
            Supply
        </router-link>
</nav>

    </div>
  </div>
</template>

