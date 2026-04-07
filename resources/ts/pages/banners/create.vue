<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// --- Form State ---
const formRef = ref<any>(null)
const isLoadingSubmit = ref(false)
const submitSuccess = ref(false)

// Banner Type
const bannerType = ref<'link' | 'product' | 'category' | 'none'>('none')

// Link Data
const url = ref('')

// Category Data
const categoryId = ref<number | null>(null)

// Product Data
const productId = ref<number | null>(null)

// Duration (Optional)
const durationType = ref<'hours' | 'days' | null>(null)
const durationValue = ref<number | null>(null)

// Sort Order
const sortOrder = ref<number>(0)

// Image Data
const imageFile = ref<File | null>(null)
const imagePreviewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const imageError = ref<string | null>(null)

// --- API State ---
const categories = ref<any[]>([])
const isLoadingCategories = ref(false)

const products = ref<any[]>([])
const isLoadingProducts = ref(false)

// --- Fetch Dummy Data ---
const fetchCategories = async () => {
  if (categories.value.length > 0) return 
  isLoadingCategories.value = true
  try {
    const res = await fetch('/api/admin/categories')
    if (res.ok) categories.value = await res.json()
  } catch (e) {
    console.error(e)
    categories.value = [{ id: 1, name: 'كاسات' }, { id: 2, name: 'عبوات' }]
  } finally {
    isLoadingCategories.value = false
  }
}

const fetchProducts = async () => {
  if (products.value.length > 0) return 
  isLoadingProducts.value = true
  try {
    // const res = await fetch(`/api/admin/products`)
    // if(res.ok) products.value = await res.json()
    setTimeout(() => {
      products.value = [
        { id: 101, name: 'كرتونة مياه دجلة 330 مل' },
        { id: 102, name: 'عبوة الريان 5 لتر' },
      ]
      isLoadingProducts.value = false
    }, 800)
  } catch (e) {
    console.error(e)
    isLoadingProducts.value = false
  }
}

watch(bannerType, (newType) => {
  url.value = ''
  categoryId.value = null
  productId.value = null
  if (newType === 'category') fetchCategories()
  else if (newType === 'product') fetchProducts()
})

const requiredRule = (v: any) => !!v || 'هذا الحقل مطلوب'
const urlRule = (v: string) => {
  if (!v) return 'الرابط مطلوب'
  const pattern = new RegExp('^(https?:\\/\\/)?((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|((\\d{1,3}\\.){3}\\d{1,3}))(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*(\\?[;&a-z\\d%_.~+=-]*)?(\\#[-a-z\\d_]*)?$','i')
  return pattern.test(v) || 'الرجاء إدخال رابط صحيح (http:// أو https://)'
}
const numberRule = (v: any) => (!v || v > 0) || 'يجب أن يكون رقم أكبر من صفر'

const handleImageSelection = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  imageError.value = null

  if (file) {
    const validTypes = ['image/jpeg', 'image/png', 'image/webp']
    if (!validTypes.includes(file.type)) {
      imageError.value = 'الرجاء اختيار صورة بصيغة jpg أو png أو webp'
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      imageError.value = 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت'
      return
    }
    imageFile.value = file
    imagePreviewUrl.value = URL.createObjectURL(file)
  }
}

const triggerFileInput = () => fileInputRef.value?.click()
const removeImage = () => {
  imageFile.value = null
  imagePreviewUrl.value = null
  if(fileInputRef.value) fileInputRef.value.value = ''
}

