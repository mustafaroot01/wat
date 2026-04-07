import { ref } from 'vue'

export function usePagination<T>(endpoint: string) {
  const items = ref<T[]>([])
  const loading = ref(false)
  const currentPage = ref(1)
  const totalPages = ref(1)

  const fetchData = async (page = 1) => {
    loading.value = true
    currentPage.value = page
    try {
      const res = await fetch(`${endpoint}?page=${page}`)
      if (res.ok) {
        const body = await res.json()
        // Support both API Resource structure (body.data) and straight paginated results
        items.value = body.data || body.results || []
        // Support body.meta.last_page (Resources) or body.last_page (standard paginate)
        totalPages.value = body.meta?.last_page || body.last_page || 1
      } else {
        throw new Error('Failed to fetch data')
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
