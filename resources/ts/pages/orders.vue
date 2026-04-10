<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

interface OrderItem {
  id: number
  product_name: string
  sku: string | null
  unit_price: string
  quantity: number
  total_price: string
  product?: { sku: string | null }
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
  coupon_id: number | null
  notes: string | null
  rejection_reason: string | null
  created_at: string
  updated_at: string
  items?: OrderItem[]
  coupon?: { code: string } | null
}

const orders       = ref<Order[]>([])
const loading      = ref(false)
const totalItems   = ref(0)
const currentPage  = ref(1)
const perPage      = ref(25)

const searchQuery  = ref('')
const statusFilter = ref<string | null>((route.query.status as string) || null)
const dateFrom     = ref('')
const dateTo       = ref('')

const selectedOrders = ref<Order[]>([])

const statusOptions = [
  { title: 'تم الإرسال',                  value: 'sent'                },
  { title: 'تم الاستلام وجاري التجهيز',  value: 'received_preparing'  },
  { title: 'جاري التوصيل',               value: 'out_for_delivery'    },
  { title: 'تم التسليم',                  value: 'delivered'           },
  { title: 'تم الرفض',                    value: 'rejected'            },
  { title: 'تم الإلغاء',                  value: 'cancelled'           },
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

const headers = [
  { title: '#',           key: 'seq',           align: 'center' as const, sortable: false, width: '60px' },
  { title: 'رقم الفاتورة', key: 'invoice_code', align: 'start'  as const, sortable: false },
  { title: 'الزبون',      key: 'customer',      align: 'start'  as const, sortable: false },
  { title: 'القضاء/المنطقة', key: 'location',  align: 'start'  as const, sortable: false },
  { title: 'المبلغ',      key: 'final_amount',  align: 'start'  as const, sortable: false },
  { title: 'التاريخ',     key: 'created_at',    align: 'start'  as const, sortable: false },
  { title: 'الحالة',      key: 'status',        align: 'center' as const, sortable: false },
  { title: 'الإجراءات',   key: 'actions',       align: 'center' as const, sortable: false },
]

const handleOrderOptions = (options: { page: number; itemsPerPage: number }) => {
  perPage.value = options.itemsPerPage
  loadOrders(options.page)
}

const loadOrders = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = new URLSearchParams({ page: String(page), per_page: String(perPage.value) })
    if (searchQuery.value)  params.set('search', searchQuery.value)
    if (statusFilter.value) params.set('status', statusFilter.value)
    if (dateFrom.value)     params.set('date_from', dateFrom.value)
    if (dateTo.value)       params.set('date_to', dateTo.value)

    const res  = await apiFetch(`/api/admin/orders?${params}`)
    const data = await res.json()
    orders.value     = data.data
    totalItems.value = data.total
  } finally {
    loading.value = false
  }
}

watch([searchQuery, statusFilter, dateFrom, dateTo], () => loadOrders(1))
watch(() => route.query.status, (s) => { statusFilter.value = (s as string) || null })
onMounted(() => loadOrders(1))

// ── Invoice Dialog ──────────────────────────────────────────────────
const invoiceDialog  = ref(false)
const invoiceOrder   = ref<Order | null>(null)
const invoiceSettings = ref<Record<string, string>>({})
const invoiceLoading = ref(false)

const openInvoice = async (order: Order) => {
  invoiceLoading.value = true
  invoiceDialog.value  = true
  try {
    const res  = await apiFetch(`/api/admin/orders/${order.id}`)
    const data = await res.json()
    invoiceOrder.value    = data.order
    invoiceSettings.value = data.settings
  } finally {
    invoiceLoading.value = false
  }
}

const printInvoice = () => {
  const o = invoiceOrder.value
  if (!o) return
  window.open(invoiceUrl(o.invoice_token) + '?print=1', '_blank')
}

const printThermal58 = () => {
  const o = invoiceOrder.value
  if (!o) return

  const s = invoiceSettings.value
  const storeName    = s.store_name    || 'المتجر'
  const storePhone   = s.store_phone   || ''
  const storeAddress = s.store_address || ''
  const thankYou     = s.thank_you_message || 'شكراً لثقتكم بنا'
  const invoiceLink  = invoiceUrl(o.invoice_token)
  const qrUrl        = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(invoiceLink)}`
  const fmt          = (v: string) => Number(v).toLocaleString('ar-IQ') + ' د.ع'

  const itemsHtml = o.items.map((item: any, i: number) => `
    <tr>
      <td style="text-align:center;color:#999;">${i + 1}</td>
      <td>${item.product_name}</td>
      <td style="text-align:center;">${item.quantity}</td>
      <td style="text-align:left;font-weight:700;">${fmt(item.total_price)}</td>
    </tr>`).join('')

  const html = `<!DOCTYPE html>
