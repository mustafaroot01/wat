<script setup lang="ts">
import { useAdminNotifications } from '@/composables/useAdminNotifications'
import { apiFetch } from '@/utils/apiFetch'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

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
const confirmDeleteAllDialog = ref(false)
const deleteTargetId = ref<number | null>(null)
const deleting = ref(false)

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
  deleting.value = true
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
  } finally {
    deleting.value = false
  }
}

const deleteAllNotifications = async () => {
  deleting.value = true
  try {
    const res = await apiFetch('/api/admin/admin-notifications/delete-all', { method: 'DELETE' })
    if (res.ok) {
      notifications.value = []
      total.value = 0
      confirmDeleteAllDialog.value = false
      fetchUnread()
    }
  } catch (e) {
    console.error('Error deleting all notifications:', e)
  } finally {
    deleting.value = false
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
      <!-- Header Card -->
      <VCard class="mb-4" rounded="lg">
        <VCardText>
          <div class="d-flex align-center justify-space-between flex-wrap gap-4">
            <!-- Title -->
            <div class="d-flex align-center gap-3">
              <VAvatar color="primary" variant="tonal" size="42" rounded="lg">
                <VIcon icon="ri-notification-3-line" size="22" />
              </VAvatar>
              <div>
                <h5 class="text-h6 font-weight-bold mb-0">إشعارات الطلبات</h5>
                <span class="text-caption text-medium-emphasis">{{ total }} إشعار إجمالي</span>
              </div>
              <VChip
                v-if="unreadCountLocal > 0"
                color="error"
                size="small"
                variant="flat"
              >
                {{ unreadCountLocal }} غير مقروء
              </VChip>
            </div>

            <!-- Actions -->
            <div class="d-flex align-center gap-2 flex-wrap">
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
                color="success"
                size="small"
                prepend-icon="ri-check-double-line"
                rounded="lg"
                @click="handleMarkAllRead"
              >
                تحديد الكل مقروء
              </VBtn>

              <VBtn
                v-if="total > 0"
                variant="tonal"
                color="error"
                size="small"
                prepend-icon="ri-delete-bin-line"
                rounded="lg"
                @click="confirmDeleteAllDialog = true"
              >
                حذف الكل
              </VBtn>
            </div>
          </div>
        </VCardText>
      </VCard>

      <!-- Notifications Card -->
      <VCard rounded="lg">
        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
          <VProgressCircular indeterminate color="primary" size="40" />
          <p class="text-body-2 text-medium-emphasis mt-3">جاري التحميل...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredNotifications.length === 0" class="text-center py-16">
          <VAvatar color="grey-lighten-3" size="80" rounded="lg" class="mb-4">
            <VIcon icon="ri-notification-off-line" size="40" color="grey" />
          </VAvatar>
          <h6 class="text-h6 text-medium-emphasis mb-1">لا توجد إشعارات</h6>
          <p class="text-body-2 text-disabled">ستظهر الإشعارات هنا عند استلام طلبات جديدة</p>
        </div>

        <!-- Notifications Table -->
        <VTable v-else hover class="text-no-wrap">
          <thead>
            <tr>
              <th style="width: 50px;" class="text-center">#</th>
              <th>الإشعار</th>
              <th>التفاصيل</th>
              <th>الوقت</th>
              <th>الحالة</th>
              <th class="text-center" style="width: 120px;">الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(notif, index) in filteredNotifications"
              :key="notif.id"
              :class="{ 'unread-row': !notif.is_read }"
              class="cursor-pointer"
              @click="goToOrder(notif)"
            >
              <td class="text-center text-medium-emphasis">
                {{ (currentPage - 1) * 20 + index + 1 }}
              </td>
              <td>
                <div class="d-flex align-center gap-3">
                  <VAvatar
                    :color="notif.is_read ? 'grey-lighten-2' : 'primary'"
                    :variant="notif.is_read ? 'flat' : 'tonal'"
                    size="38"
                    rounded="lg"
                  >
                    <VIcon icon="ri-shopping-bag-3-line" size="18" />
                  </VAvatar>
                  <div>
                    <span
                      class="d-block text-body-2"
                      :class="notif.is_read ? 'text-medium-emphasis' : 'font-weight-bold text-high-emphasis'"
                    >
                      {{ notif.title }}
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-body-2 text-medium-emphasis">{{ notif.body }}</span>
              </td>
              <td>
                <span class="text-caption text-medium-emphasis">{{ timeAgo(notif.created_at) }}</span>
              </td>
              <td>
                <VChip
                  :color="notif.is_read ? 'default' : 'primary'"
                  :variant="notif.is_read ? 'outlined' : 'flat'"
                  size="small"
                  label
                >
                  {{ notif.is_read ? 'مقروء' : 'جديد' }}
                </VChip>
              </td>
              <td class="text-center" @click.stop>
                <VBtn
                  v-if="!notif.is_read"
                  icon="ri-check-line"
                  variant="text"
                  size="x-small"
                  color="success"
                  class="me-1"
                  @click="handleMarkAsRead(notif)"
                />
                <VBtn
                  icon="ri-delete-bin-line"
                  variant="text"
                  size="x-small"
                  color="error"
                  @click="confirmDelete(notif.id)"
                />
              </td>
            </tr>
          </tbody>
        </VTable>

        <!-- Pagination -->
        <VDivider v-if="lastPage > 1" />
        <div v-if="lastPage > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
          <span class="text-caption text-medium-emphasis">
            الصفحة {{ currentPage }} من {{ lastPage }} — ({{ total }} إشعار)
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
      </VCard>
    </VCol>
  </VRow>

  <!-- Delete Single Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg">
      <VCardText class="text-center pt-8 pb-4">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-delete-bin-line" size="32" />
        </VAvatar>
        <h6 class="text-h6 font-weight-bold mb-2">حذف الإشعار</h6>
        <p class="text-body-2 text-medium-emphasis">هل تريد حذف هذا الإشعار؟</p>
      </VCardText>
      <VCardActions class="pa-5 justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" :loading="deleting" @click="deleteNotification">حذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete All Confirmation -->
  <VDialog v-model="confirmDeleteAllDialog" max-width="420">
    <VCard rounded="lg">
      <VCardText class="text-center pt-8 pb-4">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-delete-bin-2-line" size="32" />
        </VAvatar>
        <h6 class="text-h6 font-weight-bold mb-2">حذف جميع الإشعارات</h6>
        <p class="text-body-2 text-medium-emphasis">سيتم حذف جميع الإشعارات ({{ total }} إشعار). هذا الإجراء لا يمكن التراجع عنه.</p>
      </VCardText>
      <VCardActions class="pa-5 justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteAllDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" :loading="deleting" @click="deleteAllNotifications">حذف الكل</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.unread-row {
  background-color: rgba(var(--v-theme-primary), 0.03) !important;
}

.unread-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.06) !important;
}
</style>
