<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { formatIQD } from '@/utils/currency'

const route = useRoute()
const token = route.params.token as string

interface OrderItem {
  id: number
  product_name: string
  sku: string | null
  unit_price: string
  quantity: number
  total_price: string
}

interface Order {
  id: number
  invoice_code: string
  invoice_token: string
  customer_name: string
  customer_phone: string
  province: string
  district: string
  nearest_landmark: string | null
  status: string
  total_amount: string
  discount_amount: string
  final_amount: string
  rejection_reason: string | null
  notes: string | null
  created_at: string
  items: OrderItem[]
  coupon?: { code: string } | null
}

const order    = ref<Order | null>(null)
const settings = ref<Record<string, string>>({})
const loading  = ref(true)
const error    = ref('')

const statusOptions = [
  { title: 'تم الإرسال',                 value: 'sent'               },
  { title: 'تم الاستلام وجاري التجهيز', value: 'received_preparing' },
  { title: 'جاري التوصيل',              value: 'out_for_delivery'   },
  { title: 'تم التسليم',                 value: 'delivered'          },
  { title: 'تم الرفض',                   value: 'rejected'           },
  { title: 'تم الإلغاء',                 value: 'cancelled'          },
]

const statusColor = (s: string) => ({
  sent:                'primary',
  received_preparing:  'warning',
  out_for_delivery:    'info',
  delivered:           'success',
  rejected:            'error',
  cancelled:           'default',
}[s] || 'default')

const statusLabel = (s: string) =>
  statusOptions.find(o => o.value === s)?.title ?? s

const formatDate = (d: string) =>
  new Date(d).toLocaleString('ar-IQ', { dateStyle: 'short', timeStyle: 'short' })

// ── Load invoice ────────────────────────────────────────────────────
const loadInvoice = async () => {
  loading.value = true
  error.value   = ''
  try {
    const res  = await fetch(`/api/invoice/${token}`)
    if (!res.ok) { error.value = 'الفاتورة غير موجودة'; return }
    const data = await res.json()
    order.value    = data.order
    settings.value = data.settings
  } catch {
    error.value = 'تعذّر تحميل الفاتورة'
  } finally {
    loading.value = false
  }
}

onMounted(loadInvoice)

// ── Admin status change (only if admin token present) ───────────────
const isAdmin = computed(() => !!localStorage.getItem('accessToken'))

const statusDialog    = ref(false)
const newStatus       = ref('')
const rejectionReason = ref('')
const statusSaving    = ref(false)
const statusError     = ref('')

const openStatusDialog = () => {
  if (!order.value) return
  newStatus.value       = order.value.status
  rejectionReason.value = order.value.rejection_reason ?? ''
  statusError.value     = ''
  statusDialog.value    = true
}

const saveStatus = async () => {
  statusSaving.value = true
  statusError.value  = ''
  const adminToken   = localStorage.getItem('accessToken')
  try {
    const res = await fetch(`/api/admin/invoice/${token}/status`, {
      method:  'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept':        'application/json',
        'Authorization': `Bearer ${adminToken}`,
      },
      body: JSON.stringify({ status: newStatus.value, rejection_reason: rejectionReason.value }),
    })
    if (!res.ok) {
      const err = await res.json()
      statusError.value = err.message ?? 'حدث خطأ'
      return
    }
    statusDialog.value = false
    await loadInvoice()
  } finally {
    statusSaving.value = false
  }
}

const printPage = () => window.print()
</script>

