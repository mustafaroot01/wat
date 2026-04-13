<script setup lang="ts">
import { apiFetch } from '@/utils/apiFetch'
import { onMounted, ref } from 'vue'

const activeTab = ref('general')

// ── General Settings ─────────────────────────────────────────────
const settings = ref({
  otp_provider:              'arqam',
  arqam_api_key:             '',
  twilio_account_sid:        '',
  twilio_auth_token:         '',
  twilio_verify_service_sid: '',
  otpiq_api_key:             '',
})
const loading   = ref(false)
const saving    = ref(false)
const showToken = ref(false)
const successMessage = ref('')

const fetchSettings = async () => {
  loading.value = true
  try {
    const res = await apiFetch('/api/admin/settings')
    if (res.ok) settings.value = (await res.json()).settings
  } catch (e) { console.error(e) } finally { loading.value = false }
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
  } catch (e) { console.error(e) } finally { saving.value = false }
}

// ── Firebase Settings ────────────────────────────────────────────
const fbLoading  = ref(false)
const fbSaving   = ref(false)
const fbTesting  = ref(false)
const jsonExists = ref(false)
const projectIdFromJson = ref<string | null>(null)
const fileInput  = ref<HTMLInputElement | null>(null)
const fbSuccess  = ref('')
const fbError    = ref('')

const fbSettings = ref({
  api_key: '', auth_domain: '', project_id: '',
  storage_bucket: '', messaging_sender_id: '',
  app_id: '', measurement_id: '', default_topic: 'all_users',
})

const fetchFirebase = async () => {
  fbLoading.value = true
  try {
    const res  = await apiFetch('/api/admin/firebase-settings')
    const data = await res.json()
    if (data.settings) fbSettings.value = { ...fbSettings.value, ...data.settings }
    jsonExists.value       = data.json_exists
    projectIdFromJson.value = data.project_id_from_json
  } catch (e) { console.error(e) } finally { fbLoading.value = false }
}

const saveFirebase = async () => {
  fbSaving.value = true
  fbSuccess.value = ''
  fbError.value   = ''
  try {
    const res  = await apiFetch('/api/admin/firebase-settings/save', {
      method: 'POST',
      body: JSON.stringify(fbSettings.value),
    })
    const data = await res.json()
    if (res.ok) {
      fbSuccess.value = 'تم حفظ إعدادات Firebase بنجاح!'
      setTimeout(() => fbSuccess.value = '', 3000)
    } else {
      fbError.value = data.message || 'فشل الحفظ'
    }
  } catch (e) { fbError.value = 'حدث خطأ أثناء الحفظ' } finally { fbSaving.value = false }
}

