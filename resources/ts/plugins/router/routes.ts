export const routes = [
  { path: '/', redirect: '/dashboard' },
  {
    path: '/',
    component: () => import('@/layouts/default.vue'),
    children: [
      {
        path: 'dashboard',
        component: () => import('@/pages/dashboard.vue'),
      },
      {
        path: 'categories',
        component: () => import('@/pages/categories.vue'),
      },
      {
        path: 'banners',
        component: () => import('@/pages/banners.vue'),
      },
      {
        path: 'banners/create',
        component: () => import('@/pages/banners/create.vue'),
      },
      {
        path: 'banners/:id/edit',
        component: () => import('@/pages/banners/edit.vue'),
      },
      {
        path: 'account-settings',
        component: () => import('@/pages/account-settings.vue'),
      },
      {
        path: 'orders',
        component: () => import('@/pages/orders.vue'),
      },
      {
        path: 'products',
        component: () => import('@/pages/products.vue'),
      },
      {
        path: 'customers',
        component: () => import('@/pages/customers.vue'),
      },
      {
        path: 'notifications',
        component: () => import('@/pages/notifications.vue'),
      },
      {
        path: 'firebase-settings',
        component: () => import('@/pages/firebase-settings.vue'),
      },
      {
        path: 'districts',
        component: () => import('@/pages/districts.vue'),
      },
      {
        path: 'brands',
        component: () => import('@/pages/brands.vue'),
      },
      {
        path: 'settings',
        component: () => import('@/pages/settings.vue'),
      },
      {
        path: 'coupons',
        component: () => import('@/pages/coupons.vue'),
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
        path: '/:pathMatch(.*)*',
        component: () => import('@/pages/[...error].vue'),
      },
    ],
  },
]