<template>
  <div class="invoice-page pa-4">

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center align-center" style="min-height:80vh;">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="d-flex justify-center align-center" style="min-height:80vh;">
      <VAlert type="error" variant="tonal" max-width="400">{{ error }}</VAlert>
    </div>

    <!-- Invoice -->
    <div v-else-if="order" style="max-width:760px;margin:0 auto;">

      <!-- Actions bar (no-print) -->
      <div class="d-flex align-center justify-space-between mb-4 no-print">
        <VChip :color="statusColor(order.status)" variant="tonal">{{ statusLabel(order.status) }}</VChip>
        <div class="d-flex gap-2">
          <VBtn v-if="isAdmin" color="warning" variant="tonal" prepend-icon="ri-refresh-line" @click="openStatusDialog">
            تغيير الحالة
          </VBtn>
          <VBtn color="primary" variant="tonal" prepend-icon="ri-printer-line" @click="printPage">
            طباعة PDF
          </VBtn>
        </div>
      </div>

      <!-- Invoice Card -->
      <VCard id="invoice-print-area" class="pa-6">

        <!-- Header -->
        <div class="d-flex align-center justify-space-between mb-4">
          <div>
            <img
              v-if="settings.logo"
              :src="`/storage/${settings.logo}`"
              style="max-height:70px;max-width:160px;"
            />
            <div v-else class="text-h5 font-weight-bold">{{ settings.store_name || 'المتجر' }}</div>
            <div v-if="settings.store_phone" class="text-body-2 text-medium-emphasis mt-1">
              هاتف: {{ settings.store_phone }}
            </div>
            <div v-if="settings.store_address" class="text-body-2 text-medium-emphasis">
              {{ settings.store_address }}
            </div>
          </div>
          <div class="text-end">
            <div class="text-h6 font-weight-bold text-primary">{{ order.invoice_code }}</div>
            <div class="text-body-2 text-medium-emphasis mt-1">{{ formatDate(order.created_at) }}</div>
            <VChip :color="statusColor(order.status)" size="small" variant="tonal" class="mt-1">
              {{ statusLabel(order.status) }}
            </VChip>
            <div v-if="order.rejection_reason" class="text-caption text-error mt-1">
              السبب: {{ order.rejection_reason }}
            </div>
          </div>
        </div>

        <VDivider class="mb-4" />

        <!-- Customer + QR -->
        <VRow class="mb-4">
          <VCol cols="12" sm="7">
            <div class="text-subtitle-2 font-weight-bold mb-2">بيانات الزبون</div>
            <div class="text-body-2"><span class="font-weight-medium">الاسم:</span> {{ order.customer_name }}</div>
            <div class="text-body-2"><span class="font-weight-medium">الهاتف:</span> {{ order.customer_phone }}</div>
            <div class="text-body-2"><span class="font-weight-medium">القضاء:</span> {{ order.province }}</div>
            <div class="text-body-2"><span class="font-weight-medium">المنطقة:</span> {{ order.district }}</div>
            <div v-if="order.nearest_landmark" class="text-body-2">
              <span class="font-weight-medium">أقرب نقطة دالة:</span> {{ order.nearest_landmark }}
            </div>
            <div v-if="order.notes" class="text-body-2 mt-1">
              <span class="font-weight-medium">ملاحظات:</span> {{ order.notes }}
            </div>
          </VCol>
          <VCol cols="12" sm="5" class="d-flex justify-end align-start">
            <div class="text-center">
              <img
                :src="`https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=${encodeURIComponent(window.location.href)}`"
                width="110" height="110" alt="QR"
                style="border:1px solid #eee;border-radius:8px;"
              />
              <div class="text-caption text-medium-emphasis mt-1">مسح للفاتورة</div>
            </div>
          </VCol>
        </VRow>

        <!-- Items -->
        <div class="text-subtitle-2 font-weight-bold mb-2">تفاصيل الطلب</div>
        <VTable density="compact" class="mb-4" style="border:1px solid rgba(0,0,0,.12);border-radius:8px;">
          <thead>
            <tr style="background:rgba(0,0,0,.03);">
              <th class="text-center" style="width:44px;">#</th>
              <th>المنتج</th>
              <th>SKU</th>
              <th class="text-end">السعر</th>
              <th class="text-center">الكمية</th>
              <th class="text-end">المجموع</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in order.items" :key="item.id">
              <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
              <td class="font-weight-medium">{{ item.product_name }}</td>
              <td class="text-caption text-medium-emphasis">{{ item.sku ?? '—' }}</td>
              <td class="text-end">{{ formatIQD(item.unit_price) }}</td>
              <td class="text-center">{{ item.quantity }}</td>
              <td class="text-end font-weight-bold">{{ formatIQD(item.total_price) }}</td>
            </tr>
          </tbody>
        </VTable>

        <!-- Totals -->
        <div class="d-flex flex-column align-end gap-1 mb-6">
          <div class="text-body-2">
            المجموع الكلي: <strong>{{ formatIQD(order.total_amount) }}</strong>
          </div>
          <div v-if="parseFloat(order.discount_amount) > 0" class="text-body-2 text-success">
            الخصم: <strong>-{{ formatIQD(order.discount_amount) }}</strong>
            <span v-if="order.coupon" class="text-caption"> (كوبون: {{ order.coupon.code }})</span>
          </div>
          <div class="text-h6 font-weight-bold text-primary mt-1">
            الإجمالي النهائي: {{ formatIQD(order.final_amount) }}
          </div>
        </div>

        <VDivider class="mb-4" />

        <!-- Thank you -->
        <div class="text-center text-body-1 font-italic text-medium-emphasis py-2">
          {{ settings.thank_you_message || 'شكراً لثقتكم بنا' }}
        </div>
      </VCard>
    </div>

    <!-- Status Dialog (admin only) -->
    <VDialog v-model="statusDialog" max-width="420">
      <VCard title="تغيير حالة الطلب">
        <VCardText>
          <VSelect
            v-model="newStatus"
            :items="statusOptions"
            item-title="title"
            item-value="value"
            label="الحالة الجديدة"
            variant="outlined"
          />
          <VTextarea
            v-if="newStatus === 'rejected'"
            v-model="rejectionReason"
            label="سبب الرفض (اختياري)"
            rows="2"
            variant="outlined"
            class="mt-3"
          />
          <VAlert v-if="statusError" type="error" class="mt-2" variant="tonal">{{ statusError }}</VAlert>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="statusDialog = false">إلغاء</VBtn>
          <VBtn color="primary" :loading="statusSaving" @click="saveStatus">حفظ</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style>
@media print {
  body * { visibility: hidden; }
  #invoice-print-area, #invoice-print-area * { visibility: visible; }
  #invoice-print-area { position: fixed; top: 0; left: 0; width: 100%; padding: 20px; }
  .no-print { display: none !important; }
}
.invoice-page {
  min-height: 100vh;
  background: #f5f5f5;
}
</style>
