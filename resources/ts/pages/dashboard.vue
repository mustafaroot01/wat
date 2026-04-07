<script setup lang="ts">
const stats = [
  { title: 'إجمالي الطلبات', value: '0', icon: 'ri-shopping-cart-2-line', color: 'primary', sub: 'هذا الشهر' },
  { title: 'إجمالي المبيعات', value: '0 IQD', icon: 'ri-money-dollar-circle-line', color: 'success', sub: 'هذا الشهر' },
  { title: 'العملاء المسجلين', value: '0', icon: 'ri-group-line', color: 'info', sub: 'إجمالي' },
  { title: 'المنتجات المتاحة', value: '0', icon: 'ri-water-flash-line', color: 'warning', sub: 'نشط' },
]

const recentOrders = [
  { id: '#001', customer: '-', product: '-', amount: '-', status: 'pending', date: '-' },
]

const statusColor: Record<string, string> = {
  pending: 'warning',
  completed: 'success',
  cancelled: 'error',
}

const statusLabel: Record<string, string> = {
  pending: 'قيد الانتظار',
  completed: 'مكتمل',
  cancelled: 'ملغي',
}
</script>

<template>
  <VRow>
    <!-- 👉 Welcome Banner -->
    <VCol cols="12">
      <VCard
        class="welcome-card"
        rounded="lg"
        elevation="0"
      >
        <VCardText class="d-flex align-center justify-space-between pa-6">
          <div>
            <h4 class="text-h5 font-weight-bold mb-2">
              أهلاً بك في امواج ديالى 💧
            </h4>
            <p class="text-body-1 mb-4 opacity-80">
              إدارة مبيعات المياه، الطلبات، والعملاء من مكان واحد
            </p>
            <VBtn
              prepend-icon="ri-shopping-cart-2-line"
              to="/orders"
              variant="elevated"
              color="primary"
              rounded="lg"
            >
              عرض الطلبات
            </VBtn>
          </div>
          <div class="welcome-icon d-none d-sm-flex">
            <VIcon
              icon="ri-water-flash-line"
              size="100"
              color="primary"
              style="opacity: 0.15;"
            />
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Stats -->
    <VCol
      v-for="stat in stats"
      :key="stat.title"
      cols="12"
      sm="6"
      xl="3"
    >
      <VCard
        rounded="lg"
        elevation="0"
      >
        <VCardText class="pa-5">
          <div class="d-flex align-center justify-space-between mb-4">
            <VAvatar
              :color="stat.color"
              variant="tonal"
              rounded="lg"
              size="44"
            >
              <VIcon
                :icon="stat.icon"
                size="24"
              />
            </VAvatar>
            <span class="text-caption text-medium-emphasis">{{ stat.sub }}</span>
          </div>
          <h4 class="text-h4 font-weight-bold mb-1">
            {{ stat.value }}
          </h4>
          <p class="text-body-2 text-medium-emphasis mb-0">
            {{ stat.title }}
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Recent Orders -->
    <VCol cols="12">
      <VCard
        rounded="lg"
        elevation="0"
      >
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold d-flex align-center gap-2">
            <VIcon
              icon="ri-shopping-cart-2-line"
              color="primary"
              size="20"
            />
            آخر الطلبات
          </VCardTitle>
          <template #append>
            <VBtn
              variant="tonal"
              color="primary"
              size="small"
              to="/orders"
              prepend-icon="ri-eye-line"
            >
              عرض الكل
            </VBtn>
          </template>
        </VCardItem>

        <VDivider />

        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th style="min-width: 100px;">
                رقم الطلب
              </th>
              <th style="min-width: 150px;">
                العميل
              </th>
              <th>المنتج</th>
              <th>المبلغ</th>
              <th>الحالة</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in recentOrders"
              :key="order.id"
            >
              <td>
                <span class="font-weight-medium text-primary">{{ order.id }}</span>
              </td>
              <td>{{ order.customer }}</td>
              <td>{{ order.product }}</td>
              <td>{{ order.amount }}</td>
              <td>
                <VChip
                  :color="statusColor[order.status]"
                  size="small"
                  rounded="md"
                  label
                >
                  {{ statusLabel[order.status] }}
                </VChip>
              </td>
              <td class="text-medium-emphasis">
                {{ order.date }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>

    <!-- 👉 Quick Actions -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        rounded="lg"
        elevation="0"
        height="100%"
      >
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold">
            إجراءات سريعة
          </VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-5">
          <VRow>
            <VCol cols="6">
              <VBtn
                block
                prepend-icon="ri-add-circle-line"
                color="primary"
                variant="tonal"
                rounded="lg"
                to="/orders"
              >
                طلب جديد
              </VBtn>
            </VCol>
            <VCol cols="6">
              <VBtn
                block
                prepend-icon="ri-water-flash-line"
                color="success"
                variant="tonal"
                rounded="lg"
                to="/products"
              >
                إضافة منتج
              </VBtn>
            </VCol>
            <VCol cols="6">
              <VBtn
                block
                prepend-icon="ri-user-add-line"
                color="info"
                variant="tonal"
                rounded="lg"
                to="/customers"
              >
                إضافة عميل
              </VBtn>
            </VCol>
            <VCol cols="6">
              <VBtn
                block
                prepend-icon="ri-bar-chart-line"
                color="warning"
                variant="tonal"
                rounded="lg"
                to="/orders"
              >
                التقارير
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Store Status -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        rounded="lg"
        elevation="0"
        height="100%"
      >
        <VCardItem class="py-4 px-5">
          <VCardTitle class="font-weight-bold">
            حالة المتجر
          </VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-5">
          <div
            v-for="item in storeStatus"
            :key="item.label"
            class="d-flex align-center justify-space-between mb-4"
          >
            <div class="d-flex align-center gap-3">
              <VAvatar
                :color="item.color"
                variant="tonal"
                size="36"
                rounded="lg"
              >
                <VIcon
                  :icon="item.icon"
                  size="18"
                />
              </VAvatar>
              <span class="text-body-2">{{ item.label }}</span>
            </div>
            <VChip
              :color="item.status === 'نشط' ? 'success' : 'warning'"
              size="small"
              label
              rounded="md"
            >
              {{ item.status }}
            </VChip>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.welcome-card {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08) 0%, rgba(var(--v-theme-primary), 0.02) 100%);
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
}
</style>
