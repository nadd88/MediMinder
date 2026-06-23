<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const sidebarOpen = ref(false)

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

function closeSidebar() {
  sidebarOpen.value = false
}

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
  <div class="min-h-screen bg-gray-50">

    <!-- 1. OVERLAY — sits behind sidebar, closes it when tapped (mobile only) -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/40 z-20 md:hidden"
      @click="closeSidebar"
    />

    <!-- 2. SIDEBAR — slides in/out on ALL screen sizes -->
    <aside
      class="fixed top-0 left-0 h-full w-56 bg-white border-r border-gray-200 z-30 flex flex-col
             transform transition-transform duration-300 ease-in-out"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Sidebar header -->
      <div class="bg-green-700 px-5 py-5 flex items-start justify-between">
        <div>
          <img src="@/assets/MediMinder_Logo_White_v2.png" alt="MediMinder" width="200" height="200">
          <p class="text-green-100 text-xs mt-2">Good Morning, {{ auth.name }}</p>
        </div>
        <button
          @click="closeSidebar"
          class="text-white/70 hover:text-white text-xl leading-none mt-0.5"
          aria-label="Close sidebar"
        >
          ✕
        </button>
      </div>

      <!-- Nav links -->
      <nav class="flex flex-col gap-1 p-3 flex-1">
        <router-link
          to="/patient"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100"
          active-class="bg-green-50 text-green-700 font-semibold"
          @click="closeSidebar"
        >
          <img src="@/assets/home-icon.png" alt="" width="20" height="20">
          Dashboard
        </router-link>
        <router-link
          to="/patient/doses"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100"
          active-class="bg-green-50 text-green-700 font-semibold"
          @click="closeSidebar"
        >
          <img src="@/assets/pill-icon.png" alt="" width="20" height="20">
          Doses
        </router-link>
        <router-link
          to="/patient/adherence"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100"
          active-class="bg-green-50 text-green-700 font-semibold"
          @click="closeSidebar"
        >
          <img src="@/assets/adherence-icon.png" alt="" width="20" height="20">
          Adherence
        </router-link>
      </nav>
    </aside>

    <!-- 3. MAIN CONTENT -->
    <div class="flex flex-col min-h-screen">

      <!-- Top bar with hamburger -->
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

      <!-- Mobile-only green greeting strip -->
      <div class="md:hidden bg-green-700 px-4 pt-4 pb-8">
        <p class="text-sm text-white/80">Good Morning,</p>
        <h1 class="text-xl font-bold text-white">{{ auth.name }}</h1>
      </div>

      <!-- Page content -->
      <main class="px-4 md:px-8 py-4 md:py-6 -mt-4 md:mt-0 max-w-4xl w-full pb-24 md:pb-8">

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
    </div>

    <!-- 4. MOBILE BOTTOM NAV -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex z-10">
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