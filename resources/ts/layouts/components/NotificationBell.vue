<script setup lang="ts">
import { useAdminNotifications } from '@/composables/useAdminNotifications'
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const {
  unreadCount,
  latestNotifications,
  markAsRead,
  markAllAsRead,
  startPolling,
  stopPolling,
} = useAdminNotifications()

const menu = ref(false)

const handleNotificationClick = async (notif: any) => {
  if (!notif.is_read) {
    await markAsRead(notif.id)
  }
  menu.value = false
  if (notif.order_id) {
    router.push('/orders')
  }
}

const goToAllNotifications = () => {
  menu.value = false
  router.push('/admin-notifications')
}

const timeAgo = (dateStr: string) => {
  const now = new Date()
  const date = new Date(dateStr)
  const diff = Math.floor((now.getTime() - date.getTime()) / 1000)

  if (diff < 60) return 'الآن'
  if (diff < 3600) return `منذ ${Math.floor(diff / 60)} د`
  if (diff < 86400) return `منذ ${Math.floor(diff / 3600)} س`
  return `منذ ${Math.floor(diff / 86400)} ي`
}

onMounted(() => startPolling(10000))
onUnmounted(() => stopPolling())
</script>

<template>
  <VMenu
    v-model="menu"
    :close-on-content-click="false"
    location="bottom end"
    offset="14"
    min-width="380"
    max-width="420"
  >
    <template #activator="{ props }">
      <IconBtn v-bind="props" class="position-relative">
        <VIcon icon="ri-notification-line" />
        <VBadge
          v-if="unreadCount > 0"
          :content="unreadCount > 99 ? '99+' : unreadCount"
          color="error"
          floating
          class="notification-badge"
        />
      </IconBtn>
    </template>

    <VCard rounded="lg" elevation="8">
      <!-- Header -->
      <VCardItem class="py-3 px-4 bg-primary">
        <template #prepend>
          <VIcon icon="ri-notification-3-line" color="white" size="20" />
        </template>
        <VCardTitle class="text-white text-body-1 font-weight-bold">
          الإشعارات
        </VCardTitle>
        <template #append>
          <VChip
            v-if="unreadCount > 0"
            size="x-small"
            color="white"
            variant="flat"
            class="text-primary font-weight-bold"
          >
            {{ unreadCount }} جديد
          </VChip>
        </template>
      </VCardItem>

      <VDivider />

      <!-- Notifications List -->
      <div style="max-height: 360px; overflow-y: auto;">
        <template v-if="latestNotifications.length > 0">
          <div
            v-for="notif in latestNotifications"
            :key="notif.id"
            class="notification-item d-flex align-start gap-3 pa-4 cursor-pointer"
            :class="{ 'bg-primary-lighten-5': !notif.is_read }"
            @click="handleNotificationClick(notif)"
          >
            <VAvatar
              :color="notif.is_read ? 'grey-lighten-2' : 'primary'"
              :variant="notif.is_read ? 'flat' : 'tonal'"
              size="40"
              rounded="lg"
            >
              <VIcon
                :icon="notif.type === 'order' ? 'ri-shopping-bag-3-line' : 'ri-notification-3-line'"
                size="20"
              />
            </VAvatar>

            <div class="flex-grow-1" style="min-width: 0;">
              <div class="d-flex align-center justify-space-between mb-1">
                <span
                  class="text-body-2 text-truncate"
                  :class="notif.is_read ? 'text-medium-emphasis' : 'font-weight-bold'"
                  style="max-width: 220px;"
                >
                  {{ notif.title }}
                </span>
                <span class="text-caption text-disabled text-no-wrap ms-2">
                  {{ timeAgo(notif.created_at) }}
                </span>
              </div>
              <p class="text-caption text-medium-emphasis mb-0 text-truncate">
                {{ notif.body }}
              </p>
            </div>

            <div v-if="!notif.is_read" class="mt-2">
              <div class="unread-dot"></div>
            </div>
          </div>
          <VDivider
            v-for="(_, i) in latestNotifications.slice(0, -1)"
            :key="'d-' + i"
            style="display: none;"
          />
        </template>

        <div v-else class="text-center py-8">
          <VIcon icon="ri-notification-off-line" size="40" color="disabled" class="mb-2" />
          <p class="text-body-2 text-medium-emphasis mb-0">لا توجد إشعارات جديدة</p>
        </div>
      </div>

      <VDivider />

      <!-- Footer Actions -->
      <VCardActions class="pa-3 d-flex justify-space-between">
        <VBtn
          v-if="unreadCount > 0"
          variant="text"
          color="primary"
          size="small"
          prepend-icon="ri-check-double-line"
          @click="markAllAsRead"
        >
          تحديد الكل كمقروء
        </VBtn>
        <VSpacer />
        <VBtn
          variant="tonal"
          color="primary"
          size="small"
          prepend-icon="ri-list-check-3"
          @click="goToAllNotifications"
        >
          عرض الكل
        </VBtn>
      </VCardActions>
    </VCard>
  </VMenu>
</template>

<style scoped>
.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
}

.notification-item {
  transition: background-color 0.2s ease;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.notification-item:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.04) !important;
}

.notification-item:last-child {
  border-bottom: none;
}

.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: rgb(var(--v-theme-primary));
}
</style>
