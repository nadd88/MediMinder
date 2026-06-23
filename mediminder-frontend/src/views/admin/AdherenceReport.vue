<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Filler,
} from 'chart.js'
import AdminLayout from './AdminLayout.vue'
import api from '../../services/api'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const route = useRoute()

const patients = ref([])
const selectedPatientId = ref(null)
const report = ref(null)
const audit = ref([])
const loading = ref(false)

// Default range: last 30 days (matches the desktop mockup "01 Jun – 14 Jun" style window).
const today = new Date()
const range = reactive({
  from: new Date(today.getTime() - 29 * 86400000).toISOString().slice(0, 10),
  to: today.toISOString().slice(0, 10),
})

const selectedPatient = computed(() =>
  patients.value.find((p) => p.id === selectedPatientId.value),
)

const summary = computed(() => report.value?.summary ?? { taken: 0, skipped: 0, missed: 0, adherence: 0 })
const perMed = computed(() => report.value?.per_medication ?? [])

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function fmtDateTime(d) {
  if (!d) return '—'
  return new Date(d.replace(' ', 'T')).toLocaleString('en-GB', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Colour the per-medication adherence bar by band (green/amber/red).
function barColor(pct) {
  if (pct >= 80) return 'bg-emerald-500'
  if (pct >= 60) return 'bg-amber-400'
  return 'bg-red-400'
}

const csvUrl = computed(() =>
  selectedPatientId.value
    ? api.reportCsvUrl(selectedPatientId.value, range.from, range.to)
    : '#',
)

// ---- daily-trend chart ----
const chartData = computed(() => {
  const trend = report.value?.daily_trend ?? []
  return {
    labels: trend.map((t) =>
      new Date(t.day).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }),
    ),
    datasets: [
      {
        label: 'Daily adherence %',
        data: trend.map((t) => t.adherence),
        borderColor: '#0e4d3a',
        backgroundColor: 'rgba(16, 185, 129, 0.12)',
        fill: true,
        tension: 0.35,
        pointRadius: 2,
        pointBackgroundColor: '#0e4d3a',
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } },
    x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
  },
}

async function loadPatients() {
  const { data } = await api.listPatients()
  patients.value = data.patients
  const fromQuery = Number(route.query.patient)
  selectedPatientId.value = fromQuery || patients.value[0]?.id || null
}

async function loadReport() {
  if (!selectedPatientId.value) return
  loading.value = true
  try {
    const [{ data: rep }, { data: aud }] = await Promise.all([
      api.getReport(selectedPatientId.value, range.from, range.to),
      api.listAudit(selectedPatientId.value, 8),
    ])
    report.value = rep
    audit.value = aud.audit
  } finally {
    loading.value = false
  }
}

watch(selectedPatientId, loadReport)

onMounted(async () => {
  await loadPatients()
  await loadReport()
})
</script>

<template>
  <AdminLayout
    active="reports"
    title="Adherence report"
    :subtitle="selectedPatient ? `${selectedPatient.name} · ${fmtDate(range.from)} – ${fmtDate(range.to)}` : 'Select a patient'"
  >
    <!-- Controls: patient + date range + export -->
    <div class="mb-6 flex flex-wrap items-end gap-3">
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">Patient</label>
        <select
          v-model.number="selectedPatientId"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
        >
          <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">From</label>
        <input
          v-model="range.from"
          type="date"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-500">To</label>
        <input
          v-model="range.to"
          type="date"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
        />
      </div>
      <button
        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900"
        @click="loadReport"
      >
        Generate
      </button>
      <a
        :href="csvUrl"
        class="rounded-lg border border-emerald-600 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
      >
        Export CSV
      </a>
    </div>

    <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Loading report…</div>

    <template v-else>
      <!-- Summary cards -->
      <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Overall adherence</p>
          <p class="mt-1 text-3xl font-bold text-emerald-700">{{ summary.adherence }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Doses taken</p>
          <p class="mt-1 text-3xl font-bold text-slate-900">{{ summary.taken }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Doses missed</p>
          <p class="mt-1 text-3xl font-bold text-red-500">{{ summary.missed }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Skipped doses</p>
          <p class="mt-1 text-3xl font-bold text-amber-500">{{ summary.skipped }}</p>
        </div>
      </div>

      <!-- Trend chart -->
      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Daily adherence trend</h2>
        <div class="h-56">
          <Line v-if="(report?.daily_trend?.length || 0) > 0" :data="chartData" :options="chartOptions" />
          <p v-else class="py-12 text-center text-sm text-slate-400">No dose data in this range.</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-5">
        <!-- Per-medication table -->
        <section class="lg:col-span-3">
          <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-5 py-3">
              <h2 class="text-sm font-semibold text-slate-900">Per-medication adherence</h2>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="px-5 py-2.5 font-semibold">Medication</th>
                  <th class="px-5 py-2.5 font-semibold">Taken</th>
                  <th class="px-5 py-2.5 font-semibold">Missed</th>
                  <th class="px-5 py-2.5 font-semibold">Adherence</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="m in perMed" :key="m.medication" class="hover:bg-slate-50">
                  <td class="px-5 py-3 font-medium text-slate-900">
                    {{ m.medication }} <span class="text-slate-400">{{ m.strength }}</span>
                  </td>
                  <td class="px-5 py-3 text-slate-600">{{ m.taken }}</td>
                  <td class="px-5 py-3 text-slate-600">{{ m.missed }}</td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                      <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full" :class="barColor(m.adherence)" :style="{ width: m.adherence + '%' }" />
                      </div>
                      <span class="text-xs font-semibold text-slate-700">{{ m.adherence }}%</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!perMed.length">
                  <td colspan="4" class="px-5 py-6 text-center text-sm text-slate-400">
                    No medications with logged doses in this range.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Audit log panel -->
        <section class="lg:col-span-2">
          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Audit log</h2>
            <ul class="space-y-3">
              <li v-for="a in audit" :key="a.id" class="flex items-start gap-2 text-sm">
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500" />
                <div class="min-w-0">
                  <p class="text-slate-700">
                    <span class="font-medium text-slate-900">{{ a.actor_name || 'System' }}</span>
                    — {{ a.detail || a.action }}
                  </p>
                  <p class="text-xs text-slate-400">{{ fmtDateTime(a.created_at) }}</p>
                </div>
              </li>
              <li v-if="!audit.length" class="text-sm text-slate-400">No audit entries yet.</li>
            </ul>
          </div>
        </section>
      </div>
    </template>
  </AdminLayout>
</template>
