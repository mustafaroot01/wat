<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { apiFetch } from '@/utils/apiFetch'
import { formatIQD } from '@/utils/currency'
import { useRouter } from 'vue-router'

const router = useRouter()

interface Product {
  id: number
  sku: string
  name: string
  description: string
  price: number
  discount_percentage: number
  discounted_price: number | null
  has_discount: boolean
  in_stock: boolean
  is_active: boolean
  image_url: string | null
  category?: { name: string }
  brand?: { name: string }
}

interface Stats {
  total_discounted: number
  avg_discount: number
  max_discount: number
}

// Data
const products    = ref<Product[]>([])
const loading     = ref(false)
const totalItems  = ref(0)
const hasMore     = ref(false)
const currentPage = ref(1)
const perPage     = ref(12)
const stats       = ref<Stats>({ total_discounted: 0, avg_discount: 0, max_discount: 0 })

// Filters
const searchQuery    = ref('')
const categoryFilter = ref<number | null>(null)
const sortBy         = ref('discount_percentage')
const sortDir        = ref('desc')
const categories     = ref<any[]>([])

const sortOptions = [
  { title: 'أعلى خصم', value: 'discount_percentage|desc' },
  { title: 'أقل خصم',  value: 'discount_percentage|asc'  },
  { title: 'الأعلى سعراً', value: 'price|desc' },
  { title: 'الأقل سعراً',  value: 'price|asc'  },
  { title: 'الأحدث',   value: 'created_at|desc' },
]
const selectedSort = ref('discount_percentage|desc')

watch(selectedSort, (val) => {
  const [key, dir] = val.split('|')
  sortBy.value  = key
  sortDir.value = dir
  fetchProducts(1)
})

const fetchProducts = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = new URLSearchParams({
      page:     String(page),
      per_page: String(perPage.value),
      sort_by:  sortBy.value,
      sort_dir: sortDir.value,
    })
    if (searchQuery.value)    params.set('search',      searchQuery.value)
    if (categoryFilter.value) params.set('category_id', String(categoryFilter.value))

    const res = await apiFetch(`/api/admin/products/discounted?${params}`)
    if (res.ok) {
      const body = await res.json()
      products.value   = body.data || []
      totalItems.value = body.meta?.total || body.total || 0
      hasMore.value    = body.has_more || false
      if (body.stats)  stats.value = body.stats
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const res = await apiFetch('/api/admin/categories?per_page=100')
    if (res.ok) categories.value = (await res.json()).data || []
  } catch (e) { console.error(e) }
}

watch([searchQuery, categoryFilter], () => fetchProducts(1))

const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value))

const goToProducts = (id: number) => {
  router.push({ path: '/products', query: { highlight: id } })
}

onMounted(() => {
  fetchProducts(1)
  fetchCategories()
})
</script>

