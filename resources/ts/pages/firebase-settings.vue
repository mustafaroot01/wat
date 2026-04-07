<script setup lang="ts">
import { ref, onMounted } from 'vue'

const activeTab = ref('connection')
const loading = ref(true)
const isSaving = ref(false)
const isTesting = ref(false)
const jsonExists = ref(false)
const projectIdFromJson = ref<string | null>(null)

const settings = ref({
  api_key: '',
  auth_domain: '',
  project_id: '',
  storage_bucket: '',
  messaging_sender_id: '',
  app_id: '',
  measurement_id: '',
  default_topic: 'all_users',
})

const fileInput = ref<HTMLInputElement | null>(null)

const fetchSettings = async () => {
  loading.value = true
  try {
    const res = await fetch('/api/admin/firebase-settings')
    const data = await res.json()
    if (data.settings) {
      settings.value = { ...settings.value, ...data.settings }
    }
    jsonExists.value = data.json_exists
    projectIdFromJson.value = data.project_id_from_json
  } catch (error) {
    console.error('Error fetching settings:', error)
  } finally {
    loading.value = false
  }
}

const saveGeneralSettings = async () => {
  isSaving.value = true
  try {
    const res = await fetch('/api/admin/firebase-settings/save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(settings.value),
    })
    const data = await res.json()
    if (res.ok) {
      alert('تم حفظ الإعدادات بنجاح!')
    } else {
      alert(data.message || 'فشل الحفظ')
    }
  } catch (error) {
    alert('حدث خطأ أثناء الحفظ')
  } finally {
    isSaving.value = false
  }
}

const handleJsonUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files?.length) return

  const file = target.files[0]
  const formData = new FormData()
  formData.append('firebase_json', file)

  isSaving.value = true
  try {
    const res = await fetch('/api/admin/firebase-settings/upload', {
      method: 'POST',
      body: formData,
    })
    if (res.ok) {
      alert('تم رفع ملف JSON بنجاح!')
      fetchSettings()
    } else {
      const data = await res.json()
      alert(data.message || 'فشل الرفع')
    }
  } catch (error) {
    alert('خطأ في الرفع')
  } finally {
    isSaving.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const sendTestNotification = async () => {
  isTesting.value = true
  try {
    const res = await fetch('/api/admin/firebase-settings/test', { method: 'POST' })
    const data = await res.json()
    if (data.success) {
      alert('تم إرسال الإشعار التجريبي بنجاح! تحقق من جهازك.')
    } else {
      alert('فشل الإرسال: ' + data.error)
    }
  } catch (error) {
    alert('خطأ في الاتصال بالسيرفر')
  } finally {
    isTesting.value = false
  }
}

onMounted(fetchSettings)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-firebase-line" color="error" size="24" />
            إعدادات الفايربيس الكاملة
          </VCardTitle>
          <VCardSubtitle>إدارة الربط مع Firebase، إعدادات العميل، ومركز الاختبار.</VCardSubtitle>
        </VCardItem>

        <VTabs v-model="activeTab" color="primary">
          <VTab value="connection">الارتباط (Server)</VTab>
          <VTab value="client">إعدادات التطبيق (Client)</VTab>
          <VTab value="test">مركز الاختبار (Test)</VTab>
        </VTabs>

        <VDivider />

        <VCardText class="pa-6">
          <div v-if="loading" class="text-center py-10">
            <VProgressCircular indeterminate color="primary" />
          </div>

          <VWindow v-else v-model="activeTab">
            <!-- 🌐 Connection Tab -->
            <VWindowItem value="connection">
              <VAlert
                v-if="jsonExists"
                type="success"
                variant="tonal"
                class="mb-6"
                rounded="lg"
              >
                المحرك مرتبط بـ Firebase بنجاح عبر المشروع: <strong>{{ projectIdFromJson }}</strong>
              </VAlert>
              <VAlert v-else type="warning" variant="tonal" class="mb-6" rounded="lg">
                لم يتم رفع ملف Service Account JSON بعد. لن تعمل الإشعارات بدونه.
              </VAlert>

              <div class="text-center pa-10 border border-dashed rounded-xl bg-light">
                <VIcon icon="ri-file-code-line" size="48" color="secondary" class="mb-4" />
                <h4 class="text-h6 mb-2">ملف التوثيق (Credentials)</h4>
                <p class="text-body-2 text-medium-emphasis mb-6">ارفع ملف الـ JSON الذي حصلت عليه من Firebase Console (Service Accounts).</p>
                
                <input ref="fileInput" type="file" class="d-none" accept=".json" @change="handleJsonUpload">
                <VBtn color="primary" @click="fileInput?.click()" :loading="isSaving" prepend-icon="ri-upload-cloud-2-line">
                  {{ jsonExists ? 'تحديث ملف JSON' : 'رفع ملف JSON' }}
                </VBtn>
              </div>
            </VWindowItem>

            <!-- 📱 Client Config Tab -->
            <VWindowItem value="client">
              <p class="text-body-2 mb-6">هذه الإعدادات يتم استخدامها داخل تطبيق الموبايل (Flutter/React Native) للربط المباشر مع واجهة Firebase.</p>
              
              <VRow>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.api_key" label="API Key" variant="outlined" placeholder="AIzaSy..." />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.auth_domain" label="Auth Domain" variant="outlined" placeholder="project-id.firebaseapp.com" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.project_id" label="Project ID" variant="outlined" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.storage_bucket" label="Storage Bucket" variant="outlined" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.messaging_sender_id" label="Messaging Sender ID" variant="outlined" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.app_id" label="App ID" variant="outlined" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.measurement_id" label="Measurement ID (Optional)" variant="outlined" />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField v-model="settings.default_topic" label="Default Messaging Topic" variant="outlined" hint="المسار الموحد الذي يشترك به جميع المستخدمين" persistent-hint />
                </VCol>
              </VRow>

              <div class="mt-6 d-flex justify-end">
                <VBtn color="primary" :loading="isSaving" @click="saveGeneralSettings">حفظ الإعدادات</VBtn>
              </div>
            </VWindowItem>

            <!-- 🧪 Test Center Tab -->
            <VWindowItem value="test">
              <div class="text-center py-10">
                <VAvatar color="primary" variant="tonal" size="72" class="mb-4">
                  <VIcon icon="ri-flask-line" size="36" />
                </VAvatar>
                <h4 class="text-h6 mb-2">اختبار البث المباشر</h4>
                <p class="text-body-2 text-medium-emphasis mb-6" style="max-width: 500px; margin: 0 auto;">
                  عند الضغط على الزر أدناه، سيقوم السيرفر بمحاولة إرسال إشعار تجريبي إلى الـ Topic الموحد ({{ settings.default_topic }}). 
                  تأكد من أن تطبيق الموبايل مفتوح ومشترك في هذا الـ Topic.
                </p>
                
                <VBtn 
                  color="success" 
                  size="large" 
                  prepend-icon="ri-send-plane-2-line" 
                  :loading="isTesting" 
                  @click="sendTestNotification"
                  :disabled="!jsonExists"
                >
                  إرسال إشعار تجريبي الآن
                </VBtn>
                
                <p v-if="!jsonExists" class="text-caption text-error mt-4">قم برفع ملف JSON في تبويب "الارتباط" أولاً لكي تتمكن من الاختبار.</p>
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.bg-light {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}
</style>
