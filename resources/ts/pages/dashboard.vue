<script setup lang="ts">
import { useStoreSettings } from '@/composables/useStoreSettings'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const { branding, fetchBranding } = useStoreSettings()

// ── Data ────────────────────────────────────────────────
const loading      = ref(true)
const statusCounts = ref<Record<string, number>>({})
const orders       = ref({ today: 0, week: 0, month: 0, total: 0 })
const revenueMonth = ref(0)
const users        = ref({ total: 0, today: 0 })
const recentOrders = ref<any[]>([])
const topProducts  = ref<any[]>([])
const topDistricts = ref<any[]>([])
const revenueChart = ref<{ date: string; total: number }[]>([])
const ordersChart  = ref<{ date: string; count: number }[]>([])
const credits      = ref({ otp: 0, notification: 0 })

// ── Status config ────────────────────────────────────────
const statusConfig = [
  { key: 'sent',                label: 'تم الإرسال',                 icon: 'ri-send-plane-2-line',   color: '#1565c0', bg: '#e3f0ff' },
  { key: 'received_preparing',  label: 'جاري التجهيز',               icon: 'ri-refresh-line',        color: '#f57c00', bg: '#fff3e0' },
  { key: 'out_for_delivery',    label: 'جاري التوصيل',               icon: 'ri-truck-line',          color: '#0288d1', bg: '#e1f5fe' },
  { key: 'delivered',           label: 'تم التسليم',                  icon: 'ri-checkbox-circle-line',color: '#2e7d32', bg: '#e8f5e9' },
  { key: 'rejected',            label: 'مرفوض',                      icon: 'ri-close-circle-line',   color: '#c62828', bg: '#ffebee' },
  { key: 'cancelled',           label: 'ملغي',                        icon: 'ri-forbid-line',         color: '#546e7a', bg: '#eceff1' },
]

const chipColor = (s: string) => ({
  sent: 'primary', received_preparing: 'warning',
  out_for_delivery: 'info', delivered: 'success',
  rejected: 'error', cancelled: 'default',
}[s] || 'default')

const chipLabel = (s: string) =>
  statusConfig.find(c => c.key === s)?.label ?? s

const formatDate = (d: string) =>
  new Date(d).toLocaleString('ar-IQ', { dateStyle: 'short', timeStyle: 'short' })

// ── Load ─────────────────────────────────────────────────
const load = async () => {
  loading.value = true
  try {
    const res  = await apiFetch('/api/admin/dashboard')
    const data = await res.json()
    statusCounts.value = data.status_counts  ?? {}
    orders.value       = data.orders         ?? { today: 0, week: 0, month: 0, total: 0 }
    revenueMonth.value = data.revenue_month  ?? 0
    users.value        = data.users          ?? { total: 0, today: 0 }
    recentOrders.value = data.recent_orders  ?? []
    topProducts.value  = data.top_products   ?? []
    topDistricts.value = data.top_districts  ?? []
    revenueChart.value = data.revenue_chart  ?? []
    ordersChart.value  = data.orders_chart   ?? []
    credits.value      = data.credits        ?? { otp: 0, notification: 0 }
  } finally {
    loading.value = false
  }
}

onMounted(() => { load(); fetchBranding() })

const goToOrders = (status: string) =>
  router.push({ path: '/orders', query: { status } })

