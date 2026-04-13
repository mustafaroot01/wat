<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { onMounted, ref } from 'vue'

interface CreditTransaction {
  id: number
  type: 'otp' | 'notification'
  quantity: number
  price_iqd: number
  note: string | null
  created_at: string
}

// ── State ────────────────────────────────────────────────
const loading     = ref(true)
const saving      = ref(false)
const transactions = ref<CreditTransaction[]>([])
const balances    = ref({ otp: 0, notification: 0 })
const currentPage = ref(1)
const lastPage    = ref(1)
const total       = ref(0)
const filterType  = ref('')
const successMsg  = ref('')
const errorMsg    = ref('')

const form = ref({
  type:      'otp' as 'otp' | 'notification',
  quantity:  0,
  price_iqd: 0,
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
    transactions.value = data.data        ?? []
    balances.value     = data.balances    ?? { otp: 0, notification: 0 }
    currentPage.value  = data.current_page ?? 1
    lastPage.value     = data.last_page    ?? 1
    total.value        = data.total        ?? 0
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const addCredits = async () => {
  if (!form.value.quantity || form.value.quantity <= 0) return
  saving.value  = true
  successMsg.value = ''
  errorMsg.value   = ''
  try {
    const res  = await apiFetch('/api/admin/credits', {
      method: 'POST',
      body: JSON.stringify(form.value),
    })
    const data = await res.json()
    if (res.ok) {
      successMsg.value = data.message
      setTimeout(() => successMsg.value = '', 4000)
      balances.value[form.value.type] = data.new_balance
      form.value = { type: 'otp', quantity: 0, price_iqd: 0, note: '' }
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

const typeLabel = (t: string) => t === 'otp' ? 'رسائل OTP' : 'إشعارات'
const typeColor = (t: string) => t === 'otp' ? 'success' : 'primary'
const typeIcon  = (t: string) => t === 'otp' ? 'ri-message-3-line' : 'ri-notification-badge-line'

// ── Print Invoice ─────────────────────────────────────────
const printInvoice = (tx: CreditTransaction) => {
  const win = window.open('', '_blank', 'width=420,height=560')
  if (!win) return
  win.document.write(`
    <!DOCTYPE html><html dir="rtl" lang="ar">
    <head>
      <meta charset="UTF-8">
      <title>فاتورة رصيد #${tx.id}</title>
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 32px; color: #222; direction: rtl; }
        .logo { text-align:center; margin-bottom:20px; }
        .logo h1 { font-size:22px; font-weight:700; color:#1a237e; }
        .logo p  { font-size:12px; color:#666; margin-top:2px; }
        .divider { border:none; border-top:2px dashed #bbb; margin:16px 0; }
        .title { text-align:center; font-size:16px; font-weight:700; margin-bottom:18px; color:#333; }
        table { width:100%; border-collapse:collapse; }
        td { padding:9px 6px; font-size:14px; border-bottom:1px solid #eee; }
        td:first-child { color:#888; width:40%; }
        td:last-child  { font-weight:600; text-align:left; }
        .total-row td  { font-size:16px; color:#1a237e; border-bottom:none; padding-top:14px; }
        .footer { text-align:center; margin-top:28px; font-size:11px; color:#aaa; }
        @media print { body { padding:16px; } }
      </style>
    </head>
    <body>
      <div class="logo">
        <h1>لوحة التحكم</h1>
        <p>فاتورة إضافة رصيد</p>
      </div>
      <hr class="divider">
      <div class="title">فاتورة رقم #${tx.id}</div>
      <table>
        <tr><td>النوع</td><td>${typeLabel(tx.type)}</td></tr>
        <tr><td>الكمية المضافة</td><td>${tx.quantity.toLocaleString('ar-IQ')} وحدة</td></tr>
        <tr><td>التاريخ والوقت</td><td>${formatDate(tx.created_at)}</td></tr>
        ${tx.note ? `<tr><td>ملاحظة</td><td>${tx.note}</td></tr>` : ''}
        <tr class="total-row"><td>السعر الإجمالي</td><td>${formatIQD(tx.price_iqd)}</td></tr>
      </table>
      <hr class="divider">
      <div class="footer">شكراً لتعاملكم معنا</div>
      <script>window.onload = () => { window.print(); }<\/script>
    </body></html>
  `)
  win.document.close()
}

onMounted(() => load(1))
</script>

<template>
  <VRow>
    <!-- ═══ Header ════════════════════════════════════════ -->
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-coins-line" color="primary" size="26" />
            إدارة الرصيد
          </VCardTitle>
          <VCardSubtitle>إضافة رصيد OTP والإشعارات وعرض سجل الفواتير.</VCardSubtitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- ═══ Balance Cards ════════════════════════════════ -->
    <VCol cols="12" sm="6">
      <VCard rounded="lg" elevation="0" style="background:linear-gradient(135deg,#e8f5e9,#f1f8e9);">
        <VCardText class="pa-6">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis mb-1">رصيد رسائل OTP الحالي</div>
              <div class="text-h3 font-weight-black" :style="balances.otp === 0 ? 'color:#c62828' : 'color:#2e7d32'">
                {{ balances.otp.toLocaleString('ar-IQ') }}
              </div>
              <div class="text-body-2 mt-1" :style="balances.otp === 0 ? 'color:#c62828' : 'color:#388e3c'">
                {{ balances.otp > 0 ? 'الخدمة تعمل' : 'الخدمة متوقفة ⛔' }}
              </div>
            </div>
            <div class="rounded-circle d-flex align-center justify-center" style="width:72px;height:72px;background:rgba(46,125,50,0.12);">
              <VIcon icon="ri-message-3-line" color="success" size="38" />
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6">
      <VCard rounded="lg" elevation="0" style="background:linear-gradient(135deg,#e3f0ff,#ede7f6);">
        <VCardText class="pa-6">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis mb-1">رصيد الإشعارات الحالي</div>
              <div class="text-h3 font-weight-black" :style="balances.notification === 0 ? 'color:#c62828' : 'color:#1565c0'">
                {{ balances.notification.toLocaleString('ar-IQ') }}
              </div>
              <div class="text-body-2 mt-1" :style="balances.notification === 0 ? 'color:#c62828' : 'color:#1976d2'">
                {{ balances.notification > 0 ? 'الخدمة تعمل' : 'الإرسال موقف ⛔' }}
              </div>
            </div>
            <div class="rounded-circle d-flex align-center justify-center" style="width:72px;height:72px;background:rgba(21,101,192,0.12);">
              <VIcon icon="ri-notification-badge-line" color="primary" size="38" />
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ═══ Add Credits Form ══════════════════════════════ -->
    <VCol cols="12" md="5">
      <VCard rounded="lg" elevation="0" style="height:100%;">
        <VCardItem class="pa-5 pb-0">
          <VCardTitle class="d-flex align-center gap-2 text-body-1 font-weight-bold">
            <VIcon icon="ri-add-circle-line" color="primary" size="20" />
            إضافة رصيد جديد
          </VCardTitle>
        </VCardItem>
        <VCardText class="pa-5">
          <VAlert v-if="successMsg" type="success" variant="tonal" density="compact" class="mb-4" closable>{{ successMsg }}</VAlert>
          <VAlert v-if="errorMsg"   type="error"   variant="tonal" density="compact" class="mb-4" closable>{{ errorMsg }}</VAlert>

          <VForm @submit.prevent="addCredits">
            <div class="mb-4">
              <div class="text-caption mb-2 font-weight-medium">نوع الرصيد</div>
              <VBtnToggle v-model="form.type" mandatory density="compact" color="primary" class="w-100" rounded="lg">
                <VBtn value="otp" style="flex:1;" prepend-icon="ri-message-3-line">رسائل OTP</VBtn>
                <VBtn value="notification" style="flex:1;" prepend-icon="ri-notification-badge-line">إشعارات</VBtn>
              </VBtnToggle>
            </div>

            <VTextField
              v-model.number="form.quantity"
              label="الكمية المضافة"
              type="number"
              min="1"
              max="100000"
              variant="outlined"
              density="compact"
              class="mb-3"
              prepend-inner-icon="ri-stack-line"
              :hint="form.type === 'otp' ? 'عدد رسائل OTP' : 'عدد الإشعارات'"
              persistent-hint
            />

            <VTextField
              v-model.number="form.price_iqd"
              label="السعر (دينار عراقي)"
              type="number"
              min="0"
              variant="outlined"
              density="compact"
              class="mb-3"
              prepend-inner-icon="ri-money-dollar-circle-line"
              suffix="د.ع"
              dir="ltr"
            />

            <VTextField
              v-model="form.note"
              label="ملاحظة (اختياري)"
              variant="outlined"
              density="compact"
              class="mb-4"
              prepend-inner-icon="ri-sticky-note-line"
              placeholder="مثال: رصيد شهر أبريل"
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
              prepend-icon="ri-add-circle-fill"
            >
              إضافة الرصيد وحفظ الفاتورة
            </VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ═══ Transactions Table ════════════════════════════ -->
    <VCol cols="12" md="7">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5 pb-0">
          <VCardTitle class="d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2 text-body-1 font-weight-bold">
              <VIcon icon="ri-file-list-3-line" color="primary" size="20" />
              سجل الفواتير
              <VChip size="x-small" color="primary" variant="tonal">{{ total.toLocaleString('ar-IQ') }} سجل</VChip>
            </div>
            <VSelect
              v-model="filterType"
              :items="[{ title: 'الكل', value: '' }, { title: 'رسائل OTP', value: 'otp' }, { title: 'إشعارات', value: 'notification' }]"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width:150px;"
              @update:modelValue="load(1)"
            />
          </VCardTitle>
        </VCardItem>

        <VCardText class="pa-0">
          <div v-if="loading" class="text-center py-10">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <VTable v-else class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center" style="width:48px;">#</th>
                <th>النوع</th>
                <th class="text-center">الكمية</th>
                <th class="text-center">السعر</th>
                <th>الملاحظة</th>
                <th>التاريخ</th>
                <th class="text-center">فاتورة</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="transactions.length === 0">
                <td colspan="7" class="text-center py-10 text-medium-emphasis">
                  <VIcon icon="ri-inbox-line" size="40" class="mb-2 d-block mx-auto" />
                  لا توجد سجلات بعد
                </td>
              </tr>
              <tr v-for="tx in transactions" :key="tx.id">
                <td class="text-center text-caption text-medium-emphasis">{{ tx.id }}</td>
                <td>
                  <VChip :color="typeColor(tx.type)" size="small" variant="tonal" :prepend-icon="typeIcon(tx.type)">
                    {{ typeLabel(tx.type) }}
                  </VChip>
                </td>
                <td class="text-center font-weight-bold">{{ tx.quantity.toLocaleString('ar-IQ') }}</td>
                <td class="text-center font-weight-medium" dir="ltr">{{ formatIQD(tx.price_iqd) }}</td>
                <td class="text-caption text-medium-emphasis" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;">
                  {{ tx.note || '—' }}
                </td>
                <td class="text-caption">{{ formatDate(tx.created_at) }}</td>
                <td class="text-center">
                  <VBtn icon size="small" variant="text" color="primary" @click="printInvoice(tx)">
                    <VIcon icon="ri-printer-line" size="18" />
                    <VTooltip activator="parent" location="top">طباعة الفاتورة</VTooltip>
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>

          <!-- Pagination -->
          <div v-if="lastPage > 1" class="d-flex justify-center pa-4">
            <VPagination
              v-model="currentPage"
              :length="lastPage"
              :total-visible="7"
              density="compact"
              rounded="circle"
              @update:modelValue="load($event)"
            />
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>
