<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'

interface Product {
  id: number | null;
  category_id: number | null;
  filter_id: number | null;
  brand_id: number | null;
  name: string;
  description: string;
  price: number;
  is_active: boolean;
  sort_order: number;
  image?: any;
  image_url: string | null;
  category?: { name: string };
  brand?: { name: string };
  filter?: { name: string };
}

// Using Composable
const { 
  items: products, 
  loading, 
  currentPage, 
  totalPages, 
  fetchData: fetchProducts,
  handlePageChange
} = usePagination<Product>('/api/admin/products')

// Options for selects
const categories = ref<any[]>([])
const brands     = ref<any[]>([])
const filters    = ref<any[]>([])
const filtersLoading = ref(false)

const loadFiltersForCategory = async (categoryId: number | null) => {
  filters.value = []
  if (!categoryId) return
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

// Dialog variables
const showDialog = ref(false)
const dialogMode = ref('add')
const confirmDeleteDialog = ref(false)
const currProductId = ref<number | null>(null)

// Image Preview
const imagePreviewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLElement | null>(null)

const formData = ref<Product>({
  id: null,
  category_id: null,
  filter_id: null,
  brand_id: null,
  name: '',
  description: '',
  price: 0,
  is_active: true,
  sort_order: 0,
  image: null,
  image_url: null,
})

watch(() => formData.value.category_id, (newId) => {
  formData.value.filter_id = null
  loadFiltersForCategory(newId)
})

const fetchOptions = async () => {
  try {
    const [catRes, brandRes] = await Promise.all([
      apiFetch('/api/admin/categories?per_page=100'),
      apiFetch('/api/admin/brands?per_page=100')
    ])
    if (catRes.ok) categories.value = (await catRes.json()).data || []
    if (brandRes.ok) brands.value = (await brandRes.json()).data || []
  } catch (e) {
    console.error('Error fetching options:', e)
  }
}

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
  formData.value = { 
    id: null, category_id: null, filter_id: null, brand_id: null, name: '', 
    description: '', price: 0, is_active: true, sort_order: 0, 
    image: null, image_url: null 
  }
  filters.value = []
  imagePreviewUrl.value = null
  showDialog.value = true
}

const openEditDialog = (item: Product) => {
  dialogMode.value = 'edit'
  formData.value = { ...item, image: null }
  imagePreviewUrl.value = item.image_url
  if (item.category_id) loadFiltersForCategory(item.category_id)
  showDialog.value = true
}

const saveProduct = async () => {
  const payload = new FormData()
  payload.append('category_id', String(formData.value.category_id || ''))
  if (formData.value.filter_id) payload.append('filter_id', String(formData.value.filter_id))
  payload.append('brand_id', String(formData.value.brand_id || ''))
  payload.append('name', formData.value.name)
  payload.append('description', formData.value.description || '')
  payload.append('price', String(formData.value.price))
  payload.append('is_active', formData.value.is_active ? '1' : '0')
  payload.append('sort_order', String(formData.value.sort_order))
  
  if (formData.value.image instanceof File) {
    payload.append('image', formData.value.image)
  }

  try {
    const url = dialogMode.value === 'add' ? '/api/admin/products' : `/api/admin/products/${formData.value.id}`
    if (dialogMode.value === 'edit') {
        payload.append('_method', 'PUT')
    }

    const res = await apiFetch(url, {
      method: 'POST',
      body: payload,
    })

    if (res.ok) {
      showDialog.value = false
      fetchProducts(currentPage.value)
    } else {
        const errorData = await res.json()
        alert('حدث خطأ: ' + JSON.stringify(errorData.errors || errorData.message))
    }
  } catch (error) {
    console.error('Error saving product:', error)
  }
}

const confirmDelete = (id: number | null) => {
  currProductId.value = id
  confirmDeleteDialog.value = true
}

const deleteProduct = async () => {
  try {
    const res = await apiFetch(`/api/admin/products/${currProductId.value}`, {
      method: 'DELETE',
    })
    
    if (res.ok) {
      confirmDeleteDialog.value = false
      fetchProducts(currentPage.value)
    }
  } catch (error) {
    console.error('Error deleting product:', error)
  }
}

const toggleActive = async (item: Product) => {
  try {
    const res = await apiFetch(`/api/admin/products/${item.id}/toggle`, {
      method: 'PATCH',
    })
    if (!res.ok) item.is_active = !item.is_active
  } catch (error) {
    console.error('Error toggling active:', error)
    item.is_active = !item.is_active
  }
}

