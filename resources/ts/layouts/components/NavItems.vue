<script lang="ts" setup>
import VerticalNavSectionTitle from '@/@layouts/components/VerticalNavSectionTitle.vue'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import VerticalNavGroup from '@layouts/components/VerticalNavGroup.vue'
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue'

const { can, isSuperAdmin } = useAdminPermissions()
</script>

<template>
  <!-- 👉 الرئيسية -->
  <VerticalNavSectionTitle :item="{ heading: 'الرئيسية' }" />
  <VerticalNavLink v-if="can('dashboard')" :item="{ title: 'لوحة التحكم', to: '/dashboard', icon: 'ri-home-smile-line' }" />

  <!-- 👉 إدارة المتجر -->
  <VerticalNavSectionTitle :item="{ heading: 'إدارة المتجر' }" />
  <VerticalNavLink v-if="can('orders')"   :item="{ title: 'الطلبات',           icon: 'ri-shopping-cart-2-line',    to: '/orders'    }" />
  <VerticalNavLink v-if="can('categories')" :item="{ title: 'الأقسام',          icon: 'ri-layout-grid-line',        to: '/categories' }" />
  <VerticalNavLink v-if="can('brands')"   :item="{ title: 'الشركات',            icon: 'ri-verified-badge-line',     to: '/brands'    }" />

  <VerticalNavGroup v-if="can('banners')" :item="{ title: 'البانرات (الإعلانات)', icon: 'ri-advertisement-line' }">
    <VerticalNavLink :item="{ title: 'تفاصيل البانرات', to: '/banners' }" />
    <VerticalNavLink :item="{ title: 'إضافة إعلان',    to: '/banners/create' }" />
  </VerticalNavGroup>

  <VerticalNavLink v-if="can('products')"  :item="{ title: 'المنتجات',           icon: 'ri-water-flash-line',        to: '/products'  }" />
  <VerticalNavLink v-if="can('discounts')" :item="{ title: 'خصومات مميزة',       icon: 'ri-price-tag-3-line',        to: '/discounts' }" />
  <VerticalNavLink v-if="can('districts')" :item="{ title: 'الأقضية والمناطق',   icon: 'ri-map-pin-line',            to: '/districts' }" />
  <VerticalNavLink v-if="can('customers')" :item="{ title: 'العملاء',            icon: 'ri-group-line',              to: '/customers' }" />
  <VerticalNavLink v-if="can('notifications')" :item="{ title: 'الأخبار والإشعارات', icon: 'ri-notification-badge-line', to: '/notifications' }" />
  <VerticalNavLink v-if="can('coupons')"   :item="{ title: 'أكواد الخصم',        icon: 'ri-coupon-3-line',           to: '/coupons'   }" />
  <VerticalNavLink v-if="can('store-settings')" :item="{ title: 'إعدادات المتجر والفاتورة', icon: 'ri-store-3-line', to: '/store-settings' }" />
  <VerticalNavLink v-if="can('store-settings')" :item="{ title: 'صفحات التطبيق والتحديث', icon: 'ri-pages-line', to: '/app-pages' }" />
  <VerticalNavLink v-if="can('settings')"  :item="{ title: 'الإعدادات العامة',   icon: 'ri-settings-4-line',         to: '/settings'  }" />
  <VerticalNavLink v-if="can('settings')"  :item="{ title: 'إدارة الرصيد',        icon: 'ri-coins-line',               to: '/credits'   }" />

  <!-- 👉 الإعدادات -->
  <VerticalNavSectionTitle :item="{ heading: 'الإعدادات' }" />
  <VerticalNavLink :item="{ title: 'إعدادات الحساب', icon: 'ri-user-settings-line', to: '/account-settings' }" />

  <!-- 👉 إدارة المشرفين — سوبر أدمن فقط -->
  <template v-if="isSuperAdmin()">
    <VerticalNavSectionTitle :item="{ heading: 'الصلاحيات' }" />
    <VerticalNavLink :item="{ title: 'المشرفون والصلاحيات', icon: 'ri-shield-user-line', to: '/admins' }" />
  </template>
</template>

