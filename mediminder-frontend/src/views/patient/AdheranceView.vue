<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Bar } from 'vue-chartjs'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'
import { patientApi } from '../../api/patientApi'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const range = ref('7')
const loading = ref(true)
const error = ref(null)
const overall = ref(0)
const labels = ref([])
const values = ref([])
const byMedication = ref([])

async function loadAdherence() {
  loading.value = true
  error.value = null
  try {
    const response = await patientApi.getAdherence(range.value)
    if (response.success) {
      overall.value = response.data.overall
      labels.value = response.data.labels
      values.value = response.data.values
      byMedication.value = response.data.byMedication
    }
  } catch (err) {
    console.error('Failed to load adherence:', err)
    error.value = 'Failed to load adherence report. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAdherence)
watch(range, loadAdherence)

const chartData = computed(() => ({
  labels: labels.value,
  datasets: [{
    label: 'Adherence %',
    data: values.value,
    backgroundColor: '#a9d6b8',
    borderColor: '#236239',
    borderRadius: 6,
    tension: 0.3,
  }],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { min: 0, max: 100, ticks: { stepSize: 25 } },
    x: { grid: { display: false } },
  },
}

function barColor(percent) {
  if (percent >= 85) return 'bg-green-600'
  if (percent >= 60) return 'bg-amber-500'
  return 'bg-red-500'
}
</script>


<template>
  <div class="min-h-screen bg-gray-50">
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
        <h2 class="text-base font-semibold text-gray-800">Adherence report</h2>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto pb-24 md:pb-8 w-full">

      <!-- Range toggle -->
      <div class="flex bg-white rounded-lg p-1 shadow-sm mb-4">
        <button
          class="flex-1 py-2 rounded-md text-sm font-medium"
          :class="range === '7' ? 'bg-green-600 text-white' : 'text-gray-500'"
          @click="range = '7'"
        >
          7 days
        </button>
        <button
          class="flex-1 py-2 rounded-md text-sm font-medium"
          :class="range === '30' ? 'bg-green-600 text-white' : 'text-gray-500'"
          @click="range = '30'"
        >
          30 days
        </button>
      </div>

      <!-- Chart -->
      <div v-if="error" class="bg-red-50 text-red-700 rounded-xl p-4 mb-4 text-sm">{{ error }}</div>
      <div v-else class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <p class="text-3xl font-bold text-green-700 mb-1">{{ overall }}%</p>
        <p class="text-sm text-gray-500 mb-4">Overall adherence this period</p>
        <div style="height: 180px;">
          <Bar :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Per medication breakdown -->
      <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="font-medium text-gray-900 mb-4">Per medication</h2>
        <div class="space-y-4">
          <div v-for="med in byMedication" :key="med.name">
            <div class="flex justify-between text-sm mb-1">
              <span class="text-gray-700">{{ med.name }}</span>
              <span class="font-medium">{{ med.percent }}%</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full"
                :class="barColor(med.percent)"
                :style="{ width: `${med.percent}%` }"
              />
            </div>
          </div>
        </div>
      </div>

      </main>
    </div>

    <!-- bottom navigation bar-->
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
</template>

