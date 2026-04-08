<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '@/utils/apiFetch'

const settings = ref({
  whatsapp_api_key: ''
})

const loading = ref(false)
const saving = ref(false)
const successMessage = ref('')

const fetchSettings = async () => {
  loading.value = true
  try {
    const res = await apiFetch('/api/admin/settings')
    if (res.ok) {
      const data = await res.json()
      settings.value = data.settings
    }
  } catch (error) {
    console.error('Error fetching settings:', error)
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  successMessage.value = ''
  try {
    const res = await apiFetch('/api/admin/settings', {
      method: 'POST',
      body: JSON.stringify(settings.value)
    })
    
    if (res.ok) {
      successMessage.value = 'تم حفظ الإعدادات بنجاح!'
      setTimeout(() => successMessage.value = '', 3000)
    }
  } catch (error) {
    console.error('Error saving settings:', error)
  } finally {
    saving.value = false
  }
}

onMounted(fetchSettings)
</script>

<template>
  <VRow>
    <VCol cols="12" md="6">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-settings-4-line" color="primary" />
            الإعدادات العامة للنظام
          </VCardTitle>
          <VCardSubtitle>إدارة مفاتيح الربط والإعدادات الأساسية لمتجر امواج ديالى.</VCardSubtitle>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-6">
          <div v-if="loading" class="text-center py-5">
            <VProgressCircular indeterminate color="primary" />
          </div>
          <VForm v-else @submit.prevent="saveSettings">
            <VRow>
              <!-- WhatsApp OTP Key -->
              <VCol cols="12">
                <div class="d-flex align-center gap-2 mb-2">
                  <VIcon icon="ri-whatsapp-line" color="success" size="20" />
                  <span class="font-weight-medium">مفتاح خدمة الواتساب (OTP API Key)</span>
                </div>
                <VTextField
                  v-model="settings.whatsapp_api_key"
                  placeholder="أدخل مفتاح التحقق (otplive_...)"
                  variant="outlined"
                  persistent-placeholder
                  hint="هذا المفتاح يستخدم لإرسال رموز التحقق عند تسجيل الزبائن."
                  persistent-hint
                />
              </VCol>

              <VCol cols="12" class="mt-4">
                <VAlert
                  v-if="successMessage"
                  type="success"
                  variant="tonal"
                  density="compact"
                  class="mb-4"
                  closable
                >
                  {{ successMessage }}
                </VAlert>

                <VBtn
                  color="primary"
                  type="submit"
                  :loading="saving"
                  :disabled="saving"
                  rounded="lg"
                  prepend-icon="ri-save-line"
                  block
                >
                  حفظ الإعدادات
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="6">
      <VCard rounded="lg" elevation="0" border class="bg-light-primary border-primary">
        <VCardText class="pa-6">
          <div class="d-flex gap-4 align-start">
            <VIcon icon="ri-information-line" color="primary" size="32" />
            <div>
              <h4 class="text-h6 font-weight-bold mb-2 text-primary">لماذا هذا المفتاح؟</h4>
              <p class="text-body-2 mb-0" style="line-height:1.6;">
                يعتمد المتجر على مزود خدمة <strong>Arqam Tech</strong> لإرسال رسائل الواتساب.
                عند امتلاكك لحساب ومفتاح API، يرجى لصقه هنا ليتمكن الزبائن من استلام رموز التحقق (OTP)
                عند إنشاء حساباتهم أو استعادة كلمات المرور.
              </p>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.bg-light-primary {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}
</style>
