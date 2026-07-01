<script setup>
import { ref, computed, onMounted } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { useSidebar } from '@/composables/useSidebar'
import { patientApi } from '../../api/patientApi'

const { sidebarOpen, toggleSidebar, closeSidebar } = useSidebar()

const LOW_STOCK_DAYS = 5

const supplies = ref([])
const loading = ref(true)
const error = ref(null)

async function loadSupplies() {
  loading.value = true
  error.value = null
  try {
    const response = await patientApi.getSupply()
    if (response.success) {
      supplies.value = response.data.map(med => ({
        ...med,
        remaining: Number(med.remaining),
        dailyDose: Number(med.dailyDose),
        totalPack: Number(med.totalPack),
      }))
    }
  } catch (err) {
    console.error('Failed to load supplies:', err)
    error.value = 'Failed to load medicine supply. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadSupplies)

function daysLeft(item) {
  if (!item.dailyDose) return Infinity
  return Math.floor(item.remaining / item.dailyDose)
}

function status(item) {
  const d = daysLeft(item)
  if (item.remaining <= 0) return 'empty'
  if (d <= LOW_STOCK_DAYS) return 'low'
  return 'good'
}

function statusLabel(item) {
  const s = status(item)
  if (s === 'empty') return 'Out of stock'
  const d = daysLeft(item)
  return `${d} day${d === 1 ? '' : 's'} left`
}

const pctRemaining = (item) =>
  Math.min(100, Math.round((item.remaining / item.totalPack) * 100))

const lowStockItems = computed(() => supplies.value.filter(i => status(i) !== 'good'))
const sortedSupplies = computed(() =>
  [...supplies.value].sort((a, b) => daysLeft(a) - daysLeft(b))
)

const pending = ref(null)

function askRefill(med) {
  pending.value = { med, amount: med.totalPack }
}

async function confirmRefill() {
  if (!pending.value) return
  const amt = Number(pending.value.amount)
  if (!amt || amt <= 0) return

  await patientApi.refillSupply(pending.value.med.id, amt)

  const item = supplies.value.find(i => i.id === pending.value.med.id)
  if (item) {
    item.remaining += amt
    item.lastRefill = 'Today'
  }

  pending.value = null
}

function cancelRefill() {
  pending.value = null
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
        <h2 class="text-base font-semibold text-gray-800">Medicine supply</h2>
      </header>

      <main class="px-4 py-4 max-w-lg mx-auto pb-24 md:pb-8 space-y-3">

        <!-- Low stock alert banner -->
        <div
          v-if="lowStockItems.length"
          class="bg-amber-50 border border-amber-200 rounded-xl p-4"
        >
          <p class="text-sm font-medium text-amber-800">
            {{ lowStockItems.length }} medication{{ lowStockItems.length > 1 ? 's' : '' }} running low
          </p>
          <p class="text-sm text-amber-700 mt-0.5">
            {{ lowStockItems.map(i => i.name).join(', ') }} - refill soon to avoid missed doses.
          </p>
        </div>

        <!-- Supply cards -->
        <div
          v-for="item in sortedSupplies"
          :key="item.id"
          class="bg-white rounded-xl p-4 shadow-sm"
        >
          <div class="flex justify-between items-start mb-3">
            <div>
              <p class="font-medium text-gray-900">{{ item.name }}</p>
              <p class="text-sm text-gray-500">{{ item.dose }}</p>
            </div>

            <!-- Status badge -->
            <span
              class="text-xs font-medium px-2 py-1 rounded-full"
              :class="{
                'bg-green-100 text-green-700': status(item) === 'good',
                'bg-amber-100 text-amber-700': status(item) === 'low',
                'bg-red-100 text-red-700': status(item) === 'empty',
              }"
            >
              {{ statusLabel(item) }}
            </span>
          </div>

          <!-- Progress bar -->
          <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden mb-2">
            <div
              class="h-full rounded-full"
              :class="{
                'bg-green-500': status(item) === 'good',
                'bg-amber-500': status(item) === 'low',
                'bg-red-500': status(item) === 'empty',
              }"
              :style="{ width: pctRemaining(item) + '%' }"
            />
          </div>

          <div class="flex justify-between items-center">
            <p class="text-sm text-gray-400">
              {{ item.remaining }} of {{ item.totalPack }} left - last refill {{ item.lastRefill }}
            </p>
            <button
              class="py-2 px-3 rounded-lg bg-green-50 text-green-700 text-sm font-medium hover:bg-green-100"
              @click="askRefill(item)"
            >
              Refill
            </button>
          </div>
        </div>

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

    <!-- Refill confirmation modal -->
    <div
      v-if="pending"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
    >
      <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-lg">
        <h2 class="font-semibold text-gray-900 mb-1">
          Refill {{ pending.med.name }}?
        </h2>
        <p class="text-sm text-gray-500 mb-4">
          Currently {{ pending.med.remaining }} left. Enter how many you're adding.
        </p>

        <label class="text-xs font-medium text-gray-500 mb-1 block">Quantity added</label>
        <input
          v-model="pending.amount"
          type="number"
          min="1"
          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-green-500 focus:outline-none mb-4"
        />

        <div class="flex gap-2">
          <button
            class="flex-1 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm"
            @click="cancelRefill"
          >
            Cancel
          </button>
          <button
            class="flex-1 py-2 rounded-lg text-white text-sm font-medium bg-green-600 hover:bg-green-700"
            @click="confirmRefill"
          >
            Confirm refill
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
