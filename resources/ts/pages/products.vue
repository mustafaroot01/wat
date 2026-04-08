<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'

interface Product {
  id: number | null;
  category_id: number | null;
  filter_id: number | null;
  brand_id: number | null;
  sku?: string;
  name: string;
  description: string;
  price: number;
  discount_percentage: number;
  discounted_price?: number | null;
  has_discount?: boolean;
  in_stock: boolean;
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
  totalItems,
  fetchData: fetchProducts,
  handleOptionsChange,
} = usePagination<Product>('/api/admin/products')

const headers = [
  { title: 'المنتج',           key: 'name',      align: 'start'  as const, sortable: true  },
  { title: 'رمز المنتج (SKU)', key: 'sku',       align: 'start'  as const, sortable: false },
  { title: 'التصنيف / الشركة', key: 'category',  align: 'start'  as const, sortable: false },
  { title: 'السعر / الخصم',   key: 'price',     align: 'start'  as const, sortable: true  },
  { title: 'المخزون',          key: 'in_stock',  align: 'center' as const, sortable: false },
  { title: 'الحالة',           key: 'is_active', align: 'center' as const, sortable: false },
  { title: 'الإجراءات',        key: 'actions',   align: 'center' as const, sortable: false },
]

// Search and Filters
const searchQuery = ref('')
const categoryFilter = ref<number | null>(null)
const stockFilter = ref<string | null>(null)
const statusFilter = ref<string | null>(null)

const sortState = ref<{ sort_by?: string; sort_dir?: string }>({})

const loadProducts = (page = 1) => {
  const params: any = { ...sortState.value }
  if (searchQuery.value) params.search = searchQuery.value
  if (categoryFilter.value) params.category_id = categoryFilter.value
  if (stockFilter.value !== null) params.in_stock = stockFilter.value
  if (statusFilter.value !== null) params.is_active = statusFilter.value
  fetchProducts(page, params)
}

// Watch filters to trigger reload
watch([searchQuery, categoryFilter, stockFilter, statusFilter], () => {
  loadProducts(1)
})

const handleProductOptions = (options: any) => {
  const sort = options.sortBy?.[0]
  sortState.value = sort ? { sort_by: sort.key, sort_dir: sort.order } : {}
  loadProducts(options.page)
}

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
  discount_percentage: 0,
  in_stock: true,
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
    description: '', price: 0, discount_percentage: 0, in_stock: true,
    is_active: true, sort_order: 0, image: null, image_url: null 
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
  if (formData.value.sku) payload.append('sku', formData.value.sku)
  payload.append('category_id', String(formData.value.category_id || ''))
  if (formData.value.filter_id) payload.append('filter_id', String(formData.value.filter_id))
  payload.append('brand_id', String(formData.value.brand_id || ''))
  payload.append('name', formData.value.name)
  payload.append('description', formData.value.description || '')
  payload.append('price', String(formData.value.price))
  payload.append('discount_percentage', String(formData.value.discount_percentage ?? 0))
  payload.append('in_stock', formData.value.in_stock ? '1' : '0')
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
      loadProducts(1) // Reload table data using the custom load function
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
      loadProducts(1) // Reload table data using the custom load function
    }
  } catch (error) {
    console.error('Error deleting product:', error)
  }
}

const toggleActive = async (item: Product) => {
  try {
    const res = await apiFetch(`/api/admin/products/${item.id}/toggle`, { method: 'PATCH' })
    if (res.ok) {
      const data = await res.json()
      const index = products.value.findIndex(p => p.id === item.id)
      if (index !== -1) products.value[index] = data.data
    } else {
      item.is_active = !item.is_active
    }
  } catch (e) {
    item.is_active = !item.is_active
    console.error(e)
  }
}

const toggleInStock = async (item: Product) => {
  // Optimistic update
  item.in_stock = !item.in_stock
  
  try {
    const res = await apiFetch(`/api/admin/products/${item.id}/toggle-stock`, { method: 'PATCH' })
    if (!res.ok) {
      // Revert on error
      item.in_stock = !item.in_stock
    } else {
      const data = await res.json()
      // Make sure we update with the server response if needed
      item.in_stock = data.data.in_stock
    }
  } catch (e) {
    // Revert on network error
    item.in_stock = !item.in_stock
    console.error(e)
  }
}

