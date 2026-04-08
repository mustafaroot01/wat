<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'

interface UsageRow {
  id: number
  user_name: string
  user_phone: string
  discount_amount: number
  used_at: string
}

interface Coupon {
  id: number
  code: string
  type: string
  type_label: string
  value: number
  used_count: number
  max_uses: number | null
  remaining_uses: number | null
  expires_at: string | null
  is_active: boolean
  is_valid: boolean
}

const route  = useRoute()
const router = useRouter()

const couponId   = computed(() => route.params.id as string)
const coupon     = ref<Coupon | null>(null)
const usages     = ref<UsageRow[]>([])
const loading    = ref(false)
const currentPage = ref(1)
const totalPages  = ref(1)
const totalCount  = ref(0)
const perPage     = ref(50)
const search      = ref('')

const fetchUsages = async (page = 1) => {
  loading.value = true
  try {
    const res = await apiFetch(
      `/api/admin/coupons/${couponId.value}/usages?page=${page}&per_page=${perPage.value}`
    )
    if (res.ok) {
      const data = await res.json()
      coupon.value      = data.coupon
      usages.value      = data.data
      currentPage.value = data.meta.current_page
      totalPages.value  = data.meta.last_page
      totalCount.value  = data.meta.total
    }
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const handlePageChange = (page: number) => fetchUsages(page)

const filteredUsages = computed(() => {
  if (!search.value.trim()) return usages.value
  const q = search.value.toLowerCase()
  return usages.value.filter(u =>
    u.user_name.toLowerCase().includes(q) || u.user_phone.includes(q)
  )
})

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('ar-IQ', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

onMounted(() => fetchUsages(1))
</script>

<template>
  <VRow>
    <VCol cols="12">

      <!-- Back + Header -->
      <div class="d-flex align-center gap-3 mb-5">
        <VBtn icon="ri-arrow-right-line" variant="tonal" color="secondary" size="small" rounded="lg" @click="router.push('/coupons')" />
        <div>
          <h1 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-bar-chart-line" color="primary" size="22" />
            سجل استخدام كود الخصم
          </h1>
          <p v-if="coupon" class="text-body-2 text-medium-emphasis mb-0 mt-1">
            كود:
            <VChip size="x-small" color="primary" variant="tonal" class="font-weight-bold ms-1">{{ coupon.code }}</VChip>
            — {{ coupon.type_label }}:
            <strong>{{ coupon.type === 'percentage' ? coupon.value + '%' : formatIQD(coupon.value) }}</strong>
          </p>
        </div>
      </div>

      <!-- Stats Cards -->
      <VRow v-if="coupon" class="mb-5" dense>
        <VCol cols="6" sm="3">
          <VCard variant="tonal" color="primary" rounded="lg" class="pa-4 text-center">
            <VIcon icon="ri-user-line" size="24" class="mb-1" />
            <p class="text-h5 font-weight-bold mb-0">{{ coupon.used_count.toLocaleString() }}</p>
            <p class="text-caption mb-0">إجمالي الاستخدامات</p>
          </VCard>
        </VCol>
        <VCol cols="6" sm="3">
          <VCard variant="tonal" :color="coupon.max_uses ? 'warning' : 'secondary'" rounded="lg" class="pa-4 text-center">
            <VIcon icon="ri-shield-check-line" size="24" class="mb-1" />
            <p class="text-h5 font-weight-bold mb-0">{{ coupon.max_uses?.toLocaleString() ?? '∞' }}</p>
            <p class="text-caption mb-0">الحد الأقصى</p>
          </VCard>
        </VCol>
        <VCol cols="6" sm="3">
          <VCard variant="tonal" :color="coupon.is_valid ? 'success' : 'error'" rounded="lg" class="pa-4 text-center">
            <VIcon :icon="coupon.is_valid ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'" size="24" class="mb-1" />
            <p class="text-h5 font-weight-bold mb-0">{{ coupon.remaining_uses?.toLocaleString() ?? '∞' }}</p>
            <p class="text-caption mb-0">المتبقي</p>
          </VCard>
        </VCol>
        <VCol cols="6" sm="3">
          <VCard variant="tonal" :color="coupon.is_active ? 'success' : 'error'" rounded="lg" class="pa-4 text-center">
            <VIcon :icon="coupon.is_active ? 'ri-toggle-line' : 'ri-toggle-fill'" size="24" class="mb-1" />
            <p class="text-h5 font-weight-bold mb-0">{{ coupon.is_active ? 'مفعّل' : 'معطّل' }}</p>
            <p class="text-caption mb-0">حالة الكود</p>
          </VCard>
        </VCol>
      </VRow>

      <!-- Table Card -->
      <VCard rounded="lg" elevation="0">
        <!-- Toolbar -->
        <div class="pa-4 d-flex align-center gap-3 flex-wrap border-b">
          <VTextField
            v-model="search"
            placeholder="بحث باسم المستخدم أو رقم الهاتف..."
            prepend-inner-icon="ri-search-line"
            variant="outlined"
            density="compact"
            hide-details
            rounded="lg"
            style="max-width: 320px;"
            clearable
          />
          <VSpacer />
          <div class="text-caption text-medium-emphasis">
            إجمالي السجلات:
            <strong class="text-primary">{{ totalCount.toLocaleString() }}</strong>
          </div>
          <VSelect
            v-model="perPage"
            :items="[25, 50, 100, 200]"
            label="عدد الصفوف"
            variant="outlined"
            density="compact"
            hide-details
            style="max-width: 120px;"
            @update:model-value="fetchUsages(1)"
          />
        </div>

        <!-- Table -->
        <VTable>
          <thead>
            <tr class="bg-light">
              <th class="text-center" style="width:60px">#</th>
              <th>المستخدم</th>
              <th>رقم الهاتف</th>
              <th class="text-center">مقدار الخصم</th>
              <th>تاريخ الاستخدام</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center py-10">
                <VProgressCircular indeterminate color="primary" size="28" class="me-2" />
                جاري تحميل السجلات...
              </td>
            </tr>
            <tr v-else-if="filteredUsages.length === 0">
              <td colspan="5" class="text-center py-10 text-medium-emphasis">
                <VIcon icon="ri-user-unfollow-line" size="40" class="d-block mx-auto mb-2" />
                لم يستخدم أحد هذا الكود بعد
              </td>
            </tr>
            <tr
              v-for="(u, i) in filteredUsages"
              :key="u.id"
              :class="i % 2 === 0 ? '' : 'bg-surface'"
            >
              <td class="text-center text-medium-emphasis text-caption">
                {{ (currentPage - 1) * perPage + i + 1 }}
              </td>
              <td>
                <div class="d-flex align-center gap-2">
                  <VAvatar color="primary" variant="tonal" size="32" rounded="lg">
                    <span class="text-caption font-weight-bold">{{ u.user_name.charAt(0) }}</span>
                  </VAvatar>
                  <span class="font-weight-medium">{{ u.user_name }}</span>
                </div>
              </td>
              <td>
                <VChip size="x-small" color="secondary" variant="tonal" prepend-icon="ri-phone-line">
                  {{ u.user_phone }}
                </VChip>
              </td>
              <td class="text-center">
                <VChip color="success" variant="tonal" size="small" prepend-icon="ri-price-tag-3-line" class="font-weight-bold">
                  {{ formatIQD(u.discount_amount) }}
                </VChip>
              </td>
              <td class="text-caption text-medium-emphasis">{{ formatDate(u.used_at) }}</td>
            </tr>
          </tbody>
        </VTable>

        <!-- Pagination -->
        <VDivider v-if="totalPages > 1" />
        <div v-if="totalPages > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
          <span class="text-caption text-medium-emphasis">
            الصفحة {{ currentPage }} من {{ totalPages }}
            ({{ totalCount.toLocaleString() }} سجل)
          </span>
          <VPagination
            v-model="currentPage"
            :length="totalPages"
            :total-visible="7"
            density="comfortable"
            variant="tonal"
            active-color="primary"
            @update:model-value="handlePageChange"
          />
        </div>
      </VCard>

    </VCol>
  </VRow>
</template>
