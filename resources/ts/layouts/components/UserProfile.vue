<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import avatar1 from '@images/avatars/avatar-1.png'

const router = useRouter()

const adminName = computed(() => {
  try {
    const data = JSON.parse(localStorage.getItem('userData') || '{}')
    return data.name || 'المدير'
  } catch {
    return 'المدير'
  }
})

const logout = () => {
  localStorage.removeItem('accessToken')
  localStorage.removeItem('userData')
  router.push('/login')
}
</script>

<template>
  <VBadge dot location="bottom right" offset-x="3" offset-y="3" color="success" bordered>
    <VAvatar class="cursor-pointer" color="primary" variant="tonal">
      <VImg :src="avatar1" />

      <VMenu activator="parent" width="220" location="bottom end" offset="14px">
        <VList>
          <!-- Admin Info -->
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge dot location="bottom right" offset-x="3" offset-y="3" color="success">
                  <VAvatar color="primary" variant="tonal">
                    <VImg :src="avatar1" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>
            <VListItemTitle class="font-weight-semibold">{{ adminName }}</VListItemTitle>
            <VListItemSubtitle>مدير النظام</VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <!-- Logout -->
          <VListItem link @click="logout">
            <template #prepend>
              <VIcon class="me-2" icon="ri-logout-box-r-line" size="22" color="error" />
            </template>
            <VListItemTitle class="text-error">تسجيل الخروج</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
