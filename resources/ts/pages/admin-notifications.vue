<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '@/utils/apiFetch'
import { useAdminNotifications } from '@/composables/useAdminNotifications'

const router = useRouter()
const { markAsRead, markAllAsRead, fetchUnread } = useAdminNotifications()

interface Notification {
  id: number
  title: string
  body: string
  type: string
  order_id: number | null
  is_read: boolean
  created_at: string
}

const notifications = ref<Notification[]>([])
const loading = ref(true)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const filterStatus = ref<'all' | 'unread' | 'read'>('all')
const confirmDeleteDialog = ref(false)
const deleteTargetId = ref<number | null>(null)

const fetchNotifications = async (page = 1) => {
  loading.value = true
  try {
    const res = await apiFetch(`/api/admin/admin-notifications?page=${page}&per_page=20`)
    if (res.ok) {
      const data = await res.json()
      notifications.value = data.data
      currentPage.value = data.current_page
      lastPage.value = data.last_page
      total.value = data.total
    }
  } catch (e) {
    console.error('Error fetching notifications:', e)
  } finally {
    loading.value = false
  }
}

const filteredNotifications = computed(() => {
  if (filterStatus.value === 'unread') return notifications.value.filter(n => !n.is_read)
  if (filterStatus.value === 'read') return notifications.value.filter(n => n.is_read)
  return notifications.value
})

const handleMarkAsRead = async (notif: Notification) => {
  if (!notif.is_read) {
    await markAsRead(notif.id)
    notif.is_read = true
  }
}

const handleMarkAllRead = async () => {
  await markAllAsRead()
  notifications.value.forEach(n => (n.is_read = true))
}

const goToOrder = async (notif: Notification) => {
  await handleMarkAsRead(notif)
  if (notif.order_id) {
    router.push('/orders')
  }
}

const confirmDelete = (id: number) => {
  deleteTargetId.value = id
  confirmDeleteDialog.value = true
}

const deleteNotification = async () => {
  if (!deleteTargetId.value) return
  try {
    const res = await apiFetch(`/api/admin/admin-notifications/${deleteTargetId.value}`, {
      method: 'DELETE',
    })
    if (res.ok) {
      notifications.value = notifications.value.filter(n => n.id !== deleteTargetId.value)
      total.value = Math.max(0, total.value - 1)
      confirmDeleteDialog.value = false
      fetchUnread()
    }
  } catch (e) {
    console.error('Error deleting notification:', e)
  }
}

const handlePageChange = (page: number) => {
  fetchNotifications(page)
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('ar-IQ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const timeAgo = (dateStr: string) => {
  const now = new Date()
  const date = new Date(dateStr)
  const diff = Math.floor((now.getTime() - date.getTime()) / 1000)

  if (diff < 60) return 'الآن'
  if (diff < 3600) return `منذ ${Math.floor(diff / 60)} دقيقة`
  if (diff < 86400) return `منذ ${Math.floor(diff / 3600)} ساعة`
  if (diff < 604800) return `منذ ${Math.floor(diff / 86400)} يوم`
  return formatDate(dateStr)
}

const unreadCountLocal = computed(() => notifications.value.filter(n => !n.is_read).length)

onMounted(() => fetchNotifications(1))
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between flex-wrap gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-notification-3-line" color="primary" size="22" />
              إشعارات الطلبات
              <VChip
                v-if="unreadCountLocal > 0"
                color="error"
                size="small"
                variant="flat"
                class="ms-2"
              >
                {{ unreadCountLocal }} غير مقروء
              </VChip>
            </div>
            <div class="d-flex align-center gap-2">
              <VBtnToggle
                v-model="filterStatus"
                mandatory
                density="compact"
                variant="outlined"
                divided
                rounded="lg"
              >
                <VBtn value="all" size="small">الكل</VBtn>
                <VBtn value="unread" size="small">غير مقروء</VBtn>
                <VBtn value="read" size="small">مقروء</VBtn>
              </VBtnToggle>
              <VBtn
                v-if="unreadCountLocal > 0"
                variant="tonal"
                color="primary"
                size="small"
                prepend-icon="ri-check-double-line"
                rounded="lg"
                @click="handleMarkAllRead"
              >
                تحديد الكل مقروء
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <!-- Loading -->
          <div v-if="loading" class="text-center py-12">
            <VProgressCircular indeterminate color="primary" size="36" />
            <p class="text-body-2 text-medium-emphasis mt-3">جاري التحميل...</p>
          </div>

          <!-- Empty -->
          <div v-else-if="filteredNotifications.length === 0" class="text-center py-12">
            <VIcon icon="ri-notification-off-line" size="56" color="disabled" class="mb-3" />
            <p class="text-body-1 text-medium-emphasis">لا توجد إشعارات</p>
          </div>

          <!-- Notifications List -->
          <div v-else>
            <div
              v-for="notif in filteredNotifications"
              :key="notif.id"
              class="notification-row d-flex align-center gap-4 pa-4"
              :class="{ 'unread-row': !notif.is_read }"
            >
              <!-- Icon -->
              <VAvatar
                :color="notif.is_read ? 'grey-lighten-2' : 'primary'"
                :variant="notif.is_read ? 'flat' : 'tonal'"
                size="46"
                rounded="lg"
              >
                <VIcon
                  :icon="notif.type === 'order' ? 'ri-shopping-bag-3-line' : 'ri-notification-3-line'"
                  size="22"
                />
              </VAvatar>

              <!-- Content -->
              <div class="flex-grow-1 cursor-pointer" style="min-width: 0;" @click="goToOrder(notif)">
                <div class="d-flex align-center gap-2 mb-1">
                  <span
                    class="text-body-1"
                    :class="notif.is_read ? 'text-medium-emphasis' : 'font-weight-bold'"
                  >
                    {{ notif.title }}
                  </span>
                  <VChip v-if="!notif.is_read" color="primary" size="x-small" variant="flat">
                    جديد
                  </VChip>
                </div>
                <p class="text-body-2 text-medium-emphasis mb-1">{{ notif.body }}</p>
                <span class="text-caption text-disabled">{{ timeAgo(notif.created_at) }}</span>
              </div>

              <!-- Actions -->
              <div class="d-flex align-center gap-1">
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-if="!notif.is_read"
                      v-bind="props"
                      icon="ri-check-line"
                      variant="text"
                      size="small"
                      color="success"
                      @click="handleMarkAsRead(notif)"
                    />
                  </template>
                  تحديد كمقروء
                </VTooltip>
                <VTooltip location="top">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="ri-delete-bin-line"
                      variant="text"
                      size="small"
                      color="error"
                      @click="confirmDelete(notif.id)"
                    />
                  </template>
                  حذف
                </VTooltip>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <VDivider v-if="lastPage > 1" />
          <div v-if="lastPage > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
            <span class="text-caption text-medium-emphasis">
              عرض الصفحة {{ currentPage }} من {{ lastPage }} ({{ total }} إشعار)
            </span>
            <VPagination
              v-model="currentPage"
              :length="lastPage"
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

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg" elevation="0">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-delete-bin-line" size="32" />
        </VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">تأكيد الحذف</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">
        <p class="text-body-1">هل تريد حذف هذا الإشعار؟</p>
      </VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" @click="deleteNotification">نعم، احذفه</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.notification-row {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
  transition: background-color 0.2s ease;
}

.notification-row:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.notification-row:last-child {
  border-bottom: none;
}

.unread-row {
  background-color: rgba(var(--v-theme-primary), 0.03);
  border-inline-start: 3px solid rgb(var(--v-theme-primary));
}
</style>