// ── Chart: Revenue (Area) ────────────────────────────────
const revenueChartOptions = computed(() => ({
  chart: { type: 'area', toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'Cairo, sans-serif', animations: { enabled: true } },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
  colors: ['#2e7d32'],
  xaxis: {
    categories: revenueChart.value.map(r => r.date),
    labels: { rotate: 0, style: { fontSize: '9px' },
      formatter: (_: string, i: number) => (i % 5 === 0 ? revenueChart.value[i]?.date ?? '' : '') },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { labels: { formatter: (v: number) => v >= 1000 ? (v/1000).toFixed(0)+'k' : String(v) } },
  tooltip: { y: { formatter: (v: number) => Number(v).toLocaleString('ar-IQ') + ' د.ع' } },
  grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
}))

const revenueChartSeries = computed(() => [{
  name: 'الإيرادات',
  data: revenueChart.value.map(r => r.total),
}])

// ── Chart: Orders per day (Bar) ──────────────────────────
const ordersChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Cairo, sans-serif', animations: { enabled: true } },
  dataLabels: { enabled: false },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
  colors: ['#1565c0'],
  xaxis: {
    categories: ordersChart.value.map(r => r.date),
    labels: { rotate: 0, style: { fontSize: '9px' },
      formatter: (_: string, i: number) => (i % 5 === 0 ? ordersChart.value[i]?.date ?? '' : '') },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { min: 0, tickAmount: 4, labels: { formatter: (v: number) => isFinite(v) ? Math.round(v).toString() : '0' } },
  tooltip: { y: { formatter: (v: number) => v + ' طلب' } },
  grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
}))

const ordersChartSeries = computed(() => [{
  name: 'الطلبات',
  data: ordersChart.value.map(r => r.count),
}])

// ── Chart: Orders by Status (Donut) ─────────────────────
const statusDonutOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'Cairo, sans-serif', animations: { enabled: true } },
  labels: statusConfig.map(s => s.label),
  colors: statusConfig.map(s => s.color),
  legend: { position: 'bottom', fontSize: '11px', fontFamily: 'Cairo, sans-serif' },
  dataLabels: { enabled: true, formatter: (val: number) => val.toFixed(0) + '%' },
  plotOptions: { pie: { donut: { size: '60%', labels: { show: true,
    total: { show: true, label: 'الإجمالي', fontSize: '13px', fontFamily: 'Cairo, sans-serif',
      formatter: () => statusConfig.reduce((a, s) => a + (statusCounts.value[s.key] ?? 0), 0).toString() }
  } } } },
  tooltip: { y: { formatter: (v: number) => v + ' طلب' } },
}))

const statusDonutSeries = computed(() =>
  statusConfig.map(s => statusCounts.value[s.key] ?? 0)
)

// ── Chart: Top Products (Horizontal Bar) ────────────────
const topProductsChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Cairo, sans-serif', animations: { enabled: true } },
  plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%',
    dataLabels: { position: 'top' } } },
  dataLabels: { enabled: true, offsetX: -6, style: { fontSize: '10px', colors: ['#fff'] },
    formatter: (v: number) => v + ' قطعة' },
  colors: ['#1565c0'],
  xaxis: { categories: topProducts.value.map(p => p.product_name),
    labels: { style: { fontSize: '10px', fontFamily: 'Cairo, sans-serif' } } },
  yaxis: { labels: { style: { fontSize: '10px', fontFamily: 'Cairo, sans-serif' }, maxWidth: 120 } },
  tooltip: { y: { formatter: (v: number) => v + ' قطعة' } },
  grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
}))

