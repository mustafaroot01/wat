<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'

const router = useRouter()

// ── Data ────────────────────────────────────────────────
const loading      = ref(true)
const statusCounts = ref<Record<string, number>>({})
const orders       = ref({ today: 0, week: 0, month: 0, total: 0 })
const revenueMonth = ref(0)
const users        = ref({ total: 0, today: 0 })
const recentOrders = ref<any[]>([])
const topProducts  = ref<any[]>([])
const topDistricts = ref<any[]>([])

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
  } finally {
    loading.value = false
  }
}

onMounted(load)

const goToOrders = (status: string) =>
  router.push({ path: '/orders', query: { status } })
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
            <div class="d-flex align-center justify-space-between position-relative" style="z-index:1;">
              <div>
                <div class="d-flex align-center gap-3 mb-2">
                  <img src="/logo.png" style="width:52px;height:52px;object-fit:contain;" />
                  <div>
                    <h2 class="wb-title">معمل امواج ديالى</h2>
                    <p class="wb-sub">لوحة التحكم الإدارية</p>
                  </div>
                </div>
                <p class="wb-desc">إجمالي الطلبات الكلي: <strong>{{ orders.total }}</strong> طلب</p>
              </div>
              <div class="d-none d-sm-block text-end">
                <div class="wb-stat-badge">
                  <span class="wb-stat-num">{{ orders.today }}</span>
                  <span class="wb-stat-lbl">طلب اليوم</span>
                </div>
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

        <!-- ═══ Top Products + Top Districts ════════════════ -->
        <VCol cols="12" md="6">
          <VCard rounded="lg" elevation="0">
            <VCardItem class="py-4 px-5">
              <VCardTitle class="font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-fire-line" color="error" size="20" />
                أكثر المنتجات طلباً
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-0">
              <div v-if="!topProducts.length" class="text-center text-medium-emphasis pa-6">لا توجد بيانات بعد</div>
              <div
                v-for="(p, i) in topProducts"
                :key="p.product_name"
                class="top-row d-flex align-center gap-3 px-5 py-3"
                :class="{ 'top-row-border': i < topProducts.length - 1 }"
              >
                <div class="rank-badge" :class="`rank-${i+1}`">{{ i + 1 }}</div>
                <div class="flex-grow-1">
                  <div class="font-weight-medium text-body-2">{{ p.product_name }}</div>
                  <div class="text-caption text-medium-emphasis">{{ p.order_count }} طلب</div>
                </div>
                <div class="top-qty">{{ p.total_qty }} قطعة</div>
                <div class="top-bar-wrap">
                  <div
                    class="top-bar"
                    :style="`width:${Math.round((p.total_qty / (topProducts[0]?.total_qty || 1)) * 100)}%;background:#1565c0`"
                  />
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="6">
          <VCard rounded="lg" elevation="0">
            <VCardItem class="py-4 px-5">
              <VCardTitle class="font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-map-pin-2-line" color="success" size="20" />
                أكثر المناطق طلباً
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-0">
              <div v-if="!topDistricts.length" class="text-center text-medium-emphasis pa-6">لا توجد بيانات بعد</div>
              <div
                v-for="(d, i) in topDistricts"
                :key="d.district"
                class="top-row d-flex align-center gap-3 px-5 py-3"
                :class="{ 'top-row-border': i < topDistricts.length - 1 }"
              >
                <div class="rank-badge" :class="`rank-${i+1}`">{{ i + 1 }}</div>
                <div class="flex-grow-1">
                  <div class="font-weight-medium text-body-2">{{ d.district }}</div>
                </div>
                <div class="top-qty">{{ d.order_count }} طلب</div>
                <div class="top-bar-wrap">
                  <div
                    class="top-bar"
                    :style="`width:${Math.round((d.order_count / (topDistricts[0]?.order_count || 1)) * 100)}%;background:#2e7d32`"
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
  padding: 28px 32px;
  overflow: hidden;
  margin-bottom: 4px;
}
.wb-deco {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
}
.wb-deco-1 { width:260px;height:260px;top:-80px;right:-40px; }
.wb-deco-2 { width:140px;height:140px;bottom:-40px;left:80px; }
.wb-title  { color:#fff;font-size:1.35rem;font-weight:700;line-height:1.2; }
.wb-sub    { color:rgba(255,255,255,.65);font-size:.82rem;margin-top:2px; }
.wb-desc   { color:rgba(255,255,255,.8);font-size:.9rem;margin-top:10px; }
.wb-desc strong { color:#fff; }
.wb-stat-badge {
  background:rgba(255,255,255,.15);
  border-radius:12px;
  padding:12px 20px;
  display:inline-block;
  backdrop-filter:blur(8px);
}
.wb-stat-num { display:block;color:#fff;font-size:2rem;font-weight:700;line-height:1; }
.wb-stat-lbl { color:rgba(255,255,255,.7);font-size:.78rem; }

/* ── Stat Cards ───────────────────────────────── */
.stat-card   { border:1px solid rgba(0,0,0,.06); }
.stat-icon-wrap {
  width:42px;height:42px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
}
.stat-num { font-size:1.6rem;font-weight:700;color:#1a237e;line-height:1.1; }
.stat-lbl { font-size:.78rem;color:#78909c;margin-top:3px; }
.stat-sub { font-size:.7rem;color:#90a4ae;background:#f5f5f5;padding:2px 8px;border-radius:99px; }

/* ── Status Cards ─────────────────────────────── */
.status-card {
  cursor:pointer;
  border:1px solid rgba(0,0,0,.06);
  transition:transform .15s,box-shadow .15s;
}
.status-card:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1) !important; }
.status-icon-wrap {
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
}
.status-count { font-size:1.6rem;font-weight:700;line-height:1.1; }
.status-lbl   { font-size:.72rem;color:#78909c;margin-top:4px; }

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
