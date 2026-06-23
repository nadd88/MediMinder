<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const summary = ref({
  adherence7day: 87,
  dueToday: 3,
  missedToday: 1,
})

const medications = ref([
  { id: 1, name: 'Metformin 500mg', dose: '1 tablet', time: '8:00 AM', status: 'taken' },
  { id: 2, name: 'Lisinopril 10mg', dose: '1 tablet', time: '12:00 PM', status: 'due' },
  { id: 3, name: 'Aspirin 75mg', dose: '1 tablet', time: '9:00 PM', status: 'missed' },
])

function statusColor(status) {
  if (status === 'taken') return 'bg-green-100 text-green-700'
  if (status === 'due') return 'bg-amber-100 text-amber-700'
  if (status === 'missed') return 'bg-red-100 text-red-700'
  return 'bg-gray-100 text-gray-500'
}

function statusLabel(status) {
  if (status === 'taken') return 'Taken'
  if (status === 'due') return 'Due now'
  if (status === 'missed') return 'Missed'
  return 'Upcoming'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-white">

    <!-- Green header section -->
    <header class="bg-green-700 px-4 pt-6 pb-8">
      <p class="text-sm opacity-80">Good Morning,</p>
      <h1 class="text-xl font-bold">{{ auth.name }}</h1>
    </header>

    <!-- Main content pulled up to overlap header -->
    <main class="px-4 max-w-lg mx-auto pb-24 -mt-4">

      <!-- Stat cards -->
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
              <p class="text-sm text-gray-500">{{ med.dose }} · {{ med.time }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-1 rounded-full" :class="statusColor(med.status)">
              {{ statusLabel(med.status) }}
            </span>
          </div>
        </div>
      </section>
    </main>

    <!-- Bottom nav -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex">
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
    </nav>
  </div>
</template>