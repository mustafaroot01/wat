<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePagination } from '@/composables/usePagination'

// States
const activeTab = ref('districts')

// Pagination Districts
const {
  items: districts,
  loading: loadingDist,
  currentPage: distPage,
  totalPages: distTotalPages,
  fetchData: fetchDistricts
} = usePagination<any>('/api/admin/districts')

// Pagination Areas
const {
  items: areas,
  loading: loadingAreas,
  currentPage: areaPage,
  totalPages: areaTotalPages,
  fetchData: fetchAreas
} = usePagination<any>('/api/admin/areas')

// Modals
const isDistrictModalOpen = ref(false)
const isAreaModalOpen = ref(false)
const editingDistrict = ref<any>(null)
const editingArea = ref<any>(null)

// Forms
const districtForm = ref({ name: '', is_active: true })
const areaForm = ref({ name: '', district_id: null as number | null, is_active: true })

const loadAll = async () => {
  await Promise.all([fetchDistricts(1), fetchAreas(1)])
}

// Logic - Districts
const openDistrictModal = (item: any = null) => {
  editingDistrict.value = item
  districtForm.value = item ? { ...item } : { name: '', is_active: true }
  isDistrictModalOpen.value = true
}

const saveDistrict = async () => {
  const method = editingDistrict.value ? 'PUT' : 'POST'
  const url = editingDistrict.value ? `/api/admin/districts/${editingDistrict.value.id}` : '/api/admin/districts'
  
  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(districtForm.value)
    })
    if (res.ok) {
      isDistrictModalOpen.value = false
      fetchDistricts(distPage.value)
    }
  } catch (e) { console.error(e) }
}

const deleteDistrict = async (id: number) => {
  if (!confirm('هل أنت متأكد من حذف القضاء؟ سيتم حذف جميع المناطق التابعة له!')) return
  await fetch(`/api/admin/districts/${id}`, { method: 'DELETE' })
  fetchDistricts(distPage.value)
  fetchAreas(areaPage.value)
}

// Logic - Areas
const openAreaModal = (item: any = null) => {
  editingArea.value = item
  areaForm.value = item ? { ...item } : { name: '', district_id: null, is_active: true }
  isAreaModalOpen.value = true
}

const saveArea = async () => {
  const method = editingArea.value ? 'PUT' : 'POST'
  const url = editingArea.value ? `/api/admin/areas/${editingArea.value.id}` : '/api/admin/areas'
  
  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(areaForm.value)
    })
    if (res.ok) {
      isAreaModalOpen.value = false
      fetchAreas(areaPage.value)
    }
  } catch (e) { console.error(e) }
}

const deleteArea = async (id: number) => {
  if (!confirm('هل أنت متأكد من حذف المنطقة؟')) return
  await fetch(`/api/admin/areas/${id}`, { method: 'DELETE' })
  fetchAreas(areaPage.value)
}

const toggleStatus = async (type: string, id: number) => {
  await fetch(`/api/admin/${type}/${id}/toggle`, { method: 'PATCH' })
  type === 'districts' ? fetchDistricts(distPage.value) : fetchAreas(areaPage.value)
}

