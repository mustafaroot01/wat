<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'

interface OrderItem {
  id: number
  product_name: string
  quantity: number
  unit_price: string
  total_price: string
}

interface Order {
  id: number
  status: 'pending' | 'processing' | 'completed' | 'cancelled'
  total_amount: string
  discount_amount: string
  final_amount: string
  notes: string | null
  created_at: string
  items: OrderItem[]
}

interface Customer {
  id: number
  full_name: string
  phone: string
  district?: { name: string }
  area?: { name: string }
}

const route  = useRoute()
const router = useRouter()

const customerId = computed(() => route.params.id as string)
const customer   = ref<Customer | null>(null)
const orders     = ref<Order[]>([])
const loading    = ref(false)
const totalCount = ref(0)
const perPage    = ref(15)

const headers = [
  { title: 'رقم الطلب',     key: 'id',           align: 'start'  as const, sortable: true },
  { title: 'التاريخ',       key: 'created_at',   align: 'start'  as const, sortable: true },
  { title: 'المنتجات',      key: 'items',        align: 'start'  as const, sortable: false },
  { title: 'المبلغ الإجمالي', key: 'final_amount', align: 'start'  as const, sortable: true },
  { title: 'الحالة',        key: 'status',       align: 'center' as const, sortable: true },
]

const sortState = ref<{ sort_by?: string; sort_dir?: string }>({})

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    let url = `/api/admin/customers/${customerId.value}/orders?page=${page}&per_page=${perPage.value}`
    if (sortState.value.sort_by) {
      url += `&sort_by=${sortState.value.sort_by}&sort_dir=${sortState.value.sort_dir}`
    }
    
    const res = await apiFetch(url)
    if (res.ok) {
      const data = await res.json()
      customer.value   = data.customer
      orders.value     = data.data
      totalCount.value = data.meta.total
      perPage.value    = data.meta.per_page
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const handleOptionsChange = (options: { page: number; itemsPerPage: number; sortBy?: { key: string; order: string }[] }) => {
  perPage.value = options.itemsPerPage
  const sort = options.sortBy?.[0]
  sortState.value = sort ? { sort_by: sort.key, sort_dir: sort.order } : {}
  fetchOrders(options.page)
}

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('ar-IQ', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const getStatusColor = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    processing: 'info',
    completed: 'success',
    cancelled: 'error',
  }
  return map[status] || 'secondary'
}

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    pending: 'قيد الانتظار',
    processing: 'جاري التجهيز',
    completed: 'مكتمل',
    cancelled: 'ملغي',
  }
  return map[status] || status
}

onMounted(() => fetchOrders(1))
</script>

<template>
  <VRow>
    <VCol cols="12">

      <!-- Back + Header -->
      <div class="d-flex align-center gap-3 mb-5">
        <VBtn icon="ri-arrow-right-line" variant="tonal" color="secondary" size="small" rounded="lg" @click="router.push('/customers')" />
        <div>
          <h1 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-shopping-bag-3-line" color="primary" size="22" />
            سجل طلبات العميل
          </h1>
          <p v-if="customer" class="text-body-2 text-medium-emphasis mb-0 mt-1">
            العميل: <strong class="text-primary">{{ customer.full_name }}</strong>
            — هاتف: <strong class="text-primary" dir="ltr">{{ customer.phone }}</strong>
          </p>
        </div>
      </div>

      <!-- Table Card -->
      <VCard rounded="lg" elevation="0">
        <!-- Toolbar -->
        <div class="pa-4 d-flex align-center gap-3 flex-wrap border-b">
          <VSpacer />
          <div class="text-caption text-medium-emphasis">
            إجمالي الطلبات:
            <strong class="text-primary">{{ totalCount.toLocaleString() }}</strong>
          </div>
        </div>

        <!-- Table -->
        <VDataTableServer
          :headers="headers"
          :items="orders"
          :items-length="totalCount"
          :loading="loading"
          :items-per-page="perPage"
          :items-per-page-options="[15, 25, 50, 100]"
          no-data-text="لا توجد طلبات لهذا العميل"
          loading-text="جاري التحميل..."
          class="rounded-0"
          @update:options="handleOptionsChange"
        >
          <template #item.id="{ item }">
            <span class="font-weight-bold">#{{ item.id }}</span>
          </template>

          <template #item.created_at="{ item }">
            <div class="d-flex flex-column">
              <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
            </div>
          </template>

          <template #item.items="{ item }">
            <div class="d-flex flex-column gap-1 py-2">
              <div v-for="orderItem in item.items" :key="orderItem.id" class="text-caption d-flex justify-space-between align-center" style="min-width: 250px;">
                <span>
                  <strong class="text-primary">{{ orderItem.quantity }}x</strong>
                  {{ orderItem.product_name }}
                </span>
                <span class="text-medium-emphasis">{{ formatIQD(orderItem.total_price) }}</span>
              </div>
            </div>
          </template>

          <template #item.final_amount="{ item }">
            <div class="d-flex flex-column">
              <span class="font-weight-bold text-success">{{ formatIQD(item.final_amount) }}</span>
              <span v-if="Number(item.discount_amount) > 0" class="text-caption text-error">
                خصم: {{ formatIQD(item.discount_amount) }}
              </span>
            </div>
          </template>

          <template #item.status="{ item }">
            <VChip :color="getStatusColor(item.status)" size="small" variant="tonal" class="font-weight-medium">
              {{ getStatusLabel(item.status) }}
            </VChip>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>
</template>
