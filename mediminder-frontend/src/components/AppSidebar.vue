<script setup>
const props = defineProps({
  open: Boolean
})
const emit = defineEmits(['close'])
</script>

<template>
  <!-- Overlay (mobile only) -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black/40 z-20 md:hidden"
    @click="emit('close')"
  />

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full w-56 bg-white border-r border-gray-200 z-30 flex flex-col
           transform transition-transform duration-300 ease-in-out"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <!-- Header -->
    <div class="bg-green-700 px-5 py-5 flex items-start justify-between">
      <div>
        <img src="@/assets/MediMinder_Logo_White.png" alt="MediMinder" width="120">
        <p class="text-green-100 text-xs mt-2">Good Morning, {{ auth.name }}</p>
      </div>
      <button
        @click="emit('close')"
        class="text-white/70 hover:text-white text-xl leading-none mt-0.5"
        aria-label="Close sidebar"
      >✕</button>
    </div>

    <!-- Nav links — swap these per role -->
    <nav class="flex flex-col gap-1 p-3 flex-1">
      <slot name="nav-links" />
    </nav>
  </aside>
</template>