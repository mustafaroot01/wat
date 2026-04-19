export const routes = [
  { path: '/', redirect: '/dashboard' },
  {
    path: '/',
    component: () => import('@/layouts/default.vue'),
    children: [
      {
        path: 'dashboard',
        meta: { permission: 'dashboard' },
        component: () => import('@/pages/dashboard.vue'),
      },
      {
        path: 'categories',
        meta: { permission: 'categories' },
        component: () => import('@/pages/categories.vue'),
      },
      {
        path: 'banners',
        meta: { permission: 'banners' },
        component: () => import('@/pages/banners.vue'),
      },
      {
        path: 'banners/create',
        meta: { permission: 'banners' },
        component: () => import('@/pages/banners/create.vue'),
      },
      {
        path: 'banners/:id/edit',
        meta: { permission: 'banners' },
        component: () => import('@/pages/banners/edit.vue'),
      },
      {
        path: 'account-settings',
        component: () => import('@/pages/account-settings.vue'),
      },
      {
        path: 'orders',
        meta: { permission: 'orders' },
        component: () => import('@/pages/orders.vue'),
      },
      {
        path: 'admin-notifications',
        meta: { permission: 'orders' },
        component: () => import('@/pages/admin-notifications.vue'),
      },
      {
        path: 'products',
        meta: { permission: 'products' },
        component: () => import('@/pages/products.vue'),
      },
      {
        path: 'discounts',
        meta: { permission: 'discounts' },
        component: () => import('@/pages/discounts.vue'),
      },
      {
        path: 'customers',
        meta: { permission: 'customers' },
        component: () => import('@/pages/customers.vue'),
      },
      {
        path: 'notifications',
        meta: { permission: 'notifications' },
        component: () => import('@/pages/notifications.vue'),
      },
      {
        path: 'firebase-settings',
        redirect: '/settings',
      },
      {
        path: 'app-pages',
        meta: { permission: 'store-settings' },
        component: () => import('@/pages/app-pages.vue'),
      },
      {
        path: 'districts',
        meta: { permission: 'districts' },
        component: () => import('@/pages/districts.vue'),
      },
      {
        path: 'brands',
        meta: { permission: 'brands' },
        component: () => import('@/pages/brands.vue'),
      },
      {
        path: 'settings',
        meta: { permission: 'settings' },
        component: () => import('@/pages/settings.vue'),
      },
      {
        path: 'credits',
        meta: { permission: 'credits' },
        component: () => import('@/pages/credits.vue'),
      },
      {
        path: 'coupons',
        meta: { permission: 'coupons' },
        component: () => import('@/pages/coupons.vue'),
      },
      {
        path: 'coupons/:id/usages',
        meta: { permission: 'coupons' },
        component: () => import('@/pages/coupon-usages.vue'),
      },
      {
        path: 'store-settings',
        meta: { permission: 'store-settings' },
        component: () => import('@/pages/store-settings.vue'),
      },
      {
        path: 'customers/:id/orders',
        meta: { permission: 'customers' },
        component: () => import('@/pages/customers/[id]/orders.vue'),
      },
      {
        path: 'admins',
        meta: { permission: 'admins' },
        component: () => import('@/pages/admins.vue'),
      },
      {
        path: 'activity-logs',
        component: () => import('@/pages/activity-logs.vue'),
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/blank.vue'),
    children: [
      {
        path: 'login',
        component: () => import('@/pages/login.vue'),
      },
      {
        path: '403',
        component: () => import('@/pages/403.vue'),
      },
      {
        path: 'invoice/bulk',
        component: () => import('@/pages/invoice/bulk.vue'),
      },
      {
        path: 'invoice/bulk-thermal',
        component: () => import('@/pages/invoice/bulk-thermal.vue'),
      },
      {
        path: 'invoice/:token',
        component: () => import('@/pages/invoice/[token].vue'),
      },
      {
        path: '/:pathMatch(.*)*',
        component: () => import('@/pages/[...error].vue'),
      },
    ],
  },
]

