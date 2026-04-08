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
  totalItems,
  fetchData: fetchCategories,
  handleOptionsChange,
} = usePagination<Category>('/api/admin/categories')

const headers = [
  { title: 'اسم القسم', key: 'name',       align: 'start'  as const, sortable: true  },
  { title: 'Slug',       key: 'slug',       align: 'start'  as const, sortable: true  },
  { title: 'الترتيب',    key: 'sort_order', align: 'center' as const, sortable: true  },
  { title: 'الحالة',     key: 'is_active',  align: 'center' as const, sortable: false },
  { title: 'الإجراءات',  key: 'actions',    align: 'center' as const, sortable: false },
]

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
      loadCategories(1)
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
      loadCategories(1)
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

// ─── Filters Management ───────────────────────────────────────────
interface CategoryFilterItem {
  id: number | null
  category_id: number
  name: string
  sort_order: number
  is_active: boolean
}

const showFiltersDialog   = ref(false)
const selectedCategory    = ref<Category | null>(null)
const filters             = ref<CategoryFilterItem[]>([])
const filtersLoading      = ref(false)
const showFilterForm      = ref(false)
const filterDialogMode    = ref<'add' | 'edit'>('add')
const filterForm          = ref<CategoryFilterItem>({ id: null, category_id: 0, name: '', sort_order: 0, is_active: true })

const openFiltersDialog = async (category: Category) => {
  selectedCategory.value = category
  showFiltersDialog.value = true
  showFilterForm.value = false
  await loadFilters(category.id!)
}

const loadFilters = async (categoryId: number) => {
  filtersLoading.value = true
  try {
    const res = await apiFetch(`/api/admin/category-filters?category_id=${categoryId}`)
    if (res.ok) {
      const data = await res.json()
      filters.value = data.data || data
    }
  } catch (e) { console.error(e) } finally {
    filtersLoading.value = false
  }
}

const openAddFilter = () => {
  filterDialogMode.value = 'add'
  filterForm.value = { id: null, category_id: selectedCategory.value!.id!, name: '', sort_order: 0, is_active: true }
  showFilterForm.value = true
}

const openEditFilter = (f: CategoryFilterItem) => {
  filterDialogMode.value = 'edit'
  filterForm.value = { ...f }
  showFilterForm.value = true
}

const saveFilter = async () => {
  const url    = filterDialogMode.value === 'add' ? '/api/admin/category-filters' : `/api/admin/category-filters/${filterForm.value.id}`
  const method = filterDialogMode.value === 'add' ? 'POST' : 'PUT'
  try {
    const res = await apiFetch(url, { method, body: JSON.stringify(filterForm.value) })
    if (res.ok) {
      showFilterForm.value = false
      await loadFilters(selectedCategory.value!.id!)
    }
  } catch (e) { console.error(e) }
}

const deleteFilter = async (id: number) => {
  if (!confirm('هل أنت متأكد من حذف هذا الفلتر؟')) return
  try {
    const res = await apiFetch(`/api/admin/category-filters/${id}`, { method: 'DELETE' })
    if (res.ok) await loadFilters(selectedCategory.value!.id!)
  } catch (e) { console.error(e) }
}

