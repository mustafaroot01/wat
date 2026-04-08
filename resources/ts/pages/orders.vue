<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'

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

const orders       = ref<Order[]>([])
const loading      = ref(false)
const totalItems   = ref(0)
const currentPage  = ref(1)
const perPage      = ref(15)

const searchQuery  = ref('')
const statusFilter = ref<string | null>(null)
const dateFrom     = ref('')
const dateTo       = ref('')

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
  const s = invoiceSettings.value
  if (!o) return

  const qr = `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(invoiceUrl(o.invoice_token))}`
  const logo = s.logo
    ? `<img src="${window.location.origin}/storage/${s.logo}" style="max-height:50px;">`
    : `<span style="font-size:14pt;font-weight:700;">معمل امواج ديالى</span>`

  const rows = o.items.map((it, i) => `
    <tr style="background:${i%2?'#f9f9f9':'#fff'}">
      <td style="text-align:center;color:#888">${i+1}</td>
      <td>${it.product_name}${it.sku?`<br><small style="color:#aaa">${it.sku}</small>`:''}</td>
      <td style="text-align:center">${formatIQD(it.unit_price)}</td>
      <td style="text-align:center;font-weight:700">×${it.quantity}</td>
      <td style="text-align:center;font-weight:700;color:#1b5e20">${formatIQD(it.total_price)}</td>
    </tr>`).join('')

  const discount = parseFloat(o.discount_amount) > 0
    ? `<tr><td style="color:#777">الخصم${o.coupon?` (${o.coupon.code})`:''}</td><td style="color:#2e7d32;font-weight:700">- ${formatIQD(o.discount_amount)}</td></tr>`
    : `<tr><td style="color:#bbb">الخصم</td><td style="color:#bbb;font-style:italic">لا يوجد خصم</td></tr>`

  const html = `<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8">
<title>فاتورة ${o.invoice_code}</title>
<style>
@page{size:A5 portrait;margin:7mm}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Tahoma,Arial,sans-serif;font-size:10.5pt;color:#111;background:#fff}
.hd{display:flex;justify-content:space-between;align-items:center;
    background:linear-gradient(135deg,#0d47a1,#1565c0 60%,#1b7a3e);
    color:#fff;padding:10px 14px;border-radius:8px 8px 0 0}
.hd-r{text-align:start}
.hd-code{font-size:13pt;font-weight:700;font-family:monospace}
.hd-date{font-size:8pt;color:rgba(255,255,255,.7);margin-top:3px}
.info{display:flex;gap:10px;padding:10px 14px;border:1px solid #e0e0e0;
      border-top:none;background:#fafafa}
.info table{flex:1;border-collapse:collapse;font-size:9.5pt}
.info td{padding:3px 5px;vertical-align:top}
.lbl{color:#888;width:110px;white-space:nowrap}
.val{font-weight:600}
.sec{font-size:9pt;font-weight:700;color:#1565c0;border-bottom:2px solid #dde8ff;
     padding:6px 14px 4px;background:#f5f8ff}
table.items{width:100%;border-collapse:collapse;font-size:9.5pt}
table.items thead tr{background:#1565c0;color:#fff}
table.items th{padding:7px 8px;font-size:8.5pt}
table.items td{padding:6px 8px;border-bottom:1px solid #f0f0f0}
.totals{padding:10px 14px}
.totals table{border-collapse:collapse;min-width:220px;border:1px solid #dde8ff}
.totals td{padding:5px 12px;font-size:9.5pt}
.final td{font-size:11pt;font-weight:800;color:#1565c0;border-top:2px solid #dde8ff}
.foot{background:linear-gradient(135deg,#0d47a1,#1565c0 60%,#1b7a3e);
      color:#fff;text-align:center;padding:9px;margin-top:10px;
      border-radius:0 0 8px 8px;font-size:9pt}
.foot-sub{color:rgba(255,255,255,.6);font-size:7.5pt;margin-top:3px}
</style></head>
<body>
<div class="hd">
  <div style="display:flex;align-items:center;gap:10px">
    ${logo}
    <div>
      <div style="font-size:13pt;font-weight:700">معمل امواج ديالى</div>
      <div style="font-size:8pt;color:rgba(255,255,255,.7)">لإنتاج وتعبئة المياه</div>
      ${s.store_phone?`<div style="font-size:8pt;color:rgba(255,255,255,.7)" dir="ltr">${s.store_phone}</div>`:''}
    </div>
  </div>
  <div class="hd-r">
    <div class="hd-code">${o.invoice_code}</div>
    <div class="hd-date">${formatDate(o.created_at)}</div>
  </div>
</div>
<div class="info">
  <table>
    <tr><td class="lbl">الزبون</td><td class="val">${o.customer_name}</td></tr>
    <tr><td class="lbl">الهاتف</td><td class="val" dir="ltr">${o.customer_phone}</td></tr>
    <tr><td class="lbl">المحافظة</td><td class="val">${o.province}</td></tr>
    <tr><td class="lbl">القضاء / المنطقة</td><td class="val">${o.district}</td></tr>
    <tr><td class="lbl">أقرب نقطة دالة</td><td class="val">${o.nearest_landmark||'—'}</td></tr>
    ${o.notes?`<tr><td class="lbl">ملاحظات</td><td class="val">${o.notes}</td></tr>`:''}
  </table>
  <div style="text-align:center;flex-shrink:0">
    <img src="${qr}" width="85" height="85" style="border:2px solid #e0e0e0;border-radius:5px">
    <div style="font-size:7pt;color:#aaa;margin-top:3px">مسح للفاتورة</div>
  </div>
</div>
<div class="sec">تفاصيل الطلب</div>
<table class="items">
  <thead><tr>
    <th style="width:28px">#</th>
    <th style="text-align:right">المنتج</th>
    <th style="width:90px">سعر الوحدة</th>
    <th style="width:50px">الكمية</th>
    <th style="width:100px">المجموع</th>
  </tr></thead>
  <tbody>${rows}</tbody>
</table>
<div class="totals">
  <table>
    <tr><td>المجموع</td><td style="font-weight:600;text-align:center">${formatIQD(o.total_amount)}</td></tr>
    ${discount}
    <tr class="final"><td>الإجمالي النهائي</td><td style="text-align:center">${formatIQD(o.final_amount)}</td></tr>
  </table>
</div>
<div class="foot">
  <div>${s.thank_you_message||'شكراً لثقتكم بمعمل امواج ديالى'}</div>
  <div class="foot-sub">معمل امواج ديالى — ديالى، العراق</div>
</div>
<` + `script>window.onload=function(){window.print()}<` + `/script>
</body></html>`

  const w = window.open('', '_blank')
  if (!w) return
  w.document.write(html)
  w.document.close()
}

const invoiceUrl = (token: string) =>
  `${window.location.origin}/invoice/${token}`

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
            <span>إدارة الطلبات</span>
            <VChip color="primary" variant="tonal">{{ totalItems }} طلب</VChip>
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
            :headers="headers"
            :items="orders"
            :items-length="totalItems"
            :loading="loading"
            :items-per-page="perPage"
            @update:options="o => loadOrders(o.page)"
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
                <td class="text-muted">{{ item.sku || '—' }}</td>
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

/* ── Print ───────────────────────── */
@media print {
  @page { size: A5 portrait; margin: 6mm; }

  body * { visibility: hidden !important; }

  #invoice-print-area,
  #invoice-print-area * { visibility: visible !important; }

  #invoice-print-area {
    position: fixed !important;
    top: 0 !important;
    right: 0 !important;
    left: 0 !important;
    width: 100% !important;
    background: #fff !important;
    padding: 8mm !important;
    box-sizing: border-box !important;
  }

  .no-print { display: none !important; }
}
</style>
