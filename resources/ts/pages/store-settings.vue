<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '@/utils/apiFetch'

const loading = ref(false)
const saving  = ref(false)
const success  = ref('')
const errorMsg = ref('')

const form = ref({
  store_name:        '',
  store_phone:       '',
  store_address:     '',
  thank_you_message: '',
})

const logoFile        = ref<File | null>(null)
const logoPreview     = ref<string | null>(null)
const currentLogo     = ref<string | null>(null)
const fileInputRef    = ref<HTMLInputElement | null>(null)

const loadSettings = async () => {
  loading.value = true
  try {
    const res  = await apiFetch('/api/admin/store-settings')
    const data = await res.json()
    form.value.store_name        = data.store_name        ?? ''
    form.value.store_phone       = data.store_phone       ?? ''
    form.value.store_address     = data.store_address     ?? ''
    form.value.thank_you_message = data.thank_you_message ?? ''
    currentLogo.value            = data.logo              ?? null
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
    fd.append('store_name',        form.value.store_name)
    fd.append('store_phone',       form.value.store_phone)
    fd.append('store_address',     form.value.store_address)
    fd.append('thank_you_message', form.value.thank_you_message)
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