<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>وصل ${o.invoice_code}</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  @page{size:58mm auto;margin:2mm 1mm;}
  body{font-family:'Tahoma','Arial',sans-serif;font-size:10px;width:54mm;color:#000;direction:rtl;}
  .c{text-align:center;} .b{font-weight:700;}
  .line{border-top:1px dashed #000;margin:4px 0;}
  .store-name{font-size:13px;font-weight:700;margin-bottom:2px;}
  .code{font-size:12px;font-weight:700;font-family:monospace;letter-spacing:1px;}
  table{width:100%;border-collapse:collapse;}
  th,td{padding:2px 3px;font-size:9px;}
  thead th{font-weight:700;border-bottom:1px solid #000;}
  .tr{display:flex;justify-content:space-between;padding:2px 0;}
  .final{font-size:12px;font-weight:700;border-top:1px solid #000;padding-top:4px;margin-top:2px;}
  .qr{text-align:center;margin:6px 0 4px;}
  .qr img{width:90px;height:90px;}
  .qrl{font-size:8px;color:#555;margin-top:2px;}
  .ft{text-align:center;margin-top:4px;font-size:9px;}
</style></head><body>
<div class="c">
  <div class="store-name">${storeName}</div>
  ${storePhone ? `<div>${storePhone}</div>` : ''}
  ${storeAddress ? `<div>${storeAddress}</div>` : ''}
</div>
<div class="line"></div>
<div class="c">
  <div class="code">${o.invoice_code}</div>
  <div style="font-size:9px;color:#555;">${new Date(o.created_at).toLocaleString('ar-IQ',{dateStyle:'short',timeStyle:'short'})}</div>
</div>
<div class="line"></div>
<table>
  <tr><td class="b">الاسم</td><td style="text-align:left;">${o.customer_name}</td></tr>
  <tr><td class="b">الهاتف</td><td style="text-align:left;direction:ltr;">${o.customer_phone}</td></tr>
  <tr><td class="b">المنطقة</td><td style="text-align:left;">${o.district} - ${o.province}</td></tr>
  ${o.nearest_landmark ? `<tr><td class="b">أقرب نقطة</td><td style="text-align:left;">${o.nearest_landmark}</td></tr>` : ''}
  ${o.notes ? `<tr><td class="b">ملاحظات</td><td style="text-align:left;">${o.notes}</td></tr>` : ''}
</table>
<div class="line"></div>
<table>
  <thead><tr>
    <th style="text-align:center;width:16px;">#</th>
    <th style="text-align:right;">المنتج</th>
    <th style="text-align:center;width:22px;">ك</th>
    <th style="text-align:left;width:46px;">المبلغ</th>
  </tr></thead>
  <tbody>${itemsHtml}</tbody>
</table>
<div class="line"></div>
<div>
  <div class="tr"><span>المجموع</span><span>${fmt(o.total_amount)}</span></div>
  ${parseFloat(o.discount_amount) > 0 ? `<div class="tr" style="color:#2e7d32;"><span>الخصم${o.coupon ? ' (' + o.coupon.code + ')' : ''}</span><span>- ${fmt(o.discount_amount)}</span></div>` : ''}
  <div class="tr final"><span>الإجمالي</span><span>${fmt(o.final_amount)}</span></div>
</div>
<div class="qr"><img src="${qrUrl}" alt="QR"/><div class="qrl">امسح للفاتورة الإلكترونية</div></div>
<div class="line"></div>
<div class="ft">${thankYou}</div><br/>
<script>window.onload=()=>{window.focus();window.print();}<\/script>
</body></html>`

  const w = window.open('', '_blank', 'width=300,height=600')
  if (w) { w.document.write(html); w.document.close() }
}

const invoiceUrl = (token: string) =>
  `${window.location.origin}/invoice/${token}`

const bulkPrint = () => {
  if (selectedOrders.value.length === 0) return
  const ids = selectedOrders.value.map(o => o.id).join(',')
  window.open(`${window.location.origin}/invoice/bulk?ids=${ids}&print=1`, '_blank')
}

// ── Delete ─────────────────────────────────────────────────────────
const deleteDialog    = ref(false)
const deleteOrderId   = ref<number | null>(null)
const deleteOrderCode = ref('')
const deleteLoading   = ref(false)

const openDeleteDialog = (order: Order) => {
  deleteOrderId.value   = order.id
  deleteOrderCode.value = order.invoice_code
  deleteDialog.value    = true
}

const confirmDelete = async () => {
  deleteLoading.value = true
  try {
    await apiFetch(`/api/admin/orders/${deleteOrderId.value}`, { method: 'DELETE' })
    deleteDialog.value = false
    await loadOrders(currentPage.value)
  } finally {
    deleteLoading.value = false
  }
}

// ── Status Dialog ───────────────────────────────────────────────────
const statusDialog      = ref(false)
const statusOrderId     = ref<number | null>(null)
const newStatus         = ref('')
const rejectionReason   = ref('')
const statusSaving      = ref(false)
const statusError       = ref('')

const openStatusDialog = (order: Order) => {
  statusOrderId.value   = order.id
  newStatus.value       = order.status
  rejectionReason.value = order.rejection_reason ?? ''
  statusError.value     = ''
  statusDialog.value    = true
}

const saveStatus = async () => {
  statusSaving.value = true
  statusError.value  = ''
  try {
    const res = await apiFetch(`/api/admin/orders/${statusOrderId.value}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status: newStatus.value, rejection_reason: rejectionReason.value }),
    })
    if (!res.ok) {
      const err = await res.json()
      statusError.value = err.message ?? 'حدث خطأ'
      return
    }
    statusDialog.value = false
    await loadOrders(currentPage.value)
  } finally {
    statusSaving.value = false
  }
}