const handleJsonUpload = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  const fd = new FormData()
  fd.append('firebase_json', file)
  fbSaving.value = true
  try {
    const res = await apiFetch('/api/admin/firebase-settings/upload', { method: 'POST', body: fd })
    if (res.ok) {
      fbSuccess.value = 'تم رفع ملف JSON بنجاح!'
      setTimeout(() => fbSuccess.value = '', 3000)
      fetchFirebase()
    } else {
      fbError.value = (await res.json()).message || 'فشل الرفع'
    }
  } catch (e) { fbError.value = 'خطأ في الرفع' } finally {
    fbSaving.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const sendTestNotification = async () => {
  fbTesting.value = true
  try {
    const res  = await apiFetch('/api/admin/firebase-settings/test', { method: 'POST' })
    const data = await res.json()
    if (data.success) {
      fbSuccess.value = 'تم إرسال الإشعار التجريبي بنجاح! تحقق من جهازك.'
    } else {
      fbError.value = 'فشل الإرسال: ' + data.error
    }
  } catch (e) { fbError.value = 'خطأ في الاتصال بالسيرفر' } finally { fbTesting.value = false }
}

onMounted(() => {
  fetchSettings()
  fetchFirebase()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-settings-4-line" color="primary" size="24" />
            الإعدادات العامة
          </VCardTitle>
          <VCardSubtitle>إعدادات النظام والواتساب وربط Firebase.</VCardSubtitle>
        </VCardItem>

        <VTabs v-model="activeTab" color="primary">
          <VTab value="general">
            <VIcon icon="ri-settings-4-line" size="18" class="me-2" />إعدادات عامة
          </VTab>
          <VTab value="firebase">
            <VIcon icon="ri-firebase-line" size="18" class="me-2" />Firebase
          </VTab>
        </VTabs>

        <VDivider />

        <VCardText class="pa-6">
          <VWindow v-model="activeTab">

            <!-- ═══ General Settings ═══ -->
            <VWindowItem value="general">
              <div v-if="loading" class="text-center py-8">
                <VProgressCircular indeterminate color="primary" />
              </div>
              <VRow v-else>
                <VCol cols="12" md="6">
                  <VAlert v-if="successMessage" type="success" variant="tonal" density="compact" class="mb-4" closable>
                    {{ successMessage }}
                  </VAlert>
                  <VForm @submit.prevent="saveSettings">

                    <!-- Provider Selector -->
                    <div class="mb-5">
                      <div class="text-body-2 font-weight-bold mb-3 d-flex align-center gap-2">
                        <VIcon icon="ri-send-plane-line" color="primary" size="18" />
                        مزود خدمة OTP (واتساب)
                      </div>
                      <VBtnToggle
                        v-model="settings.otp_provider"
                        mandatory
                        color="primary"
                        variant="outlined"
                        rounded="lg"
                        divided
                      >
                        <VBtn value="arqam"  prepend-icon="ri-whatsapp-line">Arqam</VBtn>
                        <VBtn value="twilio" prepend-icon="ri-phone-line">Twilio</VBtn>
                        <VBtn value="otpiq"  prepend-icon="ri-message-2-line">OTPIQ</VBtn>
                      </VBtnToggle>
                    </div>

                    <!-- Arqam Fields -->
                    <template v-if="settings.otp_provider === 'arqam'">
                      <VTextField
                        v-model="settings.arqam_api_key"
                        label="Arqam API Key"
                        placeholder="otplive_xxxxxxxxxxxxxxxx"
                        variant="outlined"
                        persistent-placeholder
                        hint="من لوحة Arqam Tech — يبدأ بـ otplive_"
                        persistent-hint
                        class="mb-5"
                        prepend-inner-icon="ri-key-2-line"
                        :type="showToken ? 'text' : 'password'"
                        :append-inner-icon="showToken ? 'ri-eye-off-line' : 'ri-eye-line'"
                        @click:append-inner="showToken = !showToken"
                        dir="ltr"
                      />
                    </template>

                    <!-- Twilio Fields -->
                    <template v-else-if="settings.otp_provider === 'twilio'">
                      <VTextField
                        v-model="settings.twilio_account_sid"
                        label="Account SID"
                        placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        variant="outlined"
                        persistent-placeholder
                        hint="يبدأ بـ AC — من لوحة Twilio Console"
                        persistent-hint
                        class="mb-4"
                        prepend-inner-icon="ri-account-circle-line"
                        dir="ltr"
                      />
                      <VTextField
                        v-model="settings.twilio_auth_token"
                        label="Auth Token"
                        placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        variant="outlined"
                        persistent-placeholder
                        hint="من لوحة Twilio Console — تحته مباشرة"
                        persistent-hint
                        class="mb-4"
                        prepend-inner-icon="ri-key-2-line"
                        :type="showToken ? 'text' : 'password'"
                        :append-inner-icon="showToken ? 'ri-eye-off-line' : 'ri-eye-line'"
                        @click:append-inner="showToken = !showToken"
                        dir="ltr"
                      />
                      <VTextField
                        v-model="settings.twilio_verify_service_sid"
                        label="Verify Service SID"
                        placeholder="VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        variant="outlined"
                        persistent-placeholder
                        hint="يبدأ بـ VA — من Twilio → Verify → Services"
                        persistent-hint
                        class="mb-5"
                        prepend-inner-icon="ri-shield-check-line"
                        dir="ltr"
                      />
                    </template>

                    <!-- OTPIQ Fields -->
                    <template v-else-if="settings.otp_provider === 'otpiq'">
                      <VTextField
                        v-model="settings.otpiq_api_key"
                        label="OTPIQ API Key"
                        placeholder="your-otpiq-api-key-here"
                        variant="outlined"
                        persistent-placeholder
                        hint="من لوحة OTPIQ → API Keys"
                        persistent-hint
                        class="mb-5"
                        prepend-inner-icon="ri-key-2-line"
                        :type="showToken ? 'text' : 'password'"
                        :append-inner-icon="showToken ? 'ri-eye-off-line' : 'ri-eye-line'"
                        @click:append-inner="showToken = !showToken"
                        dir="ltr"
                      />
                    </template>

                    <VBtn color="primary" type="submit" :loading="saving" prepend-icon="ri-save-line" rounded="lg">
                      حفظ الإعدادات
                    </VBtn>
                  </VForm>
                </VCol>
                <VCol cols="12" md="6">
                  <VCard variant="tonal" color="primary" rounded="lg">
                    <VCardText class="pa-5">
                      <div class="d-flex gap-3 align-start">
                        <VIcon icon="ri-information-line" color="primary" size="28" />
                        <div>
                          <template v-if="settings.otp_provider === 'arqam'">
                            <div class="font-weight-bold mb-2 text-primary">إعداد Arqam Tech</div>
                            <ol class="text-body-2 ps-4 mb-0" style="line-height:1.9;">
                              <li>سجّل على <strong>otp.arqam.tech</strong></li>
                              <li>اذهب لـ <strong>API Keys</strong> وأنشئ مفتاحاً جديداً</li>
                              <li>انسخ المفتاح (يبدأ بـ otplive_) والصقه</li>
                              <li>تأكد من تفعيل قناة <strong>WhatsApp</strong> في حسابك</li>
                            </ol>
                          </template>
                          <template v-else-if="settings.otp_provider === 'twilio'">
                            <div class="font-weight-bold mb-2 text-primary">إعداد Twilio Verify</div>
                            <ol class="text-body-2 ps-4 mb-0" style="line-height:1.9;">
                              <li>سجّل على <strong>console.twilio.com</strong></li>
                              <li>انسخ <strong>Account SID</strong> و <strong>Auth Token</strong></li>
                              <li>اذهب لـ <strong>Verify → Services</strong> وأنشئ Service جديد</li>
                              <li>فعّل قناة <strong>WhatsApp</strong> على الـ Service</li>
                              <li>انسخ الـ <strong>Service SID</strong> (يبدأ بـ VA)</li>
                            </ol>
                          </template>
                          <template v-else>
                            <div class="font-weight-bold mb-2 text-primary">إعداد OTPIQ</div>
                            <ol class="text-body-2 ps-4 mb-0" style="line-height:1.9;">
                              <li>سجّل على <strong>app.otpiq.com</strong></li>
                              <li>اذهب لـ <strong>API Keys</strong></li>
                              <li>انسخ المفتاح وألصقه في الحقل</li>
                              <li>تأكد من وجود رصيد كافٍ في حسابك</li>
                            </ol>
                          </template>
                        </div>
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- ═══ Firebase Settings ═══ -->
            <VWindowItem value="firebase">
              <div v-if="fbLoading" class="text-center py-8">
                <VProgressCircular indeterminate color="primary" />
              </div>
              <template v-else>
                <VAlert v-if="fbSuccess" type="success" variant="tonal" density="compact" class="mb-4" closable @click:close="fbSuccess=''">{{ fbSuccess }}</VAlert>
                <VAlert v-if="fbError"   type="error"   variant="tonal" density="compact" class="mb-4" closable @click:close="fbError=''">{{ fbError }}</VAlert>

                <VTabs color="deep-orange" density="compact" class="mb-4">
                  <VTab value="conn">الارتباط (Server)</VTab>
                  <VTab value="client">إعدادات التطبيق</VTab>
                  <VTab value="test">مركز الاختبار</VTab>
                </VTabs>

                <!-- Workaround: show all sections stacked since nested VWindow can conflict -->
                <!-- Connection -->
                <VCard variant="outlined" rounded="lg" class="mb-4">
                  <VCardItem>
                    <VCardTitle class="d-flex align-center gap-2 text-body-1 font-weight-bold">
                      <VIcon icon="ri-file-code-line" color="secondary" />الارتباط — Service Account JSON
                    </VCardTitle>
                  </VCardItem>
                  <VCardText>
                    <VAlert v-if="jsonExists" type="success" variant="tonal" class="mb-4" rounded="lg">
                      مرتبط بنجاح — مشروع: <strong>{{ projectIdFromJson }}</strong>
                    </VAlert>
                    <VAlert v-else type="warning" variant="tonal" class="mb-4" rounded="lg">
                      لم يتم رفع ملف JSON بعد. لن تعمل الإشعارات.
                    </VAlert>
                    <input ref="fileInput" type="file" class="d-none" accept=".json" @change="handleJsonUpload">
                    <VBtn color="primary" @click="fileInput?.click()" :loading="fbSaving" prepend-icon="ri-upload-cloud-2-line">
                      {{ jsonExists ? 'تحديث ملف JSON' : 'رفع ملف JSON' }}
                    </VBtn>
                  </VCardText>
                </VCard>

                <!-- Client Config -->
                <VCard variant="outlined" rounded="lg" class="mb-4">
                  <VCardItem>
                    <VCardTitle class="d-flex align-center gap-2 text-body-1 font-weight-bold">
                      <VIcon icon="ri-smartphone-line" color="primary" />إعدادات التطبيق (Client)
                    </VCardTitle>
                  </VCardItem>
                  <VCardText>
                    <VRow>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.api_key" label="API Key" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.auth_domain" label="Auth Domain" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.project_id" label="Project ID" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.storage_bucket" label="Storage Bucket" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.messaging_sender_id" label="Messaging Sender ID" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.app_id" label="App ID" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.measurement_id" label="Measurement ID (اختياري)" variant="outlined" density="compact" /></VCol>
                      <VCol cols="12" md="6"><VTextField v-model="fbSettings.default_topic" label="Default Topic" variant="outlined" density="compact" hint="يشترك به جميع المستخدمين" persistent-hint /></VCol>
                    </VRow>
                    <div class="mt-4 d-flex justify-end">
                      <VBtn color="primary" :loading="fbSaving" @click="saveFirebase" prepend-icon="ri-save-line">حفظ إعدادات Firebase</VBtn>
                    </div>
                  </VCardText>
                </VCard>

                <!-- Test Center -->
                <VCard variant="outlined" rounded="lg">
                  <VCardItem>
                    <VCardTitle class="d-flex align-center gap-2 text-body-1 font-weight-bold">
                      <VIcon icon="ri-flask-line" color="success" />مركز الاختبار
                    </VCardTitle>
                  </VCardItem>
                  <VCardText class="text-center py-6">
                    <p class="text-body-2 text-medium-emphasis mb-4">
                      إرسال إشعار تجريبي إلى الـ Topic: <strong>{{ fbSettings.default_topic }}</strong>
                    </p>
                    <VBtn color="success" size="large" prepend-icon="ri-send-plane-2-line" :loading="fbTesting" @click="sendTestNotification" :disabled="!jsonExists">
                      إرسال إشعار تجريبي
                    </VBtn>
                    <p v-if="!jsonExists" class="text-caption text-error mt-3">ارفع ملف JSON أولاً.</p>
                  </VCardText>
                </VCard>

              </template>
            </VWindowItem>

          </VWindow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

