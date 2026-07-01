const nativeApiFallback = 'https://mediminder-1-x9h8.onrender.com'
const isNativeApp = typeof window !== 'undefined' && !!window.Capacitor?.isNativePlatform?.()

export const hasConfiguredApiUrl = Boolean(import.meta.env.VITE_API_URL)

export const useDemoApi =
  !hasConfiguredApiUrl &&
  typeof window !== 'undefined' &&
  window.location.hostname !== 'localhost' &&
  window.location.hostname !== '127.0.0.1'

export const apiBaseUrl = import.meta.env.VITE_API_URL || (isNativeApp ? nativeApiFallback : 'http://localhost:8000')
