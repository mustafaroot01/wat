import { ref } from 'vue'

interface Branding {
  store_name: string
  logo_url: string | null
}

const branding = ref<Branding>({ store_name: 'امواج ديالى', logo_url: null })
const loaded   = ref(false)

export function useStoreSettings() {
  const fetchBranding = async () => {
    if (loaded.value) return
    try {
      const res  = await fetch('/api/app/branding')
      const data = await res.json()
      branding.value = data
      loaded.value   = true
    } catch {
      // fallback stays as default
    }
  }

  return { branding, fetchBranding }
}