onMounted(() => {
  fetchProducts(1)
  fetchOptions()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-water-flash-line" color="primary" size="20" />
              قائمة المنتجات
            </div>
            <VBtn
              color="primary"
              prepend-icon="ri-add-line"
              rounded="lg"
              @click="openAddDialog"
            >
              إضافة منتج جديد
            </VBtn>
          </VCardTitle>
        </VCardItem>
        <VDivider />

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr class="bg-light">
                <th style="width: 50px;" class="text-center">#</th>
                <th>المنتج</th>
                <th>التصنيف / الشركة</th>
                <th>السعر</th>
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
              <tr v-else-if="products.length === 0">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">لا توجد منتجات مضافة بعد</td>
              </tr>
              <tr v-for="(item, index) in products" :key="item.id || index">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td class="font-weight-medium">
                  <div class="d-flex align-center gap-3">
                    <VAvatar v-if="item.image_url" :image="item.image_url" size="40" rounded="lg" border />
                    <VAvatar v-else color="surface-variant" size="40" rounded="lg">
                      <VIcon icon="ri-image-line" size="20" />
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span>{{ item.name }}</span>
                      <span class="text-caption text-medium-emphasis">{{ item.description?.substring(0, 30) }}...</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <VChip size="x-small" color="primary" variant="tonal" class="w-fit">{{ item.category?.name || 'بدون تصنيف' }}</VChip>
                    <VChip size="x-small" color="secondary" variant="outlined" class="w-fit" v-if="item.brand?.name">
                      <VIcon icon="ri-verified-badge-line" start size="12" />
                      {{ item.brand?.name }}
                    </VChip>
                  </div>
                </td>
                <td class="font-weight-bold text-primary">{{ item.price }} د.ع</td>
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
  <VDialog v-model="showDialog" max-width="600" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold text-h6">{{ dialogMode === 'add' ? 'إضافة منتج جديد' : 'تعديل بيانات المنتج' }}</span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDialog = false" />
      </VCardTitle>
      
      <VCardText class="pa-6">
        <input type="file" ref="fileInputRef" class="d-none" accept="image/*" @change="handleImageSelection">

        <VRow spacing="4">
          <VCol cols="12" class="text-center mb-4">
            <div 
              class="image-upload-wrapper mx-auto position-relative" 
              @click="triggerFileInput"
              :class="{'has-image': imagePreviewUrl}"
            >
              <VImg v-if="imagePreviewUrl" :src="imagePreviewUrl" cover class="product-image-preview" />
              <div v-else class="upload-placeholder d-flex flex-column align-center justify-center h-100">
                <VIcon icon="ri-image-add-line" size="32" color="secondary" class="mb-2" />
                <span class="text-caption text-secondary">صورة المنتج</span>
              </div>
            </div>
          </VCol>

          <VCol cols="12" md="6">
            <VSelect
              v-model="formData.category_id"
              :items="categories"
              item-title="name"
              item-value="id"
              label="التصنيف الرئيسي"
              placeholder="اختر التصنيف"
              variant="outlined"
              color="primary"
            />
          </VCol>

          <VCol cols="12" md="6">
            <VSelect
              v-model="formData.filter_id"
              :items="filters"
              item-title="name"
              item-value="id"
              label="الفلتر / التصنيف الفرعي"
              placeholder="اختر الفلتر"
              variant="outlined"
              color="info"
              clearable
              :disabled="!formData.category_id || filtersLoading"
              :loading="filtersLoading"
              :hint="!formData.category_id ? 'اختر التصنيف أولاً' : (filters.length === 0 ? 'لا توجد فلاتر لهذا التصنيف' : '')"
              persistent-hint
            />
          </VCol>

          <VCol cols="12" md="6">
            <VSelect
              v-model="formData.brand_id"
              :items="brands"
              item-title="name"
              item-value="id"
              label="الشركة (اختياري)"
              placeholder="اختر الشركة"
              variant="outlined"
              color="secondary"
              clearable
            />
          </VCol>

          <VCol cols="12">
            <VTextField v-model="formData.name" label="اسم المنتج" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12">
            <VTextarea v-model="formData.description" label="وصف المنتج" rows="2" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField v-model.number="formData.price" label="السعر (د.ع)" type="number" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12" md="6">
             <VTextField v-model.number="formData.sort_order" label="الترتيب" type="number" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12">
            <VSwitch v-model="formData.is_active" label="المنتج متاح للبيع" color="success" hide-details />
          </VCol>
        </VRow>
      </VCardText>
      
      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn color="secondary" variant="tonal" rounded="lg" @click="showDialog = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" rounded="lg" class="px-8" @click="saveProduct">حفظ المنتج</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete Confirmation -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4"><VIcon icon="ri-delete-bin-line" size="32" /></VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">حذف المنتج</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-6">هل أنت متأكد من حذف هذا المنتج؟ لا يمكن التراجع عن هذا الإجراء.</VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" @click="deleteProduct">تأكيد الحذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.w-fit { width: fit-content; }
.image-upload-wrapper {
  width: 150px;
  height: 150px;
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}
.image-upload-wrapper:hover { border-color: rgb(var(--v-theme-primary)); background: rgba(var(--v-theme-primary), 0.04); }
.product-image-preview { width: 100%; height: 100%; object-fit: cover; }
.image-upload-wrapper.has-image { border-style: solid; border-color: transparent; }
</style>
