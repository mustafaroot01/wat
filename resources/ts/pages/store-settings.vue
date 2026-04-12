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
})

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
  <VRow>
    <VCol cols="12" md="8" lg="6">
      <VCard title="إعدادات المتجر والفاتورة">
        <VCardText v-if="loading" class="text-center pa-8">
          <VProgressCircular indeterminate color="primary" />
        </VCardText>

        <VCardText v-else>
          <!-- Logo -->
          <div class="mb-6">
            <div class="text-subtitle-2 font-weight-bold mb-3">شعار المتجر (يظهر في الفاتورة)</div>

            <div v-if="logoPreview || currentLogo" class="mb-3 d-flex align-center gap-3">
              <img
                :src="logoPreview ?? `/storage/${currentLogo}`"
                style="max-height:80px;max-width:200px;border:1px solid #eee;border-radius:8px;padding:4px;"
              />
              <VBtn variant="text" color="error" size="small" @click="removeLogo">
                <VIcon size="16" class="me-1">ri-delete-bin-line</VIcon> إزالة
              </VBtn>
            </div>

            <VBtn
              variant="tonal"
              color="primary"
              prepend-icon="ri-upload-2-line"
              @click="fileInputRef?.click()"
            >
              {{ logoPreview || currentLogo ? 'تغيير الشعار' : 'رفع الشعار' }}
            </VBtn>
            <input
              ref="fileInputRef"
              type="file"
              accept="image/*"
              style="display:none;"
              @change="onLogoChange"
            />
            <div class="text-caption text-medium-emphasis mt-1">PNG, JPG, SVG — بحد أقصى 2MB</div>
          </div>

          <VDivider class="mb-5" />

          <!-- Fields -->
          <VTextField
            v-model="form.store_name"
            label="اسم المتجر"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-store-2-line"
          />

          <VTextField
            v-model="form.store_phone"
            label="رقم هاتف المتجر"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-phone-line"
            dir="ltr"
          />

          <VTextField
            v-model="form.store_address"
            label="عنوان المتجر"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-map-pin-line"
          />

          <VTextarea
            v-model="form.thank_you_message"
            label="رسالة الشكر (تظهر أسفل الفاتورة)"
            variant="outlined"
            rows="2"
            prepend-inner-icon="ri-chat-heart-line"
          />

          <VDivider class="my-5" />

          <!-- Contact Info & About Us -->
          <div class="mb-3">
            <div class="text-subtitle-2 font-weight-bold d-flex align-center gap-2">
              <VIcon icon="ri-contacts-line" color="primary" size="20" />
              معلومات التواصل (تظهر في صفحة من نحن بالتطبيق)
            </div>
          </div>

          <VTextField
            v-model="form.contact_phone2"
            label="رقم هاتف إضافي للتواصل"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-phone-line"
            dir="ltr"
            placeholder="+9647XXXXXXXXX"
          />

          <VTextField
            v-model="form.contact_instagram"
            label="معرف انستغرام"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-instagram-line"
            dir="ltr"
            placeholder="@amwaj_dialay"
          />

          <VTextField
            v-model="form.contact_facebook"
            label="رابط صفحة فيس بوك"
            variant="outlined"
            class="mb-4"
            prepend-inner-icon="ri-facebook-line"
            dir="ltr"
            placeholder="https://facebook.com/amwajdialay"
          />

          <VTextarea
            v-model="form.about_us_description"
            label="نص من نحن (يظهر في صفحة التطبيق)"
            variant="outlined"
            rows="3"
            class="mb-4"
            prepend-inner-icon="ri-information-line"
            placeholder="اكتب نبذة عن متجرك هنا..."
          />

          <VDivider class="my-5" />

          <!-- Minimum Order Amount -->
          <div class="mb-2">
            <div class="text-subtitle-2 font-weight-bold mb-1 d-flex align-center gap-2">
              <VIcon icon="ri-money-dollar-circle-line" color="warning" size="20" />
              الحد الأدنى لمبلغ الطلب
            </div>
            <div class="text-caption text-medium-emphasis mb-3">
              إذا كان المبلغ 0 — لا يوجد حد أدنى (مفعّل دائماً)
            </div>
          </div>
          <VTextField
            v-model.number="form.minimum_order_amount"
            label="أقل مبلغ للطلب (بالدينار العراقي)"
            type="number"
            min="0"
            step="500"
            variant="outlined"
            color="warning"
            prepend-inner-icon="ri-shield-check-line"
            :hint="form.minimum_order_amount > 0 ? `الزبون لازم يطلب بمبلغ لا يقل عن ${form.minimum_order_amount.toLocaleString()} د.ع` : 'لا يوجد حد أدنى حالياً'"
            persistent-hint
          />

          <VDivider class="my-5" />

          <!-- Weekly Schedule -->
          <div class="mb-3">
            <div class="text-subtitle-2 font-weight-bold d-flex align-center gap-2">
              <VIcon icon="ri-calendar-schedule-line" color="info" size="20" />
              جدول الدوام الأسبوعي
            </div>
            <div class="text-caption text-medium-emphasis mt-1">
              فعّل الأيام وحدد وقت الفتح والإغلاق — خارج هذه الأوقات يظهر للزبون: <strong>المتجر مغلق</strong>
            </div>
          </div>

          <div
            v-for="day in days"
            :key="day.key"
            class="day-row mb-2 pa-3 rounded-lg"
            :class="weeklySchedule[day.key]?.enabled ? 'day-open' : 'day-closed'"
          >
            <VRow align="center" no-gutters>
              <!-- Day name + toggle -->
              <VCol cols="4" sm="3" class="d-flex align-center gap-2">
                <VSwitch
                  v-model="weeklySchedule[day.key].enabled"
                  color="success"
                  hide-details
                  density="compact"
                />
                <span class="font-weight-bold text-body-2">{{ day.label }}</span>
              </VCol>

              <!-- Closed label -->
              <VCol v-if="!weeklySchedule[day.key]?.enabled" cols="8" sm="9">
                <VChip color="error" size="small" variant="tonal">مغلق</VChip>
              </VCol>

              <!-- Time inputs -->
              <template v-else>
                <VCol cols="4" sm="4" class="px-1">
                  <VTextField
                    v-model="weeklySchedule[day.key].open"
                    type="time"
                    density="compact"
                    variant="outlined"
                    hide-details
                    color="success"
                    prepend-inner-icon="ri-sun-line"
                    :hint="to12h(weeklySchedule[day.key].open)"
                  />
                  <div class="text-caption text-center text-success mt-1 font-weight-medium">
                    {{ to12h(weeklySchedule[day.key].open) }}
                  </div>
                </VCol>
                <VCol cols="1" sm="1" class="text-center">
                  <VIcon size="16" color="medium-emphasis">ri-arrow-left-line</VIcon>
                </VCol>
                <VCol cols="4" sm="4" class="px-1">
                  <VTextField
                    v-model="weeklySchedule[day.key].close"
                    type="time"
                    density="compact"
                    variant="outlined"
                    hide-details
                    color="error"
                    prepend-inner-icon="ri-moon-line"
                  />
                  <div class="text-caption text-center text-error mt-1 font-weight-medium">
                    {{ to12h(weeklySchedule[day.key].close) }}
                  </div>
                </VCol>
              </template>
            </VRow>
          </div>

          <VAlert v-if="success"  type="success" variant="tonal" class="mt-4" closable @click:close="success=''">{{ success }}</VAlert>
          <VAlert v-if="errorMsg" type="error"   variant="tonal" class="mt-4" closable @click:close="errorMsg=''">{{ errorMsg }}</VAlert>
        </VCardText>

        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn color="primary" :loading="saving" prepend-icon="ri-save-line" @click="saveSettings">
            حفظ الإعدادات
          </VBtn>
        </VCardActions>
      </VCard>
    </VCol>

    <!-- Preview -->
    <VCol cols="12" md="4" lg="6">
      <VCard title="معاينة رأس الفاتورة">
        <VCardText>
          <div class="d-flex align-center justify-space-between pa-3" style="border:1px dashed #ccc;border-radius:8px;">
            <div>
              <img
                v-if="logoPreview || currentLogo"
                :src="logoPreview ?? `/storage/${currentLogo}`"
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
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.day-open {
  background: #f0faf0;
  border: 1px solid #c8e6c9;
}
.day-closed {
  background: #fafafa;
  border: 1px dashed #e0e0e0;
  opacity: 0.75;
}
</style>
