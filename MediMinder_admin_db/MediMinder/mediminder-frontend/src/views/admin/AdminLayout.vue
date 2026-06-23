<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  active: { type: String, default: '' },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
})

const router = useRouter()
const auth = useAuthStore()

const nav = [
  { key: 'patients', label: 'All patients', to: '/admin/patients', section: 'PATIENTS' },
  { key: 'prescriptions', label: 'Prescriptions', to: '/admin/prescriptions', section: 'PATIENTS' },
  { key: 'reports', label: 'Reports', to: '/admin/reports', section: 'PATIENTS' },
  { key: 'audit', label: 'Audit log', to: '/admin/reports', section: 'SYSTEM' },
]

const grouped = computed(() => {
  const out = {}
  for (const item of nav) {
    ;(out[item.section] ??= []).push(item)
  }
  return out
})

const initials = computed(() => {
  const n = auth.name || 'Admin'
  return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()
})

function logout() {
  auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="flex min-h-screen bg-slate-50 text-slate-800">
    <!-- Sidebar -->
    <aside class="flex w-64 shrink-0 flex-col bg-[#0e4d3a] text-emerald-50">
      <div class="flex items-center gap-2 px-6 py-5">
        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-400/20 text-lg">🔗</span>
        <span class="text-lg font-semibold tracking-tight">MediMinder</span>
      </div>

      <nav class="flex-1 space-y-6 px-3 py-2">
        <div v-for="(items, section) in grouped" :key="section">
          <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-emerald-200/60">
            {{ section }}
          </p>
          <RouterLink
            v-for="item in items"
            :key="item.label"
            :to="item.to"
            class="block rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="props.active === item.key
              ? 'bg-emerald-400/20 text-white'
              : 'text-emerald-100/80 hover:bg-emerald-400/10 hover:text-white'"
          >
            {{ item.label }}
          </RouterLink>
        </div>
      </nav>

      <!-- User footer -->
      <div class="m-3 flex items-center gap-3 rounded-lg bg-emerald-400/10 px-3 py-3">
        <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-300 text-sm font-bold text-emerald-900">
          {{ initials }}
        </span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-white">{{ auth.name || 'Admin' }}</p>
          <p class="text-xs text-emerald-200/70">Clinic Admin</p>
        </div>
        <button
          class="text-emerald-200/70 hover:text-white"
          title="Log out"
          @click="logout"
        >⎋</button>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-x-hidden">
      <header v-if="title" class="border-b border-slate-200 bg-white px-8 py-5">
        <h1 class="text-xl font-semibold text-slate-900">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
      </header>
      <div class="p-8">
        <slot />
      </div>
    </main>
  </div>
</template>
