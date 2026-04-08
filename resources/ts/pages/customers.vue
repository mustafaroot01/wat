<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { usePagination } from '@/composables/usePagination'
import { apiFetch } from '@/utils/apiFetch'

interface Customer {
  id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  phone: string;
  gender: 'male' | 'female';
  birth_date: string;
  district_id: number | null;
  area_id: number | null;
  district?: { id: number; name: string };
  area?: { id: number; name: string };
  is_active: boolean;
  is_self_deleted: boolean;
  orders_count: number;
  created_at: string;
}

// Filters
const searchQuery = ref('')
const statusFilter = ref('all')

// Using Composable
const { 
  items: customers, 
  loading,
  totalItems,
  itemsPerPage,
  fetchData: fetchCustomers,
} = usePagination<Customer>('/api/admin/customers')

const headers = [
  { title: 'اسم العميل',          key: 'full_name',    align: 'start' as const, sortable: false },
  { title: 'رقم الهاتف',         key: 'phone',        align: 'start' as const, sortable: true },
  { title: 'الموقع (قضاء/منطقة)', key: 'district',     align: 'start' as const, sortable: false },
  { title: 'عدد الطلبات',       key: 'orders_count', align: 'center' as const, sortable: true },
  { title: 'الحالة',             key: 'is_active',    align: 'center' as const, sortable: false },
  { title: 'العمليات',            key: 'actions',      align: 'center' as const, sortable: false },
]

const sortState = ref<{ sort_by?: string; sort_dir?: string }>({})

const loadCustomers = (page = 1) => {
  const params: any = { ...sortState.value }
  if (searchQuery.value) params.search = searchQuery.value
  if (statusFilter.value !== 'all') params.status = statusFilter.value
  fetchCustomers(page, params)
}

const handleCustomerOptions = (options: { page: number; itemsPerPage: number; sortBy?: { key: string; order: string }[] }) => {
  itemsPerPage.value = options.itemsPerPage
  const sort = options.sortBy?.[0]
  sortState.value = sort ? { sort_by: sort.key, sort_dir: sort.order } : {}
  loadCustomers(options.page)
}

watch([searchQuery, statusFilter], () => { loadCustomers(1) })

// Dialogs
const isEditModalOpen = ref(false)
const isPasswordModalOpen = ref(false)
const editingCustomer = ref<Customer | null>(null)

// Forms
const editForm = ref({
  first_name: '',
  last_name: '',
  gender: 'male',
  birth_date: '',
  district_id: null as number | null,
  area_id: null as number | null,
  phone: ''
})

const passwordForm = ref({
  password: '',
  password_confirmation: ''
})

// Districts/Areas for selects
const districts = ref<any[]>([])
const areas = ref<any[]>([])

const fetchStaticData = async () => {
  try {
    const [dRes, aRes] = await Promise.all([
      apiFetch('/api/admin/districts?per_page=100').then(r => r.json()),
      apiFetch('/api/admin/areas?per_page=100').then(r => r.json())
    ])
    districts.value = dRes.data || []
    areas.value = aRes.data || []
  } catch (e) {
    console.error('Error fetching static data:', e)
  }
}

const openEditModal = (item: Customer) => {
  editingCustomer.value = item
  editForm.value = {
    first_name: item.first_name,
    last_name: item.last_name,
    gender: item.gender,
    birth_date: item.birth_date || '',
    district_id: item.district_id,
    area_id: item.area_id,
    phone: item.phone
  }
  isEditModalOpen.value = true
}

const openPasswordModal = (item: Customer) => {
  editingCustomer.value = item
  passwordForm.value = { password: '', password_confirmation: '' }
  isPasswordModalOpen.value = true
}

