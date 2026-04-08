<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'

interface Coupon {
  id: number | null
  code: string
  type: 'percentage' | 'fixed'
  type_label: string
  value: number
  min_order_amount: number | null
  max_uses: number | null
  used_count: number
  remaining_uses: number | null
  expires_at: string | null
  is_active: boolean
  is_valid: boolean
}

const { items: coupons, loading, currentPage, totalPages, fetchData: fetchCoupons, handlePageChange } =
  usePagination<Coupon>('/api/admin/coupons')

const showDialog      = ref(false)
const dialogMode      = ref<'add' | 'edit'>('add')
const confirmDelete   = ref(false)
const currId          = ref<number | null>(null)
const showUsageDialog = ref(false)
const usageData       = ref<{ coupon: Coupon | null; usages: any[] }>({ coupon: null, usages: [] })
const usageLoading    = ref(false)

const formData = ref<Coupon>({
  id: null, code: '', type: 'percentage', type_label: '', value: 0,
  min_order_amount: null, max_uses: null, used_count: 0,
  remaining_uses: null, expires_at: null, is_active: true, is_valid: true,
})

const openAdd = () => {
  dialogMode.value = 'add'
  formData.value = {
    id: null, code: '', type: 'percentage', type_label: '', value: 10,
    min_order_amount: null, max_uses: null, used_count: 0,
    remaining_uses: null, expires_at: null, is_active: true, is_valid: true,
  }
  showDialog.value = true
}

const openEdit = (item: Coupon) => {
  dialogMode.value = 'edit'
  formData.value = { ...item }
  showDialog.value = true
}

const saveCoupon = async () => {
  const url    = dialogMode.value === 'add' ? '/api/admin/coupons' : `/api/admin/coupons/${formData.value.id}`
  const method = dialogMode.value === 'add' ? 'POST' : 'PUT'
  try {
    const res = await apiFetch(url, {
      method,
      body: JSON.stringify({
        code:             formData.value.code,
        type:             formData.value.type,
        value:            formData.value.value,
        min_order_amount: formData.value.min_order_amount || null,
        max_uses:         formData.value.max_uses || null,
        expires_at:       formData.value.expires_at || null,
        is_active:        formData.value.is_active,
      }),
    })
    if (res.ok) {
      showDialog.value = false
      fetchCoupons(currentPage.value)
    } else {
      const err = await res.json()
      alert('خطأ: ' + JSON.stringify(err.errors || err.message))
    }
  } catch (e) { console.error(e) }
}

const deleteCoupon = async () => {
  try {
    const res = await apiFetch(`/api/admin/coupons/${currId.value}`, { method: 'DELETE' })
    if (res.ok) { confirmDelete.value = false; fetchCoupons(currentPage.value) }
  } catch (e) { console.error(e) }
}

const toggleActive = async (item: Coupon) => {
  try {
    const res = await apiFetch(`/api/admin/coupons/${item.id}/toggle`, { method: 'PATCH' })
    if (!res.ok) item.is_active = !item.is_active
  } catch (e) { item.is_active = !item.is_active }
}

const openUsage = async (item: Coupon) => {
  showUsageDialog.value = true
  usageLoading.value = true
  usageData.value = { coupon: item, usages: [] }
  try {
    const res = await apiFetch(`/api/admin/coupons/${item.id}`)
    if (res.ok) {
      const data = await res.json()
      usageData.value = { coupon: data.coupon, usages: data.usages }
    }
  } catch (e) { console.error(e) } finally { usageLoading.value = false }
}