const topProductsChartSeries = computed(() => [{
  name: 'الكمية المطلوبة',
  data: topProducts.value.map(p => p.total_qty),
}])
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center align-center pa-16">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <template v-else>
      <VRow>

        <!-- ═══ Welcome Banner ══════════════════════════════ -->
        <VCol cols="12">
          <div class="welcome-banner">
            <div class="wb-deco wb-deco-1" /><div class="wb-deco wb-deco-2" />
            <div class="d-flex align-center justify-space-between position-relative" style="z-index:1;gap:12px;">
              <div class="d-flex align-center gap-3" style="min-width:0;">
                <img
                  v-if="branding.logo_url"
                  :src="branding.logo_url"
                  style="width:54px;height:54px;object-fit:contain;border-radius:10px;flex-shrink:0;"
                />
                <div style="min-width:0;">
                  <h2 class="wb-title">{{ branding.store_name }}</h2>
                  <p class="wb-sub">لوحة التحكم الإدارية</p>
                  <p class="wb-desc">إجمالي الطلبات: <strong>{{ orders.total }}</strong> طلب</p>
                </div>
              </div>
              <div class="wb-stat-badge flex-shrink-0">
                <span class="wb-stat-num">{{ orders.today }}</span>
                <span class="wb-stat-lbl">طلب اليوم</span>
              </div>
            </div>
          </div>
        </VCol>

        <!-- ═══ Order Stats Cards ════════════════════════════ -->
        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#e3f0ff;">
                  <VIcon icon="ri-shopping-cart-2-line" color="#1565c0" size="22" />
                </div>
                <span class="stat-sub">اليوم</span>
              </div>
              <div class="stat-num">{{ orders.today }}</div>
              <div class="stat-lbl">طلبات اليوم</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#e8f5e9;">
                  <VIcon icon="ri-calendar-2-line" color="#2e7d32" size="22" />
                </div>
                <span class="stat-sub">الأسبوع</span>
              </div>
              <div class="stat-num">{{ orders.week }}</div>
              <div class="stat-lbl">طلبات الأسبوع</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#fff3e0;">
                  <VIcon icon="ri-calendar-line" color="#f57c00" size="22" />
                </div>
                <span class="stat-sub">الشهر</span>
              </div>
              <div class="stat-num">{{ orders.month }}</div>
              <div class="stat-lbl">طلبات الشهر</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#e8f5e9;">
                  <VIcon icon="ri-money-dollar-circle-line" color="#2e7d32" size="22" />
                </div>
                <span class="stat-sub">الشهر</span>
              </div>
              <div class="stat-num" style="font-size:1rem;">{{ formatIQD(revenueMonth) }}</div>
              <div class="stat-lbl">إيرادات الشهر</div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Users Cards ══════════════════════════════════ -->
        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#e3f2fd;">
                  <VIcon icon="ri-group-line" color="#0277bd" size="22" />
                </div>
                <span class="stat-sub">الكل</span>
              </div>
              <div class="stat-num">{{ users.total }}</div>
              <div class="stat-lbl">إجمالي المستخدمين</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" style="background:#f3e5f5;">
                  <VIcon icon="ri-user-add-line" color="#7b1fa2" size="22" />
                </div>
                <span class="stat-sub">اليوم</span>
              </div>
              <div class="stat-num">{{ users.today }}</div>
              <div class="stat-lbl">مستخدمين جدد</div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Credits Cards ════════════════════════════════ -->
        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0" :style="credits.otp === 0 ? 'border:1px solid #ff980020' : ''">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" :style="credits.otp > 10 ? 'background:#e8f5e9;' : credits.otp > 0 ? 'background:#fff3e0;' : 'background:#ffebee;'">
                  <VIcon icon="ri-message-3-line" :color="credits.otp > 10 ? '#2e7d32' : credits.otp > 0 ? '#f57c00' : '#c62828'" size="22" />
                </div>
                <VChip :color="credits.otp > 10 ? 'success' : credits.otp > 0 ? 'warning' : 'error'" size="x-small" variant="flat">
                  {{ credits.otp > 0 ? 'متاح' : 'صفر' }}
                </VChip>
              </div>
              <div class="stat-num">{{ credits.otp }}</div>
              <div class="stat-lbl">رصيد OTP</div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="6" sm="3">
          <VCard class="stat-card" rounded="lg" elevation="0" :style="credits.notification === 0 ? 'border:1px solid #f4433620' : ''">
            <VCardText class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <div class="stat-icon-wrap" :style="credits.notification > 5 ? 'background:#e3f0ff;' : credits.notification > 0 ? 'background:#fff3e0;' : 'background:#ffebee;'">
                  <VIcon icon="ri-notification-badge-line" :color="credits.notification > 5 ? '#1565c0' : credits.notification > 0 ? '#f57c00' : '#c62828'" size="22" />
                </div>
                <VChip :color="credits.notification > 5 ? 'primary' : credits.notification > 0 ? 'warning' : 'error'" size="x-small" variant="flat">
                  {{ credits.notification > 0 ? 'متاح' : 'منتهي' }}
                </VChip>
              </div>
              <div class="stat-num">{{ credits.notification }}</div>
              <div class="stat-lbl">رصيد الإشعارات</div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Order Status Cards (clickable) ═══════════════ -->
        <VCol
          v-for="sc in statusConfig"
          :key="sc.key"
          cols="6"
          sm="4"
          md="2"
        >
          <VCard
            class="status-card"
            rounded="lg"
            elevation="0"
            :style="`border-top:3px solid ${sc.color};`"
            @click="goToOrders(sc.key)"
          >
            <VCardText class="pa-4 text-center">
              <div class="status-icon-wrap mx-auto mb-2" :style="`background:${sc.bg};`">
                <VIcon :icon="sc.icon" :color="sc.color" size="24" />
              </div>
              <div class="status-count" :style="`color:${sc.color};`">
                {{ statusCounts[sc.key] ?? 0 }}
              </div>
              <div class="status-lbl">{{ sc.label }}</div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Revenue Chart ════════════════════════════════ -->
        <VCol cols="12" md="8">
          <VCard rounded="xl" elevation="0" class="chart-card" style="height:100%;">
            <div class="chart-header chart-header-green">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div class="d-flex align-center gap-3">
                  <div class="chart-icon-box" style="background:rgba(255,255,255,0.2);">
                    <VIcon icon="ri-line-chart-line" color="white" size="22" />
                  </div>
                  <div>
                    <div class="chart-header-title">الإيرادات</div>
                    <div class="chart-header-sub">آخر 30 يوم</div>
                  </div>
                </div>
                <div class="chart-kpi-box">
                  <div class="chart-kpi-num">{{ formatIQD(revenueMonth) }}</div>
                  <div class="chart-kpi-lbl">إجمالي الشهر</div>
                </div>
              </div>
            </div>
            <VCardText class="pa-4 pt-3">
              <div v-if="!revenueChart.length" class="empty-chart-state">
                <VIcon icon="ri-line-chart-line" size="40" color="grey-lighten-2" />
                <div class="mt-2 text-caption text-medium-emphasis">لا توجد بيانات إيرادات بعد</div>
              </div>
              <VueApexCharts
                v-else
                type="area"
                height="200"
                :options="revenueChartOptions"
                :series="revenueChartSeries"
              />
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Orders Donut ══════════════════════════════════ -->
        <VCol cols="12" md="4">
          <VCard rounded="xl" elevation="0" class="chart-card" style="height:100%;">
            <div class="chart-header chart-header-blue">
              <div class="d-flex align-center gap-3">
                <div class="chart-icon-box" style="background:rgba(255,255,255,0.2);">
                  <VIcon icon="ri-pie-chart-2-line" color="white" size="22" />
                </div>
                <div>
                  <div class="chart-header-title">توزيع الطلبات</div>
                  <div class="chart-header-sub">حسب الحالة</div>
                </div>
              </div>
            </div>
            <VCardText class="pa-2 pt-1">
              <VueApexCharts
                type="donut"
                height="248"
                :options="statusDonutOptions"
                :series="statusDonutSeries"
              />
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Daily Orders Chart ════════════════════════════ -->
        <VCol cols="12" md="6">
          <VCard rounded="xl" elevation="0" class="chart-card">
            <div class="chart-header chart-header-indigo">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div class="d-flex align-center gap-3">
                  <div class="chart-icon-box" style="background:rgba(255,255,255,0.2);">
                    <VIcon icon="ri-bar-chart-2-line" color="white" size="22" />
                  </div>
                  <div>
                    <div class="chart-header-title">الطلبات اليومية</div>
                    <div class="chart-header-sub">آخر 30 يوم</div>
                  </div>
                </div>
                <div class="chart-kpi-box">
                  <div class="chart-kpi-num">{{ orders.month }}</div>
                  <div class="chart-kpi-lbl">هذا الشهر</div>
                </div>
              </div>
            </div>
            <VCardText class="pa-4 pt-3">
              <div v-if="!ordersChart.length" class="empty-chart-state">
                <VIcon icon="ri-bar-chart-2-line" size="40" color="grey-lighten-2" />
                <div class="mt-2 text-caption text-medium-emphasis">لا توجد بيانات بعد</div>
              </div>
              <VueApexCharts
                v-else
                type="bar"
                height="200"
                :options="ordersChartOptions"
                :series="ordersChartSeries"
              />
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Top Products List ══════════════════════════════ -->
        <VCol cols="12" md="6">
          <VCard rounded="xl" elevation="0" class="chart-card" style="height:100%;">
            <div class="chart-header chart-header-orange">
              <div class="d-flex align-center gap-3">
                <div class="chart-icon-box" style="background:rgba(255,255,255,0.2);">
                  <VIcon icon="ri-fire-line" color="white" size="22" />
                </div>
                <div>
                  <div class="chart-header-title">أكثر المنتجات طلباً</div>
                  <div class="chart-header-sub">أعلى 5 منتجات</div>
                </div>
              </div>
            </div>
            <VCardText class="pa-0">
              <div v-if="!topProducts.length" class="empty-chart-state py-10">
                <VIcon icon="ri-shopping-basket-line" size="40" color="grey-lighten-2" />
                <div class="mt-2 text-caption text-medium-emphasis">لا توجد بيانات بعد</div>
              </div>
              <div
                v-for="(p, i) in topProducts"
                :key="p.product_name"
                class="top-row px-5 py-3"
                :class="{ 'top-row-border': i < topProducts.length - 1 }"
              >
                <div class="d-flex align-center gap-3 mb-2">
                  <div class="rank-badge" :class="`rank-${i+1}`">{{ i + 1 }}</div>
                  <div class="flex-grow-1 font-weight-medium text-body-2" style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ p.product_name }}
                  </div>
                  <div class="top-qty" style="color:#e65100;">{{ p.total_qty }} قطعة</div>
                </div>
                <div class="top-bar-wrap" style="width:100%;margin-right:0;">
                  <div
                    class="top-bar"
                    :style="`width:${Math.round((p.total_qty / (topProducts[0]?.total_qty || 1)) * 100)}%;background:linear-gradient(90deg,#e65100,#ff8f00)`"
                  />
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Top Districts List ════════════════════════════ -->
        <VCol cols="12" md="6">
          <VCard rounded="xl" elevation="0" class="chart-card">
            <div class="chart-header chart-header-teal">
              <div class="d-flex align-center gap-3">
                <div class="chart-icon-box" style="background:rgba(255,255,255,0.2);">
                  <VIcon icon="ri-map-pin-2-line" color="white" size="22" />
                </div>
                <div>
                  <div class="chart-header-title">أكثر المناطق طلباً</div>
                  <div class="chart-header-sub">أعلى 5 مناطق</div>
                </div>
              </div>
            </div>
            <VCardText class="pa-0">
              <div v-if="!topDistricts.length" class="empty-chart-state py-10">
                <VIcon icon="ri-map-pin-line" size="40" color="grey-lighten-2" />
                <div class="mt-2 text-caption text-medium-emphasis">لا توجد بيانات بعد</div>
              </div>
              <div
                v-for="(d, i) in topDistricts"
                :key="d.district"
                class="top-row px-5 py-3"
                :class="{ 'top-row-border': i < topDistricts.length - 1 }"
              >
                <div class="d-flex align-center gap-3 mb-2">
                  <div class="rank-badge" :class="`rank-${i+1}`">{{ i + 1 }}</div>
                  <div class="flex-grow-1 font-weight-medium text-body-2">{{ d.district }}</div>
                  <div class="top-qty">{{ d.order_count }} طلب</div>
                </div>
                <div class="top-bar-wrap" style="width:100%;margin-right:0;">
                  <div
                    class="top-bar"
                    :style="`width:${Math.round((d.order_count / (topDistricts[0]?.order_count || 1)) * 100)}%;background:linear-gradient(90deg,#00695c,#26a69a)`"
                  />
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ═══ Recent Orders Table ══════════════════════════ -->
        <VCol cols="12">
          <VCard rounded="lg" elevation="0">
            <VCardItem class="py-4 px-5">
              <VCardTitle class="font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-list-check-3" color="primary" size="20" />
                آخر ١٠ طلبات
              </VCardTitle>
              <template #append>
                <VBtn variant="tonal" color="primary" size="small" to="/orders" prepend-icon="ri-eye-line">
                  عرض الكل
                </VBtn>
              </template>
            </VCardItem>
            <VDivider />
            <div class="table-scroll">
              <VTable density="comfortable" class="text-no-wrap">
                <thead>
                  <tr class="table-head-row">
                    <th>رمز الفاتورة</th>
                    <th>الزبون</th>
                    <th>الهاتف</th>
                    <th class="text-end">المبلغ</th>
                    <th class="text-center">الحالة</th>
                    <th>التاريخ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="order in recentOrders"
                    :key="order.id"
                    class="order-row"
                    @click="router.push('/orders')"
                  >
                    <td>
                      <span class="invoice-code">{{ order.invoice_code }}</span>
                    </td>
                    <td class="font-weight-medium">{{ order.customer_name }}</td>
                    <td class="text-medium-emphasis" dir="ltr">{{ order.customer_phone }}</td>
                    <td class="text-end font-weight-bold text-success">{{ formatIQD(order.final_amount) }}</td>
                    <td class="text-center">
                      <VChip :color="chipColor(order.status)" size="small" label rounded="md">
                        {{ chipLabel(order.status) }}
                      </VChip>
                    </td>
                    <td class="text-medium-emphasis">{{ formatDate(order.created_at) }}</td>
                  </tr>
                  <tr v-if="!recentOrders.length">
                    <td colspan="6" class="text-center text-medium-emphasis pa-6">لا توجد طلبات بعد</td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VCard>
        </VCol>

      </VRow>
    </template>
  </div>