onMounted(loadAll)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-map-pin-line" color="primary" />
            إدارة الأقضية والمناطق
          </VCardTitle>
          <VCardSubtitle>إضافة وإدارة الأقضية في المحافظة والمناطق التابعة لها.</VCardSubtitle>
          
          <template #append>
            <VBtn
              v-if="activeTab === 'districts'"
              color="primary"
              prepend-icon="ri-add-line"
              @click="openDistrictModal()"
            >
              إضافة قضاء جديد
            </VBtn>
            <VBtn
              v-else
              color="primary"
              prepend-icon="ri-add-line"
              @click="openAreaModal()"
            >
              إضافة منطقة جديدة
            </VBtn>
          </template>
        </VCardItem>

        <VTabs v-model="activeTab" color="primary">
          <VTab value="districts">الأقضية ({{ districts.length }})</VTab>
          <VTab value="areas">المناطق ({{ areas.length }})</VTab>
        </VTabs>

        <VDivider />

        <VCardText class="pa-0">
          <VWindow v-model="activeTab">
            <!-- Districts Table -->
            <VWindowItem value="districts">
              <VTable class="text-no-wrap">
                <thead>
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>اسم القضاء</th>
                    <th>عدد المناطق</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">العمليات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingDist"><td colspan="5" class="text-center pa-4">جاري التحميل...</td></tr>
                  <tr v-else-if="districts.length === 0"><td colspan="5" class="text-center pa-4">لا توجد أقضية مضافة</td></tr>
                  <tr v-for="(item, index) in districts" :key="item.id">
                    <td>{{ index + 1 + (distPage - 1) * 15 }}</td>
                    <td class="font-weight-medium">{{ item.name }}</td>
                    <td>
                      <VChip size="small" color="info" variant="tonal">{{ item.areas_count }} منطقة</VChip>
                    </td>
                    <td class="text-center">
                      <VSwitch
                        :model-value="!!item.is_active"
                        color="success"
                        density="compact"
                        hide-details
                        class="d-inline-flex"
                        @change="toggleStatus('districts', item.id)"
                      />
                    </td>
                    <td class="text-center">
                      <VBtn icon size="small" variant="text" color="primary" @click="openDistrictModal(item)">
                        <VIcon icon="ri-edit-line" />
                      </VBtn>
                      <VBtn icon size="small" variant="text" color="error" @click="deleteDistrict(item.id)">
                        <VIcon icon="ri-delete-bin-line" />
                      </VBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <!-- Pagination Districts -->
              <VDivider v-if="distTotalPages > 1" />
              <div v-if="distTotalPages > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
                <span class="text-caption text-medium-emphasis">الصفحة {{ distPage }} من {{ distTotalPages }}</span>
                <VPagination
                  v-model="distPage"
                  :length="distTotalPages"
                  :total-visible="5"
                  density="comfortable"
                  variant="tonal"
                  @update:model-value="fetchDistricts"
                />
              </div>
            </VWindowItem>

            <!-- Areas Table -->
            <VWindowItem value="areas">
              <VTable class="text-no-wrap">
                <thead>
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>اسم المنطقة</th>
                    <th>القضاء الملحق</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-center">العمليات</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingAreas"><td colspan="5" class="text-center pa-4">جاري التحميل...</td></tr>
                  <tr v-else-if="areas.length === 0"><td colspan="5" class="text-center pa-4">لا توجد مناطق مضافة</td></tr>
                  <tr v-for="(item, index) in areas" :key="item.id">
                    <td>{{ index + 1 + (areaPage - 1) * 15 }}</td>
                    <td class="font-weight-medium">{{ item.name }}</td>
                    <td>
                      <VChip size="small" border color="secondary" variant="text">
                        {{ item.district?.name || 'غير محدد' }}
                      </VChip>
                    </td>
                    <td class="text-center">
                      <VSwitch
                        :model-value="!!item.is_active"
                        color="success"
                        density="compact"
                        hide-details
                        class="d-inline-flex"
                        @change="toggleStatus('areas', item.id)"
                      />
                    </td>
                    <td class="text-center">
                      <VBtn icon size="small" variant="text" color="primary" @click="openAreaModal(item)">
                        <VIcon icon="ri-edit-line" />
                      </VBtn>
                      <VBtn icon size="small" variant="text" color="error" @click="deleteArea(item.id)">
                        <VIcon icon="ri-delete-bin-line" />
                      </VBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <!-- Pagination Areas -->
              <VDivider v-if="areaTotalPages > 1" />
              <div v-if="areaTotalPages > 1" class="pa-4 d-flex align-center justify-space-between flex-wrap gap-4">
                <span class="text-caption text-medium-emphasis">الصفحة {{ areaPage }} من {{ areaTotalPages }}</span>
                <VPagination
                  v-model="areaPage"
                  :length="areaTotalPages"
                  :total-visible="5"
                  density="comfortable"
                  variant="tonal"
                  @update:model-value="fetchAreas"
                />
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- District Modal -->
  <VDialog v-model="isDistrictModalOpen" max-width="500">
    <VCard rounded="lg" :title="editingDistrict ? 'تعديل قضاء' : 'إضافة قضاء جديد'">
      <VCardText>
        <VTextField v-model="districtForm.name" label="اسم القضاء" variant="outlined" class="mb-4" />
        <VCheckbox v-model="districtForm.is_active" label="نشط" hide-details />
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isDistrictModalOpen = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" @click="saveDistrict">حفظ</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Area Modal -->
  <VDialog v-model="isAreaModalOpen" max-width="500">
    <VCard rounded="lg" :title="editingArea ? 'تعديل منطقة' : 'إضافة منطقة جديدة'">
      <VCardText>
        <VSelect
          v-model="areaForm.district_id"
          :items="districts"
          item-title="name"
          item-value="id"
          label="اختر القضاء"
          variant="outlined"
          class="mb-4"
        />
        <VTextField v-model="areaForm.name" label="اسم المنطقة" variant="outlined" class="mb-4" />
        <VCheckbox v-model="areaForm.is_active" label="نشطة" hide-details />
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isAreaModalOpen = false">إلغاء</VBtn>
        <VBtn color="primary" variant="elevated" @click="saveArea">حفظ</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
