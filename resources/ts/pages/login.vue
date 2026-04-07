<script setup lang="ts">
import { useTheme } from 'vuetify'

import authV1MaskDark from '@images/pages/auth-v1-mask-dark.png'
import authV1MaskLight from '@images/pages/auth-v1-mask-light.png'
import authV1Tree2 from '@images/pages/auth-v1-tree-2.png'
import authV1Tree from '@images/pages/auth-v1-tree.png'

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const vuetifyTheme = useTheme()

const authThemeMask = computed(() => {
  return vuetifyTheme.global.name.value === 'light'
    ? authV1MaskLight
    : authV1MaskDark
})

const isPasswordVisible = ref(false)
const isLoading = ref(false)

const handleLogin = () => {
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
  }, 1500)
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard
      class="auth-card pa-6 pt-8"
      max-width="460"
      rounded="xl"
      elevation="8"
    >
      <!-- Logo + Brand -->
      <VCardItem class="justify-center pb-2">
        <RouterLink
          to="/"
          class="d-flex flex-column align-center gap-2 text-decoration-none"
        >
          <div class="brand-icon-wrapper">
            <VIcon
              icon="ri-water-flash-line"
              size="48"
              color="primary"
            />
          </div>
          <h2 class="font-weight-bold text-h5 text-primary">
            امواج ديالى
          </h2>
          <span class="text-caption text-medium-emphasis">متجر مياه الشرب</span>
        </RouterLink>
      </VCardItem>

      <VDivider class="my-4" />

      <!-- Welcome Text -->
      <VCardText class="pt-2 pb-4 text-center">
        <h4 class="text-h6 font-weight-bold mb-1">
          أهلاً وسهلاً 👋
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          سجّل دخولك للوصول إلى لوحة التحكم
        </p>
      </VCardText>

      <!-- Form -->
      <VCardText>
        <VForm @submit.prevent="handleLogin">
          <VRow>
            <!-- Email -->
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="البريد الإلكتروني"
                placeholder="admin@amwajdiyala.com"
                type="email"
                prepend-inner-icon="ri-mail-line"
                variant="outlined"
                density="comfortable"
                dir="ltr"
              />
            </VCol>

            <!-- Password -->
            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="كلمة المرور"
                placeholder="············"
                :type="isPasswordVisible ? 'text' : 'password'"
                autocomplete="current-password"
                prepend-inner-icon="ri-lock-line"
                :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                variant="outlined"
                density="comfortable"
                dir="ltr"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />

              <!-- Remember me + Forgot Password -->
              <div class="d-flex align-center justify-space-between flex-wrap mt-2 mb-6">
                <VCheckbox
                  v-model="form.remember"
                  label="تذكرني"
                  density="compact"
                  hide-details
                />
                <a
                  class="text-primary text-body-2"
                  href="javascript:void(0)"
                >
                  نسيت كلمة المرور؟
                </a>
              </div>

              <!-- Login Button -->
              <VBtn
                block
                type="submit"
                size="large"
                :loading="isLoading"
                prepend-icon="ri-login-box-line"
              >
                تسجيل الدخول
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <!-- Footer -->
      <VCardText class="text-center pt-0">
        <span class="text-body-2 text-medium-emphasis">© {{ new Date().getFullYear() }} امواج ديالى — جميع الحقوق محفوظة</span>
      </VCardText>
    </VCard>

    <VImg
      class="auth-footer-start-tree d-none d-md-block"
      :src="authV1Tree"
      :width="250"
    />

    <VImg
      :src="authV1Tree2"
      class="auth-footer-end-tree d-none d-md-block"
      :width="350"
    />

    <!-- bg img -->
    <VImg
      class="auth-footer-mask d-none d-md-block"
      :src="authThemeMask"
    />
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";

.brand-icon-wrapper {
  background: rgba(var(--v-theme-primary), 0.1);
  border-radius: 50%;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-card {
  backdrop-filter: blur(10px);
}
</style>
