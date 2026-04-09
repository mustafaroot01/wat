import { ref } from 'vue'

interface Branding {
  store_name: string
  logo_url: string | null
}

const branding = ref<Branding>({ store_name: 'امواج ديالى', logo_url: null })

export function useStoreSettings() {
  const fetchBranding = async () => {
    try {
      const res  = await fetch('/api/app/branding?t=' + Date.now())
      const data = await res.json()
      branding.value = {
        store_name: data.store_name,
        logo_url: data.logo_url ? data.logo_url + '?t=' + Date.now() : null,
      }
    } catch {
      // fallback stays as default
    }
  }

  return { branding, fetchBranding }
}
