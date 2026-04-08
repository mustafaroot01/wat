<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '@/utils/apiFetch'
import { useAdminPermissions } from '@/composables/useAdminPermissions'

const { isSuperAdmin } = useAdminPermissions()

interface Admin {
  id: number
  name: string
  email: string
  is_active: boolean
  is_super_admin: boolean
  permissions: string[]
  created_at: string
}

const ALL_PERMISSIONS: { key: string; label: string; icon: string }[] = [
  { key: 'dashboard',        label: 'لوحة التحكم',           icon: 'ri-home-smile-line' },
  { key: 'orders',           label: 'الطلبات',               icon: 'ri-shopping-cart-2-line' },
  { key: 'products',         label: 'المنتجات',              icon: 'ri-water-flash-line' },
  { key: 'discounts',        label: 'الخصومات المميزة',       icon: 'ri-price-tag-3-line' },
  { key: 'categories',       label: 'الأقسام',               icon: 'ri-layout-grid-line' },
  { key: 'brands',           label: 'الشركات',               icon: 'ri-verified-badge-line' },
  { key: 'customers',        label: 'العملاء',               icon: 'ri-group-line' },
  { key: 'notifications',    label: 'الإشعارات',             icon: 'ri-notification-badge-line' },
  { key: 'coupons',          label: 'أكواد الخصم',           icon: 'ri-coupon-3-line' },
  { key: 'banners',          label: 'البانرات',              icon: 'ri-advertisement-line' },
  { key: 'districts',        label: 'الأقضية والمناطق',      icon: 'ri-map-pin-line' },
  { key: 'store-settings',   label: 'إعدادات المتجر',        icon: 'ri-store-3-line' },
  { key: 'settings',         label: 'الإعدادات العامة',      icon: 'ri-settings-4-line' },
  { key: 'firebase-settings',label: 'إعدادات Firebase',      icon: 'ri-settings-5-line' },
]

// Data
const admins     = ref<Admin[]>([])
const loading    = ref(false)
const totalItems = ref(0)

const headers = [
  { title: '#',           key: 'id',             align: 'center' as const, sortable: false, width: '60px' },
  { title: 'المشرف',      key: 'name',           align: 'start'  as const, sortable: false },
  { title: 'النوع',       key: 'is_super_admin', align: 'center' as const, sortable: false },
  { title: 'الصلاحيات',  key: 'permissions',    align: 'start'  as const, sortable: false },
  { title: 'الحالة',      key: 'is_active',      align: 'center' as const, sortable: false },
  { title: 'الإجراءات',  key: 'actions',        align: 'center' as const, sortable: false },
]

const fetchAdmins = async () => {
  loading.value = true
  try {
    const res = await apiFetch('/api/admin/admins?per_page=50')
    if (res.ok) {
      const body = await res.json()
      admins.value   = body.data || []
      totalItems.value = body.total || admins.value.length
    }
  } catch (e) { console.error(e) } finally { loading.value = false }
}

// Dialog
const showDialog  = ref(false)
const dialogMode  = ref<'add' | 'edit'>('add')
const formSaving  = ref(false)
const formError   = ref('')
const editTarget  = ref<Admin | null>(null)

const formData = ref({
  name: '',
  email: '',
  password: '',
  is_super_admin: false,
  permissions: [] as string[],
})

const openAdd = () => {
  dialogMode.value = 'add'
  editTarget.value = null
  formData.value = { name: '', email: '', password: '', is_super_admin: false, permissions: [] }
  formError.value = ''
  showDialog.value = true
}

const openEdit = (admin: Admin) => {
  dialogMode.value = 'edit'
  editTarget.value = admin
  formData.value = {
    name:           admin.name,
    email:          admin.email,
    password:       '',
    is_super_admin: admin.is_super_admin,
    permissions:    [...(admin.permissions || [])],
  }
  formError.value = ''
  showDialog.value = true
}

const saveAdmin = async () => {
  formSaving.value = true
  formError.value  = ''
  try {
    const url    = dialogMode.value === 'add' ? '/api/admin/admins' : `/api/admin/admins/${editTarget.value?.id}`
    const method = dialogMode.value === 'add' ? 'POST' : 'PUT'

    const payload: any = {
      name:           formData.value.name,
      email:          formData.value.email,
      is_super_admin: formData.value.is_super_admin,
      permissions:    formData.value.is_super_admin ? [] : formData.value.permissions,
    }
    if (formData.value.password) payload.password = formData.value.password

    const res = await apiFetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const body = await res.json()
    if (res.ok) {
      showDialog.value = false
      fetchAdmins()
    } else {
      formError.value = body.message || JSON.stringify(body.errors || 'حدث خطأ')
    }
  } catch (e) { formError.value = 'خطأ في الاتصال' } finally { formSaving.value = false }
}

// Delete
const confirmDeleteDialog = ref(false)
const deleteTarget        = ref<Admin | null>(null)

const confirmDelete = (admin: Admin) => {
  deleteTarget.value = admin
  confirmDeleteDialog.value = true
}