const submitForm = async () => {
  const { valid } = await formRef.value.validate()
  
  if (!imageFile.value) {
    imageError.value = 'صورة البانر مطلوبة'
    return
  }

  if (!valid) return

  isLoadingSubmit.value = true
  submitSuccess.value = false

  try {
    const formData = new FormData()

    formData.append('image', imageFile.value)
    formData.append('type', bannerType.value)
    
    if (bannerType.value === 'link') formData.append('url', url.value)
    else if (bannerType.value === 'category') formData.append('category_id', String(categoryId.value))
    else if (bannerType.value === 'product') formData.append('product_id', String(productId.value))

    if (durationType.value && durationValue.value) {
      formData.append('duration_type', durationType.value)
      formData.append('duration_value', String(durationValue.value))
    }

    if (sortOrder.value !== null) {
      formData.append('sort_order', String(sortOrder.value))
    }

    const res = await fetch('/api/admin/banners', { method: 'POST', body: formData })
    
    if(!res.ok) throw new Error('Submission Failed')

    isLoadingSubmit.value = false
    submitSuccess.value = true
    setTimeout(() => {
      router.push('/banners')
    }, 1500)

  } catch (error) {
    console.error(error)
    isLoadingSubmit.value = false
    alert('حدث خطأ')
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12" md="8" class="mx-auto">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="py-4 px-6 border-b">
          <VCardTitle class="font-weight-bold d-flex align-center gap-2 text-h5">
            <VIcon icon="ri-advertisement-line" color="primary" />
            إضافة إعلان (بانر) جديد
          </VCardTitle>
          <VCardSubtitle class="mt-1">
            قم بتخصيص تفاصيل البانر الإعلاني واختيار ما إذا كان يوجه المستخدم لرابط خارجي أو لقسم أو لمنتج معين.
          </VCardSubtitle>
        </VCardItem>

        <VAlert v-if="submitSuccess" type="success" variant="tonal" class="ma-6" closable>
          تمت إضافة البانر بنجاح! جاري التوجيه للبانرات...
        </VAlert>

        <VCardText class="pa-6">
          <VForm ref="formRef" @submit.prevent="submitForm">
            <VRow>
              <VCol cols="12">
                <p class="text-body-1 font-weight-medium mb-3">صورة البانر <span class="text-error">*</span></p>
                <input type="file" ref="fileInputRef" class="d-none" accept=".jpg,.jpeg,.png,.webp" @change="handleImageSelection">
                <div class="banner-upload-wrapper position-relative mx-auto" @click="triggerFileInput" :class="{'has-image': imagePreviewUrl, 'border-error': imageError}">
                  <VImg v-if="imagePreviewUrl" :src="imagePreviewUrl" cover class="banner-image" />
                  <div v-else class="upload-placeholder d-flex flex-column align-center justify-center h-100 text-secondary">
                    <VIcon icon="ri-image-add-line" size="48" class="mb-3" />
                    <span class="text-h6 font-weight-bold">اضغط لاختيار صورة</span>
                    <span class="text-caption mt-1">يجب أن لا يتجاوز الحجم 2MB (JPG, PNG, WebP)</span>
                  </div>
                  <div v-if="imagePreviewUrl" class="image-overlay d-flex align-center justify-center">
                    <VIcon icon="ri-camera-switch-line" color="white" size="32" />
                  </div>
                </div>
                <div v-if="imageError" class="text-error text-caption text-center mt-2">{{ imageError }}</div>
                <div class="text-center mt-3" v-if="imagePreviewUrl">
                  <VBtn variant="text" color="error" size="small" @click="removeImage" prepend-icon="ri-delete-bin-line">حذف الصورة</VBtn>
                </div>
              </VCol>

              <VCol cols="12"><VDivider class="my-2" /></VCol>

              <VCol cols="12" md="6">
                <p class="text-body-1 font-weight-medium mb-2">تسلسل البانر (الترتيب الظاهري في التطبيق)</p>
                <VTextField v-model.number="sortOrder" type="number" placeholder="0" variant="outlined" color="primary" prepend-inner-icon="ri-sort-asc" hint="الأرقام الأقل تظهر أولاً (مثال: 0, 1, 2...)" persistent-hint />
              </VCol>

              <VCol cols="12"><VDivider class="my-2" /></VCol>

              <VCol cols="12">
                <p class="text-body-1 font-weight-medium mb-2">نوع البانر (إلى أين يوجه المستخدم؟) <span class="text-error">*</span></p>
                <VRadioGroup v-model="bannerType" inline color="primary" hide-details>
                  <VRadio label="إعلان صوري فقط" value="none" class="me-4" />
                  <VRadio label="رابط مخصص (URL)" value="link" class="me-4" />
                  <VRadio label="قسم داخل المتجر" value="category" class="me-4" />
                  <VRadio label="منتج داخل المتجر" value="product" />
                </VRadioGroup>
              </VCol>

              <VCol cols="12" v-if="bannerType === 'link'">
                <VTextField v-model="url" label="رابط التوجيه (URL)" hint="مثال: https://example.com" persistent-hint variant="outlined" color="primary" prepend-inner-icon="ri-link-m" :rules="[requiredRule, urlRule]" />
              </VCol>

              <VCol cols="12" v-if="bannerType === 'category'">
                <VAutocomplete v-model="categoryId" :items="categories" item-title="name" item-value="id" label="القسم المستهدف" hint="ابحث عن اسم القسم لتوجيه المستخدم إليه..." persistent-hint variant="outlined" color="primary" prepend-inner-icon="ri-layout-grid-line" :loading="isLoadingCategories" clearable :rules="[requiredRule]" no-data-text="لا توجد أقسام متاحة" />
              </VCol>

              <VCol cols="12" v-if="bannerType === 'product'">
                <VAutocomplete v-model="productId" :items="products" item-title="name" item-value="id" label="المنتج المعلن عنه" hint="ابحث عن اسم المنتج لتوجيه المستخدم إليه..." persistent-hint variant="outlined" color="primary" prepend-inner-icon="ri-box-3-line" :loading="isLoadingProducts" clearable :rules="[requiredRule]" no-data-text="لا توجد منتجات متاحة" />
              </VCol>

              <VCol cols="12"><VDivider class="my-2" /></VCol>

              <VCol cols="12">
                <p class="text-body-1 font-weight-medium mb-2">فترة الإعلان (اختياري)</p>
                <VRow>
                  <VCol cols="12" sm="6">
                    <VSelect v-model="durationType" :items="[{title: 'بالساعات', value: 'hours'}, {title: 'بالأيام', value: 'days'}]" label="نوع المدة الزمنية" variant="outlined" clearable prepend-inner-icon="ri-time-line" />
                  </VCol>
                  
                  <VCol cols="12" sm="6">
                    <VTextField v-if="durationType" v-model.number="durationValue" type="number" :label="durationType === 'hours' ? 'عدد الساعات' : 'عدد الأيام'" variant="outlined" color="primary" :rules="[requiredRule, numberRule]" :hint="`سيختفي البانر تلقائياً بعد ${durationValue || 0} ${durationType === 'hours' ? 'ساعة' : 'يوم'}`" persistent-hint />
                  </VCol>
                </VRow>
              </VCol>

            </VRow>

            <VCardActions class="px-0 pt-6 mt-4 border-t">
              <VBtn variant="tonal" color="secondary" rounded="lg" class="px-6" to="/banners" :disabled="isLoadingSubmit">إلغاء والعودة</VBtn>
              <VSpacer />
              <VBtn type="submit" variant="elevated" color="primary" rounded="lg" class="px-8" :loading="isLoadingSubmit" :disabled="isLoadingSubmit" prepend-icon="ri-save-3-line">حفظ البانر</VBtn>
            </VCardActions>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.banner-upload-wrapper {
  width: 100%;
  max-width: 600px;
  aspect-ratio: 21 / 9;
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
  background: rgba(var(--v-theme-surface-variant), 0.3);
}

.banner-upload-wrapper.border-error {
  border-color: rgb(var(--v-theme-error));
  background: rgba(var(--v-theme-error), 0.05);
}

.banner-upload-wrapper:hover:not(.border-error) {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.banner-upload-wrapper.has-image {
  border-style: solid;
  border-color: transparent;
}

.banner-image {
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

.banner-upload-wrapper:hover .image-overlay {
  opacity: 1;
}
</style>
