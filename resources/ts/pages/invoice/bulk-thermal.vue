<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

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
  notes: string | null
  created_at: string
  items: OrderItem[]
  coupon?: { code: string } | null
}

const orders   = ref<Order[]>([])
const settings = ref<Record<string, string>>({})
const loading  = ref(true)
const error    = ref('')

const idsString = String(route.query.ids || '')

const loadInvoices = async () => {
  loading.value = true
  error.value   = ''
  let adminToken = ''
  try { adminToken = localStorage.getItem('accessToken') || '' } catch(e) {}

  if (!adminToken) {
    error.value = 'يرجى تسجيل الدخول أولاً'
    loading.value = false
    return
  }

  try {
    const res  = await fetch(`/api/admin/orders/bulk-invoice?ids=${idsString}`, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${adminToken}`
      }
    })
    const data = await res.json()
    if (!res.ok) { error.value = data.message || 'تعذّر تحميل الفواتير.'; return }
    orders.value   = data.orders
    settings.value = data.settings
  } catch {
    error.value = 'تعذّر تحميل الفواتير'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadInvoices().then(() => {
    if (route.query.print && orders.value.length) {
      setTimeout(() => window.print(), 800)
    }
  })
})

const printPage = () => window.print()

const fmt = (v: string) => Number(v).toLocaleString('ar-IQ') + ' د.ع'
const formatDate = (d: string) =>
  new Date(d).toLocaleString('ar-IQ', { dateStyle: 'short', timeStyle: 'short' })
const invoiceUrl = (token: string) => `${window.location.origin}/invoice/${token}`
</script>

<template>
  <div class="thermal-page">

    <div v-if="loading" class="d-flex justify-center align-center" style="min-height:80vh;">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <div v-else-if="error" class="d-flex justify-center align-center" style="min-height:80vh;">
      <VAlert type="error" variant="tonal" max-width="400">{{ error }}</VAlert>
    </div>

    <template v-else-if="orders.length">

      <!-- Toolbar (no-print) -->
      <div class="no-print toolbar">
        <span class="font-weight-bold text-h6">طباعة حرارية 58mm — {{ orders.length }} وصل</span>
        <VBtn color="primary" prepend-icon="ri-printer-line" @click="printPage" size="large">
          طباعة الكل
        </VBtn>
      </div>

      <!-- Receipts -->
      <div
        v-for="order in orders"
        :key="order.id"
        class="receipt"
      >
        <!-- Store Header -->
        <div class="r-center r-store-name">{{ settings.store_name || 'المتجر' }}</div>
        <div v-if="settings.store_phone" class="r-center r-small">{{ settings.store_phone }}</div>
        <div v-if="settings.store_address" class="r-center r-small">{{ settings.store_address }}</div>

        <div class="r-dashed" />

        <!-- Invoice Code + Date -->
        <div class="r-center r-code">{{ order.invoice_code }}</div>
        <div class="r-center r-muted">{{ formatDate(order.created_at) }}</div>

        <div class="r-dashed" />

        <!-- Customer Info -->
        <table class="r-info">
          <tr><td class="r-lbl">الاسم</td><td>{{ order.customer_name }}</td></tr>
          <tr><td class="r-lbl">الهاتف</td><td dir="ltr">{{ order.customer_phone }}</td></tr>
          <tr><td class="r-lbl">المنطقة</td><td>{{ order.district }} - {{ order.province }}</td></tr>
          <tr v-if="order.nearest_landmark">
            <td class="r-lbl">أقرب نقطة</td><td>{{ order.nearest_landmark }}</td>
          </tr>
          <tr v-if="order.notes">
            <td class="r-lbl">ملاحظات</td><td>{{ order.notes }}</td>
          </tr>
        </table>

        <div class="r-dashed" />

        <!-- Items -->
        <table class="r-items">
          <thead>
            <tr>
              <th class="r-center">#</th>
              <th>المنتج</th>
              <th class="r-center">ك</th>
              <th class="r-left">المبلغ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in order.items" :key="item.id">
              <td class="r-center r-muted">{{ i + 1 }}</td>
              <td>{{ item.product_name }}</td>
              <td class="r-center">{{ item.quantity }}</td>
              <td class="r-left r-bold">{{ fmt(item.total_price) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="r-dashed" />

        <!-- Totals -->
        <div class="r-row"><span>المجموع</span><span>{{ fmt(order.total_amount) }}</span></div>
        <div v-if="parseFloat(order.discount_amount) > 0" class="r-row r-discount">
          <span>الخصم{{ order.coupon ? ' (' + order.coupon.code + ')' : '' }}</span>
          <span>- {{ fmt(order.discount_amount) }}</span>
        </div>
        <div class="r-row r-final"><span>الإجمالي</span><span>{{ fmt(order.final_amount) }}</span></div>

        <!-- QR -->
        <div class="r-center r-qr">
          <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(invoiceUrl(order.invoice_token))}`" alt="QR" />
          <div class="r-muted" style="font-size:9px;margin-top:3px;">امسح للفاتورة الإلكترونية</div>
        </div>

        <div class="r-dashed" />
        <div class="r-center r-footer">{{ settings.thank_you_message || 'شكراً لثقتكم بنا' }}</div>
      </div>

    </template>

    <div v-else class="d-flex justify-center align-center" style="min-height:80vh;">
      <VAlert type="warning" variant="tonal">لم يتم تحديد أي طلبات</VAlert>
    </div>
  </div>
</template>

<style scoped>
.thermal-page {
  direction: rtl;
  font-family: 'Tahoma', 'Arial', sans-serif;
  background: #f5f5f5;
  padding: 16px;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  background: #fff;
  padding: 12px 20px;
  border-radius: 8px;
}

.receipt {
  width: 58mm;
  margin: 0 auto 16px;
  background: #fff;
  padding: 6mm 4mm;
  font-size: 11px;
  color: #000;
  page-break-after: always;
}

.r-center  { text-align: center; }
.r-left    { text-align: left; }
.r-bold    { font-weight: 700; }
.r-muted   { color: #666; }
.r-store-name { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.r-code    { font-size: 14px; font-weight: 700; font-family: monospace; letter-spacing: 1px; }
.r-small   { font-size: 10px; }
.r-footer  { font-size: 10px; margin-top: 4px; }

.r-dashed {
  border-top: 1px dashed #000;
  margin: 5px 0;
}

.r-info {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.r-info td { padding: 2px 4px; }
.r-lbl { font-weight: 700; white-space: nowrap; width: 72px; }

.r-items {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.r-items th, .r-items td { padding: 3px 4px; }
.r-items thead th { font-weight: 700; border-bottom: 1px solid #000; }

.r-row {
  display: flex;
  justify-content: space-between;
  padding: 3px 4px;
  font-size: 11px;
}
.r-discount { color: #2e7d32; }
.r-final {
  font-size: 13px;
  font-weight: 700;
  border-top: 1px solid #000;
  padding-top: 4px;
  margin-top: 2px;
}

.r-qr { margin: 8px 0 4px; }
.r-qr img { width: 100px; height: 100px; }

@media print {
  .no-print { display: none !important; }
  .thermal-page { background: #fff; padding: 0; }
  .receipt { margin: 0; box-shadow: none; }

  @page {
    size: 58mm auto;
    margin: 2mm 1mm;
  }
}
</style>
