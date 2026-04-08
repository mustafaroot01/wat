<template>
  <div class="forbidden-page d-flex flex-column align-center justify-center text-center">
    <div class="forbidden-code">403</div>
    <VAvatar color="error" variant="tonal" size="96" class="mb-6">
      <VIcon icon="ri-shield-cross-line" size="48" />
    </VAvatar>
    <h1 class="text-h5 font-weight-bold mb-2">ليس لديك صلاحية الوصول</h1>
    <p class="text-body-2 text-medium-emphasis mb-8" style="max-width:360px;">
      هذه الصفحة محمية ولا تملك الإذن للوصول إليها. تواصل مع المشرف الرئيسي لطلب الصلاحية.
    </p>
    <VBtn color="primary" variant="elevated" rounded="lg" prepend-icon="ri-home-line" to="/dashboard">
      العودة للرئيسية
    </VBtn>
  </div>
</template>

<style scoped>
.forbidden-page {
  min-height: 100vh;
  padding: 40px 20px;
}
.forbidden-code {
  font-size: 8rem;
  font-weight: 900;
  line-height: 1;
  color: rgba(var(--v-theme-error), 0.12);
  margin-bottom: -20px;
  letter-spacing: -4px;
}
</style>
