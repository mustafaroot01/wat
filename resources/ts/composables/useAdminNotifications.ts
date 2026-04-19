import { apiFetch } from '@/utils/apiFetch'
import { ref } from 'vue'

export interface AdminNotification {
  id: number
  title: string
  body: string
  type: string
  order_id: number | null
  is_read: boolean
  created_at: string
}

const unreadCount = ref(0)
const latestNotifications = ref<AdminNotification[]>([])
const loading = ref(false)
let pollInterval: ReturnType<typeof setInterval> | null = null
let lastKnownCount = 0
function playNotificationSound() {
  try {
    const ctx = new (window.AudioContext || (window as any).webkitAudioContext)()
    const oscillator = ctx.createOscillator()
    const gain = ctx.createGain()

    oscillator.connect(gain)
    gain.connect(ctx.destination)

    oscillator.type = 'sine'
    gain.gain.setValueAtTime(0.3, ctx.currentTime)

    // Two-tone chime: C5 → E5
    oscillator.frequency.setValueAtTime(523.25, ctx.currentTime)       // C5
    oscillator.frequency.setValueAtTime(659.25, ctx.currentTime + 0.15) // E5
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4)

    oscillator.start(ctx.currentTime)
    oscillator.stop(ctx.currentTime + 0.4)
  } catch (e) {
    // Browser may block AudioContext until user interaction
  }
}

async function fetchUnread() {
  try {
    const res = await apiFetch('/api/admin/admin-notifications/unread')
    if (res.ok) {
      const data = await res.json()
      const newCount = data.unread_count ?? 0

      // Play sound if new notifications arrived
      if (newCount > lastKnownCount && lastKnownCount >= 0) {
        try {
          playNotificationSound()
        } catch (e) {
          // Browser may block autoplay until user interaction
        }
      }

      lastKnownCount = newCount
      unreadCount.value = newCount
      latestNotifications.value = data.notifications ?? []
    }
  } catch (e) {
    // Silently fail — don't break the UI
  }
}

async function markAsRead(id: number) {
  try {
    const res = await apiFetch(`/api/admin/admin-notifications/${id}/read`, { method: 'PATCH' })
    if (res.ok) {
      const notif = latestNotifications.value.find(n => n.id === id)
      if (notif) notif.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
      lastKnownCount = unreadCount.value
    }
  } catch (e) {
    console.error('Failed to mark notification as read:', e)
  }
}

async function markAllAsRead() {
  try {
    const res = await apiFetch('/api/admin/admin-notifications/mark-all-read', { method: 'POST' })
    if (res.ok) {
      latestNotifications.value.forEach(n => (n.is_read = true))
      unreadCount.value = 0
      lastKnownCount = 0
    }
  } catch (e) {
    console.error('Failed to mark all as read:', e)
  }
}

function startPolling(intervalMs = 30000) {
  // Initial fetch
  fetchUnread()

  // Set up interval
  if (!pollInterval) {
    pollInterval = setInterval(fetchUnread, intervalMs)
  }
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

export function useAdminNotifications() {
  return {
    unreadCount,
    latestNotifications,
    loading,
    fetchUnread,
    markAsRead,
    markAllAsRead,
    startPolling,
    stopPolling,
  }
}