<template>
  <VRow>
    <VCol cols="12">

      <!-- Page Header -->
      <div class="d-flex align-center justify-space-between mb-6">
        <div>
          <h1 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-price-tag-3-line" color="error" size="28" />
            خصومات مميزة
          </h1>
          <p class="text-body-2 text-medium-emphasis mt-1 mb-0">
            جميع المنتجات النشطة التي تحتوي على خصم في الوقت الحالي
          </p>
        </div>
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-arrow-left-line"
          rounded="lg"
          to="/products"
        >
          إدارة المنتجات
        </VBtn>
      </div>

      <!-- Stats Row -->
      <VRow class="mb-5">
        <VCol cols="12" sm="4">
          <VCard rounded="lg" elevation="0" class="stat-card stat-card--total">
            <VCardText class="d-flex align-center gap-4 pa-5">
              <VAvatar color="error" variant="tonal" size="52" rounded="lg">
                <VIcon icon="ri-price-tag-3-fill" size="26" />
              </VAvatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.total_discounted }}</div>
                <div class="text-body-2 text-medium-emphasis">منتج مخفّض</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" sm="4">
          <VCard rounded="lg" elevation="0" class="stat-card stat-card--avg">
            <VCardText class="d-flex align-center gap-4 pa-5">
              <VAvatar color="warning" variant="tonal" size="52" rounded="lg">
                <VIcon icon="ri-percent-line" size="26" />
              </VAvatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.avg_discount }}%</div>
                <div class="text-body-2 text-medium-emphasis">متوسط نسبة الخصم</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" sm="4">
          <VCard rounded="lg" elevation="0" class="stat-card stat-card--max">
            <VCardText class="d-flex align-center gap-4 pa-5">
              <VAvatar color="success" variant="tonal" size="52" rounded="lg">
                <VIcon icon="ri-rocket-line" size="26" />
              </VAvatar>
              <div>
                <div class="text-h4 font-weight-bold">{{ stats.max_discount }}%</div>
                <div class="text-body-2 text-medium-emphasis">أعلى خصم متاح</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Filters Bar -->
      <VCard rounded="lg" elevation="0" class="mb-5">
        <VCardText class="py-4 px-5">
          <VRow align="center">
            <VCol cols="12" md="4">
              <VTextField
                v-model="searchQuery"
                placeholder="ابحث بالاسم أو SKU..."
                prepend-inner-icon="ri-search-line"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
                clearable
              />
            </VCol>
            <VCol cols="12" md="4">
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
            <VCol cols="12" md="4">
              <VSelect
                v-model="selectedSort"
                :items="sortOptions"
                item-title="title"
                item-value="value"
                prepend-inner-icon="ri-sort-desc"
                variant="solo"
                bg-color="surface"
                hide-details
                density="compact"
                rounded="lg"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Loading Skeleton -->
      <VRow v-if="loading">
        <VCol v-for="n in 8" :key="n" cols="12" sm="6" md="4" lg="3">
          <VCard rounded="xl" elevation="0">
            <VSkeleton type="image" height="180" />
            <VCardText>
              <VSkeleton type="text" class="mb-2" />
              <VSkeleton type="text" width="60%" />
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Empty State -->
      <VCard v-else-if="!loading && products.length === 0" rounded="xl" elevation="0" class="text-center py-16">
        <VAvatar color="surface-variant" size="80" class="mb-4">
          <VIcon icon="ri-price-tag-3-line" size="40" />
        </VAvatar>
        <VCardTitle class="text-h6">لا توجد منتجات مخفّضة</VCardTitle>
        <VCardText class="text-medium-emphasis">
          لم تُضف أي خصومات على المنتجات بعد. يمكنك إضافة خصم من صفحة المنتجات.
        </VCardText>
        <VBtn color="primary" variant="tonal" rounded="lg" to="/products" class="mt-2">
          الذهاب لإدارة المنتجات
        </VBtn>
      </VCard>

      <!-- Products Grid -->
      <VRow v-else>
        <VCol
          v-for="product in products"
          :key="product.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <VCard rounded="xl" elevation="0" class="discount-card h-100 d-flex flex-column" @click="goToProducts(product.id)">

            <!-- Discount Badge -->
            <div class="discount-badge">
              <VIcon icon="ri-price-tag-3-fill" size="12" class="me-1" />
              وفّر {{ product.discount_percentage }}%
            </div>

            <!-- Product Image -->
            <div class="product-img-wrap">
              <VImg
                v-if="product.image_url"
                :src="product.image_url"
                cover
                height="180"
                class="product-img"
              />
              <div v-else class="product-img-placeholder d-flex align-center justify-center" style="height:180px;">
                <VIcon icon="ri-image-line" size="48" color="surface-variant" />
              </div>
            </div>

            <VCardText class="pa-4 d-flex flex-column flex-grow-1">

              <!-- Category -->
              <VChip
                v-if="product.category?.name"
                size="x-small"
                color="primary"
                variant="tonal"
                class="mb-2 w-fit"
              >
                {{ product.category.name }}
              </VChip>

              <!-- Product Name -->
              <div class="text-body-1 font-weight-semibold product-name mb-1">
                {{ product.name }}
              </div>

              <!-- Brand -->
              <div v-if="product.brand?.name" class="text-caption text-medium-emphasis mb-3 d-flex align-center gap-1">
                <VIcon icon="ri-verified-badge-line" size="12" />
                {{ product.brand.name }}
              </div>

              <VSpacer />

              <!-- Pricing -->
              <div class="pricing-block mt-auto pt-3 border-t">
                <div class="d-flex align-center justify-space-between">
                  <div>
                    <!-- Original price crossed out -->
                    <div class="text-decoration-line-through text-caption text-medium-emphasis">
                      {{ formatIQD(product.price) }}
                    </div>
                    <!-- Discounted price -->
                    <div class="text-h6 font-weight-bold text-success">
                      {{ formatIQD(product.discounted_price) }}
                    </div>
                  </div>
                  <div class="text-end">
                    <!-- Savings chip -->
                    <VChip color="error" variant="flat" size="small" class="font-weight-bold savings-chip">
                      -{{ product.discount_percentage }}%
                    </VChip>
                    <!-- Stock status -->
                    <div class="mt-1">
                      <VChip
                        :color="product.in_stock ? 'success' : 'warning'"
                        size="x-small"
                        variant="tonal"
                      >
                        {{ product.in_stock ? 'متوفر' : 'نافذ' }}
                      </VChip>
                    </div>
                  </div>
                </div>
              </div>
            </VCardText>

            <!-- Edit Hover Overlay -->
            <div class="edit-overlay d-flex align-center justify-center">
              <VBtn color="white" variant="elevated" rounded="lg" size="small">
                <VIcon icon="ri-pencil-line" size="16" start />
                تعديل من صفحة المنتجات
              </VBtn>
            </div>

          </VCard>
        </VCol>
      </VRow>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="d-flex justify-center mt-8">
        <VPagination
          v-model="currentPage"
          :length="totalPages"
          :total-visible="7"
          rounded="lg"
          color="primary"
          @update:model-value="fetchProducts"
        />
      </div>

      <!-- Results Count -->
      <div v-if="!loading && products.length > 0" class="text-center mt-4 text-caption text-medium-emphasis">
        يُعرض {{ products.length }} من أصل {{ totalItems }} منتج مخفّض
      </div>

    </VCol>
  </VRow>
</template>

<style scoped>
/* Stat Cards */
.stat-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important;
}

/* Discount Card */
.discount-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  position: relative;
  overflow: hidden;
}
.discount-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12) !important;
}
.discount-card:hover .edit-overlay {
  opacity: 1;
}

/* Discount Badge */
.discount-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
  background: rgb(var(--v-theme-error));
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(var(--v-theme-error), 0.4);
}

/* Product Image */
.product-img-wrap {
  overflow: hidden;
}
.product-img {
  transition: transform 0.35s ease;
}
.discount-card:hover .product-img {
  transform: scale(1.06);
}
.product-img-placeholder {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

/* Product Name */
.product-name {
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Savings Chip */
.savings-chip {
  letter-spacing: 0.3px;
}

/* Edit Overlay */
.edit-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  opacity: 0;
  transition: opacity 0.25s ease;
  border-radius: inherit;
}

.w-fit { width: fit-content; }
</style>
