<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { onMounted, ref } from 'vue'

const loading = ref(false)
const saving  = ref(false)
const success  = ref('')
const errorMsg = ref('')

const form = ref({
  store_name:            '',
  store_phone:           '',
  store_address:         '',
  thank_you_message:     '',
  minimum_order_amount:  0,
  contact_phone2:        '',
  contact_instagram:     '',
  contact_facebook:      '',
  about_us_description:  '',
  telegram_bot_token:    '',
  telegram_chat_id:      '',
  telegram_enabled:      false,
  always_open:           false,
})

const testingTelegram = ref(false)
const telegramTestResult = ref('')

type DaySchedule = { enabled: boolean; open: string; close: string }
type WeeklySchedule = Record<string, DaySchedule>

const days = [
  { key: 'sunday',    label: 'الأحد' },
  { key: 'monday',    label: 'الاثنين' },
  { key: 'tuesday',   label: 'الثلاثاء' },
  { key: 'wednesday', label: 'الأربعاء' },
  { key: 'thursday',  label: 'الخميس' },
  { key: 'friday',    label: 'الجمعة' },
  { key: 'saturday',  label: 'السبت' },
]

const defaultSchedule = (): WeeklySchedule => Object.fromEntries(
  days.map(d => [d.key, { enabled: true, open: '08:00', close: '22:00' }])
)

const weeklySchedule = ref<WeeklySchedule>(defaultSchedule())

const to12h = (time: string): string => {
  if (!time) return ''
  const [h, m] = time.split(':').map(Number)
  const period = h < 12 ? 'ص' : 'م'
  const hour   = h % 12 || 12
  return `${hour}:${String(m).padStart(2, '0')} ${period}`
}

const logoFile        = ref<File | null>(null)
const logoPreview     = ref<string | null>(null)
const currentLogo     = ref<string | null>(null)
const fileInputRef    = ref<HTMLInputElement | null>(null)

const loadSettings = async () => {
  loading.value = true
  try {
    const res  = await apiFetch('/api/admin/store-settings')
    const data = await res.json()
    form.value.store_name            = data.store_name            ?? ''
    form.value.store_phone           = data.store_phone           ?? ''
    form.value.store_address         = data.store_address         ?? ''
    form.value.thank_you_message     = data.thank_you_message     ?? ''
    form.value.minimum_order_amount  = Number(data.minimum_order_amount ?? 0)
    form.value.contact_phone2        = data.contact_phone2        ?? ''
    form.value.contact_instagram     = data.contact_instagram     ?? ''
    form.value.contact_facebook      = data.contact_facebook      ?? ''
    form.value.about_us_description  = data.about_us_description  ?? ''
    form.value.telegram_bot_token    = data.telegram_bot_token    ?? ''
    form.value.telegram_chat_id      = data.telegram_chat_id      ?? ''
    form.value.telegram_enabled      = Boolean(data.telegram_enabled ?? false)
    form.value.always_open           = Boolean(Number(data.always_open ?? 0))
    currentLogo.value                = data.logo                  ?? null
    if (data.weekly_schedule) {
      try { weeklySchedule.value = { ...defaultSchedule(), ...JSON.parse(data.weekly_schedule) } }
      catch { weeklySchedule.value = defaultSchedule() }
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadSettings)

const onLogoChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  logoFile.value    = file
  logoPreview.value = URL.createObjectURL(file)
}

const removeLogo = () => {
  logoFile.value    = null
  logoPreview.value = null
  currentLogo.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const testTelegramConnection = async () => {
  testingTelegram.value = true
  telegramTestResult.value = ''
  try {
    const res = await apiFetch('/api/admin/store-settings/test-telegram', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        bot_token: form.value.telegram_bot_token,
        chat_id: form.value.telegram_chat_id
      })
    })
    const data = await res.json()
    telegramTestResult.value = data.message ?? (res.ok ? 'تم الاتصال بنجاح!' : 'فشل الاتصال')
  } catch (err) {
    telegramTestResult.value = 'حدث خطأ أثناء الاختبار'
  } finally {
    testingTelegram.value = false
  }
}