</template>

<style scoped>
/* ── Welcome Banner ───────────────────────────── */
.welcome-banner {
  position: relative;
  background: linear-gradient(130deg, #0d47a1 0%, #1565c0 55%, #1b7a3e 100%);
  border-radius: 16px;
  padding: 22px 24px;
  overflow: hidden;
  margin-bottom: 4px;
  min-height: 90px;
}
.wb-deco {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
  pointer-events: none;
}
.wb-deco-1 { width:220px;height:220px;top:-70px;right:-30px; }
.wb-deco-2 { width:120px;height:120px;bottom:-30px;left:60px; }
.wb-title  { color:#fff;font-size:1.2rem;font-weight:700;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.wb-sub    { color:rgba(255,255,255,.65);font-size:.78rem;margin-top:2px; }
.wb-desc   { color:rgba(255,255,255,.8);font-size:.85rem;margin-top:6px; }
.wb-desc strong { color:#fff; }
.wb-stat-badge {
  background:rgba(255,255,255,.15);
  border-radius:12px;
  padding:10px 18px;
  text-align:center;
  backdrop-filter:blur(8px);
  white-space:nowrap;
}
.wb-stat-num { display:block;color:#fff;font-size:1.8rem;font-weight:700;line-height:1; }
.wb-stat-lbl { color:rgba(255,255,255,.7);font-size:.75rem; }

/* ── Stat Cards ───────────────────────────────── */
.stat-card   { border:1px solid rgba(0,0,0,.06); }
.stat-icon-wrap {
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.stat-num { font-size:1.5rem;font-weight:700;color:#1a237e;line-height:1.1;word-break:break-all; }
.stat-lbl { font-size:.75rem;color:#78909c;margin-top:3px; }
.stat-sub { font-size:.68rem;color:#90a4ae;background:#f5f5f5;padding:2px 7px;border-radius:99px;white-space:nowrap; }

/* ── Status Cards ─────────────────────────────── */
.status-card {
  cursor:pointer;
  border:1px solid rgba(0,0,0,.06);
  transition:transform .15s,box-shadow .15s;
}
.status-card:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1) !important; }
.status-icon-wrap {
  width:44px;height:44px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
}
.status-count { font-size:1.5rem;font-weight:700;line-height:1.1; }
.status-lbl   { font-size:.7rem;color:#78909c;margin-top:4px;line-height:1.3; }

/* ── Chart Cards ──────────────────────────────── */
.chart-card { border: 1px solid rgba(0,0,0,.06); overflow: hidden; }

.chart-header {
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.chart-header-green  { background: linear-gradient(135deg,#2e7d32,#43a047); }
.chart-header-blue   { background: linear-gradient(135deg,#1565c0,#1976d2); }
.chart-header-indigo { background: linear-gradient(135deg,#283593,#3949ab); }
.chart-header-orange { background: linear-gradient(135deg,#e65100,#fb8c00); }
.chart-header-teal   { background: linear-gradient(135deg,#00695c,#00897b); }

.chart-icon-box {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.chart-header-title { color:#fff; font-size:.95rem; font-weight:700; line-height:1.2; }
.chart-header-sub   { color:rgba(255,255,255,.7); font-size:.72rem; margin-top:2px; }

.chart-kpi-box {
  background: rgba(255,255,255,.18);
  border-radius: 10px;
  padding: 8px 14px;
  text-align: center;
  backdrop-filter: blur(4px);
  white-space: nowrap;
}
.chart-kpi-num { color:#fff; font-size:1rem; font-weight:700; line-height:1.1; }
.chart-kpi-lbl { color:rgba(255,255,255,.75); font-size:.68rem; margin-top:1px; }

.empty-chart-state {
  text-align: center;
  padding: 32px 16px;
  color: #aaa;
}

/* ── Top Lists ────────────────────────────────── */
.top-row        { transition: background .15s; }
.top-row:hover  { background: rgba(0,0,0,.025); }
.top-row-border { border-bottom: 1px solid rgba(0,0,0,.06); }

.rank-badge {
  width: 26px; height: 26px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 700; flex-shrink: 0;
}
.rank-1 { background: #fff3cd; color: #b8860b; }
.rank-2 { background: #e8e8e8; color: #555; }
.rank-3 { background: #ffe0cc; color: #a0522d; }
.rank-4, .rank-5 { background: #f5f5f5; color: #78909c; }

.top-qty {
  font-size: .78rem; font-weight: 600; color: #455a64;
  white-space: nowrap; min-width: 60px; text-align: end;
}
.top-bar-wrap {
  width: 60px; height: 6px; background: #f0f0f0;
  border-radius: 3px; overflow: hidden; flex-shrink: 0;
}
.top-bar { height: 100%; border-radius: 3px; transition: width .4s; }

/* ── Table ─────────────────────────────────────── */
.table-scroll    { overflow-x:auto; }
.table-head-row th { background:#f8fafc;font-size:.82rem;font-weight:600;color:#455a64; }
.order-row       { cursor:pointer;transition:background .15s; }
.order-row:hover { background:rgba(13,71,161,.03); }
.invoice-code    { font-weight:700;color:#1565c0;font-family:monospace; }
</style>
