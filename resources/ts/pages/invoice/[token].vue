<script setup lang="ts">
import { formatIQD } from '@/utils/currency'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const token = route.params.token as string

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

const pageUrl = ref('')

onMounted(() => {
  pageUrl.value = window.location.href.split('?')[0]
  loadInvoice().then(() => {
    if (route.query.print && order.value) {
      setTimeout(() => window.print(), 500)
    }
  })
})

// ── Admin status change (only if admin token present) ───────────────
const isAdmin = computed(() => {
  try {
    return !!localStorage.getItem('accessToken')
  } catch (e) {
    return false
  }
})

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
  let adminToken = ''
  try {
    adminToken = localStorage.getItem('accessToken') || ''
  } catch(e) {}
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

const printThermal58 = () => {
  const o = order.value
  if (!o) return

  const storeName    = settings.value.store_name    || 'المتجر'
  const storePhone   = settings.value.store_phone   || ''
  const storeAddress = settings.value.store_address || ''
  const thankYou     = settings.value.thank_you_message || 'شكراً لثقتكم بنا'
  const invoiceUrl   = pageUrl.value || window.location.href.split('?')[0]
  const qrUrl        = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(invoiceUrl)}`

  const formatMoney = (v: string) =>
    Number(v).toLocaleString('ar-IQ') + ' د.ع'

  const itemsHtml = o.items.map((item, i) => `
    <tr>
      <td style="text-align:center;color:#999;">${i + 1}</td>
      <td>${item.product_name}</td>
      <td style="text-align:center;">${item.quantity}</td>
      <td style="text-align:left;font-weight:700;">${formatMoney(item.total_price)}</td>
    </tr>
  `).join('')

  const discountRow = parseFloat(o.discount_amount) > 0
    ? `<tr><td colspan="2">خصم${o.coupon ? ' (' + o.coupon.code + ')' : ''}</td><td colspan="2" style="text-align:left;color:#2e7d32;">- ${formatMoney(o.discount_amount)}</td></tr>`
    : ''

  const html = `<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<title>وصل ${o.invoice_code}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  @page { size: 58mm auto; margin: 2mm 1mm; }
  body {
    font-family: 'Tahoma', 'Arial', sans-serif;
    font-size: 10px;
    width: 54mm;
    color: #000;
    direction: rtl;
  }
  .center { text-align: center; }
  .bold   { font-weight: 700; }
  .line   { border-top: 1px dashed #000; margin: 4px 0; }
  .store-name { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
  .code   { font-size: 12px; font-weight: 700; font-family: monospace; letter-spacing: 1px; }
  table  { width: 100%; border-collapse: collapse; }
  th, td { padding: 2px 3px; font-size: 9px; }
  thead th { font-weight: 700; border-bottom: 1px solid #000; }
  .total-section { margin-top: 4px; }
  .total-row { display: flex; justify-content: space-between; padding: 2px 0; }
  .final { font-size: 12px; font-weight: 700; border-top: 1px solid #000; padding-top: 4px; margin-top: 2px; }
  .qr-section { text-align: center; margin: 6px 0 4px; }
  .qr-section img { width: 90px; height: 90px; }
  .qr-lbl { font-size: 8px; color: #555; margin-top: 2px; }
  .footer { text-align: center; margin-top: 4px; font-size: 9px; }
  @media print { body { width: 54mm; } }
</style>
</head>
<body>

<div class="center">
  <div class="store-name">${storeName}</div>
  ${storePhone ? `<div>${storePhone}</div>` : ''}
  ${storeAddress ? `<div>${storeAddress}</div>` : ''}
</div>

<div class="line"></div>

<div class="center">
  <div class="code">${o.invoice_code}</div>
  <div style="font-size:9px;color:#555;">${new Date(o.created_at).toLocaleString('ar-IQ', {dateStyle:'short', timeStyle:'short'})}</div>
</div>

<div class="line"></div>

<table>
  <tr><td class="bold">الاسم</td><td style="text-align:left;">${o.customer_name}</td></tr>
  <tr><td class="bold">الهاتف</td><td style="text-align:left;direction:ltr;">${o.customer_phone}</td></tr>
  <tr><td class="bold">المنطقة</td><td style="text-align:left;">${o.district} - ${o.province}</td></tr>
  ${o.nearest_landmark ? `<tr><td class="bold">أقرب نقطة</td><td style="text-align:left;">${o.nearest_landmark}</td></tr>` : ''}
  ${o.notes ? `<tr><td class="bold">ملاحظات</td><td style="text-align:left;">${o.notes}</td></tr>` : ''}
</table>

<div class="line"></div>

<table>
  <thead>
    <tr>
      <th style="text-align:center;width:16px;">#</th>
      <th style="text-align:right;">المنتج</th>
      <th style="text-align:center;width:22px;">ك</th>
      <th style="text-align:left;width:46px;">المبلغ</th>
    </tr>
  </thead>
  <tbody>${itemsHtml}</tbody>
</table>

<div class="line"></div>

<div class="total-section">
  <div class="total-row"><span>المجموع</span><span>${formatMoney(o.total_amount)}</span></div>
  ${parseFloat(o.discount_amount) > 0 ? `<div class="total-row" style="color:#2e7d32;"><span>الخصم${o.coupon ? ' (' + o.coupon.code + ')' : ''}</span><span>- ${formatMoney(o.discount_amount)}</span></div>` : ''}
  <div class="total-row final"><span>الإجمالي</span><span>${formatMoney(o.final_amount)}</span></div>
</div>

<div class="qr-section">
  <img src="${qrUrl}" alt="QR" />
  <div class="qr-lbl">امسح للفاتورة الإلكترونية</div>
</div>

<div class="line"></div>
<div class="footer">${thankYou}</div>
<br/>

<script>
  window.onload = () => { window.focus(); window.print(); }
<\/script>
</body>
</html>`

  const w = window.open('', '_blank', 'width=300,height=600')
  if (w) { w.document.write(html); w.document.close() }
}
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
    <div v-else-if="order" class="invoice-wrapper">

      <!-- Actions bar (no-print) -->
      <div class="d-flex align-center justify-space-between mb-4 no-print">
        <VChip :color="statusColor(order.status)" variant="tonal" size="large">
          {{ statusLabel(order.status) }}
        </VChip>
        <div class="d-flex gap-2">
          <VBtn v-if="isAdmin" color="warning" variant="tonal" prepend-icon="ri-refresh-line" @click="openStatusDialog">
            تغيير الحالة
          </VBtn>
          <VBtn color="primary" prepend-icon="ri-printer-line" @click="printPage">
            طباعة / PDF
          </VBtn>
          <VBtn color="teal" variant="tonal" prepend-icon="ri-receipt-line" @click="printThermal58">
            طباعة 58mm
          </VBtn>
        </div>
      </div>

      <!-- ═══ Invoice Card (A4) ═══ -->
      <div id="invoice-print-area" class="inv-card">

        <!-- Header -->
        <div class="inv-header">
          <div class="inv-brand">
            <img v-if="settings.logo" :src="`/media/${settings.logo}`" :alt="settings.store_name" class="inv-logo" />
            <div v-else class="inv-logo d-flex align-center justify-center font-weight-bold" style="background:rgba(255,255,255,.15);color:#fff;font-size:24px;">
              {{ (settings.store_name?.charAt(0) || 'م') }}
            </div>
            <div>
              <div class="inv-brand-name">{{ settings.store_name || 'فاتورة طلب' }}</div>
              <div v-if="settings.store_address" class="inv-brand-sub">{{ settings.store_address }}</div>
              <div v-if="settings.store_phone" class="inv-brand-sub" dir="ltr">{{ settings.store_phone }}</div>
            </div>
          </div>
          <div class="inv-meta">
            <div class="inv-code">{{ order.invoice_code }}</div>
            <div class="inv-date">{{ formatDate(order.created_at) }}</div>
            <div class="inv-status-badge" :class="`status-${order.status}`">
              {{ statusLabel(order.status) }}
            </div>
            <div v-if="order.rejection_reason" class="inv-rejection">
              سبب الرفض: {{ order.rejection_reason }}
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="inv-divider" />

        <!-- Customer + QR row -->
        <div class="inv-info-row">
          <div class="inv-customer">
            <div class="inv-section-title">بيانات الزبون والتوصيل</div>
            <table class="inv-info-table">
              <tr>
                <td class="inv-lbl">الاسم</td>
                <td class="inv-val">{{ order.customer_name }}</td>
              </tr>
              <tr>
                <td class="inv-lbl">الهاتف</td>
                <td class="inv-val"><span dir="ltr" style="display: inline-block;">{{ order.customer_phone }}</span></td>
              </tr>
              <tr>
                <td class="inv-lbl">المحافظة</td>
                <td class="inv-val">{{ order.province }}</td>
              </tr>
              <tr>
                <td class="inv-lbl">القضاء / المنطقة</td>
                <td class="inv-val">{{ order.district }}</td>
              </tr>
              <tr>
                <td class="inv-lbl">أقرب نقطة دالة</td>
                <td class="inv-val">{{ order.nearest_landmark || '—' }}</td>
              </tr>
              <tr v-if="order.notes">
                <td class="inv-lbl">ملاحظات</td>
                <td class="inv-val">{{ order.notes }}</td>
              </tr>
            </table>
          </div>
          <div class="inv-qr">
            <img v-if="pageUrl"
              :src="`https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=${encodeURIComponent(pageUrl)}`"
              width="90" height="90" alt="QR"
              class="inv-qr-img"
            />
            <div class="inv-qr-lbl">مسح للفاتورة</div>
          </div>
        </div>

        <!-- Items table -->
        <div class="inv-section-title mt-4 mb-2">تفاصيل الطلب</div>
        <table class="inv-table">
          <thead>
            <tr>
              <th style="width:36px;">#</th>
              <th class="text-start">المنتج</th>
              <th style="width:110px;">SKU</th>
              <th style="width:110px;" class="text-end">سعر الوحدة</th>
              <th style="width:60px;" class="text-center">الكمية</th>
              <th style="width:120px;" class="text-end">المجموع</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in order.items" :key="item.id">
              <td class="text-center text-muted">{{ i + 1 }}</td>
              <td class="inv-product-name">{{ item.product_name }}</td>
              <td class="text-muted inv-sku">{{ item.sku || item.product?.sku || '—' }}</td>
              <td class="text-end">{{ formatIQD(item.unit_price) }}</td>
              <td class="text-center inv-qty">× {{ item.quantity }}</td>
              <td class="text-end inv-total-cell">{{ formatIQD(item.total_price) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Totals -->
        <div class="inv-totals">
          <div class="inv-totals-inner">
            <div class="inv-total-row">
              <span class="inv-total-lbl">المجموع</span>
              <span class="inv-total-val">{{ formatIQD(order.total_amount) }}</span>
            </div>
            <div class="inv-total-row" :class="parseFloat(order.discount_amount) > 0 ? 'inv-discount' : 'inv-no-discount'">
              <span class="inv-total-lbl">
                الخصم
                <span v-if="order.coupon" class="inv-coupon-badge">{{ order.coupon.code }}</span>
              </span>
              <span class="inv-total-val">
                {{ parseFloat(order.discount_amount) > 0 ? '- ' + formatIQD(order.discount_amount) : 'لا يوجد خصم' }}
              </span>
            </div>
            <div class="inv-divider my-2" />
            <div class="inv-total-row inv-final">
              <span class="inv-total-lbl">الإجمالي النهائي</span>
              <span class="inv-total-val">{{ formatIQD(order.final_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="inv-footer">
          <div class="inv-footer-wave" />
          <div class="inv-footer-text">{{ settings.thank_you_message || 'شكراً لثقتكم بنا' }}</div>
          <div class="inv-footer-copy">{{ settings.store_name || 'المتجر' }} — {{ settings.store_address || 'العراق' }}</div>
        </div>

      </div>
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
/* ── Page shell ─────────────────────────────────── */
.invoice-page {
  min-height: 100vh;
  background: #eceff1;
  padding: 24px 16px;
}
.invoice-wrapper {
  max-width: 559px; /* A5 width at 96dpi: 148mm */
  margin: 0 auto;
}

/* ── Card ───────────────────────────────────────── */
.inv-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0,0,0,.10);
  overflow: hidden;
  font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
  direction: rtl;
  font-size: 13px;
  color: #263238;
}

/* ── Header ─────────────────────────────────────── */
.inv-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 28px 32px 20px;
  background: linear-gradient(130deg, #0d47a1 0%, #1565c0 60%, #1b7a3e 100%);
}
.inv-brand { display: flex; align-items: center; gap: 14px; }
.inv-logo  { width: 56px; height: 56px; object-fit: contain; border-radius: 8px; background: rgba(255,255,255,.15); padding: 4px; }
.inv-brand-name { color: #fff; font-size: 1.2rem; font-weight: 700; line-height: 1.2; }
.inv-brand-sub  { color: rgba(255,255,255,.7); font-size: .78rem; margin-top: 2px; }

.inv-meta       { text-align: start; }
.inv-code       { color: #fff; font-size: 1.1rem; font-weight: 700; font-family: monospace; }
.inv-date       { color: rgba(255,255,255,.75); font-size: .8rem; margin-top: 4px; }
.inv-rejection  { color: #ffcdd2; font-size: .75rem; margin-top: 4px; }

.inv-status-badge {
  display: inline-block;
  margin-top: 6px;
  padding: 3px 12px;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 600;
  background: rgba(255,255,255,.2);
  color: #fff;
}
.status-sent               { background: rgba(255,255,255,.25); }
.status-received_preparing { background: rgba(245,124,0,.6); }
.status-out_for_delivery   { background: rgba(2,136,209,.6); }
.status-delivered          { background: rgba(46,125,50,.7); }
.status-rejected           { background: rgba(198,40,40,.6); }
.status-cancelled          { background: rgba(84,110,122,.5); }

/* ── Divider ─────────────────────────────────────── */
.inv-divider {
  height: 1px;
  background: #e0e0e0;
  margin: 0 32px;
}

/* ── Info row ─────────────────────────────────────── */
.inv-info-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px 32px;
  gap: 20px;
}
.inv-customer { flex: 1; }
.inv-section-title {
  font-size: .8rem;
  font-weight: 700;
  color: #1565c0;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 10px;
  border-bottom: 2px solid #e3f0ff;
  padding-bottom: 4px;
}
.inv-info-table { width: 100%; border-collapse: collapse; }
.inv-info-table tr td { padding: 4px 6px; font-size: .83rem; vertical-align: top; }
.inv-lbl { color: #78909c; width: 130px; white-space: nowrap; font-weight: 500; text-align: start; }
.inv-val { color: #263238; font-weight: 700; text-align: start; }

.inv-qr { display: flex; flex-direction: column; align-items: center; gap: 6px; flex-shrink: 0; }
.inv-qr-img { border: 2px solid #e0e0e0; border-radius: 8px; }
.inv-qr-lbl { font-size: .72rem; color: #90a4ae; }

/* ── Items table ─────────────────────────────────── */
.inv-table {
  width: calc(100% - 64px);
  margin: 0 32px 0;
  border-collapse: collapse;
  font-size: .83rem;
}
.inv-table thead tr {
  background: #1565c0;
  color: #fff;
}
.inv-table thead th {
  padding: 9px 10px;
  font-weight: 600;
  font-size: .78rem;
}
.inv-table tbody tr { border-bottom: 1px solid #f0f0f0; }
.inv-table tbody tr:last-child { border-bottom: none; }
.inv-table tbody tr:nth-child(even) { background: #f8faff; }
.inv-table tbody td { padding: 8px 10px; }
.inv-product-name { font-weight: 700; color: #263238; }
.inv-sku          { font-size: .75rem; color: #90a4ae; font-family: monospace; }
.inv-qty          { font-weight: 700; color: #546e7a; }
.inv-total-cell   { font-weight: 700; color: #2e7d32; }
.text-muted       { color: #90a4ae; }

/* ── Totals ─────────────────────────────────────── */
.inv-totals { display: flex; justify-content: flex-start; padding: 16px 32px 0; }
.inv-totals-inner {
  min-width: 260px;
  background: #f8faff;
  border: 1px solid #e3f0ff;
  border-radius: 10px;
  padding: 12px 16px;
}
.inv-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 0;
  font-size: .85rem;
}
.inv-total-lbl { color: #546e7a; font-weight: 500; }
.inv-total-val { font-weight: 600; color: #263238; }
.inv-discount .inv-total-val { color: #2e7d32; }
.inv-no-discount .inv-total-val { color: #90a4ae; font-style: italic; font-size: .78rem; }
.inv-coupon-badge {
  display: inline-block;
  background: #e8f5e9;
  color: #2e7d32;
  font-size: .68rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
  margin-right: 6px;
  font-family: monospace;
}
.inv-final .inv-total-lbl { font-weight: 700; color: #1a237e; font-size: .95rem; }
.inv-final .inv-total-val { font-size: 1.1rem; font-weight: 800; color: #1565c0; }

/* ── Footer ─────────────────────────────────────── */
.inv-footer {
  margin-top: 24px;
  background: linear-gradient(130deg, #0d47a1 0%, #1565c0 60%, #1b7a3e 100%);
  padding: 18px 32px 16px;
  text-align: center;
}
.inv-footer-text { color: #fff; font-size: .9rem; font-weight: 600; }
.inv-footer-copy { color: rgba(255,255,255,.6); font-size: .75rem; margin-top: 4px; }

/* ── mt/mb helpers ──────────────────────────────── */
.mt-4 { margin-top: 16px !important; }
.mb-2 { margin-bottom: 8px !important; }
.my-2 { margin-top: 8px !important; margin-bottom: 8px !important; }

/* ── Print ──────────────────────────────────────── */
@media print {
  @page { size: A5 portrait; margin: 6mm; }

  html, body, #app, .v-application, .v-application__wrap, .layout-wrapper.layout-blank {
    display: block !important;
    height: auto !important;
    min-height: auto !important;
    overflow: visible !important;
    background: #fff !important;
  }

  .invoice-page { background: #fff !important; padding: 0 !important; min-height: auto !important; }
  .invoice-wrapper { max-width: 100% !important; margin: 0 !important; }
  .no-print { display: none !important; }

  .inv-card {
    box-shadow: none !important;
    border-radius: 0 !important;
    font-size: 11px !important;
  }
  .inv-header  { padding: 14px 18px 12px !important; }
  .inv-logo    { width: 40px !important; height: 40px !important; }
  .inv-brand-name { font-size: 1rem !important; }
  .inv-info-row   { padding: 12px 18px !important; }
  .inv-table      { width: calc(100% - 36px) !important; margin: 0 18px !important; }
  .inv-totals     { padding: 10px 18px 0 !important; }
  .inv-footer     { padding: 12px 18px !important; }
  .inv-qr-img     { width: 80px !important; height: 80px !important; }
}
</style>
