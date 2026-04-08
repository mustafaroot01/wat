<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route  = useRoute()

const form = ref({ email: '', password: '' })
const isPasswordVisible = ref(false)
const isLoading = ref(false)
const errorMsg  = ref('')
const formRef   = ref()

const rules = {
  required: (v: string) => !!v || 'هذا الحقل مطلوب',
  email:    (v: string) => /.+@.+\..+/.test(v) || 'البريد الإلكتروني غير صحيح',
}

const handleLogin = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  isLoading.value = true
  errorMsg.value  = ''

  try {
    const response = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: form.value.email, password: form.value.password }),
    })
    const data = await response.json()

    if (response.ok && data.success) {
      localStorage.setItem('accessToken', data.token)
      localStorage.setItem('userData', JSON.stringify(data.admin))
      const redirect = route.query.redirect as string
      router.push(redirect || '/')
    } else {
      errorMsg.value = data.message || 'فشل تسجيل الدخول. يرجى التحقق من بياناتك.'
    }
  } catch {
    errorMsg.value = 'حدث خطأ في الاتصال بالسيرفر.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-page">

    <!-- ═══ Branding Panel (left on desktop, top on mobile) ═══ -->
    <div class="brand-panel d-flex flex-column align-center justify-center">
      <!-- Decorative circles -->
      <div class="deco-circle deco-circle-1" />
      <div class="deco-circle deco-circle-2" />
      <div class="deco-circle deco-circle-3" />

      <div class="brand-content text-center">
        <img src="/logo.png" alt="امواج ديالى" class="brand-logo mb-6" />
        <h1 class="brand-title">معمل امواج ديالى</h1>
        <p class="brand-subtitle">لإنتاج وتعبئة المياه</p>

        <div class="brand-divider my-6" />

        <p class="brand-desc">
          لوحة التحكم الإدارية<br />
          <span>إدارة المنتجات · الطلبات · الزبائن</span>
        </p>

        <!-- Stats row -->
        <div class="brand-stats d-flex justify-center gap-6 mt-8">
          <div class="stat-item text-center">
            <div class="stat-icon"><VIcon icon="ri-shopping-cart-2-line" size="22" /></div>
            <div class="stat-label">الطلبات</div>
          </div>
          <div class="stat-sep" />
          <div class="stat-item text-center">
            <div class="stat-icon"><VIcon icon="ri-box-3-line" size="22" /></div>
            <div class="stat-label">المنتجات</div>
          </div>
          <div class="stat-sep" />
          <div class="stat-item text-center">
            <div class="stat-icon"><VIcon icon="ri-group-line" size="22" /></div>
            <div class="stat-label">الزبائن</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Form Panel (right) ═══ -->
    <div class="form-panel d-flex flex-column align-center justify-center">
      <div class="form-wrapper">

        <!-- Mobile logo -->
        <div class="mobile-logo d-flex d-md-none flex-column align-center mb-6">
          <img src="/logo.png" alt="امواج ديالى" style="width:80px;" />
          <h2 class="mt-2 font-weight-bold text-primary">معمل امواج ديالى</h2>
        </div>

        <!-- Heading -->
        <div class="mb-7">
          <h2 class="form-heading">تسجيل الدخول</h2>
          <p class="form-subheading">أدخل بياناتك للوصول إلى لوحة التحكم</p>
        </div>

        <!-- Error -->
        <VAlert
          v-if="errorMsg"
          type="error"
          variant="tonal"
          density="compact"
          rounded="lg"
          class="mb-5"
          closable
          @click:close="errorMsg = ''"
        >
          {{ errorMsg }}
        </VAlert>

        <!-- Form -->
        <VForm ref="formRef" @submit.prevent="handleLogin">

          <div class="field-label">البريد الإلكتروني</div>
          <VTextField
            v-model="form.email"
            placeholder="admin@amwajdiyala.com"
            type="email"
            prepend-inner-icon="ri-mail-line"
            variant="outlined"
            density="comfortable"
            rounded="lg"
            dir="ltr"
            class="mb-4"
            :rules="[rules.required, rules.email]"
          />

          <div class="field-label">كلمة المرور</div>
          <VTextField
            v-model="form.password"
            placeholder="············"
            :type="isPasswordVisible ? 'text' : 'password'"
            autocomplete="current-password"
            prepend-inner-icon="ri-lock-line"
            :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
            variant="outlined"
            density="comfortable"
            rounded="lg"
            dir="ltr"
            class="mb-6"
            :rules="[rules.required]"
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
          />

          <VBtn
            block
            type="submit"
            size="x-large"
            rounded="lg"
            :loading="isLoading"
            class="login-btn"
          >
            <VIcon start icon="ri-login-box-line" />
            تسجيل الدخول
          </VBtn>
        </VForm>

        <p class="text-center text-caption text-medium-emphasis mt-8">
          © {{ new Date().getFullYear() }} معمل امواج ديالى — جميع الحقوق محفوظة
        </p>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* ── Layout ────────────────────────────────────────────── */
.login-page {
  min-height: 100vh;
  display: flex;
  flex-direction: row;
}

/* ── Brand Panel ───────────────────────────────────────── */
.brand-panel {
  position: relative;
  width: 45%;
  min-height: 100vh;
  background: linear-gradient(145deg, #0d47a1 0%, #1565c0 40%, #1b7a3e 100%);
  overflow: hidden;
  padding: 40px;
}

.deco-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
}
.deco-circle-1 { width: 340px; height: 340px; top: -80px;  right: -80px; }
.deco-circle-2 { width: 220px; height: 220px; bottom: 60px; left: -60px; }
.deco-circle-3 { width: 120px; height: 120px; bottom: 180px; right: 40px; background: rgba(255,255,255,0.04); }

.brand-content { position: relative; z-index: 1; }

.brand-logo {
  width: 130px;
  height: 130px;
  object-fit: contain;
  filter: drop-shadow(0 8px 24px rgba(0,0,0,0.3));
}

.brand-title {
  color: #fff;
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.brand-subtitle {
  color: rgba(255,255,255,0.75);
  font-size: 0.95rem;
  margin-top: 4px;
}

.brand-divider {
  width: 60px;
  height: 3px;
  background: rgba(255,255,255,0.35);
  border-radius: 2px;
  margin: 0 auto;
}

.brand-desc {
  color: rgba(255,255,255,0.85);
  font-size: 1rem;
  line-height: 1.8;
}
.brand-desc span {
  color: rgba(255,255,255,0.6);
  font-size: 0.85rem;
}

.stat-item .stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: rgba(255,255,255,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  margin: 0 auto 6px;
}
.stat-label {
  color: rgba(255,255,255,0.75);
  font-size: 0.78rem;
}
.stat-sep {
  width: 1px;
  height: 40px;
  background: rgba(255,255,255,0.2);
  align-self: center;
}

/* ── Form Panel ────────────────────────────────────────── */
.form-panel {
  flex: 1;
  min-height: 100vh;
  background: #f8fafc;
  padding: 40px 20px;
}

.form-wrapper {
  width: 100%;
  max-width: 420px;
}

.form-heading {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0d47a1;
  margin-bottom: 6px;
}

.form-subheading {
  color: #78909c;
  font-size: 0.92rem;
}

.field-label {
  font-size: 0.87rem;
  font-weight: 600;
  color: #37474f;
  margin-bottom: 6px;
}

.login-btn {
  background: linear-gradient(90deg, #0d47a1, #1b7a3e) !important;
  color: #fff !important;
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 20px rgba(13,71,161,0.35) !important;
  transition: opacity 0.2s;
}
.login-btn:hover { opacity: 0.92; }

/* ── Mobile ────────────────────────────────────────────── */
@media (max-width: 960px) {
  .login-page   { flex-direction: column; }
  .brand-panel  { display: none !important; }
  .form-panel   { min-height: 100vh; background: #fff; }
}
</style>
