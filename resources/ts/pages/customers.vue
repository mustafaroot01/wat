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
  created_at: string;
}

// Filters
const searchQuery = ref('')
const statusFilter = ref('all')

// Using Composable
const { 
  items: customers, 
  loading, 
  currentPage, 
  totalPages, 
  fetchData: fetchCustomers,
  handlePageChange
} = usePagination<Customer>('/api/admin/customers')

// Extended fetch with filters
const loadCustomers = (page = 1) => {
  const params: any = { page }
  if (searchQuery.value) params.search = searchQuery.value
  if (statusFilter.value !== 'all') params.status = statusFilter.value
  
  fetchCustomers(page, params)
}

// Watch filters
watch([searchQuery, statusFilter], () => {
  loadCustomers(1)
})

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
      loadCustomers(currentPage.value)
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

        <VCardText class="pa-0">
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th>اسم العميل</th>
                <th>رقم الهاتف</th>
                <th>الموقع (قضاء/منطقة)</th>
                <th class="text-center">الحالة</th>
                <th class="text-center">العمليات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-8">
                  <VProgressCircular indeterminate color="primary" />
                </td>
              </tr>
              <tr v-else-if="customers.length === 0">
                <td colspan="6" class="text-center py-8 text-medium-emphasis">لا يوجد عملاء مطابقين للبحث</td>
              </tr>
              <tr v-for="(item, index) in customers" :key="item.id" :class="{'bg-red-lighten-5': item.is_self_deleted}">
                <td class="text-center text-medium-emphasis">{{ index + 1 + (currentPage - 1) * 15 }}</td>
                <td>
                  <div class="d-flex align-center gap-3">
                    <VAvatar color="primary" variant="tonal" size="36">
                      {{ item.first_name?.[0] }}{{ item.last_name?.[0] }}
                    </VAvatar>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">{{ item.full_name }}</span>
                      <span class="text-caption text-medium-emphasis">{{ item.gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                    </div>
                  </div>
                </td>
                <td>{{ item.phone }}</td>
                <td>
                  <span v-if="item.district">
                    {{ item.district.name }} / {{ item.area?.name || '---' }}
                  </span>
                  <span v-else class="text-medium-emphasis text-caption">غير محدد</span>
                </td>
                <td class="text-center">
                  <VChip v-if="item.is_self_deleted" color="error" size="small" variant="flat">محذوف</VChip>
                  <VSwitch
                    v-else
                    :model-value="item.is_active"
                    color="success"
                    density="compact"
                    hide-details
                    class="d-inline-flex"
                    @change="toggleActive(item)"
                  />
                </td>
                <td class="text-center">
                  <VMenu location="bottom end">
                    <template #activator="{ props }">
                      <VBtn icon="ri-more-2-fill" variant="text" size="small" v-bind="props" />
                    </template>
                    <VList density="compact">
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
              @update:model-value="handlePageChange"
            />
          </div>
        </VCardText>
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
