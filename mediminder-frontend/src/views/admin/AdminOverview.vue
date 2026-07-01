<script setup>
import AdminLayout from './AdminLayout.vue'
</script>

<template>
  <AdminLayout active="patients" title="Admin overview" subtitle="Monitor patients, prescriptions, and reporting.">
    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Patients</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">3</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Active prescriptions</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">8</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">This week</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">92%</p>
      </div>
    </div>
  </AdminLayout>
</template>
