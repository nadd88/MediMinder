<script setup>
import { ref, computed } from 'vue'
import { Bar, Line } from 'vue-chartjs'

const range = ref('7')  // '7' or '30'

const sevenDayData = {
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  values: [80, 100, 60, 85, 100, 70, 87],
}

const thirtyDayData = {
  labels: ['Wk 1', 'Wk 2', 'Wk 3', 'Wk 4'],
  values: [82, 75, 90, 87],
}

const currentData = computed(() => range.value === '7' ? sevenDayData : thirtyDayData)

const chartData = computed(() => ({
  labels: currentData.value.labels,
  datasets: [{
    label: 'Adherence %',
    data: currentData.value.values,
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

// Per medication breakdown
const byMedication = [
  { name: 'Metformin 500mg', percent: 95 },
  { name: 'Lisinopril 10mg', percent: 80 },
  { name: 'Aspirin 75mg', percent: 60 },
]

function barColor(percent) {
  if (percent >= 85) return 'bg-green-600'
  if (percent >= 60) return 'bg-amber-500'
  return 'bg-red-500'
}
</script>

<template>
  <div class="min-h-screen bg-green-50">
    <header class="bg-green-700 text-white px-4 py-4">
      <h1 class="text-xl font-bold">Adherence report</h1>
      <p class="text-sm opacity-80">Your medication compliance</p>
    </header>

    <main class="px-4 py-4 max-w-lg mx-auto pb-20">

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
      <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <p class="text-3xl font-bold text-green-700 mb-1">87%</p>
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

<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex">
      <router-link
        to="/patient"
        class="flex-1 flex flex-col items-center py-3 text-xs"
        active-class="text-green-700 font-semibold"
      >
        <span class="text-lg"><img src="@/assets/home-icon.png" alt="Home" width="24" height="24"></span>
        Dashboard
      </router-link>
      <router-link
        to="/patient/doses"
        class="flex-1 flex flex-col items-center py-3 text-xs"
        active-class="text-green-700 font-semibold"
      >
        <span class="text-lg"><img src="@/assets/pill-icon.png" alt="Doses" width="24" height="24"></span>
        Doses
      </router-link>
      <router-link
        to="/patient/adherence"
        class="flex-1 flex flex-col items-center py-3 text-xs"
        active-class="text-green-700 font-semibold"
      >
        <span class="text-lg"><img src="@/assets/adherence-icon.png" alt="Adherence" width="24" height="24"></span>
        Adherence
      </router-link>
    </nav>
  </div>
</template>