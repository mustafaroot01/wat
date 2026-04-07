<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'

interface AppNotification {
  id: number | null
  title: string
  message: string
  image_url: string | null
  delivery_status: string
  failure_reason: string | null
  sent_at: string | null
  created_at: string
}

const {
  items: notifications,
  loading,
  currentPage,
  totalPages,
  fetchData: fetchNotifications,
  handlePageChange
} = usePagination<AppNotification>('/api/admin/notifications')

// Dialog variables
const showDialog = ref(false)
const confirmDeleteDialog = ref(false)
const currNotificationId = ref<number | null>(null)

// Image Preview
const imagePreviewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLElement | null>(null)
const isSending = ref(false)

const formData = ref({
  title: '',
  message: '',
  image: null as File | null,
})

const handleImageSelection = (event: any) => {
  const file = event.target.files[0]
  if (file) {
    formData.value.image = file
    imagePreviewUrl.value = URL.createObjectURL(file)
  }
}

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

const removeImage = () => {
  formData.value.image = null
  imagePreviewUrl.value = null
  if (fileInputRef.value) {
    (fileInputRef.value as HTMLInputElement).value = ''
  }
}

const openAddDialog = () => {
  formData.value = { title: '', message: '', image: null }
  imagePreviewUrl.value = null
  showDialog.value = true
}

const sendNotification = async () => {
  if (!formData.value.title || !formData.value.message) {
    alert('يرجى ملء العنوان ونص الرسالة.')
    return
  }

  isSending.value = true
  const payload = new FormData()
  payload.append('title', formData.value.title)
  payload.append('message', formData.value.message)

  if (formData.value.image instanceof File) {
    payload.append('image', formData.value.image)
  }

  try {
    const res = await fetch('/api/admin/notifications', {
      method: 'POST',
      body: payload,
    })

    if (res.ok) {
      showDialog.value = false
      fetchNotifications(currentPage.value)
    } else {
      const errorData = await res.json()
      alert('خطأ: ' + JSON.stringify(errorData.errors || errorData.message))
    }
  } catch (error) {
    console.error('Error saving notification:', error)
  } finally {
    isSending.value = false
  }
}

const confirmDelete = (id: number | null) => {
  currNotificationId.value = id
  confirmDeleteDialog.value = true
}

const deleteNotification = async () => {
  try {
    const res = await fetch(`/api/admin/notifications/${currNotificationId.value}`, {
      method: 'DELETE',
    })

    if (res.ok) {
      confirmDeleteDialog.value = false
      fetchNotifications(currentPage.value)
    } else {
      const errorData = await res.json()
      alert('خطأ أثناء الحذف: ' + (errorData.message || ''))
    }
  } catch (error) {
    console.error('Error deleting notification:', error)
  }
}

const getStatusColor = (status: string) => {
  if (status === 'sent') return 'success'
  if (status === 'failed') return 'error'
  return 'warning'
}

const getStatusText = (status: string) => {
  if (status === 'sent') return 'تم الإرسال'
  if (status === 'failed') return 'فشل'
  return 'معلق'
}