const saveCustomer = async () => {
  if (!editingCustomer.value) return
  
  try {
    const res = await apiFetch(`/api/admin/customers/${editingCustomer.value.id}`, {
      method: 'PUT',
      body: JSON.stringify(editForm.value)
    })
    
    if (res.ok) {
      isEditModalOpen.value = false
      loadCustomers(currentPage.value)
    } else {
      const err = await res.json()
      alert('خطأ: ' + JSON.stringify(err.errors))
    }
  } catch (e) { console.error(e) }
}

const savePassword = async () => {
  if (!editingCustomer.value) return
  
  try {
    const res = await apiFetch(`/api/admin/customers/${editingCustomer.value.id}/password`, {
      method: 'PUT',
      body: JSON.stringify(passwordForm.value)
    })
    
    if (res.ok) {
      isPasswordModalOpen.value = false
      alert('تم تغيير كلمة المرور بنجاح')
    } else {
      const err = await res.json()
      alert('خطأ: ' + JSON.stringify(err.errors))
    }
  } catch (e) { console.error(e) }
}

const toggleActive = async (item: Customer) => {
  try {
    const res = await apiFetch(`/api/admin/customers/${item.id}/toggle`, {
      method: 'PATCH'
    })
    if (res.ok) {
      const updated = await res.json()
      item.is_active = updated.data.is_active
    }
  } catch (e) { console.error(e) }
}

const restoreAccount = async (item: Customer) => {
  if (!confirm('هل تريد استعادة هذا الحساب المحذوف؟')) return
  
  try {
    const res = await apiFetch(`/api/admin/customers/${item.id}/restore`, {
      method: 'POST'
    })
    if (res.ok) {
      loadCustomers(1)
    }
  } catch (e) { console.error(e) }
}

