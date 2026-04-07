<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'

const {
  items: banners,
  loading,
  currentPage,
  totalPages,
  fetchData: fetchBanners,
  handlePageChange
} = usePagination<any>('/api/admin/banners')

const confirmDeleteDialog = ref(false)
const currBannerId = ref<number | null>(null)

const confirmDelete = (id: number | null) => {
  currBannerId.value = id
  confirmDeleteDialog.value = true
}

const deleteBanner = async () => {
  try {
    const res = await fetch(`/api/admin/banners/${currBannerId.value}`, {
      method: 'DELETE',
    })
    
    if (res.ok) {
      confirmDeleteDialog.value = false
      fetchBanners(currentPage.value)
    } else {
        alert('خطأ أثناء الحذف')
    }
  } catch (error) {
    console.error('Error deleting banner:', error)
  }
}

const toggleActive = async (item: any) => {
  try {
    const res = await fetch(`/api/admin/banners/${item.id}/toggle`, {
      method: 'PATCH',
    })
    
    if (!res.ok) {
      item.is_active = !item.is_active
    }
  } catch (error) {
    console.error('Error toggling active:', error)
    item.is_active = !item.is_active
  }
}

const getTypeLabel = (type: string) => {
    switch (type) {
        case 'none': return 'إعلان فقط'
        case 'link': return 'رابط خارجي'
        case 'category': return 'قسم'
        case 'product': return 'منتج'
        default: return 'غير معروف'
    }
}

onMounted(() => {
  fetchBanners(1)
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-advertisement-fill" color="primary" size="20" />
              البانرات الإعلانية
            </div>
            <VBtn
              color="primary"
              prepend-icon="ri-add-line"
              rounded="lg"
              to="/banners/create"
            >
              إضافة إعلان جديد
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr class="bg-light">
                <th style="width: 50px;" class="text-center">#</th>
                <th style="width: 250px;">الصورة</th>
                <th>نوع الإعلان</th>
                <th>التسلسل</th>
                <th class="text-center">الحالة</th>
                <th class="text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">
                  <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                  جاري التحميل...
                </td>
              </tr>
              <tr v-else-if="banners.length === 0">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">لا توجد إعلانات مضافة بعد</td>
              </tr>
              <tr v-for="(item, index) in banners" :key="item.id">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td>
                  <VCard border class="my-2 rounded d-flex align-center justify-center bg-surface-variant overflow-hidden" 
                         width="180" height="60" elevation="0">
                    <VImg v-if="item.image_url || item.image" :src="item.image_url || item.image" cover />
                    <VIcon v-else icon="ri-image-line" size="24" color="secondary" />
                  </VCard>
                </td>
                <td><VChip size="small" variant="tonal" color="primary">{{ getTypeLabel(item.type) }}</VChip></td>
                <td class="font-weight-medium text-medium-emphasis">{{ item.sort_order ?? 0 }}</td>
                <td class="text-center">
                  <VSwitch
                    v-model="item.is_active"
                    color="success"
                    density="compact"
                    hide-details
                    class="d-inline-flex justify-center"
                    @change="toggleActive(item)"
                  />
                </td>
                <td class="text-center">
                  <VMenu location="bottom end">
                    <template #activator="{ props }">
                      <VBtn icon="ri-more-2-fill" variant="text" size="small" color="secondary" v-bind="props" />
                    </template>
                    <VList density="compact" min-width="150" rounded="lg" elevation="3">
                      <VListItem value="edit" :to="`/banners/${item.id}/edit`">
                        <template #prepend><VIcon icon="ri-pencil-line" size="18" class="me-2 text-primary" /></template>
                        <VListItemTitle>تعديل</VListItemTitle>
                      </VListItem>
                      <VListItem value="delete" @click="confirmDelete(item.id)">
                        <template #prepend><VIcon icon="ri-delete-bin-line" size="18" class="me-2 text-error" /></template>
                        <VListItemTitle class="text-error">حذف</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
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

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg" elevation="0">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-error-warning-line" size="32" />
        </VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">تأكيد الحذف</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">
        <p class="text-body-1 mb-1">هل أنت متأكد من رغبتك في حذف هذا البانر؟</p>
      </VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" @click="deleteBanner">نعم، احذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