const toggleFilterActive = async (f: CategoryFilterItem) => {
  await apiFetch(`/api/admin/category-filters/${f.id}/toggle`, { method: 'PATCH' })
  await loadFilters(selectedCategory.value!.id!)
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

        <VDataTableServer
          :headers="headers"
          :items="categories"
          :items-length="totalItems"
          :loading="loading"
          :items-per-page="15"
          :items-per-page-options="[15, 25, 50]"
          no-data-text="لا توجد أقسام مضافة بعد"
          loading-text="جاري التحميل..."
          class="rounded-0"
          @update:options="handleOptionsChange"
        >
          <template #item.name="{ item }">
            <div class="d-flex align-center gap-3 py-1">
              <VAvatar v-if="item.image_url" :image="item.image_url" size="36" rounded="lg" border />
              <VAvatar v-else color="primary" variant="tonal" size="36" rounded="lg">
                <VIcon icon="ri-layout-grid-line" size="18" />
              </VAvatar>
              <span class="font-weight-medium">{{ item.name }}</span>
            </div>
          </template>

          <template #item.slug="{ item }">
            <VChip size="small" variant="tonal" color="secondary" rounded="sm">{{ item.slug }}</VChip>
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
                <VListItem @click="openFiltersDialog(item)">
                  <template #prepend><VIcon icon="ri-filter-3-line" size="18" class="me-2 text-info" /></template>
                  <VListItemTitle class="text-info">إدارة الفلاتر</VListItemTitle>
                </VListItem>
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

  <!-- Filters Management Dialog -->
  <VDialog v-model="showFiltersDialog" max-width="560" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-filter-3-line" color="info" />
          <span class="font-weight-bold text-h6">فلاتر قسم: {{ selectedCategory?.name }}</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showFiltersDialog = false; showFilterForm = false" />
      </VCardTitle>

      <VCardText class="pa-5">
        <!-- Filter Form -->
        <VExpandTransition>
          <div v-if="showFilterForm" class="mb-5">
            <VCard variant="tonal" color="info" rounded="lg" class="pa-4">
              <p class="text-body-2 font-weight-bold mb-3">
                {{ filterDialogMode === 'add' ? 'إضافة فلتر جديد' : 'تعديل الفلتر' }}
              </p>
              <VRow dense>
                <VCol cols="12" md="7">
                  <VTextField
                    v-model="filterForm.name"
                    label="اسم الفلتر"
                    placeholder="مثال: 180 مل، 500 مل"
                    variant="outlined"
                    density="compact"
                    bg-color="white"
                    hide-details
                  />
                </VCol>
                <VCol cols="6" md="3">
                  <VTextField
                    v-model.number="filterForm.sort_order"
                    label="الترتيب"
                    type="number"
                    variant="outlined"
                    density="compact"
                    bg-color="white"
                    hide-details
                  />
                </VCol>
                <VCol cols="6" md="2" class="d-flex align-center">
                  <VSwitch
                    v-model="filterForm.is_active"
                    label="نشط"
                    color="success"
                    density="compact"
                    hide-details
                  />
                </VCol>
              </VRow>
              <div class="d-flex gap-2 mt-3">
                <VBtn color="info" size="small" rounded="lg" @click="saveFilter">
                  {{ filterDialogMode === 'add' ? 'إضافة' : 'حفظ' }}
                </VBtn>
                <VBtn variant="text" size="small" rounded="lg" @click="showFilterForm = false">إلغاء</VBtn>
              </div>
            </VCard>
          </div>
        </VExpandTransition>

        <!-- Filters List -->
        <div v-if="filtersLoading" class="text-center py-6">
          <VProgressCircular indeterminate color="info" size="24" />
        </div>
        <div v-else-if="filters.length === 0" class="text-center py-6 text-medium-emphasis">
          <VIcon icon="ri-filter-off-line" size="40" class="mb-2 d-block" />
          لا توجد فلاتر لهذا القسم بعد
        </div>
        <VList v-else density="compact" rounded="lg" border>
          <VListItem
            v-for="(f, i) in filters"
            :key="f.id"
            :class="{'border-t': i > 0}"
          >
            <template #prepend>
              <VIcon icon="ri-filter-line" size="16" class="me-2 text-medium-emphasis" />
            </template>
            <VListItemTitle class="font-weight-medium">{{ f.name }}</VListItemTitle>
            <VListItemSubtitle>ترتيب: {{ f.sort_order }}</VListItemSubtitle>
            <template #append>
              <div class="d-flex align-center gap-1">
                <VSwitch
                  :model-value="f.is_active"
                  color="success"
                  density="compact"
                  hide-details
                  class="me-1"
                  @change="toggleFilterActive(f)"
                />
                <VBtn icon="ri-pencil-line" variant="text" size="x-small" color="primary" @click="openEditFilter(f)" />
                <VBtn icon="ri-delete-bin-line" variant="text" size="x-small" color="error" @click="deleteFilter(f.id!)" />
              </div>
            </template>
          </VListItem>
        </VList>
      </VCardText>

      <VCardActions class="pa-5 border-t">
        <VBtn
          v-if="!showFilterForm"
          color="info"
          variant="tonal"
          prepend-icon="ri-add-line"
          rounded="lg"
          @click="openAddFilter"
        >
          إضافة فلتر
        </VBtn>
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" @click="showFiltersDialog = false; showFilterForm = false">إغلاق</VBtn>
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
