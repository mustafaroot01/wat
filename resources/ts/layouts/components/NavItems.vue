<script lang="ts" setup>
import VerticalNavSectionTitle from '@/@layouts/components/VerticalNavSectionTitle.vue'
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue'
import VerticalNavGroup from '@layouts/components/VerticalNavGroup.vue'
</script>

<template>
  <!-- 👉 Dashboards -->
  <VerticalNavSectionTitle
    :item="{
      heading: 'الرئيسية',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'لوحة التحكم',
      to: '/dashboard',
      icon: 'ri-home-smile-line',
    }"
  />

  <!-- 👉 Store Management -->
  <VerticalNavSectionTitle
    :item="{
      heading: 'إدارة المتجر',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الطلبات',
      icon: 'ri-shopping-cart-2-line',
      to: '/orders',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الأقسام',
      icon: 'ri-layout-grid-line',
      to: '/categories',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الشركات',
      icon: 'ri-verified-badge-line',
      to: '/brands',
    }"
  />
  <VerticalNavGroup
    :item="{
      title: 'البانرات (الإعلانات)',
      icon: 'ri-advertisement-line',
    }"
  >
    <VerticalNavLink
      :item="{
        title: 'تفاصيل البانرات',
        to: '/banners',
      }"
    />
    <VerticalNavLink
      :item="{
        title: 'إضافة إعلان',
        to: '/banners/create',
      }"
    />
  </VerticalNavGroup>
  <VerticalNavLink
    :item="{
      title: 'المنتجات',
      icon: 'ri-water-flash-line',
      to: '/products',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الأقضية والمناطق',
      icon: 'ri-map-pin-line',
      to: '/districts',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'العملاء',
      icon: 'ri-group-line',
      to: '/customers',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الأخبار والإشعارات',
      icon: 'ri-notification-badge-line',
      to: '/notifications',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'أكواد الخصم',
      icon: 'ri-coupon-3-line',
      to: '/coupons',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'إعدادات المتجر والفاتورة',
      icon: 'ri-store-3-line',
      to: '/store-settings',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'الإعدادات العامة',
      icon: 'ri-settings-4-line',
      to: '/settings',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'إعدادات الفايربيس',
      icon: 'ri-settings-5-line',
      to: '/firebase-settings',
    }"
  />

  <!-- 👉 Settings -->
  <VerticalNavSectionTitle
    :item="{
      heading: 'الإعدادات',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'إعدادات الحساب',
      icon: 'ri-user-settings-line',
      to: '/account-settings',
    }"
  />
</template>
