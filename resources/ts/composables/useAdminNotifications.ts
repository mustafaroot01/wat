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
let lastKnownCount = -1
let audioCtx: AudioContext | null = null

function unlockAudio() {
  if (audioCtx) return
  try {
    audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)()
    // Play silent buffer to fully unlock
    const buf = audioCtx.createBuffer(1, 1, 22050)
    const src = audioCtx.createBufferSource()
    src.buffer = buf
    src.connect(audioCtx.destination)
    src.start()
  } catch (e) {
    // Ignore
  }
  document.removeEventListener('click', unlockAudio)
  document.removeEventListener('touchstart', unlockAudio)
}

// Unlock audio on first user interaction
document.addEventListener('click', unlockAudio)
document.addEventListener('touchstart', unlockAudio)

function playNotificationSound() {
  if (!audioCtx) return
  if (audioCtx.state === 'suspended') audioCtx.resume()

  try {
    const t = audioCtx.currentTime

    // First tone — C5
    const osc1 = audioCtx.createOscillator()
    const gain1 = audioCtx.createGain()
    osc1.connect(gain1)
    gain1.connect(audioCtx.destination)
    osc1.type = 'sine'
    osc1.frequency.value = 523.25
    gain1.gain.setValueAtTime(0.4, t)
    gain1.gain.exponentialRampToValueAtTime(0.01, t + 0.2)
    osc1.start(t)
    osc1.stop(t + 0.2)

    // Second tone — E5
    const osc2 = audioCtx.createOscillator()
    const gain2 = audioCtx.createGain()
    osc2.connect(gain2)
    gain2.connect(audioCtx.destination)
    osc2.type = 'sine'
    osc2.frequency.value = 659.25
    gain2.gain.setValueAtTime(0.4, t + 0.15)
    gain2.gain.exponentialRampToValueAtTime(0.01, t + 0.4)
    osc2.start(t + 0.15)
    osc2.stop(t + 0.4)

    // Third tone — G5
    const osc3 = audioCtx.createOscillator()
    const gain3 = audioCtx.createGain()
    osc3.connect(gain3)
    gain3.connect(audioCtx.destination)
    osc3.type = 'sine'
    osc3.frequency.value = 783.99
    gain3.gain.setValueAtTime(0.3, t + 0.3)
    gain3.gain.exponentialRampToValueAtTime(0.01, t + 0.6)
    osc3.start(t + 0.3)
    osc3.stop(t + 0.6)
  } catch (e) {
    // Ignore
  }
}

async function fetchUnread() {
  try {
    const res = await apiFetch('/api/admin/admin-notifications/unread')
    if (res.ok) {
      const data = await res.json()
      const newCount = data.unread_count ?? 0

      // Play sound if new notifications arrived (skip first load)
      if (lastKnownCount >= 0 && newCount > lastKnownCount) {
        playNotificationSound()
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