onMounted(() => fetchCoupons(1))
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-coupon-3-line" color="primary" size="20" />
              أكواد الخصم
            </div>
            <VBtn color="primary" prepend-icon="ri-add-line" rounded="lg" @click="openAdd">
              إضافة كود جديد
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr class="bg-light">
                <th class="text-center">#</th>
                <th>الكود</th>
                <th>نوع الخصم</th>
                <th>قيمة الخصم</th>
                <th class="text-center">الاستخدامات</th>
                <th>الانتهاء</th>
                <th class="text-center">الحالة</th>
                <th class="text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center py-8">
                  <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                  جاري التحميل...
                </td>
              </tr>
              <tr v-else-if="coupons.length === 0">
                <td colspan="8" class="text-center py-8 text-medium-emphasis">لا توجد أكواد خصم</td>
              </tr>
              <tr v-for="(item, index) in coupons" :key="item.id ?? index">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td>
                  <VChip color="primary" variant="tonal" size="small" class="font-weight-bold" style="font-family: monospace; letter-spacing: 1px;">
                    {{ item.code }}
                  </VChip>
                </td>
                <td>
                  <VChip :color="item.type === 'percentage' ? 'info' : 'warning'" variant="tonal" size="small">
                    {{ item.type_label }}
                  </VChip>
                </td>
                <td class="font-weight-bold">
                  {{ item.type === 'percentage' ? item.value + '%' : item.value.toLocaleString() + ' د.ع' }}
                </td>
                <td class="text-center">
                  <VBtn variant="text" size="small" color="secondary" @click="openUsage(item)">
                    <VIcon icon="ri-user-line" size="14" class="me-1" />
                    {{ item.used_count }}
                    <span v-if="item.max_uses" class="text-medium-emphasis"> / {{ item.max_uses }}</span>
                  </VBtn>
                </td>
                <td>
                  <span v-if="!item.expires_at" class="text-medium-emphasis text-caption">بلا انتهاء</span>
                  <VChip v-else :color="item.is_valid ? 'success' : 'error'" variant="tonal" size="x-small">
                    {{ new Date(item.expires_at).toLocaleDateString('ar-IQ') }}
                  </VChip>
                </td>
                <td class="text-center">
                  <VSwitch
                    v-model="item.is_active"
                    color="success"
                    density="compact"
                    hide-details
                    class="d-inline-flex"
                    @change="toggleActive(item)"
                  />
                </td>
                <td class="text-center">
                  <VMenu location="bottom end">
                    <template #activator="{ props }">
                      <VBtn icon="ri-more-2-fill" variant="text" size="small" color="secondary" v-bind="props" />
                    </template>
                    <VList density="compact" min-width="150" rounded="lg" elevation="3">
                      <VListItem @click="openUsage(item)">
                        <template #prepend><VIcon icon="ri-bar-chart-line" size="18" class="me-2 text-info" /></template>
                        <VListItemTitle class="text-info">سجل الاستخدام</VListItemTitle>
                      </VListItem>
                      <VListItem @click="openEdit(item)">
                        <template #prepend><VIcon icon="ri-pencil-line" size="18" class="me-2 text-primary" /></template>
                        <VListItemTitle>تعديل</VListItemTitle>
                      </VListItem>
                      <VListItem @click="currId = item.id; confirmDelete = true">
                        <template #prepend><VIcon icon="ri-delete-bin-line" size="18" class="me-2 text-error" /></template>
                        <VListItemTitle class="text-error">حذف</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VDivider v-if="totalPages > 1" />
          <div v-if="totalPages > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
            <span class="text-caption text-medium-emphasis">عرض الصفحة {{ currentPage }} من {{ totalPages }}</span>
            <VPagination v-model="currentPage" :length="totalPages" :total-visible="5" density="comfortable"
              variant="tonal" active-color="primary" @update:model-value="handlePageChange" />
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Add/Edit Dialog -->
  <VDialog v-model="showDialog" max-width="540" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold text-h6">{{ dialogMode === 'add' ? 'إضافة كود خصم' : 'تعديل كود الخصم' }}</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDialog = false" />
      </VCardTitle>

      <VCardText class="pa-6">
        <VRow>
          <VCol cols="12">
            <VTextField
              v-model="formData.code"
              label="كود الخصم"
              placeholder="مثال: SUMMER20 أو خصم_صيف"
              hint="يمكن استخدام حروف عربية أو إنجليزية وأرقام"
              persistent-hint
              variant="outlined"
              color="primary"
              style="font-family: monospace;"
            />
          </VCol>

          <VCol cols="12" md="6">
            <VSelect
              v-model="formData.type"
              :items="[{title: 'نسبة مئوية (%)', value: 'percentage'}, {title: 'مبلغ ثابت (د.ع)', value: 'fixed'}]"
              label="نوع الخصم"
              variant="outlined"
              color="primary"
            />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField
              v-model.number="formData.value"
              :label="formData.type === 'percentage' ? 'نسبة الخصم (%)' : 'مقدار الخصم (د.ع)'"
              type="number"
              :suffix="formData.type === 'percentage' ? '%' : 'د.ع'"
              variant="outlined"
              color="primary"
              :hint="formData.type === 'percentage' ? 'بين 1 و 100' : 'مبلغ ثابت يُخصم'"
              persistent-hint
            />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField
              v-model.number="formData.max_uses"
              label="الحد الأقصى للاستخدام"
              type="number"
              variant="outlined"
              color="primary"
              hint="اتركه فارغاً = غير محدود"
              persistent-hint
              clearable
            />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField
              v-model.number="formData.min_order_amount"
              label="الحد الأدنى للطلب (د.ع)"
              type="number"
              variant="outlined"
              color="primary"
              hint="اتركه فارغاً = بلا حد أدنى"
              persistent-hint
              clearable
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="formData.expires_at"
              label="تاريخ الانتهاء"
              type="datetime-local"
              variant="outlined"
              color="primary"
              hint="اتركه فارغاً = بلا انتهاء"
              persistent-hint
              clearable
            />
          </VCol>

          <VCol cols="12">
            <VSwitch v-model="formData.is_active" label="الكود مفعّل" color="success" hide-details />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" @click="showDialog = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" rounded="lg" class="px-8" @click="saveCoupon">
          {{ dialogMode === 'add' ? 'إضافة الكود' : 'حفظ التعديلات' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Usage Dialog -->
  <VDialog v-model="showUsageDialog" max-width="600">
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-bar-chart-line" color="info" />
          <span class="font-weight-bold">سجل استخدام: {{ usageData.coupon?.code }}</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showUsageDialog = false" />
      </VCardTitle>

      <VCardText class="pa-5">
        <!-- Stats -->
        <VRow v-if="usageData.coupon" class="mb-4" dense>
          <VCol cols="4">
            <VCard variant="tonal" color="primary" rounded="lg" class="pa-3 text-center">
              <p class="text-h5 font-weight-bold">{{ usageData.coupon.used_count }}</p>
              <p class="text-caption">عدد الاستخدامات</p>
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard variant="tonal" color="info" rounded="lg" class="pa-3 text-center">
              <p class="text-h5 font-weight-bold">
                {{ usageData.coupon.max_uses ?? '∞' }}
              </p>
              <p class="text-caption">الحد الأقصى</p>
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard variant="tonal" :color="usageData.coupon.is_valid ? 'success' : 'error'" rounded="lg" class="pa-3 text-center">
              <p class="text-h5 font-weight-bold">
                {{ usageData.coupon.remaining_uses ?? '∞' }}
              </p>
              <p class="text-caption">المتبقي</p>
            </VCard>
          </VCol>
        </VRow>

        <div v-if="usageLoading" class="text-center py-6">
          <VProgressCircular indeterminate color="info" size="24" />
        </div>
        <div v-else-if="usageData.usages.length === 0" class="text-center py-6 text-medium-emphasis">
          <VIcon icon="ri-user-unfollow-line" size="40" class="mb-2 d-block" />
          لم يستخدم أحد هذا الكود بعد
        </div>
        <VTable v-else density="compact">
          <thead>
            <tr>
              <th>#</th>
              <th>المستخدم</th>
              <th>رقم الهاتف</th>
              <th>مقدار الخصم</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(u, i) in usageData.usages" :key="i">
              <td class="text-medium-emphasis">{{ i + 1 }}</td>
              <td class="font-weight-medium">{{ u.user_name }}</td>
              <td>{{ u.user_phone }}</td>
              <td class="text-success font-weight-bold">{{ u.discount_amount.toLocaleString() }} د.ع</td>
              <td class="text-caption text-medium-emphasis">{{ new Date(u.used_at).toLocaleDateString('ar-IQ') }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>

      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" @click="showUsageDialog = false">إغلاق</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDelete" max-width="400">
    <VCard rounded="lg" elevation="0">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-coupon-3-line" size="32" />
        </VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">حذف كود الخصم</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">هل أنت متأكد من حذف هذا الكود؟ سيتم حذف سجل استخداماته أيضاً.</VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" @click="confirmDelete = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" @click="deleteCoupon">تأكيد الحذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