const deleteAdmin = async () => {
  if (!deleteTarget.value) return
  try {
    const res = await apiFetch(`/api/admin/admins/${deleteTarget.value.id}`, { method: 'DELETE' })
    if (res.ok) { confirmDeleteDialog.value = false; fetchAdmins() }
  } catch (e) { console.error(e) }
}

// Toggle active
const toggleActive = async (admin: Admin) => {
  try {
    const res = await apiFetch(`/api/admin/admins/${admin.id}/toggle`, { method: 'PATCH' })
    if (res.ok) {
      const body = await res.json()
      const idx = admins.value.findIndex(a => a.id === admin.id)
      if (idx !== -1) admins.value[idx] = body.data
    }
  } catch (e) { console.error(e) }
}

const permissionLabel = (key: string) => ALL_PERMISSIONS.find(p => p.key === key)?.label || key

onMounted(fetchAdmins)
</script>

<template>
  <VRow>
    <VCol cols="12">

      <!-- Header -->
      <div class="d-flex align-center justify-space-between mb-6">
        <div>
          <h1 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-shield-user-line" color="primary" size="28" />
            إدارة المشرفين والصلاحيات
          </h1>
          <p class="text-body-2 text-medium-emphasis mt-1 mb-0">
            أضف مشرفين جدد وحدّد ما يُسمح لهم برؤيته في لوحة التحكم
          </p>
        </div>
        <VBtn color="primary" prepend-icon="ri-user-add-line" rounded="lg" @click="openAdd">
          إضافة مشرف
        </VBtn>
      </div>

      <!-- Table -->
      <VCard rounded="lg" elevation="0">
        <VDataTable
          :headers="headers"
          :items="admins"
          :loading="loading"
          no-data-text="لا يوجد مشرفون"
          loading-text="جاري التحميل..."
          class="rounded-lg"
        >
          <!-- Name + Email -->
          <template #item.name="{ item }">
            <div class="d-flex align-center gap-3 py-2">
              <VAvatar color="primary" variant="tonal" size="38" rounded="lg">
                <VIcon icon="ri-user-line" size="18" />
              </VAvatar>
              <div>
                <div class="font-weight-medium">{{ item.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
              </div>
            </div>
          </template>

          <!-- Type -->
          <template #item.is_super_admin="{ item }">
            <VChip
              :color="item.is_super_admin ? 'warning' : 'secondary'"
              size="small"
              variant="tonal"
            >
              <VIcon :icon="item.is_super_admin ? 'ri-vip-crown-line' : 'ri-user-line'" size="14" start />
              {{ item.is_super_admin ? 'سوبر أدمن' : 'مشرف عادي' }}
            </VChip>
          </template>

          <!-- Permissions -->
          <template #item.permissions="{ item }">
            <div v-if="item.is_super_admin" class="text-caption text-success font-weight-medium">
              <VIcon icon="ri-infinity-line" size="14" class="me-1" />
              صلاحيات كاملة
            </div>
            <div v-else class="d-flex flex-wrap gap-1 py-1">
              <template v-if="item.permissions?.length">
                <VChip
                  v-for="perm in item.permissions.slice(0, 3)"
                  :key="perm"
                  size="x-small"
                  color="primary"
                  variant="tonal"
                >
                  {{ permissionLabel(perm) }}
                </VChip>
                <VChip v-if="item.permissions.length > 3" size="x-small" color="secondary" variant="tonal">
                  +{{ item.permissions.length - 3 }} أخرى
                </VChip>
              </template>
              <span v-else class="text-caption text-error">بدون صلاحيات</span>
            </div>
          </template>

          <!-- Status -->
          <template #item.is_active="{ item }">
            <VSwitch
              v-model="item.is_active"
              color="success"
              density="compact"
              hide-details
              class="d-inline-flex justify-center"
              @change="toggleActive(item)"
            />
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <VMenu location="bottom end">
              <template #activator="{ props }">
                <VBtn icon="ri-more-2-fill" variant="text" size="small" color="secondary" v-bind="props" />
              </template>
              <VList density="compact" min-width="150" rounded="lg" elevation="3">
                <VListItem @click="openEdit(item)">
                  <template #prepend><VIcon icon="ri-pencil-line" size="18" class="me-2 text-primary" /></template>
                  <VListItemTitle>تعديل</VListItemTitle>
                </VListItem>
                <VListItem @click="confirmDelete(item)">
                  <template #prepend><VIcon icon="ri-delete-bin-line" size="18" class="me-2 text-error" /></template>
                  <VListItemTitle class="text-error">حذف</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </template>
        </VDataTable>
      </VCard>

    </VCol>
  </VRow>

  <!-- Add/Edit Dialog -->
  <VDialog v-model="showDialog" max-width="620" persistent>
    <VCard rounded="lg" elevation="0">
      <VCardTitle class="pa-5 d-flex justify-space-between align-center border-b">
        <span class="font-weight-bold text-h6">
          <VIcon :icon="dialogMode === 'add' ? 'ri-user-add-line' : 'ri-pencil-line'" class="me-2" />
          {{ dialogMode === 'add' ? 'إضافة مشرف جديد' : 'تعديل بيانات المشرف' }}
        </span>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="showDialog = false" />
      </VCardTitle>

      <VCardText class="pa-6">
        <VAlert v-if="formError" type="error" variant="tonal" density="compact" rounded="lg" class="mb-4">
          {{ formError }}
        </VAlert>

        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="formData.name" label="الاسم" variant="outlined" color="primary" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="formData.email" label="البريد الإلكتروني" type="email" variant="outlined" color="primary" dir="ltr" />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="formData.password"
              :label="dialogMode === 'edit' ? 'كلمة المرور الجديدة (اتركها فارغة للإبقاء)' : 'كلمة المرور'"
              type="password"
              variant="outlined"
              color="primary"
              dir="ltr"
              :hint="dialogMode === 'edit' ? 'اتركها فارغة إذا لا تريد تغييرها' : ''"
              persistent-hint
            />
          </VCol>

          <!-- Super Admin Toggle -->
          <VCol cols="12">
            <VCard rounded="lg" color="warning" variant="tonal" class="pa-4">
              <div class="d-flex align-center justify-space-between">
                <div>
                  <div class="font-weight-semibold d-flex align-center gap-2">
                    <VIcon icon="ri-vip-crown-line" size="18" />
                    مشرف رئيسي (صلاحيات كاملة)
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">
                    يشوف ويتحكم بكل شي — تجاهل الصلاحيات المحددة أدناه
                  </div>
                </div>
                <VSwitch v-model="formData.is_super_admin" color="warning" hide-details />
              </div>
            </VCard>
          </VCol>

          <!-- Permissions Grid -->
          <VCol v-if="!formData.is_super_admin" cols="12">
            <div class="text-body-2 font-weight-semibold mb-3">
              <VIcon icon="ri-key-2-line" size="16" class="me-1" />
              تحديد الصفحات المسموح بها:
            </div>
            <VRow dense>
              <VCol
                v-for="perm in ALL_PERMISSIONS"
                :key="perm.key"
                cols="12" sm="6"
              >
                <VCard
                  rounded="lg"
                  :color="formData.permissions.includes(perm.key) ? 'primary' : undefined"
                  :variant="formData.permissions.includes(perm.key) ? 'tonal' : 'outlined'"
                  class="perm-card pa-3 d-flex align-center gap-3 cursor-pointer"
                  @click="
                    formData.permissions.includes(perm.key)
                      ? formData.permissions.splice(formData.permissions.indexOf(perm.key), 1)
                      : formData.permissions.push(perm.key)
                  "
                >
                  <VIcon :icon="perm.icon" size="18" :color="formData.permissions.includes(perm.key) ? 'primary' : 'secondary'" />
                  <span class="text-body-2">{{ perm.label }}</span>
                  <VSpacer />
                  <VIcon
                    :icon="formData.permissions.includes(perm.key) ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line'"
                    :color="formData.permissions.includes(perm.key) ? 'primary' : 'secondary'"
                    size="18"
                  />
                </VCard>
              </VCol>
            </VRow>

            <!-- Select All / Clear -->
            <div class="d-flex gap-2 mt-3">
              <VBtn
                size="small" variant="tonal" color="primary" rounded="lg"
                @click="formData.permissions = ALL_PERMISSIONS.map(p => p.key)"
              >
                تحديد الكل
              </VBtn>
              <VBtn
                size="small" variant="tonal" color="secondary" rounded="lg"
                @click="formData.permissions = []"
              >
                إلغاء الكل
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-5 border-t">
        <VSpacer />
        <VBtn variant="tonal" color="secondary" rounded="lg" @click="showDialog = false">إلغاء</VBtn>
        <VBtn
          color="primary" variant="elevated" rounded="lg" class="px-8"
          :loading="formSaving"
          @click="saveAdmin"
        >
          {{ dialogMode === 'add' ? 'إضافة المشرف' : 'حفظ التعديلات' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Delete Confirm -->
  <VDialog v-model="confirmDeleteDialog" max-width="400">
    <VCard rounded="lg">
      <VCardItem class="text-center pt-8">
        <VAvatar color="error" variant="tonal" size="64" class="mb-4">
          <VIcon icon="ri-user-unfollow-line" size="32" />
        </VAvatar>
        <VCardTitle class="text-h6 font-weight-bold">حذف المشرف</VCardTitle>
      </VCardItem>
      <VCardText class="text-center pb-4">
        هل أنت متأكد من حذف المشرف <strong>{{ deleteTarget?.name }}</strong>؟<br />
        سيتم إلغاء صلاحياته فوراً.
      </VCardText>
      <VCardActions class="pa-5 border-t justify-center gap-3">
        <VBtn variant="tonal" color="secondary" rounded="lg" @click="confirmDeleteDialog = false">إلغاء</VBtn>
        <VBtn variant="elevated" color="error" rounded="lg" @click="deleteAdmin">تأكيد الحذف</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.perm-card {
  transition: all 0.15s ease;
  user-select: none;
}
.perm-card:hover {
  border-color: rgb(var(--v-theme-primary)) !important;
}
</style>
