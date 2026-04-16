<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { onMounted, ref, watch } from 'vue'

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleString('ar-EG', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

interface ActivityLog {
  id: number
  admin_id: number
  admin: { id: number; name: string; email: string }
  action: string
  model_type: string
  model_id: number | null
  description: string
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address: string
  user_agent: string
  device_type: string
  created_at: string
}

const logs = ref<ActivityLog[]>([])
const loading = ref(false)
const totalItems = ref(0)
const currentPage = ref(1)
const perPage = ref(50)

// Filters
const searchQuery = ref('')
const adminFilter = ref<number | null>(null)
const actionFilter = ref<string | null>(null)
const modelTypeFilter = ref<string | null>(null)
const fromDate = ref('')
const toDate = ref('')

// Filter options
const admins = ref<any[]>([])
const actions = ref<string[]>([])
const modelTypes = ref<string[]>([])

// Detail dialog
const showDetailDialog = ref(false)
const selectedLog = ref<ActivityLog | null>(null)

const headers = [
  { title: '#', key: 'id', align: 'center' as const, sortable: false, width: '80px' },
  { title: 'المشرف', key: 'admin', align: 'start' as const, sortable: false },
  { title: 'العملية', key: 'description', align: 'start' as const, sortable: false },
  { title: 'النوع', key: 'model_type', align: 'center' as const, sortable: false },
  { title: 'الجهاز / IP', key: 'device', align: 'start' as const, sortable: false },
  { title: 'التاريخ', key: 'created_at', align: 'center' as const, sortable: false },
  { title: 'تفاصيل', key: 'actions', align: 'center' as const, sortable: false },
]

const loadLogs = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  
  const params: any = { page, per_page: perPage.value }
  if (searchQuery.value) params.search = searchQuery.value
  if (adminFilter.value) params.admin_id = adminFilter.value
  if (actionFilter.value) params.action = actionFilter.value
  if (modelTypeFilter.value) params.model_type = modelTypeFilter.value
  if (fromDate.value) params.from_date = fromDate.value
  if (toDate.value) params.to_date = toDate.value

  try {
    const queryString = new URLSearchParams(params).toString()
    const res = await apiFetch(`/api/admin/activity-logs?${queryString}`)
    if (res.ok) {
      const data = await res.json()
      logs.value = data.data || []
      totalItems.value = data.total || 0
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const loadFilterOptions = async () => {
  try {
    const res = await apiFetch('/api/admin/activity-logs/filter-options')
    if (res.ok) {
      const data = await res.json()
      admins.value = data.admins || []
      actions.value = data.actions || []
      modelTypes.value = data.model_types || []
    }
  } catch (e) {
    console.error(e)
  }
}

const showDetails = async (log: ActivityLog) => {
  try {
    const res = await apiFetch(`/api/admin/activity-logs/${log.id}`)
    if (res.ok) {
      const data = await res.json()
      selectedLog.value = data.data
      showDetailDialog.value = true
    }
  } catch (e) {
    console.error(e)
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  adminFilter.value = null
  actionFilter.value = null
  modelTypeFilter.value = null
  fromDate.value = ''
  toDate.value = ''
  loadLogs(1)
}

const deviceIcon = (type: string) => {
  switch (type) {
    case 'mobile': return 'ri-smartphone-line'
    case 'tablet': return 'ri-tablet-line'
    case 'desktop': return 'ri-computer-line'
    default: return 'ri-device-line'
  }
}

const deviceLabel = (type: string) => {
  switch (type) {
    case 'mobile': return 'هاتف'
    case 'tablet': return 'تابلت'
    case 'desktop': return 'حاسوب'
    default: return 'غير معروف'
  }
}

const actionColor = (action: string) => {
  switch (action) {
    case 'created': return 'success'
    case 'updated': return 'info'
    case 'deleted': return 'error'
    case 'toggled_active': 
    case 'toggled_stock': return 'warning'
    case 'status_changed': return 'primary'
    default: return 'secondary'
  }
}

const actionLabel = (action: string) => {
  switch (action) {
    case 'created': return 'إضافة'
    case 'updated': return 'تعديل'
    case 'deleted': return 'حذف'
    case 'toggled_active': return 'تفعيل/تعطيل'
    case 'toggled_stock': return 'مخزون'
    case 'status_changed': return 'تغيير حالة'
    default: return action
  }
}

const modelTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    'Product': 'منتج',
    'Category': 'قسم',
    'Brand': 'شركة',
    'Order': 'طلب',
    'Customer': 'عميل',
    'Coupon': 'كود خصم',
    'Banner': 'بانر',
    'Discount': 'خصم مميز',
    'District': 'قضاء',
    'Area': 'منطقة',
    'Notification': 'إشعار',
    'Admin': 'مشرف',
    'Setting': 'إعداد',
  }
  return labels[type] || type
}

// Watch filters
watch([searchQuery, adminFilter, actionFilter, modelTypeFilter, fromDate, toDate], () => {
  loadLogs(1)
})

onMounted(() => {
  loadLogs()
  loadFilterOptions()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- Header -->
      <div class="d-flex align-center justify-space-between mb-6">
        <div>
          <h1 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-history-line" color="primary" size="28" />
            سجل النشاطات
          </h1>
          <p class="text-body-2 text-medium-emphasis mt-1 mb-0">
            جميع العمليات التي قام بها المشرفون في لوحة التحكم
          </p>
        </div>
      </div>

      <!-- Filters Card -->
      <VCard rounded="lg" elevation="0" class="mb-4">
        <VCardText class="pa-5">
          <VRow dense>
            <VCol cols="12" md="3">
              <VTextField
                v-model="searchQuery"
                label="بحث في الوصف"
                prepend-inner-icon="ri-search-line"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>

            <VCol cols="12" md="3">
              <VSelect
                v-model="adminFilter"
                :items="admins"
                item-title="name"
                item-value="id"
                label="المشرف"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>

            <VCol cols="12" md="2">
              <VSelect
                v-model="actionFilter"
                :items="actions"
                label="نوع العملية"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>

            <VCol cols="12" md="2">
              <VSelect
                v-model="modelTypeFilter"
                :items="modelTypes"
                label="النوع"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>

            <VCol cols="12" md="2">
              <VBtn
                color="secondary"
                variant="tonal"
                block
                rounded="lg"
                @click="resetFilters"
              >
                <VIcon icon="ri-refresh-line" start />
                إعادة تعيين
              </VBtn>
            </VCol>

            <VCol cols="12" md="3">
              <VTextField
                v-model="fromDate"
                type="date"
                label="من تاريخ"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>

            <VCol cols="12" md="3">
              <VTextField
                v-model="toDate"
                type="date"
                label="إلى تاريخ"
                variant="outlined"
                density="compact"
                clearable
                hide-details
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Table -->
      <VCard rounded="lg" elevation="0">
        <VDataTable
          :headers="headers"
          :items="logs"
          :loading="loading"
          :items-length="totalItems"
          :page="currentPage"
          :items-per-page="perPage"
          no-data-text="لا توجد نشاطات"
          loading-text="جاري التحميل..."
          @update:page="loadLogs"
        >
          <!-- Admin -->
          <template #item.admin="{ item }">
            <div class="d-flex align-center gap-2 py-2">
              <VAvatar color="primary" variant="tonal" size="32">
                <VIcon icon="ri-user-line" size="16" />
              </VAvatar>
              <div>
                <div class="font-weight-medium text-body-2">{{ item.admin.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.admin.email }}</div>
              </div>
            </div>
          </template>

          <!-- Description -->
          <template #item.description="{ item }">
            <div class="d-flex align-center gap-2">
              <VChip :color="actionColor(item.action)" size="x-small" variant="tonal">
                {{ actionLabel(item.action) }}
              </VChip>
              <span class="text-body-2">{{ item.description }}</span>
            </div>
          </template>

          <!-- Model Type -->
          <template #item.model_type="{ item }">
            <VChip size="small" variant="outlined" color="secondary">
              {{ modelTypeLabel(item.model_type) }}
            </VChip>
          </template>

          <!-- Device -->
          <template #item.device="{ item }">
            <div>
              <div class="d-flex align-center gap-1 mb-1">
                <VIcon :icon="deviceIcon(item.device_type)" size="14" />
                <span class="text-caption">{{ deviceLabel(item.device_type) }}</span>
              </div>
              <div class="text-caption text-medium-emphasis" style="font-family:monospace;" dir="ltr">
                {{ item.ip_address }}
              </div>
            </div>
          </template>

          <!-- Date -->
          <template #item.created_at="{ item }">
            <div class="text-caption">{{ formatDate(item.created_at) }}</div>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <VBtn
              icon="ri-eye-line"
              variant="text"
              size="small"
              color="primary"
              @click="showDetails(item)"
            />
          </template>
        </VDataTable>
      </VCard>
    </VCol>
  </VRow>

  <!-- Details Dialog -->
  <VDialog v-model="showDetailDialog" max-width="700">
    <VCard v-if="selectedLog" rounded="lg">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold">
          <VIcon icon="ri-file-list-line" class="me-2" />
          تفاصيل النشاط
        </span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDetailDialog = false" />
      </VCardTitle>

      <VCardText class="pa-6">
        <VRow dense>
          <VCol cols="12">
            <div class="text-caption text-medium-emphasis mb-1">المشرف</div>
            <div class="font-weight-medium">{{ selectedLog.admin.name }} ({{ selectedLog.admin.email }})</div>
          </VCol>

          <VCol cols="12" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">الوصف</div>
            <div>{{ selectedLog.description }}</div>
          </VCol>

          <VCol cols="6" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">نوع العملية</div>
            <VChip :color="actionColor(selectedLog.action)" size="small" variant="tonal">
              {{ actionLabel(selectedLog.action) }}
            </VChip>
          </VCol>

          <VCol cols="6" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">النوع</div>
            <VChip size="small" variant="outlined">
              {{ modelTypeLabel(selectedLog.model_type) }}
            </VChip>
          </VCol>

          <VCol cols="6" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">IP Address</div>
            <div style="font-family:monospace;" dir="ltr">{{ selectedLog.ip_address }}</div>
          </VCol>

          <VCol cols="6" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">نوع الجهاز</div>
            <div class="d-flex align-center gap-1">
              <VIcon :icon="deviceIcon(selectedLog.device_type)" size="16" />
              {{ deviceLabel(selectedLog.device_type) }}
            </div>
          </VCol>

          <VCol cols="12" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">User Agent</div>
            <div class="text-caption" style="word-break:break-all;">{{ selectedLog.user_agent }}</div>
          </VCol>

          <VCol cols="12" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-1">التاريخ</div>
            <div>{{ formatDate(selectedLog.created_at) }}</div>
          </VCol>

          <!-- Old Values -->
          <VCol v-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0" cols="12" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-2">القيم القديمة</div>
            <VCard rounded="lg" color="grey-lighten-4" variant="flat" class="pa-3">
              <pre class="text-caption" style="white-space:pre-wrap;">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
            </VCard>
          </VCol>

          <!-- New Values -->
          <VCol v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0" cols="12" class="mt-4">
            <div class="text-caption text-medium-emphasis mb-2">القيم الجديدة</div>
            <VCard rounded="lg" color="success-lighten-5" variant="flat" class="pa-3">
              <pre class="text-caption" style="white-space:pre-wrap;">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
pre {
  margin: 0;
  font-family: 'Courier New', monospace;
  direction: ltr;
  text-align: left;
}
</style>
