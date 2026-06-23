import { ref } from 'vue'

export function useSidebar() {
  const sidebarOpen = ref(false)
  const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value }
  const closeSidebar = () => { sidebarOpen.value = false }
  return { sidebarOpen, toggleSidebar, closeSidebar }
}