import { apiFetch } from '@/utils/apiFetch'
import { ref } from 'vue'

export function usePagination<T>(endpoint: string) {
  const items = ref<T[]>([])
  const loading = ref(false)
  const currentPage = ref(1)
  const totalPages = ref(1)

  const fetchData = async (page = 1, extraParams: Record<string, any> = {}) => {
    loading.value = true
    currentPage.value = page

    const params = new URLSearchParams({ page: String(page) })
    Object.entries(extraParams).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.set(k, String(v))
    })

    try {
      const res = await apiFetch(`${endpoint}?${params.toString()}`)
      if (res.ok) {
        const body = await res.json()
        items.value = body.data || body.results || []
        totalPages.value = body.meta?.last_page || body.last_page || 1
      } else {
        items.value = []
      }
    } catch (error) {
      console.error(`Error fetching from ${endpoint}:`, error)
      items.value = []
    } finally {
      loading.value = false
    }
  }

  const handlePageChange = (page: number) => {
    fetchData(page)
  }

  return {
    items,
    loading,
    currentPage,
    totalPages,
    fetchData,
    handlePageChange,
  }
}