const formatDate = (d: string) =>
  new Date(d).toLocaleString('ar-IQ', { dateStyle: 'short', timeStyle: 'short' })

const rowSeq = (index: number) => (currentPage.value - 1) * perPage.value + index + 1

// ── Copy phone ─────────────────────────────────────────────────
const copySnack = ref(false)

const copyPhone = async (phone: string) => {
  try {
    await navigator.clipboard.writeText(phone)
    copySnack.value = true
  } catch {
    // Fallback
    const el = document.createElement('textarea')
    el.value = phone
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    copySnack.value = true
  }
}
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardTitle class="pa-4 d-flex align-center justify-space-between flex-wrap gap-2">
            <div class="d-flex align-center gap-4">
              <span>إدارة الطلبات</span>
              <VChip color="primary" variant="tonal">{{ totalItems }} طلب</VChip>
            </div>
            
            <VBtn
              v-if="selectedOrders.length > 0"
              color="primary"
              prepend-icon="ri-printer-line"
              @click="bulkPrint"
            >
              طباعة الفواتير المحددة ({{ selectedOrders.length }})
            </VBtn>
          </VCardTitle>

          <!-- Filters -->
          <VCardText class="pb-0">
            <VRow dense>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="searchQuery"
                  prepend-inner-icon="ri-search-line"
                  placeholder="رقم الفاتورة أو هاتف الزبون..."
                  density="compact"
                  clearable
                  hide-details
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" md="2">
                <VSelect
                  v-model="statusFilter"
                  :items="statusOptions"
                  item-title="title"
                  item-value="value"
                  placeholder="الحالة"
                  density="compact"
                  clearable
                  hide-details
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" md="2">
                <VTextField
                  v-model="dateFrom"
                  type="date"
                  label="من"
                  density="compact"
                  hide-details
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" md="2">
                <VTextField
                  v-model="dateTo"
                  type="date"
                  label="إلى"
                  density="compact"
                  hide-details
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- Table -->
          <VDataTableServer
            v-model="selectedOrders"
            show-select
            item-value="id"
            return-object
            :headers="headers"
            :items="orders"
            :items-length="totalItems"
            :loading="loading"
            :items-per-page="perPage"
            @update:options="handleOrderOptions"
            class="mt-2"
          >
            <template #item.seq="{ index }">
              <span class="text-medium-emphasis text-body-2">{{ rowSeq(index) }}</span>
            </template>

            <template #item.invoice_code="{ item }">
              <span class="font-weight-bold text-primary">{{ item.invoice_code }}</span>
            </template>

            <template #item.customer="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.customer_name }}</div>
                <div
                  class="text-caption text-primary d-flex align-center gap-1 cursor-pointer"
                  dir="ltr"
                  style="text-align:right; width:fit-content;"
                  title="اضغط لنسخ رقم الهاتف"
                  @click="copyPhone(item.customer_phone)"
                >
                  <VIcon size="12" icon="ri-file-copy-line" />
                  {{ item.customer_phone }}
                </div>
              </div>
            </template>

            <template #item.location="{ item }">
              <div class="text-body-2">{{ item.province }} / {{ item.district }}</div>
            </template>

            <template #item.final_amount="{ item }">
              <div>
                <div class="font-weight-bold">{{ formatIQD(item.final_amount) }}</div>
                <div v-if="parseFloat(item.discount_amount) > 0" class="text-caption text-success">
                  خصم: -{{ formatIQD(item.discount_amount) }}
                </div>
              </div>
            </template>

            <template #item.created_at="{ item }">
              <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
            </template>

            <template #item.status="{ item }">
              <VChip :color="statusColor(item.status)" size="small" variant="tonal">
                {{ statusLabel(item.status) }}
              </VChip>
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex gap-1 justify-center">
                <VTooltip text="عرض الفاتورة">
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon size="small" variant="text" color="info" @click="openInvoice(item)">
                      <VIcon>ri-eye-line</VIcon>
                    </VBtn>
                  </template>
                </VTooltip>
                <VTooltip text="تغيير الحالة">
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon size="small" variant="text" color="warning" @click="openStatusDialog(item)">
                      <VIcon>ri-refresh-line</VIcon>
                    </VBtn>
                  </template>
                </VTooltip>
                <VTooltip text="حذف الطلب">
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon size="small" variant="text" color="error" @click="openDeleteDialog(item)">
                      <VIcon>ri-delete-bin-line</VIcon>
                    </VBtn>
                  </template>
                </VTooltip>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Delete Confirm Dialog ────────────── -->
    <VDialog v-model="deleteDialog" max-width="380">
      <VCard>
        <VCardTitle class="pa-4 d-flex align-center gap-2">
          <VIcon color="error" size="24">ri-error-warning-line</VIcon>
          تأكيد الحذف
        </VCardTitle>
        <VCardText>
          هل تريد حذف الطلب <strong class="text-error">{{ deleteOrderCode }}</strong> نهائياً؟
          <br />
          <span class="text-caption text-medium-emphasis">لا يمكن التراجع عن هذا الإجراء.</span>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="deleteDialog = false">إلغاء</VBtn>
          <VBtn color="error" :loading="deleteLoading" @click="confirmDelete">حذف</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Status Dialog ─────────────────────── -->
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

    <!-- ── Invoice Dialog ────────────────────── -->
    <VDialog v-model="invoiceDialog" max-width="780" scrollable>
      <VCard>
        <VCardTitle class="pa-4 d-flex align-center justify-space-between no-print">
          <span class="d-flex align-center gap-2">
            <VIcon icon="ri-file-text-line" color="primary" />
            {{ invoiceOrder?.invoice_code || 'الفاتورة' }}
          </span>
          <div class="d-flex gap-2">
            <VBtn color="primary" variant="elevated" rounded="lg" prepend-icon="ri-printer-line" @click="printInvoice">
              طباعة PDF
            </VBtn>
            <VBtn color="teal" variant="tonal" rounded="lg" prepend-icon="ri-receipt-line" @click="printThermal58">
              58mm
            </VBtn>
            <VBtn icon variant="text" @click="invoiceDialog = false"><VIcon>ri-close-line</VIcon></VBtn>
          </div>
        </VCardTitle>

        <VCardText v-if="invoiceLoading" class="text-center pa-8">
          <VProgressCircular indeterminate color="primary" />
        </VCardText>

        <VCardText v-else-if="invoiceOrder" id="invoice-print-area" class="pa-6">
          <!-- Header -->
          <div class="d-flex align-center justify-space-between mb-4">
            <div>
              <img v-if="invoiceSettings.logo" :src="`/storage/${invoiceSettings.logo}`" style="max-height:70px;max-width:160px;" />
              <div v-else class="text-h6 font-weight-bold">{{ invoiceSettings.store_name || 'المتجر' }}</div>
              <div class="text-body-2 text-medium-emphasis mt-1">{{ invoiceSettings.store_phone }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ invoiceSettings.store_address }}</div>
            </div>
            <div class="text-end">
              <div class="text-h6 font-weight-bold text-primary">{{ invoiceOrder.invoice_code }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ formatDate(invoiceOrder.created_at) }}</div>
              <VChip :color="statusColor(invoiceOrder.status)" size="small" variant="tonal" class="mt-1">
                {{ statusLabel(invoiceOrder.status) }}
              </VChip>
            </div>
          </div>

          <VDivider class="mb-4" />

          <!-- Customer Info -->
          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <div class="text-subtitle-2 font-weight-bold mb-2">بيانات الزبون</div>
              <div class="text-body-2"><span class="font-weight-medium">الاسم:</span> {{ invoiceOrder.customer_name }}</div>
              <div class="text-body-2"><span class="font-weight-medium">الهاتف:</span> <span dir="ltr" style="unicode-bidi:embed;">{{ invoiceOrder.customer_phone }}</span></div>
              <div class="text-body-2"><span class="font-weight-medium">القضاء:</span> {{ invoiceOrder.province }}</div>
              <div class="text-body-2"><span class="font-weight-medium">المنطقة:</span> {{ invoiceOrder.district }}</div>
              <div class="text-body-2">
                <span class="font-weight-medium">أقرب نقطة دالة:</span> {{ invoiceOrder.nearest_landmark || '—' }}
              </div>
            </VCol>
            <VCol cols="12" md="6" class="d-flex justify-end align-start">
              <!-- QR Code -->
              <div class="text-center">
                <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(invoiceUrl(invoiceOrder.invoice_token))}`"
                     width="100" height="100" alt="QR" style="border:1px solid #eee;border-radius:6px;" />
                <div class="text-caption text-medium-emphasis mt-1">مسح للفاتورة</div>
              </div>
            </VCol>
          </VRow>

          <!-- Items Table -->
          <div class="text-subtitle-2 font-weight-bold mb-2">المنتجات</div>
          <table class="inv-dlg-table mb-4">
            <thead>
              <tr>
                <th>#</th>
                <th>المنتج</th>
                <th>SKU</th>
                <th>السعر</th>
                <th>الكمية</th>
                <th>المجموع</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, i) in invoiceOrder.items" :key="item.id">
                <td>{{ i + 1 }}</td>
                <td>{{ item.product_name }}</td>
                <td class="text-muted">{{ item.sku || item.product?.sku || '—' }}</td>
                <td>{{ formatIQD(item.unit_price) }}</td>
                <td>{{ item.quantity }}</td>
                <td class="font-weight-bold">{{ formatIQD(item.total_price) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="d-flex flex-column align-end gap-1 mb-4">
            <div class="text-body-2">المجموع: <strong>{{ formatIQD(invoiceOrder.total_amount) }}</strong></div>
            <div v-if="parseFloat(invoiceOrder.discount_amount) > 0" class="text-body-2 text-success">
              الخصم: <strong>-{{ formatIQD(invoiceOrder.discount_amount) }}</strong>
              <span v-if="invoiceOrder.coupon" class="text-caption"> ({{ invoiceOrder.coupon.code }})</span>
            </div>
            <div class="text-h6 font-weight-bold text-primary">الإجمالي: {{ formatIQD(invoiceOrder.final_amount) }}</div>
          </div>

          <VDivider class="mb-4" />

          <!-- Thank you -->
          <div class="text-center text-body-1 text-medium-emphasis font-italic">
            {{ invoiceSettings.thank_you_message || 'شكراً لثقتكم بنا' }}
          </div>
        </VCardText>
      </VCard>
    </VDialog>
    <!-- Copy Snackbar -->
    <VSnackbar v-model="copySnack" :timeout="2000" color="success" location="bottom center" rounded="lg">
      <VIcon icon="ri-check-line" class="me-2" />
      تم نسخ رقم الهاتف ✓
    </VSnackbar>

  </div>
</template>

<style>
/* ── Dialog table ────────────────── */
.inv-dlg-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  margin-bottom: 16px;
}
.inv-dlg-table th {
  background: #f4f6f8;
  padding: 8px 10px;
  border-bottom: 2px solid #ddd;
  text-align: right;
  font-weight: 600;
  color: #444;
}
.inv-dlg-table td {
  padding: 7px 10px;
  border-bottom: 1px solid #f0f0f0;
  text-align: right;
}
.inv-dlg-table tbody tr:nth-child(even) { background: #fafafa; }
.text-muted { color: #999; font-size: 12px; }

</style>