const saveSettings = async () => {
  saving.value  = true
  success.value = ''
  errorMsg.value = ''
  try {
    const fd = new FormData()
    fd.append('store_name',           form.value.store_name)
    fd.append('store_phone',          form.value.store_phone)
    fd.append('store_address',        form.value.store_address)
    fd.append('thank_you_message',    form.value.thank_you_message)
    fd.append('minimum_order_amount', String(form.value.minimum_order_amount || 0))
    fd.append('contact_phone2',       form.value.contact_phone2)
    fd.append('contact_instagram',    form.value.contact_instagram)
    fd.append('contact_facebook',     form.value.contact_facebook)
    fd.append('about_us_description', form.value.about_us_description)
    fd.append('telegram_bot_token',   form.value.telegram_bot_token)
    fd.append('telegram_chat_id',     form.value.telegram_chat_id)
    fd.append('telegram_enabled',     form.value.telegram_enabled ? '1' : '0')
    fd.append('always_open',          form.value.always_open ? '1' : '0')
    fd.append('weekly_schedule', JSON.stringify(weeklySchedule.value))
    if (logoFile.value) fd.append('logo', logoFile.value)

    const res  = await apiFetch('/api/admin/store-settings', { method: 'POST', body: fd })
    const data = await res.json()

    if (!res.ok) {
      errorMsg.value = data.message ?? 'حدث خطأ'
      return
    }

    currentLogo.value = data.settings?.logo ?? currentLogo.value
    logoFile.value    = null
    logoPreview.value = null
    success.value     = 'تم حفظ الإعدادات بنجاح ✓'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="loading" class="text-center pa-16">
    <VProgressCircular indeterminate color="primary" size="64" />
    <div class="text-h6 mt-4 text-medium-emphasis">جاري التحميل...</div>
  </div>

  <VRow v-else>
    <!-- Right Column: Main Settings -->
    <VCol cols="12" lg="8">
      <!-- 1. Store Basic Info -->
      <VCard class="mb-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-store-2-line" color="primary" />
          معلومات المتجر الأساسية
        </VCardTitle>
        <VCardText>
          <div class="mb-4">
            <div class="text-subtitle-2 font-weight-bold mb-3">📷 شعار المتجر</div>
            <div v-if="logoPreview || currentLogo" class="mb-3 d-flex align-center gap-3">
              <img
                :src="logoPreview ?? `/media/${currentLogo}`"
                style="max-height:80px;max-width:200px;border:1px solid #eee;border-radius:8px;padding:4px;"
              />
              <VBtn variant="text" color="error" size="small" @click="removeLogo">
                <VIcon size="16" class="me-1">ri-delete-bin-line</VIcon> إزالة
              </VBtn>
            </div>
            <VBtn variant="tonal" color="primary" prepend-icon="ri-upload-2-line" @click="fileInputRef?.click()">
              {{ logoPreview || currentLogo ? 'تغيير الشعار' : 'رفع الشعار' }}
            </VBtn>
            <input ref="fileInputRef" type="file" accept="image/*" style="display:none;" @change="onLogoChange" />
            <div class="text-caption text-medium-emphasis mt-1">PNG, JPG, SVG — بحد أقصى 2MB</div>
          </div>

          <VTextField v-model="form.store_name" label="اسم المتجر" variant="outlined" class="mb-4" prepend-inner-icon="ri-store-2-line" />
          <VTextField v-model="form.store_phone" label="رقم هاتف المتجر" variant="outlined" class="mb-4" prepend-inner-icon="ri-phone-line" dir="ltr" />
          <VTextField v-model="form.store_address" label="عنوان المتجر" variant="outlined" class="mb-4" prepend-inner-icon="ri-map-pin-line" />
          <VTextarea v-model="form.thank_you_message" label="رسالة الشكر (تظهر أسفل الفاتورة)" variant="outlined" rows="2" prepend-inner-icon="ri-chat-heart-line" />
        </VCardText>
      </VCard>

      <!-- 2. Contact Info -->
      <VCard class="mb-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-contacts-line" color="info" />
          معلومات التواصل
        </VCardTitle>
        <VCardText>
          <div class="text-caption text-medium-emphasis mb-4">تظهر هذه المعلومات في صفحة "من نحن" بالتطبيق</div>
          <VTextField v-model="form.contact_phone2" label="رقم هاتف إضافي" variant="outlined" class="mb-4" prepend-inner-icon="ri-phone-line" dir="ltr" placeholder="+9647XXXXXXXXX" />
          <VTextField v-model="form.contact_instagram" label="معرف انستغرام" variant="outlined" class="mb-4" prepend-inner-icon="ri-instagram-line" dir="ltr" placeholder="@amwaj_store" />
          <VTextField v-model="form.contact_facebook" label="رابط صفحة فيس بوك" variant="outlined" class="mb-4" prepend-inner-icon="ri-facebook-line" dir="ltr" placeholder="https://facebook.com/amwajstore" />
          <VTextarea v-model="form.about_us_description" label="نبذة عن المتجر" variant="outlined" rows="3" prepend-inner-icon="ri-information-line" placeholder="اكتب نبذة عن متجرك هنا..." />
        </VCardText>
      </VCard>

      <!-- 3. Order Settings -->
      <VCard class="mb-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-shopping-bag-line" color="warning" />
          إعدادات الطلبات
        </VCardTitle>
        <VCardText>
          <div class="text-subtitle-2 font-weight-bold mb-2">الحد الأدنى لمبلغ الطلب</div>
          <div class="text-caption text-medium-emphasis mb-4">إذا كان المبلغ 0 — لا يوجد حد أدنى</div>
          <VTextField
            v-model.number="form.minimum_order_amount"
            label="أقل مبلغ للطلب (بالدينار العراقي)"
            type="number" min="0" step="500" variant="outlined" color="warning"
            prepend-inner-icon="ri-money-dollar-circle-line"
            :hint="form.minimum_order_amount > 0 ? `الحد الأدنى: ${form.minimum_order_amount.toLocaleString()} د.ع` : 'لا يوجد حد أدنى'"
            persistent-hint
          />
        </VCardText>
      </VCard>

      <!-- 4. Telegram Bot -->
      <VCard class="mb-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-telegram-line" color="#0088cc" />
          🤖 بوت التيليجرام
        </VCardTitle>
        <VCardText>
          <VAlert type="info" variant="tonal" class="mb-4">
            استقبل إشعارات فورية عند ورود طلبات جديدة مباشرة على التيليجرام
          </VAlert>

          <VSwitch v-model="form.telegram_enabled" label="تفعيل الإشعارات" color="success" class="mb-4" hide-details />

          <VTextField
            v-model="form.telegram_bot_token" label="Bot Token" variant="outlined" class="mb-4"
            prepend-inner-icon="ri-key-line" dir="ltr" placeholder="123456:ABC-DEF1234ghIkl..."
            hint="احصل عليه من @BotFather" persistent-hint
          />

          <VTextField
            v-model="form.telegram_chat_id" label="Chat ID / Group ID" variant="outlined" class="mb-4"
            prepend-inner-icon="ri-chat-3-line" dir="ltr" placeholder="-1001234567890"
            hint="استخدم @userinfobot للحصول عليه" persistent-hint
          />

          <div class="d-flex align-center gap-3 flex-wrap">
            <VBtn variant="tonal" color="primary" prepend-icon="ri-send-plane-line"
              :loading="testingTelegram" :disabled="!form.telegram_bot_token || !form.telegram_chat_id"
              @click="testTelegramConnection">
              اختبار الاتصال
            </VBtn>
            <VChip v-if="telegramTestResult" :color="telegramTestResult.includes('نجاح') ? 'success' : 'error'" variant="tonal">
              {{ telegramTestResult }}
            </VChip>
          </div>
        </VCardText>
      </VCard>

      <!-- 5. Weekly Schedule -->
      <VCard class="mb-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-calendar-schedule-line" color="success" />
          جدول الدوام الأسبوعي
        </VCardTitle>
        <VCardText>
          <div class="text-caption text-medium-emphasis mb-4">
            خارج أوقات الدوام يظهر للعميل: <strong>المتجر مغلق حالياً</strong>
          </div>

          <!-- Always Open Toggle -->
          <VAlert :type="form.always_open ? 'success' : 'warning'" variant="tonal" class="mb-4">
            <div class="d-flex align-center justify-space-between flex-wrap gap-2">
              <div>
                <div class="font-weight-bold">
                  {{ form.always_open ? '🟢 المتجر مفتوح دائماً (24/7)' : '⏰ يعمل حسب الجدول الأسبوعي' }}
                </div>
                <div class="text-caption mt-1">
                  {{ form.always_open ? 'المتجر يقبل طلبات طول الوقت بغض النظر عن الجدول' : 'يُفعّل للإيقاف المؤقت للجدول وفتح المتجر دائماً' }}
                </div>
              </div>
              <VSwitch v-model="form.always_open" color="success" hide-details inset label="تفعيل الفتح الدائم" />
            </div>
          </VAlert>

          <div v-for="day in days" :key="day.key" class="day-row mb-2 pa-3 rounded-lg"
            :class="weeklySchedule[day.key]?.enabled ? 'day-open' : 'day-closed'">
            <VRow align="center" no-gutters>
              <VCol cols="4" sm="3" class="d-flex align-center gap-2">
                <VSwitch v-model="weeklySchedule[day.key].enabled" color="success" hide-details density="compact" />
                <span class="font-weight-bold text-body-2">{{ day.label }}</span>
              </VCol>
              <VCol v-if="!weeklySchedule[day.key]?.enabled" cols="8" sm="9">
                <VChip color="error" size="small" variant="tonal">مغلق</VChip>
              </VCol>
              <template v-else>
                <VCol cols="4" sm="4" class="px-1">
                  <VTextField v-model="weeklySchedule[day.key].open" type="time" density="compact" variant="outlined"
                    hide-details color="success" prepend-inner-icon="ri-sun-line" />
                  <div class="text-caption text-center text-success mt-1 font-weight-medium">
                    {{ to12h(weeklySchedule[day.key].open) }}
                  </div>
                </VCol>
                <VCol cols="1" sm="1" class="text-center">
                  <VIcon size="16" color="medium-emphasis">ri-arrow-left-line</VIcon>
                </VCol>
                <VCol cols="4" sm="4" class="px-1">
                  <VTextField v-model="weeklySchedule[day.key].close" type="time" density="compact" variant="outlined"
                    hide-details color="error" prepend-inner-icon="ri-moon-line" />
                  <div class="text-caption text-center text-error mt-1 font-weight-medium">
                    {{ to12h(weeklySchedule[day.key].close) }}
                  </div>
                </VCol>
              </template>
            </VRow>
          </div>
        </VCardText>
      </VCard>

      <!-- Save Button & Alerts -->
      <div class="sticky-bottom mb-4">
        <VAlert v-if="success" type="success" variant="tonal" class="mb-3" closable @click:close="success=''">{{ success }}</VAlert>
        <VAlert v-if="errorMsg" type="error" variant="tonal" class="mb-3" closable @click:close="errorMsg=''">{{ errorMsg }}</VAlert>
        <VBtn block color="primary" size="large" :loading="saving" prepend-icon="ri-save-line" @click="saveSettings">
          💾 حفظ جميع الإعدادات
        </VBtn>
      </div>
    </VCol>

    <!-- Left Column: Preview -->
    <VCol cols="12" lg="4">
      <VCard sticky class="sticky-preview">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-eye-line" color="primary" />
          معاينة الفاتورة
        </VCardTitle>
        <VCardText>
          <div class="invoice-preview pa-4 rounded-lg">
            <div class="d-flex align-center justify-space-between mb-4">
              <div>
                <img
                  v-if="logoPreview || currentLogo"
                  :src="logoPreview ?? `/media/${currentLogo}`"
                  style="max-height:60px;max-width:140px;"
                />
                <div v-else class="text-h6 font-weight-bold">{{ form.store_name || 'اسم المتجر' }}</div>
                <div class="text-body-2 text-medium-emphasis mt-1">{{ form.store_phone || 'رقم الهاتف' }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ form.store_address || 'العنوان' }}</div>
              </div>
              <div class="text-end">
                <div class="text-h6 font-weight-bold text-primary">INV-00001</div>
                <div class="text-body-2 text-medium-emphasis">{{ new Date().toLocaleDateString('ar-IQ') }}</div>
              </div>
            </div>
            <VDivider class="my-3" />
            <div class="text-center text-body-2 font-italic text-medium-emphasis">
              {{ form.thank_you_message || 'شكراً لثقتكم بنا' }}
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.day-open {
  background: linear-gradient(135deg, #f0faf0 0%, #e8f5e9 100%);
  border: 1px solid #c8e6c9;
  transition: all 0.2s ease;
}
.day-open:hover {
  border-color: #81c784;
  box-shadow: 0 2px 8px rgba(129, 199, 132, 0.2);
}
.day-closed {
  background: #fafafa;
  border: 1px dashed #e0e0e0;
  opacity: 0.7;
}
.invoice-preview {
  border: 2px dashed #e0e0e0;
  background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
}
.sticky-preview {
  position: sticky;
  top: 80px;
}
@media (max-width: 1280px) {
  .sticky-preview {
    position: relative;
    top: 0;
  }
}
</style>