onMounted(() => {
  loadProducts(1)
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

        <!-- Filters Section -->
        <VCardText class="bg-surface-variant bg-opacity-25 py-5 border-b">
          <VRow>
            <VCol cols="12" md="3">
              <VTextField
                v-model="searchQuery"
                placeholder="ابحث باسم أو رمز المنتج..."
                prepend-inner-icon="ri-search-line"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="categoryFilter"
                :items="categories"
                item-title="name"
                item-value="id"
                placeholder="كل الأقسام"
                prepend-inner-icon="ri-folder-2-line"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
                clearable
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="stockFilter"
                :items="[{ title: 'الكل', value: null }, { title: 'متوفر', value: 1 }, { title: 'نافذ', value: 0 }]"
                placeholder="حالة المخزون"
                prepend-inner-icon="ri-box-3-line"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
              />
            </VCol>
            <VCol cols="12" md="3">
              <VSelect
                v-model="statusFilter"
                :items="[{ title: 'الكل', value: null }, { title: 'متاح للعرض', value: 1 }, { title: 'مخفي', value: 0 }]"
                placeholder="حالة العرض"
                prepend-inner-icon="ri-eye-line"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDataTableServer
          :headers="headers"
          :items="products"
          :items-length="totalItems"
          :loading="loading"
          :items-per-page="15"
          :items-per-page-options="[15, 25, 50, 100]"
          no-data-text="لا توجد منتجات تطابق البحث"
          loading-text="جاري التحميل..."
          class="rounded-0"
          @update:options="handleProductOptions"
        >
          <template #item.sku="{ item }">
            <VChip size="small" color="secondary" variant="outlined" class="font-weight-medium font-monospace">
              {{ item.sku }}
            </VChip>
          </template>

          <template #item.name="{ item }">
            <div class="d-flex align-center gap-3 py-2">
              <VAvatar v-if="item.image_url" :image="item.image_url" size="40" rounded="lg" border />
              <VAvatar v-else color="surface-variant" size="40" rounded="lg">
                <VIcon icon="ri-image-line" size="20" />
              </VAvatar>
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ item.name }}</span>
                <span class="text-caption text-medium-emphasis">{{ item.description?.substring(0, 35) }}</span>
              </div>
            </div>
          </template>

          <template #item.category="{ item }">
            <div class="d-flex flex-column gap-1">
              <VChip size="x-small" color="primary" variant="tonal">{{ item.category?.name || 'بدون تصنيف' }}</VChip>
              <VChip v-if="item.brand?.name" size="x-small" color="secondary" variant="outlined">
                <VIcon icon="ri-verified-badge-line" start size="12" />
                {{ item.brand?.name }}
              </VChip>
            </div>
          </template>

          <template #item.price="{ item }">
            <div class="d-flex flex-column gap-1">
              <div class="d-flex align-center gap-2">
                <span :class="item.has_discount ? 'text-decoration-line-through text-medium-emphasis text-caption' : 'font-weight-bold text-primary'">
                  {{ formatIQD(item.price) }}
                </span>
                <VChip v-if="item.has_discount" color="error" size="x-small" variant="flat">
                  وفّر {{ item.discount_percentage }}%
                </VChip>
              </div>
              <span v-if="item.has_discount" class="font-weight-bold text-success">
                {{ formatIQD(item.discounted_price) }}
              </span>
            </div>
          </template>

          <template #item.in_stock="{ item }">
            <VChip :color="item.in_stock ? 'success' : 'warning'" size="x-small" variant="tonal" class="cursor-pointer" @click="toggleInStock(item)">
              <VIcon :icon="item.in_stock ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'" size="12" start />
              {{ item.in_stock ? 'متوفر' : 'نافذ' }}
            </VChip>
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

          <VCol cols="12" md="6">
            <VTextField
              v-model="formData.sku"
              label="رمز المنتج (SKU)"
              placeholder="يُولد تلقائياً إذا تُرك فارغاً"
              variant="outlined"
              color="primary"
              :disabled="dialogMode === 'edit'"
              hint="يفضل تركه فارغاً ليتم توليده تلقائياً"
              persistent-hint
            />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField v-model="formData.name" label="اسم المنتج" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12">
            <VTextarea v-model="formData.description" label="وصف المنتج" rows="2" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField v-model.number="formData.price" label="السعر الأصلي (د.ع)" type="number" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12" md="6">
            <VTextField
              v-model.number="formData.discount_percentage"
              label="نسبة الخصم (%)"
              type="number"
              variant="outlined"
              color="error"
              :hint="formData.discount_percentage > 0 ? 'السعر بعد الخصم: ' + formatIQD(formData.price * (1 - formData.discount_percentage / 100)) : 'أدخل 0 لإلغاء الخصم'"
              persistent-hint
              :min="0"
              :max="99"
            />
          </VCol>

          <VCol cols="12" md="6">
             <VTextField v-model.number="formData.sort_order" label="الترتيب" type="number" variant="outlined" color="primary" />
          </VCol>

          <VCol cols="12" md="6" class="d-flex align-center gap-4">
            <VSwitch v-model="formData.in_stock" :label="formData.in_stock ? 'متوفر في المخزون' : 'نافذ (غير متوفر)'"
              color="success" hide-details />
          </VCol>

          <VCol cols="12">
            <VSwitch v-model="formData.is_active" label="المنتج متاح للعرض" color="primary" hide-details />
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
