<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'

interface Category {
  id: number | null;
  name: string;
  slug?: string;
  sort_order: number;
  is_active: boolean;
  image?: any;
  image_url: string | null;
}

// Using Composable
const { 
  items: categories, 
  loading, 
  currentPage, 
  totalPages, 
  fetchData: fetchCategories,
  handlePageChange
} = usePagination<Category>('/api/admin/categories')

// Dialog variables
const showDialog = ref(false)
const dialogMode = ref('add') // 'add' or 'edit'
const confirmDeleteDialog = ref(false)
const currCategoryId = ref<number | null>(null)

// Image Preview
const imagePreviewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLElement | null>(null)

const formData = ref<Category>({
  id: null,
  name: '',
  sort_order: 0,
  is_active: true,
  image: null,
  image_url: null,
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
  imagePreviewUrl.value = formData.value.image_url // Revert to old image if editing, or null
}

const openAddDialog = () => {
  dialogMode.value = 'add'
  formData.value = { id: null, name: '', sort_order: 0, is_active: true, image: null, image_url: null }
  imagePreviewUrl.value = null
  showDialog.value = true
}

const openEditDialog = (item: Category) => {
  dialogMode.value = 'edit'
  formData.value = { ...item, image: null }
  imagePreviewUrl.value = item.image_url
  showDialog.value = true
}

const saveCategory = async () => {
  const payload = new FormData()
  payload.append('name', formData.value.name)
  payload.append('sort_order', formData.value.sort_order.toString())
  payload.append('is_active', formData.value.is_active ? '1' : '0')
  
  if (formData.value.image instanceof File) {
    payload.append('image', formData.value.image)
  }

  try {
    const url = dialogMode.value === 'add' ? '/api/admin/categories' : `/api/admin/categories/${formData.value.id}`
    if (dialogMode.value === 'edit') {
        payload.append('_method', 'PUT')
    }

    const res = await apiFetch(url, {
      method: 'POST',
      body: payload,
    })

    if (res.ok) {
      showDialog.value = false
      fetchCategories(currentPage.value)
    } else {
        const errorData = await res.json()
        alert('خطأ: ' + JSON.stringify(errorData.errors || errorData.message))
    }
  } catch (error) {
    console.error('Error saving category:', error)
  }
}

const confirmDelete = (id: number | null) => {
  currCategoryId.value = id
  confirmDeleteDialog.value = true
}

const deleteCategory = async () => {
  try {
    const res = await apiFetch(`/api/admin/categories/${currCategoryId.value}`, {
      method: 'DELETE',
    })
    
    if (res.ok) {
      confirmDeleteDialog.value = false
      fetchCategories(currentPage.value)
    } else {
        const errorData = await res.json()
        alert('خطأ أثناء الحذف: ' + (errorData.message || ''))
    }
  } catch (error) {
    console.error('Error deleting category:', error)
  }
}

const toggleActive = async (item: Category) => {
  try {
    const res = await apiFetch(`/api/admin/categories/${item.id}/toggle`, {
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
  fetchCategories(1)
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-layout-grid-line" color="primary" size="20" />
              أقسام المتجر
            </div>
            <VBtn
              color="primary"
              prepend-icon="ri-add-line"
              rounded="lg"
              @click="openAddDialog"
            >
              إضافة قسم جديد
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr class="bg-light">
                <th style="width: 50px;" class="text-center">#</th>
                <th>اسم القسم</th>
                <th>العنوان اللطيف (Slug)</th>
                <th class="text-center">الترتيب</th>
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
              <tr v-else-if="categories.length === 0">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">لا توجد أقسام مضافة بعد</td>
              </tr>
              <tr v-for="(item, index) in categories" :key="item.id || item.name">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td class="font-weight-medium">{{ item.name }}</td>
                <td><VChip size="small" variant="tonal" color="secondary" rounded="sm">{{ item.slug }}</VChip></td>
                <td class="text-center">{{ item.sort_order }}</td>
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
                      <VListItem value="edit" @click="openEditDialog(item)">
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

  <!-- Add/Edit Dialog -->
  <VDialog v-model="showDialog" max-width="500" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold text-h6">{{ dialogMode === 'add' ? 'إضافة قسم جديد' : 'تعديل بيانات القسم' }}</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDialog = false" />
      </VCardTitle>
      
      <VCardText class="pa-6">
        <!-- خفي، لتشغيل الـ File Selection عند الضغط على المربع -->
        <input 
          type="file" 
          ref="fileInputRef" 
          class="d-none" 
          accept="image/*"
          @change="handleImageSelection"
        >

        <VRow>
          <VCol cols="12" class="text-center mb-2">
            <p class="text-body-2 text-medium-emphasis mb-3">صورة القسم</p>
            <div 
              class="image-upload-wrapper mx-auto position-relative" 
              @click="triggerFileInput"
              :class="{'has-image': imagePreviewUrl}"
            >
              <VImg 
                v-if="imagePreviewUrl" 
                :src="imagePreviewUrl" 
                cover 
                class="category-image"
              />
              <div v-else class="upload-placeholder d-flex flex-column align-center justify-center h-100">
                <VIcon icon="ri-image-add-line" size="32" color="secondary" class="mb-2" />
                <span class="text-caption text-secondary">اضغط لاختيار صورة</span>
              </div>

              <!-- Overlay for changing image -->
              <div v-if="imagePreviewUrl" class="image-overlay d-flex align-center justify-center">
                <VIcon icon="ri-camera-switch-line" color="white" size="24" />
              </div>
            </div>
            <VBtn 
              v-if="imagePreviewUrl && formData.image" 
              variant="text" 
              color="error" 
              size="small" 
              class="mt-2"
              @click="removeImage"
            >
              إلغاء الصورة المحددة
            </VBtn>
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="formData.name"
              label="اسم القسم"
              hint="مثال: كاسات، عبوات"
              persistent-hint
              variant="outlined"
              color="primary"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model.number="formData.sort_order"
              label="الترتيب الظاهري"
              type="number"
              variant="outlined"
              color="primary"
            />
          </VCol>
          <VCol cols="12" md="6" class="d-flex align-center">
            <VSwitch
              v-model="formData.is_active"
              label="مرئي في الموبايل"
              color="success"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
      
      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" class="px-5" @click="showDialog = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" rounded="lg" class="px-6" @click="saveCategory">
          {{ dialogMode === 'add' ? 'إضافة القسم' : 'حفظ التعديلات' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

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
        <p class="text-body-1 mb-1">هل أنت متأكد من رغبتك في حذف هذا القسم؟</p>
        <p class="text-caption text-error">لا يمكن التراجع عن هذا الإجراء، ولن يتم الحذف إذا ارتبط بمنتجات.</p>
      </VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" class="px-6" @click="deleteCategory">نعم، احذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.image-upload-wrapper {
  width: 120px;
  height: 120px;
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}

.image-upload-wrapper:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.image-upload-wrapper.has-image {
  border-style: solid;
  border-color: transparent;
}

.category-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.image-upload-wrapper:hover .image-overlay {
  opacity: 1;
}
</style>
