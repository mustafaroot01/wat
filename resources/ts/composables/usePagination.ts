import { apiFetch } from '@/utils/apiFetch'
import { ref } from 'vue'

export function usePagination<T>(endpoint: string) {
  const items        = ref<T[]>([])
  const loading      = ref(false)
  const currentPage  = ref(1)
  const totalPages   = ref(1)
  const totalItems   = ref(0)
  const itemsPerPage = ref(15)

  const fetchData = async (page = 1, extraParams: Record<string, any> = {}) => {
    loading.value = true
    currentPage.value = page

    const params = new URLSearchParams({
      page:     String(page),
      per_page: String(itemsPerPage.value),
    })
    Object.entries(extraParams).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params.set(k, String(v))
    })

    try {
      const res = await apiFetch(`${endpoint}?${params.toString()}`)
      if (res.ok) {
        const body = await res.json()
        items.value      = body.data || body.results || []
        totalPages.value = body.meta?.last_page  || body.last_page  || 1
        totalItems.value = body.meta?.total       || body.total       || items.value.length
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

  const handlePageChange = (page: number) => fetchData(page)

  const handleOptionsChange = (options: { page: number; itemsPerPage: number; sortBy?: { key: string; order: string }[] }) => {
    itemsPerPage.value = options.itemsPerPage
    const sort = options.sortBy?.[0]
    fetchData(options.page, sort ? { sort_by: sort.key, sort_dir: sort.order } : {})
  }

  return {
    items,
    loading,
    currentPage,
    totalPages,
    totalItems,
    itemsPerPage,
    fetchData,
    handlePageChange,
    handleOptionsChange,
  }
}
