<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { computed, onMounted, ref } from 'vue'

interface CreditTransaction {
  id: number
  type: 'otp' | 'notification'
  quantity: number
  price_iqd: number
  note: string | null
  created_at: string
}

// ── State ────────────────────────────────────────────────
const loading      = ref(true)
const saving       = ref(false)
const transactions = ref<CreditTransaction[]>([])
const balances     = ref({ otp: 0, notification: 0 })
const currentPage  = ref(1)
const lastPage     = ref(1)
const total        = ref(0)
const filterType   = ref('')
const successMsg   = ref('')
const errorMsg     = ref('')

const form = ref({
  type:      'otp' as 'otp' | 'notification',
  quantity:  null as number | null,
  price_iqd: null as number | null,
  note:      '',
})

// ── API ──────────────────────────────────────────────────
const load = async (page = 1) => {
  loading.value = true
  try {
    let url = `/api/admin/credits?page=${page}`
    if (filterType.value) url += `&type=${filterType.value}`
    const res  = await apiFetch(url)
    const data = await res.json()
    transactions.value = data.data         ?? []
    balances.value     = data.balances     ?? { otp: 0, notification: 0 }
    currentPage.value  = data.current_page ?? 1
    lastPage.value     = data.last_page    ?? 1
    total.value        = data.total        ?? 0
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const addCredits = async () => {
  if (!form.value.quantity || form.value.quantity <= 0) return
  saving.value     = true
  successMsg.value = ''
  errorMsg.value   = ''
  try {
    const res  = await apiFetch('/api/admin/credits', {
      method: 'POST',
      body: JSON.stringify({
        type:      form.value.type,
        quantity:  form.value.quantity,
        price_iqd: form.value.price_iqd ?? 0,
        note:      form.value.note,
      }),
    })
    const data = await res.json()
    if (res.ok) {
      successMsg.value = data.message
      setTimeout(() => successMsg.value = '', 4000)
      balances.value[form.value.type] = data.new_balance
      form.value = { type: 'otp', quantity: null, price_iqd: null, note: '' }
      load(1)
    } else {
      errorMsg.value = data.message || 'حدث خطأ'
    }
  } catch (e) { errorMsg.value = 'خطأ في الاتصال' } finally { saving.value = false }
}

// ── Helpers ──────────────────────────────────────────────
const formatDate = (d: string) =>
  new Date(d).toLocaleString('ar-IQ', { dateStyle: 'medium', timeStyle: 'short' })

const formatIQD = (n: number) =>
  new Intl.NumberFormat('ar-IQ', { style: 'currency', currency: 'IQD', maximumFractionDigits: 0 }).format(n)

const typeLabel   = (t: string) => t === 'otp' ? 'رسائل OTP' : 'إشعارات'
const typeColor   = (t: string) => t === 'otp' ? 'success' : 'primary'
const typeIcon    = (t: string) => t === 'otp' ? 'ri-message-3-line' : 'ri-notification-badge-line'

const otpStatus   = computed(() => balances.value.otp > 0
  ? { text: 'الخدمة تعمل', color: '#2e7d32', bg: 'rgba(46,125,50,0.1)' }
  : { text: 'الخدمة متوقفة', color: '#c62828', bg: 'rgba(198,40,40,0.1)' })

const notifStatus = computed(() => balances.value.notification > 0
  ? { text: 'الخدمة تعمل', color: '#1565c0', bg: 'rgba(21,101,192,0.1)' }
  : { text: 'الإرسال موقف', color: '#c62828', bg: 'rgba(198,40,40,0.1)' })

// ── Print Invoice ─────────────────────────────────────────
const printInvoice = (tx: CreditTransaction) => {
  const win = window.open('', '_blank', 'width=460,height=600')
  if (!win) return
  win.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar">
<head>
  <meta charset="UTF-8">
  <title>فاتورة #${tx.id}</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:#fff;color:#222;direction:rtl;padding:40px 36px;}
    .header{text-align:center;margin-bottom:24px;}
    .header h1{font-size:20px;font-weight:800;color:#1a237e;letter-spacing:.5px;}
    .header p{font-size:11px;color:#888;margin-top:4px;}
    .badge{display:inline-block;background:#e8eaf6;color:#1a237e;border-radius:20px;padding:3px 14px;font-size:12px;font-weight:700;margin-top:8px;}
    .divider{border:none;border-top:2px dashed #ddd;margin:18px 0;}
    .row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;}
    .row:last-child{border-bottom:none;}
    .label{color:#888;font-size:13px;}
    .value{font-weight:600;text-align:left;}
    .total{background:#f5f5f5;border-radius:8px;padding:14px;margin-top:12px;}
    .total .label{font-size:14px;color:#555;}
    .total .value{font-size:18px;color:#1a237e;font-weight:800;}
    .footer{text-align:center;margin-top:28px;font-size:11px;color:#bbb;}
    .type-chip{display:inline-flex;align-items:center;gap:4px;background:${tx.type==='otp'?'#e8f5e9':'#e3f0ff'};color:${tx.type==='otp'?'#2e7d32':'#1565c0'};border-radius:20px;padding:2px 12px;font-size:12px;font-weight:700;}
    @media print{body{padding:24px;}}
  </style>
</head>
<body>
  <div class="header">
    <h1>لوحة التحكم الإدارية</h1>
    <p>سجل إضافة رصيد</p>
    <div class="badge">فاتورة رقم #${String(tx.id).padStart(5,'0')}</div>
  </div>
  <hr class="divider">
  <div class="row"><span class="label">نوع الرصيد</span><span class="value"><span class="type-chip">${typeLabel(tx.type)}</span></span></div>
  <div class="row"><span class="label">الكمية المضافة</span><span class="value">${tx.quantity.toLocaleString('ar-IQ')} وحدة</span></div>
  <div class="row"><span class="label">التاريخ والوقت</span><span class="value">${formatDate(tx.created_at)}</span></div>
  ${tx.note ? `<div class="row"><span class="label">ملاحظة</span><span class="value">${tx.note}</span></div>` : ''}
  <hr class="divider">
  <div class="total"><div class="row" style="border:none;padding:0;"><span class="label">المبلغ الإجمالي</span><span class="value">${formatIQD(tx.price_iqd)}</span></div></div>
  <div class="footer">شكراً لتعاملكم معنا</div>
  <script>window.onload=()=>window.print()<\/script>
</body></html>`)
  win.document.close()
}

onMounted(() => load(1))
</script>

<template>
  <VRow>

    <!-- ═══ Page Title ═══════════════════════════════════ -->
    <VCol cols="12">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-1">
        <div>
          <h2 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-coins-line" color="primary" size="28" />
            إدارة الرصيد
          </h2>
          <p class="text-body-2 text-medium-emphasis mt-1">إضافة رصيد OTP والإشعارات — سجل الفواتير</p>
        </div>
        <VChip color="primary" variant="tonal" prepend-icon="ri-file-list-3-line">
          إجمالي الفواتير: {{ total.toLocaleString('ar-IQ') }}
        </VChip>
      </div>
    </VCol>

    <!-- ═══ Balance Cards ════════════════════════════════ -->
    <VCol cols="12" sm="6">
      <VCard rounded="xl" elevation="0" class="overflow-hidden" style="border:1px solid #e0f2e9;">
        <VCardText class="pa-0">
          <div class="d-flex" style="min-height:120px;">
            <!-- Icon Side -->
            <div class="d-flex align-center justify-center flex-shrink-0"
                 style="width:90px;background:linear-gradient(160deg,#2e7d32,#43a047);">
              <VIcon icon="ri-message-3-line" color="white" size="36" />
            </div>
            <!-- Content -->
            <div class="pa-5 flex-grow-1">
              <div class="text-caption text-medium-emphasis mb-1">رصيد رسائل OTP</div>
              <div class="font-weight-black mb-2"
                   :style="`font-size:2.2rem;color:${balances.otp===0?'#c62828':'#1b1b1b'};line-height:1`">
                {{ balances.otp.toLocaleString('ar-IQ') }}
              </div>
              <VChip
                size="x-small"
                :style="`background:${otpStatus.color}18;color:${otpStatus.color};font-weight:700;`"
              >
                <VIcon :icon="balances.otp > 0 ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill'" size="12" class="me-1" />
                {{ otpStatus.text }}
              </VChip>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6">
      <VCard rounded="xl" elevation="0" class="overflow-hidden" style="border:1px solid #dce8fa;">
        <VCardText class="pa-0">
          <div class="d-flex" style="min-height:120px;">
            <div class="d-flex align-center justify-center flex-shrink-0"
                 style="width:90px;background:linear-gradient(160deg,#1565c0,#1976d2);">
              <VIcon icon="ri-notification-badge-line" color="white" size="36" />
            </div>
            <div class="pa-5 flex-grow-1">
              <div class="text-caption text-medium-emphasis mb-1">رصيد الإشعارات</div>
              <div class="font-weight-black mb-2"
                   :style="`font-size:2.2rem;color:${balances.notification===0?'#c62828':'#1b1b1b'};line-height:1`">
                {{ balances.notification.toLocaleString('ar-IQ') }}
              </div>
              <VChip
                size="x-small"
                :style="`background:${notifStatus.color}18;color:${notifStatus.color};font-weight:700;`"
              >
                <VIcon :icon="balances.notification > 0 ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill'" size="12" class="me-1" />
                {{ notifStatus.text }}
              </VChip>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ═══ Add Form + Table ══════════════════════════════ -->
    <VCol cols="12" md="4" lg="4">
      <VCard rounded="xl" elevation="0" style="border:1px solid #ebebeb;">
        <!-- Card Header -->
        <div class="pa-5 pb-3 d-flex align-center gap-2">
          <div class="rounded-lg d-flex align-center justify-center"
               style="width:36px;height:36px;background:#ede7f6;">
            <VIcon icon="ri-add-circle-fill" color="deep-purple" size="20" />
          </div>
          <div>
            <div class="font-weight-bold text-body-1">إضافة رصيد جديد</div>
            <div class="text-caption text-medium-emphasis">إنشاء فاتورة وتحديث الرصيد</div>
          </div>
        </div>
        <VDivider />

        <VCardText class="pa-5">
          <VAlert v-if="successMsg" type="success" variant="tonal" density="compact" class="mb-4" closable rounded="lg">
            {{ successMsg }}
          </VAlert>
          <VAlert v-if="errorMsg" type="error" variant="tonal" density="compact" class="mb-4" closable rounded="lg">
            {{ errorMsg }}
          </VAlert>

          <VForm @submit.prevent="addCredits">
            <!-- Type Toggle -->
            <div class="mb-5">
              <div class="text-caption font-weight-bold mb-2 text-medium-emphasis">نوع الرصيد</div>
              <VBtnToggle
                v-model="form.type"
                mandatory
                color="primary"
                rounded="lg"
                class="w-100"
                style="border:1px solid #e0e0e0;"
              >
                <VBtn value="otp" style="flex:1;font-size:13px;" prepend-icon="ri-message-3-line">
                  رسائل OTP
                </VBtn>
                <VBtn value="notification" style="flex:1;font-size:13px;" prepend-icon="ri-notification-badge-line">
                  إشعارات
                </VBtn>
              </VBtnToggle>
            </div>

            <!-- Quantity -->
            <VTextField
              v-model.number="form.quantity"
              label="الكمية"
              type="number"
              min="1"
              max="100000"
              variant="outlined"
              density="comfortable"
              class="mb-4"
              rounded="lg"
              prepend-inner-icon="ri-stack-line"
              :placeholder="form.type === 'otp' ? 'عدد رسائل OTP' : 'عدد الإشعارات'"
            />

            <!-- Price -->
            <VTextField
              v-model.number="form.price_iqd"
              label="السعر"
              type="number"
              min="0"
              variant="outlined"
              density="comfortable"
              class="mb-4"
              rounded="lg"
              prepend-inner-icon="ri-money-dollar-circle-line"
              suffix="د.ع"
              dir="ltr"
              placeholder="0"
            />

            <!-- Note -->
            <VTextField
              v-model="form.note"
              label="ملاحظة"
              variant="outlined"
              density="comfortable"
              class="mb-5"
              rounded="lg"
              prepend-inner-icon="ri-sticky-note-line"
              placeholder="مثال: رصيد شهر مايو (اختياري)"
            />

            <VBtn
              type="submit"
              color="primary"
              variant="elevated"
              :loading="saving"
              :disabled="!form.quantity || form.quantity <= 0"
              block
              size="large"
              rounded="lg"
              prepend-icon="ri-save-line"
              elevation="2"
            >
              إضافة الرصيد وحفظ الفاتورة
            </VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ═══ Invoices Table ════════════════════════════════ -->
    <VCol cols="12" md="8" lg="8">
      <VCard rounded="xl" elevation="0" style="border:1px solid #ebebeb;">
        <!-- Table Header -->
        <div class="pa-5 pb-3 d-flex align-center justify-space-between flex-wrap gap-3">
          <div class="d-flex align-center gap-2">
            <div class="rounded-lg d-flex align-center justify-center"
                 style="width:36px;height:36px;background:#e8eaf6;">
              <VIcon icon="ri-file-list-3-line" color="primary" size="20" />
            </div>
            <div>
              <div class="font-weight-bold text-body-1">سجل الفواتير</div>
              <div class="text-caption text-medium-emphasis">
                صفحة {{ currentPage }} من {{ lastPage }}
                &nbsp;·&nbsp;
                {{ total.toLocaleString('ar-IQ') }} فاتورة
              </div>
            </div>
          </div>
          <VSelect
            v-model="filterType"
            :items="[
              { title: 'جميع الأنواع', value: '' },
              { title: 'رسائل OTP',     value: 'otp' },
              { title: 'الإشعارات',     value: 'notification' },
            ]"
            item-title="title"
            item-value="value"
            variant="outlined"
            density="compact"
            hide-details
            rounded="lg"
            style="max-width:160px;"
            @update:modelValue="load(1)"
          />
        </div>
        <VDivider />

        <!-- Loading -->
        <div v-if="loading" class="text-center py-14">
          <VProgressCircular indeterminate color="primary" size="40" />
          <div class="text-caption text-medium-emphasis mt-3">جاري التحميل...</div>
        </div>

        <template v-else>
          <!-- Empty -->
          <div v-if="transactions.length === 0" class="text-center py-14">
            <VIcon icon="ri-file-list-line" size="56" color="grey-lighten-1" />
            <div class="text-h6 font-weight-bold mt-3 text-medium-emphasis">لا توجد فواتير بعد</div>
            <div class="text-caption text-medium-emphasis mt-1">أضف رصيداً لتظهر هنا الفواتير</div>
          </div>

          <!-- Table -->
          <VTable v-else class="text-no-wrap credits-table">
            <thead>
              <tr style="background:#fafafa;">
                <th class="text-center py-3" style="width:56px;color:#888;font-size:12px;">#</th>
                <th class="py-3" style="color:#888;font-size:12px;">النوع</th>
                <th class="text-center py-3" style="color:#888;font-size:12px;">الكمية</th>
                <th class="text-center py-3" style="color:#888;font-size:12px;">السعر</th>
                <th class="py-3" style="color:#888;font-size:12px;">الملاحظة</th>
                <th class="py-3" style="color:#888;font-size:12px;">التاريخ والوقت</th>
                <th class="text-center py-3" style="width:56px;color:#888;font-size:12px;">طباعة</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(tx, i) in transactions"
                :key="tx.id"
                :style="i % 2 === 0 ? 'background:#fff;' : 'background:#fafbff;'"
                class="credit-row"
              >
                <td class="text-center">
                  <span class="text-caption font-weight-bold text-medium-emphasis">
                    #{{ String(tx.id).padStart(4, '0') }}
                  </span>
                </td>
                <td>
                  <VChip
                    :color="typeColor(tx.type)"
                    size="small"
                    variant="tonal"
                    rounded="lg"
                    :prepend-icon="typeIcon(tx.type)"
                    class="font-weight-medium"
                  >
                    {{ typeLabel(tx.type) }}
                  </VChip>
                </td>
                <td class="text-center">
                  <span class="font-weight-bold text-body-2">
                    {{ tx.quantity.toLocaleString('ar-IQ') }}
                  </span>
                </td>
                <td class="text-center">
                  <span
                    class="font-weight-bold text-body-2"
                    :style="tx.price_iqd > 0 ? 'color:#2e7d32;' : 'color:#aaa;'"
                    dir="ltr"
                  >
                    {{ tx.price_iqd > 0 ? formatIQD(tx.price_iqd) : '—' }}
                  </span>
                </td>
                <td>
                  <span
                    class="text-caption"
                    :class="tx.note ? '' : 'text-medium-emphasis'"
                    style="max-width:160px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                  >
                    {{ tx.note || '—' }}
                  </span>
                </td>
                <td>
                  <div class="text-caption">{{ formatDate(tx.created_at) }}</div>
                </td>
                <td class="text-center">
                  <VBtn
                    icon
                    size="small"
                    variant="tonal"
                    color="primary"
                    rounded="lg"
                    @click="printInvoice(tx)"
                  >
                    <VIcon icon="ri-printer-line" size="16" />
                    <VTooltip activator="parent" location="top">طباعة الفاتورة</VTooltip>
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>

          <!-- Pagination -->
          <div v-if="lastPage > 1" class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
            <span class="text-caption text-medium-emphasis">
              عرض {{ (currentPage - 1) * 10 + 1 }}–{{ Math.min(currentPage * 10, total) }} من {{ total.toLocaleString('ar-IQ') }}
            </span>
            <VPagination
              v-model="currentPage"
              :length="lastPage"
              :total-visible="5"
              density="compact"
              rounded="lg"
              @update:modelValue="load($event)"
            />
          </div>
        </template>
      </VCard>
    </VCol>

  </VRow>
</template>

<style scoped>
.credit-row {
  transition: background 0.15s;
}
.credit-row:hover {
  background: #f0f4ff !important;
}
</style>