onMounted(() => {
  loadCustomers(1)
  fetchStaticData()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-group-line" color="primary" />
            إدارة العملاء والزبائن
          </VCardTitle>
          <VCardSubtitle>إدارة حسابات المستخدمين، تنشيط الحسابات، وتعديل البيانات الشخصية.</VCardSubtitle>
          
          <template #append>
            <div class="d-flex gap-3 align-center">
              <VTextField
                v-model="searchQuery"
                placeholder="بحث بالاسم أو الهاتف..."
                density="compact"
                hide-details
                variant="outlined"
                prepend-inner-icon="ri-search-line"
                style="width: 250px;"
              />
              <VSelect
                v-model="statusFilter"
                :items="[
                  { title: 'الكل', value: 'all' },
                  { title: 'نشط', value: 'active' },
                  { title: 'معطل', value: 'inactive' },
                  { title: 'محذوف', value: 'deleted' }
                ]"
                density="compact"
                hide-details
                variant="outlined"
                style="width: 150px;"
              />
            </div>
          </template>
        </VCardItem>

        <VDivider />

        <VDataTableServer
          :headers="headers"
          :items="customers"
          :items-length="totalItems"
          :loading="loading"
          :items-per-page="15"
          :items-per-page-options="[15, 25, 50, 100]"
          no-data-text="لا يوجد عملاء مطابقين للبحث"
          loading-text="جاري التحميل..."
          class="rounded-0"
          @update:options="handleCustomerOptions"
        >
          <template #item.full_name="{ item }">
            <div class="d-flex align-center gap-3 py-1" :class="{'opacity-60': item.is_self_deleted}">
              <VAvatar color="primary" variant="tonal" size="36" rounded="lg">
                <span class="text-caption">{{ item.first_name?.[0] }}{{ item.last_name?.[0] }}</span>
              </VAvatar>
              <div class="d-flex flex-column">
                <span class="font-weight-medium">{{ item.full_name }}</span>
                <span class="text-caption text-medium-emphasis">{{ item.gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
              </div>
              <VChip v-if="item.is_self_deleted" color="error" size="x-small" class="ms-1">محذوف</VChip>
            </div>
          </template>

          <template #item.phone="{ item }">
            <VChip size="x-small" color="secondary" variant="tonal" prepend-icon="ri-phone-line">{{ item.phone }}</VChip>
          </template>

          <template #item.district="{ item }">
            <span v-if="item.district">{{ item.district.name }} / {{ item.area?.name || '---' }}</span>
            <span v-else class="text-medium-emphasis text-caption">غير محدد</span>
          </template>

          <template #item.orders_count="{ item }">
            <VBtn
              variant="tonal"
              color="primary"
              size="small"
              class="font-weight-bold"
              prepend-icon="ri-shopping-cart-2-line"
              @click="$router.push(`/customers/${item.id}/orders`)"
            >
              {{ item.orders_count }} طلب
            </VBtn>
          </template>

          <template #item.is_active="{ item }">
            <VChip v-if="item.is_self_deleted" color="error" size="small" variant="flat">محذوف</VChip>
            <VSwitch v-else :model-value="item.is_active" color="success" density="compact" hide-details
              class="d-inline-flex" @change="toggleActive(item)" />
          </template>

          <template #item.actions="{ item }">
            <VMenu location="bottom end">
              <template #activator="{ props }">
                <VBtn icon="ri-more-2-fill" variant="text" size="small" v-bind="props" />
              </template>
              <VList density="compact" min-width="160" rounded="lg" elevation="3">
                <VListItem v-if="item.is_self_deleted" @click="restoreAccount(item)">
                  <template #prepend><VIcon icon="ri-restart-line" color="success" class="me-2"/></template>
                  <VListItemTitle>استعادة الحساب</VListItemTitle>
                </VListItem>
                <template v-else>
                  <VListItem @click="openEditModal(item)">
                    <template #prepend><VIcon icon="ri-edit-line" color="primary" class="me-2"/></template>
                    <VListItemTitle>تعديل البيانات</VListItemTitle>
                  </VListItem>
                  <VListItem @click="openPasswordModal(item)">
                    <template #prepend><VIcon icon="ri-lock-password-line" color="warning" class="me-2"/></template>
                    <VListItemTitle>تغيير كلمة المرور</VListItemTitle>
                  </VListItem>
                </template>
              </VList>
            </VMenu>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Edit Customer Dialog -->
  <VDialog v-model="isEditModalOpen" max-width="600">
    <VCard rounded="lg" title="تعديل بيانات العميل">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="editForm.first_name" label="الاسم الأول" variant="outlined" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="editForm.last_name" label="الاسم الثاني" variant="outlined" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="editForm.phone" label="رقم الهاتف" variant="outlined" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField v-model="editForm.birth_date" label="تاريخ الميلاد" type="date" variant="outlined" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="editForm.gender"
              label="الجنس"
              :items="[{title:'ذكر', value:'male'}, {title:'أنثى', value:'female'}]"
              variant="outlined"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect
              v-model="editForm.district_id"
              label="القضاء"
              :items="districts"
              item-title="name"
              item-value="id"
              variant="outlined"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              v-model="editForm.area_id"
              label="المنطقة"
              :items="areas.filter(a => a.district_id === editForm.district_id)"
              item-title="name"
              item-value="id"
              variant="outlined"
              :disabled="!editForm.district_id"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isEditModalOpen = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" @click="saveCustomer">حفظ التغييرات</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Reset Password Dialog -->
  <VDialog v-model="isPasswordModalOpen" max-width="400">
    <VCard rounded="lg" title="تغيير كلمة المرور">
      <VCardText>
        <p class="text-caption text-medium-emphasis mb-4">سيتم تسجيل خروج المستخدم من كافة أجهزته الحالية عند تغيير كلمة المرور.</p>
        <VTextField
          v-model="passwordForm.password"
          label="كلمة المرور الجديدة"
          type="password"
          variant="outlined"
          class="mb-4"
        />
        <VTextField
          v-model="passwordForm.password_confirmation"
          label="تأكيد كلمة المرور"
          type="password"
          variant="outlined"
        />
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isPasswordModalOpen = false">إلغاء</VBtn>
        <VBtn color="warning" variant="elevated" @click="savePassword">تحديث وطرده من الأجهزة</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
