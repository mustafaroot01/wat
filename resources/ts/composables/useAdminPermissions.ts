import { ref } from 'vue'
import { apiFetch } from '@/utils/apiFetch'

interface AdminInfo {
  id: number
  name: string
  email: string
  is_super_admin: boolean
  permissions: string[]
}

const adminInfo = ref<AdminInfo | null>(null)
const permissionsLoaded = ref(false)

export function useAdminPermissions() {

  const fetchPermissions = async () => {
    try {
      const res = await apiFetch('/api/admin/me')
      if (res.ok) {
        adminInfo.value = await res.json()
        permissionsLoaded.value = true
      }
    } catch (e) {
      console.error('Failed to fetch admin permissions', e)
    }
  }

  const can = (permission: string): boolean => {
    if (!adminInfo.value) return false
    if (adminInfo.value.is_super_admin) return true
    return adminInfo.value.permissions.includes(permission)
  }

  const isSuperAdmin = (): boolean => {
    return adminInfo.value?.is_super_admin ?? false
  }

  const clearPermissions = () => {
    adminInfo.value = null
    permissionsLoaded.value = false
  }

  return {
    adminInfo,
    permissionsLoaded,
    fetchPermissions,
    can,
    isSuperAdmin,
    clearPermissions,
  }
}
