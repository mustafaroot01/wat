<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'

interface Brand {
  id: number | null;
  name: string;
  slug?: string;
  sort_order: number;
  is_active: boolean;
  image?: any;
  image_url: string | null;
  description?: string;
}

// Using Composable
const { 
  items: brands, 
  loading,
  totalItems,
  fetchData: fetchBrands,
  handleOptionsChange,
} = usePagination<Brand>('/api/admin/brands')

const headers = [
  { title: 'اسم الشركة', key: 'name',       align: 'start'  as const, sortable: true  },
  { title: 'الترتيب',    key: 'sort_order', align: 'center' as const, sortable: true  },
  { title: 'الحالة',     key: 'is_active',  align: 'center' as const, sortable: false },
  { title: 'الإجراءات',  key: 'actions',    align: 'center' as const, sortable: false },
]

// Dialog variables
const showDialog = ref(false)
const dialogMode = ref('add') // 'add' or 'edit'
const confirmDeleteDialog = ref(false)
const currBrandId = ref<number | null>(null)

// Image Preview
const imagePreviewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLElement | null>(null)

const formData = ref<Brand>({
  id: null,
  name: '',
  sort_order: 0,
  is_active: true,
  image: null,
  image_url: null,
  description: '',
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
  imagePreviewUrl.value = formData.value.image_url 
}

const openAddDialog = () => {
  dialogMode.value = 'add'
  formData.value = { id: null, name: '', sort_order: 0, is_active: true, image: null, image_url: null, description: '' }
  imagePreviewUrl.value = null
  showDialog.value = true
}

const openEditDialog = (item: Brand) => {
  dialogMode.value = 'edit'
  formData.value = { ...item, image: null }
  imagePreviewUrl.value = item.image_url
  showDialog.value = true
}

const saveBrand = async () => {
  const payload = new FormData()
  payload.append('name', formData.value.name)
  payload.append('sort_order', formData.value.sort_order.toString())
  payload.append('is_active', formData.value.is_active ? '1' : '0')
  payload.append('description', formData.value.description || '')
  
  if (formData.value.image instanceof File) {
    payload.append('image', formData.value.image)
  }

  try {
    const url = dialogMode.value === 'add' ? '/api/admin/brands' : `/api/admin/brands/${formData.value.id}`
    if (dialogMode.value === 'edit') {
        payload.append('_method', 'PUT')
    }

    const res = await apiFetch(url, {
      method: 'POST',
      body: payload,
    })

    if (res.ok) {
      showDialog.value = false
      fetchBrands(currentPage.value)
    } else {
        const errorData = await res.json()
        alert('حدث خطأ: ' + JSON.stringify(errorData.errors || errorData.message))
    }
  } catch (error) {
    console.error('Error saving brand:', error)
  }
}

const confirmDelete = (id: number | null) => {
  currBrandId.value = id
  confirmDeleteDialog.value = true
}

const deleteBrand = async () => {
  try {
    const res = await apiFetch(`/api/admin/brands/${currBrandId.value}`, {
      method: 'DELETE',
    })
    
    if (res.ok) {
      confirmDeleteDialog.value = false
      fetchBrands(currentPage.value)
    } else {
        const errorData = await res.json()
        alert('خطأ: ' + (errorData.message || 'لا يمكن الحذف لارتباطه بمنتجات'))
    }
  } catch (error) {
    console.error('Error deleting brand:', error)
  }
}

const toggleActive = async (item: Brand) => {
  try {
    const res = await apiFetch(`/api/admin/brands/${item.id}/toggle`, {
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

onMounted(() => {
  fetchBrands(1)
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-verified-badge-line" color="primary" size="20" />
              العلامات التجارية (الشركات)
            </div>
            <VBtn
              color="primary"
              prepend-icon="ri-add-line"
              rounded="lg"
              @click="openAddDialog"
            >
              إضافة شركة جديدة
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VDataTableServer
          :headers="headers"
          :items="brands"
          :items-length="totalItems"
          :loading="loading"
          :items-per-page="15"
          :items-per-page-options="[15, 25, 50]"
          no-data-text="لا توجد شركات مضافة بعد"
          loading-text="جاري التحميل..."
          class="rounded-0"
          @update:options="handleOptionsChange"
        >
          <template #item.name="{ item }">
            <div class="d-flex align-center gap-3 py-1">
              <VAvatar v-if="item.image_url" :image="item.image_url" size="40" rounded="lg" border />
              <VAvatar v-else color="surface-variant" size="40" rounded="lg">
                <VIcon icon="ri-building-line" size="20" />
              </VAvatar>
              <span class="font-weight-medium">{{ item.name }}</span>
            </div>
          </template>

          <template #item.sort_order="{ item }">
            <span class="text-medium-emphasis">{{ item.sort_order }}</span>
          </template>

          <template #item.is_active="{ item }">
            <VSwitch v-model="item.is_active" color="success" density="compact" hide-details
              class="d-inline-flex justify-center" @change="toggleActive(item)" />
          </template>

          <template #item.actions="{ item }">
            <VMenu location="bottom end">
              <template #activator="{ props }">
                <VBtn icon="ri-more-2-fill" variant="text" size="small" color="secondary" v-bind="props" />
              </template>
              <VList density="compact" min-width="150" rounded="lg" elevation="3">
                <VListItem @click="openEditDialog(item)">
                  <template #prepend><VIcon icon="ri-pencil-line" size="18" class="me-2 text-primary" /></template>
                  <VListItemTitle>تعديل</VListItemTitle>
                </VListItem>
                <VListItem @click="confirmDelete(item.id)">
                  <template #prepend><VIcon icon="ri-delete-bin-line" size="18" class="me-2 text-error" /></template>
                  <VListItemTitle class="text-error">حذف</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </template>
        </VDataTableServer>

      </VCard>
    </VCol>
  </VRow>

  <!-- Add/Edit Dialog -->
  <VDialog v-model="showDialog" max-width="500" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold text-h6">{{ dialogMode === 'add' ? 'إضافة شركة جديدة' : 'تعديل بيانات الشركة' }}</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDialog = false" />
      </VCardTitle>
      
      <VCardText class="pa-6">
        <input 
          type="file" 
          ref="fileInputRef" 
          class="d-none" 
          accept="image/*"
          @change="handleImageSelection"
        >

        <VRow>
          <VCol cols="12" class="text-center mb-2">
            <p class="text-body-2 text-medium-emphasis mb-3">شعار الشركة</p>
            <div 
              class="image-upload-wrapper mx-auto position-relative" 
              @click="triggerFileInput"
              :class="{'has-image': imagePreviewUrl}"
            >
              <VImg 
                v-if="imagePreviewUrl" 
                :src="imagePreviewUrl" 
                cover 
                class="brand-image"
              />
              <div v-else class="upload-placeholder d-flex flex-column align-center justify-center h-100">
                <VIcon icon="ri-camera-line" size="32" color="secondary" class="mb-2" />
                <span class="text-caption text-secondary">اضغط لاختيار شعار</span>
              </div>
              <div v-if="imagePreviewUrl" class="image-overlay d-flex align-center justify-center">
                <VIcon icon="ri-camera-switch-line" color="white" size="24" />
              </div>
            </div>
            <VBtn v-if="imagePreviewUrl && formData.image" variant="text" color="error" size="small" class="mt-2" @click="removeImage">إلغاء</VBtn>
          </VCol>

          <VCol cols="12">
            <VTextField v-model="formData.name" label="اسم الشركة" variant="outlined" color="primary" />
          </VCol>
          <VCol cols="12">
            <VTextarea v-model="formData.description" label="وصف الشركة (اختياري)" rows="2" variant="outlined" color="primary" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model.number="formData.sort_order" label="الترتيب" type="number" variant="outlined" color="primary" />
          </VCol>
          <VCol cols="12" md="6" class="d-flex align-center">
            <VSwitch v-model="formData.is_active" label="نشط" color="success" hide-details />
          </VCol>
        </VRow>
      </VCardText>
      
      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" @click="showDialog = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" rounded="lg" class="px-6" @click="saveBrand">حفظ</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg" elevation="0">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4"><VIcon icon="ri-error-warning-line" size="32" /></VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">تأكيد الحذف</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">هذا الإجراء سيقوم بحذف الشركة من النظام بشكل كامل.</VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" @click="deleteBrand">حذف نهائي</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.image-upload-wrapper {
  width: 120px;
  height: 120px;
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}
.image-upload-wrapper:hover { border-color: rgb(var(--v-theme-primary)); background: rgba(var(--v-theme-primary), 0.04); }
.image-upload-wrapper.has-image { border-style: solid; border-color: transparent; }
.brand-image { width: 100%; height: 100%; object-fit: cover; }
.image-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); opacity: 0; transition: opacity 0.2s ease; }
.image-upload-wrapper:hover .image-overlay { opacity: 1; }
</style>
