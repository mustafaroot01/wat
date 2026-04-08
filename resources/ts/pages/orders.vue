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
  if (!invoiceOrder.value || !invoiceSettings.value) return;

  const order = invoiceOrder.value;
  const settings = invoiceSettings.value;

  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  document.body.appendChild(iframe);

  const doc = iframe.contentWindow?.document;
  if (!doc) return;

  const logoHtml = settings.logo 
    ? `<img src="${window.location.origin}/storage/${settings.logo}" class="logo" />` 
    : `<h2 class="store-name">${settings.store_name || 'المتجر'}</h2>`;

  const itemsHtml = order.items.map((item, i) => `
    <tr>
      <td class="text-center">${i + 1}</td>
      <td>${item.product_name} <br> <span class="sku">${item.sku || ''}</span></td>
      <td class="text-center">${formatIQD(item.unit_price)}</td>
      <td class="text-center">${item.quantity}</td>
      <td class="text-center font-bold">${formatIQD(item.total_price)}</td>
    </tr>
  `).join('');

  const barcodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(invoiceUrl(order.invoice_token))}`;

  const html = `
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <title>فاتورة ${order.invoice_code}</title>
      <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        @page { size: A5 portrait; margin: 10mm; }
        body { 
          font-family: 'Cairo', sans-serif; 
          margin: 0; 
          padding: 0; 
          color: #000;
          font-size: 11pt;
        }
        .invoice-box {
          max-width: 100%;
          margin: auto;
          box-sizing: border-box;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .logo { max-height: 60px; max-width: 150px; }
        .store-name { margin: 0; font-size: 16pt; color: #333; }
        .store-info { margin-top: 5px; font-size: 10pt; color: #555; }
        .invoice-details { text-align: left; }
        .invoice-details h2 { margin: 0 0 5px 0; color: #1565c0; font-size: 14pt;}
        .customer-area { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; border-radius: 8px; background: #fafafa; padding: 10px;}
        .customer-info p { margin: 3px 0; font-size: 10pt; }
        .qr-code { border: 1px solid #ddd; border-radius: 8px; padding: 4px; text-align: center; background: #fff;}
        .qr-code img { width: 70px; height: 70px; }
        .qr-code p { margin: 0; font-size: 7pt; color: #777; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10pt; }
        th { background: #f4f6f8; padding: 8px; border-bottom: 2px solid #ddd; text-align: right; color: #333;}
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .sku { font-size: 8pt; color: #777; }
        .totals-area { width: 45%; margin-left: 0; margin-right: auto; background: #fafafa; padding: 10px; border-radius: 8px;}
        .total-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 10pt;}
        .total-row.final { font-weight: bold; font-size: 12pt; color: #1565c0; border-top: 2px solid #eee; padding-top: 6px; }
        .discount-row { color: #2e7d32; }
        .footer { text-align: center; margin-top: 25px; font-size: 10pt; color: #777; font-style: italic; }
      </style>
    </head>
    <body>
      <div class="invoice-box">
        <div class="header">
          <div>
            ${logoHtml}
            <div class="store-info">
              ${settings.store_phone ? '<div dir="ltr" style="text-align: right;">' + settings.store_phone + '</div>' : ''}
              ${settings.store_address ? '<div>' + settings.store_address + '</div>' : ''}
            </div>
          </div>
          <div class="invoice-details">
            <h2>${order.invoice_code}</h2>
            <div>${formatDate(order.created_at)}</div>
          </div>
        </div>

        <div class="customer-area">
          <div class="customer-info">
            <p><strong>الزبون:</strong> ${order.customer_name}</p>
            <p><strong>الهاتف:</strong> <span dir="ltr">${order.customer_phone}</span></p>
            <p><strong>العنوان:</strong> ${order.province} - ${order.district}</p>
            ${order.nearest_landmark ? `<p><strong>أقرب نقطة دالة:</strong> ${order.nearest_landmark}</p>` : ''}
          </div>
          <div class="qr-code">
            <img src="${barcodeUrl}" />
            <p>مسح الفاتورة</p>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th class="text-center" width="5%">#</th>
              <th width="40%">المنتج</th>
              <th class="text-center" width="20%">السعر</th>
              <th class="text-center" width="10%">الكمية</th>
              <th class="text-center" width="25%">المجموع</th>
            </tr>
          </thead>
          <tbody>
            ${itemsHtml}
          </tbody>
        </table>

        <div class="totals-area">
          <div class="total-row">
            <span>المجموع:</span>
            <span>${formatIQD(order.total_amount)}</span>
          </div>
          ${parseFloat(order.discount_amount) > 0 ? `
            <div class="total-row discount-row">
              <span>الخصم ${order.coupon ? '('+order.coupon.code+')' : ''}:</span>
              <span>- ${formatIQD(order.discount_amount)}</span>
            </div>
          ` : ''}
          <div class="total-row final">
             <span>الإجمالي:</span>
            <span>${formatIQD(order.final_amount)}</span>
          </div>
        </div>

        <div class="footer">
          ${settings.thank_you_message || 'شكراً لتسوقكم معنا'}
        </div>
      </div>
      <script>
        window.onload = function() {
          window.print();
        }
      </script>
    </body>
    </html>
  `;

  doc.open();
  doc.write(html);
  doc.close();

  // Clean up
  setTimeout(() => {
    if(document.body.contains(iframe)) document.body.removeChild(iframe);
  }, 5000);
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
            <VBtn
              color="primary" variant="elevated" rounded="lg"
              prepend-icon="ri-printer-line"
              @click="printInvoice"
            >
              طباعة / PDF
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
          <VTable density="compact" class="mb-4">
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
                <td class="text-caption text-medium-emphasis">{{ item.sku ?? '—' }}</td>
                <td>{{ formatIQD(item.unit_price) }}</td>
                <td>{{ item.quantity }}</td>
                <td class="font-weight-medium">{{ formatIQD(item.total_price) }}</td>
              </tr>
            </tbody>
          </VTable>

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
/* 
  قمنا بنقل وظيفة الطباعة لتستخدم iframe مخفي،
  لذا لا حاجة لميديا كويري الطباعة المعقدة هنا.
*/
</style>