onMounted(() => {
  fetchNotifications(1)
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-notification-badge-line" color="primary" size="20" />
              الأخبار والإشعارات
            </div>
            <VBtn
              color="primary"
              prepend-icon="ri-send-plane-fill"
              rounded="lg"
              @click="openAddDialog"
            >
              بث إشعار جديد
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr class="bg-light">
                <th style="width: 50px;" class="text-center">#</th>
                <th>العنوان</th>
                <th>نص الخبر</th>
                <th class="text-center">الحالة</th>
                <th class="text-center">وقت النشر</th>
                <th class="text-center">حذف</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">
                  <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                  جاري التحميل...
                </td>
              </tr>
              <tr v-else-if="notifications.length === 0">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">لم يتم إرسال أي إشعارات سابقة</td>
              </tr>
              <tr v-for="(item, index) in notifications" :key="item.id || index" class="notification-row">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td class="font-weight-medium">
                  <div class="d-flex align-center gap-3">
                    <VAvatar v-if="item.image_url" :image="item.image_url" size="36" rounded="sm" />
                    <VAvatar v-else color="surface-variant" size="36" rounded="sm">
                      <VIcon icon="ri-notification-3-line" size="18" />
                    </VAvatar>
                    <span class="text-truncate" style="max-width: 200px;">{{ item.title }}</span>
                  </div>
                </td>
                <td>
                  <div class="text-truncate text-medium-emphasis text-body-2" style="max-width: 250px;">
                    {{ item.message }}
                  </div>
                </td>
                <td class="text-center">
                  <VChip size="small" :color="getStatusColor(item.delivery_status)" variant="tonal" rounded="sm">
                    {{ getStatusText(item.delivery_status) }}
                  </VChip>
                </td>
                <td class="text-center text-body-2 text-medium-emphasis" dir="ltr">
                  {{ item.sent_at || '-' }}
                </td>
                <td class="text-center">
                  <VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="confirmDelete(item.id)" />
                </td>
              </tr>
            </tbody>
          </VTable>

          <!-- Pagination -->
          <VDivider v-if="totalPages > 1" />
          <div v-if="totalPages > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
            <span class="text-caption text-medium-emphasis">
              عرض الصفحة {{ currentPage }} من {{ totalPages }}
            </span>
            <VPagination
              v-model="currentPage"
              :length="totalPages"
              :total-visible="5"
              density="comfortable"
              variant="tonal"
              active-color="primary"
              @update:model-value="handlePageChange"
            />
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Add Notification Dialog -->
  <VDialog v-model="showDialog" max-width="600" persistent transition="dialog-bottom-transition">
    <VCard rounded="xl" elevation="24" class="notification-broadcast-card overflow-hidden">
      <!-- Gradient Header -->
      <div class="header-gradient pa-6 d-flex justify-space-between align-center">
        <div class="d-flex align-center gap-3">
          <VAvatar color="white" rounded="lg" size="44" class="elevation-2 text-primary">
            <VIcon icon="ri-broadcast-line" size="24" />
          </VAvatar>
          <div>
            <h3 class="text-h6 font-weight-bold mb-0 text-white">بث إشعار وخبر جديد</h3>
            <p class="text-caption mb-0 text-white-50">أرسل تنبيهاً فورياً لكل مستخدمي التطبيق</p>
          </div>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="white"
          class="glass-effect"
          @click="showDialog = false"
          :disabled="isSending"
        />
      </div>

      <VCardText class="pa-8 pb-4">
        <input
          type="file"
          ref="fileInputRef"
          class="d-none"
          accept="image/*"
          @change="handleImageSelection"
        >

        <VRow>
          <!-- Image Section -->
          <VCol cols="12" md="4" class="d-flex flex-column">
            <p class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
              <VIcon icon="ri-image-line" size="18" color="primary" />
              الصورة المرفقة
            </p>
            <div
              class="premium-upload-zone flex-grow-1 d-flex align-center justify-center position-relative"
              @click="triggerFileInput"
              :class="{'has-image': imagePreviewUrl}"
            >
              <VImg
                v-if="imagePreviewUrl"
                :src="imagePreviewUrl"
                cover
                class="rounded-lg h-100"
              />
              <div v-else class="text-center pa-4">
                <VIcon icon="ri-camera-lens-line" size="36" color="primary" class="mb-2 opacity-50" />
                <p class="text-caption text-medium-emphasis mb-0">انقر هنا لإضافة صورة جذابة</p>
              </div>

              <!-- Premium Overlay -->
              <div v-if="imagePreviewUrl" class="image-premium-overlay d-flex align-center justify-center gap-2">
                <VBtn icon="ri-camera-switch-line" size="small" variant="text" color="white" class="glass-effect" @click.stop="triggerFileInput" />
                <VBtn icon="ri-delete-bin-7-line" size="small" variant="text" color="error" class="glass-effect" @click.stop="removeImage" />
              </div>
            </div>
          </VCol>

          <!-- Inputs Section -->
          <VCol cols="12" md="8">
            <VRow>
              <VCol cols="12">
                <p class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center gap-2">
                  <VIcon icon="ri-text-snippet" size="18" color="primary" />
                  محتوى الإشعار
                </p>
                <VTextField
                  v-model="formData.title"
                  label="عنوان الإشعار الرئيسي"
                  placeholder="مثال: عرض حصري لفترة محدودة! 🎁"
                  variant="outlined"
                  bg-color="grey-lighten-4"
                  rounded="lg"
                  persistent-placeholder
                  class="mb-2"
                />
                <VTextarea
                  v-model="formData.message"
                  label="نص الإشعار التفصيلي"
                  placeholder="اكتب هنا التفاصيل التي سيقرأها العميل عند النقر على التنبيه..."
                  variant="outlined"
                  bg-color="grey-lighten-4"
                  rounded="lg"
                  rows="5"
                  auto-grow
                  persistent-placeholder
                />
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-8 pt-0">
        <VRow dense>
          <VCol cols="4">
            <VBtn
              variant="tonal"
              color="secondary"
              rounded="lg"
              block
              size="large"
              class="font-weight-bold"
              @click="showDialog = false"
              :disabled="isSending"
            >
              إلغاء
            </VBtn>
          </VCol>
          <VCol cols="8">
            <VBtn
              color="primary"
              variant="elevated"
              rounded="lg"
              block
              size="large"
              class="font-weight-bold premium-broadcast-btn"
              @click="sendNotification"
              :loading="isSending"
            >
              بث الإشعار الآن
              <VIcon icon="ri-send-plane-2-fill" end class="ms-2" />
            </VBtn>
          </VCol>
        </VRow>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg" elevation="0">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-delete-bin-line" size="32" />
        </VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">تأكيد مسح السجل</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">
        <p class="text-body-1 mb-1">هل تريد حذف هذا الإشعار من سجل الأخبار؟</p>
        <p class="text-caption text-error">حذفه سيؤدي لاختفائه من صفحة الأخبار داخل تطبيق الموبايل أيضاً.</p>
      </VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" @click="deleteNotification">نعم، احذفه</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.notification-broadcast-card {
  border: none !important;
}

.header-gradient {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, #6366f1 100%);
}

.text-white-50 {
  color: rgba(255, 255, 255, 0.7);
}

.premium-upload-zone {
  min-height: 200px;
  border: 2px dashed rgba(var(--v-theme-primary), 0.2);
  border-radius: 16px;
  cursor: pointer;
  background-color: rgba(var(--v-theme-primary), 0.02);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.premium-upload-zone:hover {
  background-color: rgba(var(--v-theme-primary), 0.05);
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
}

.premium-upload-zone.has-image {
  border-style: solid;
  border-color: transparent;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.image-premium-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px);
  border-radius: 16px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.premium-upload-zone:hover .image-premium-overlay {
  opacity: 1;
}

.premium-broadcast-btn {
  box-shadow: 0 4px 15px rgba(var(--v-theme-primary), 0.3);
  letter-spacing: 0.5px;
}

.notification-row:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

/* Glass effect class */
.glass-effect {
  background: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
}
</style>
