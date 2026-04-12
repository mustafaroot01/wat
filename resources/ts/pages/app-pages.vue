<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { apiFetch } from '@/utils/apiFetch'

const activeTab   = ref('privacy')
const loading     = ref(false)
const savingPrivacy = ref(false)
const savingUpdate  = ref(false)
const success     = ref('')
const error       = ref('')

// ── Privacy Policy ────────────────────────────────────────────────
const editorRef   = ref<HTMLElement | null>(null)
const privacyHtml = ref('')
const showSource  = ref(false)
const sourceText  = ref('')

const DEFAULT_PRIVACY = `<h2>سياسة الخصوصية</h2>
<p>آخر تحديث: ${new Date().toLocaleDateString('ar-IQ')}</p>

<h2>١. مقدمة</h2>
<p>نرحب بك في تطبيق <strong>امواج ديالى</strong>. نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية وفق هذه السياسة.</p>

<h2>٢. البيانات التي نجمعها</h2>
<ul>
  <li>معلومات التسجيل: الاسم، رقم الهاتف.</li>
  <li>بيانات الطلبات: العناوين، المنتجات المطلوبة، طريقة الدفع.</li>
  <li>بيانات الجهاز: نوع الجهاز، نظام التشغيل، رمز FCM للإشعارات.</li>
</ul>

<h2>٣. كيف نستخدم بياناتك</h2>
<ul>
  <li>معالجة وتسليم طلباتك.</li>
  <li>التواصل معك بشأن طلباتك وخدمة العملاء.</li>
  <li>إرسال الإشعارات المتعلقة بالعروض والطلبات.</li>
  <li>تحسين خدماتنا وتجربة المستخدم.</li>
</ul>

<h2>٤. مشاركة البيانات</h2>
<p>لا نبيع بياناتك الشخصية لأطراف ثالثة. قد نشارك البيانات مع شركاء التوصيل لإتمام طلباتك فقط.</p>

<h2>٥. أمان البيانات</h2>
<p>نستخدم تقنيات تشفير SSL وإجراءات أمنية متقدمة لحماية بياناتك من الوصول غير المصرح به.</p>

<h2>٦. حقوقك</h2>
<ul>
  <li>يحق لك طلب الاطلاع على بياناتك أو تعديلها أو حذفها في أي وقت.</li>
  <li>يمكنك حذف حسابك من إعدادات التطبيق.</li>
</ul>

<h2>٧. التواصل معنا</h2>
<p>لأي استفسار حول سياسة الخصوصية، تواصل معنا عبر معلومات التواصل المتاحة في التطبيق.</p>`

const execCmd = (cmd: string, val?: string) => {
  editorRef.value?.focus()
  document.execCommand(cmd, false, val ?? '')
  syncFromEditor()
}

const syncFromEditor = () => {
  if (editorRef.value) privacyHtml.value = editorRef.value.innerHTML
}

const toggleSource = () => {
  if (!showSource.value) {
    sourceText.value = privacyHtml.value
    showSource.value = true
  } else {
    privacyHtml.value = sourceText.value
    showSource.value  = false
    nextTick(() => {
      if (editorRef.value) editorRef.value.innerHTML = privacyHtml.value
    })
  }
}

const savePrivacy = async () => {
  savingPrivacy.value = true
  success.value = ''
  error.value   = ''
  try {
    const fd = new FormData()
    fd.append('privacy_policy', privacyHtml.value)
    const res = await apiFetch('/api/admin/store-settings', { method: 'POST', body: fd })
    if (res.ok) {
      success.value = 'تم حفظ سياسة الخصوصية بنجاح!'
      setTimeout(() => success.value = '', 4000)
    } else { error.value = 'فشل الحفظ' }
  } catch { error.value = 'خطأ في الاتصال' } finally { savingPrivacy.value = false }
}

// ── App Update Settings ───────────────────────────────────────────
const update = ref({
  min_version_android:     '1.0.0',
  current_version_android: '1.0.0',
  update_url_android:      '',
  force_update_android:    false,
  min_version_ios:         '1.0.0',
  current_version_ios:     '1.0.0',
  update_url_ios:          '',
  force_update_ios:        false,
})

const saveUpdate = async () => {
  savingUpdate.value = true
  success.value = ''
  error.value   = ''
  try {
    const fd = new FormData()
    fd.append('min_version_android',     update.value.min_version_android)
    fd.append('current_version_android', update.value.current_version_android)
    fd.append('update_url_android',      update.value.update_url_android)
    fd.append('force_update_android',    update.value.force_update_android ? '1' : '0')
    fd.append('min_version_ios',         update.value.min_version_ios)
    fd.append('current_version_ios',     update.value.current_version_ios)
    fd.append('update_url_ios',          update.value.update_url_ios)
    fd.append('force_update_ios',        update.value.force_update_ios ? '1' : '0')
    const res = await apiFetch('/api/admin/store-settings', { method: 'POST', body: fd })
    if (res.ok) {
      success.value = 'تم حفظ إعدادات التحديث بنجاح!'
      setTimeout(() => success.value = '', 4000)
    } else { error.value = 'فشل الحفظ' }
  } catch { error.value = 'خطأ في الاتصال' } finally { savingUpdate.value = false }
}

// ── Load ──────────────────────────────────────────────────────────
const loadAll = async () => {
  loading.value = true
  try {
    const res  = await apiFetch('/api/admin/store-settings')
    const data = await res.json()
    privacyHtml.value = data.privacy_policy || DEFAULT_PRIVACY
    nextTick(() => {
      if (editorRef.value) editorRef.value.innerHTML = privacyHtml.value
    })
    update.value.min_version_android     = data.min_version_android     ?? '1.0.0'
    update.value.current_version_android = data.current_version_android ?? '1.0.0'
    update.value.update_url_android      = data.update_url_android      ?? ''
    update.value.force_update_android    = data.force_update_android    === '1'
    update.value.min_version_ios         = data.min_version_ios         ?? '1.0.0'
    update.value.current_version_ios     = data.current_version_ios     ?? '1.0.0'
    update.value.update_url_ios          = data.update_url_ios          ?? ''
    update.value.force_update_ios        = data.force_update_ios        === '1'
  } catch (e) { console.error(e) } finally { loading.value = false }
}

onMounted(loadAll)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard rounded="lg" elevation="0">
        <VCardItem class="pa-5">
          <VCardTitle class="d-flex align-center gap-2 font-weight-bold">
            <VIcon icon="ri-pages-line" color="primary" size="24" />
            صفحات التطبيق
          </VCardTitle>
          <VCardSubtitle>سياسة الخصوصية · إعدادات التحديث الإجباري</VCardSubtitle>
        </VCardItem>

        <VTabs v-model="activeTab" color="primary">
          <VTab value="privacy">
            <VIcon icon="ri-shield-check-line" size="18" class="me-2" />سياسة الخصوصية
          </VTab>
          <VTab value="update">
            <VIcon icon="ri-refresh-line" size="18" class="me-2" />تحديثات التطبيق
          </VTab>
        </VTabs>

        <VDivider />

        <VCardText class="pa-5">
          <div v-if="loading" class="text-center py-12">
            <VProgressCircular indeterminate color="primary" size="48" />
          </div>

          <VWindow v-else v-model="activeTab">

            <!-- ══════════════════════ Privacy Policy ══════════════════════ -->
            <VWindowItem value="privacy">
              <VAlert v-if="success" type="success" variant="tonal" density="compact" class="mb-4" closable @click:close="success=''">{{ success }}</VAlert>
              <VAlert v-if="error"   type="error"   variant="tonal" density="compact" class="mb-4" closable @click:close="error=''">{{ error }}</VAlert>

              <VRow>
                <VCol cols="12" md="8">
                  <div class="editor-wrap rounded-lg overflow-hidden border">

                    <!-- Toolbar -->
                    <div class="editor-toolbar d-flex flex-wrap gap-1 pa-2 align-center">
                      <!-- Text Style -->
                      <VBtn size="x-small" variant="text" title="عريض" @click="execCmd('bold')"><strong>B</strong></VBtn>
                      <VBtn size="x-small" variant="text" title="مائل"  @click="execCmd('italic')"><em>I</em></VBtn>
                      <VBtn size="x-small" variant="text" title="خط تحت" @click="execCmd('underline')"><u>U</u></VBtn>
                      <VDivider vertical class="mx-1" style="height:24px" />
                      <!-- Headings -->
                      <VBtn size="x-small" variant="tonal" @click="execCmd('formatBlock','h2')">H2</VBtn>
                      <VBtn size="x-small" variant="tonal" @click="execCmd('formatBlock','h3')">H3</VBtn>
                      <VBtn size="x-small" variant="tonal" @click="execCmd('formatBlock','p')">P</VBtn>
                      <VDivider vertical class="mx-1" style="height:24px" />
                      <!-- Lists -->
                      <VBtn size="x-small" variant="tonal" title="قائمة نقطية" @click="execCmd('insertUnorderedList')">
                        <VIcon size="16">ri-list-unordered</VIcon>
                      </VBtn>
                      <VBtn size="x-small" variant="tonal" title="قائمة مرقمة" @click="execCmd('insertOrderedList')">
                        <VIcon size="16">ri-list-ordered</VIcon>
                      </VBtn>
                      <VDivider vertical class="mx-1" style="height:24px" />
                      <!-- Align -->
                      <VBtn size="x-small" variant="text" @click="execCmd('justifyRight')"><VIcon size="16">ri-align-right</VIcon></VBtn>
                      <VBtn size="x-small" variant="text" @click="execCmd('justifyCenter')"><VIcon size="16">ri-align-center</VIcon></VBtn>
                      <VBtn size="x-small" variant="text" @click="execCmd('justifyLeft')"><VIcon size="16">ri-align-left</VIcon></VBtn>
                      <VDivider vertical class="mx-1" style="height:24px" />
                      <!-- Undo / Redo -->
                      <VBtn size="x-small" variant="text" @click="execCmd('undo')"><VIcon size="16">ri-arrow-go-back-line</VIcon></VBtn>
                      <VBtn size="x-small" variant="text" @click="execCmd('redo')"><VIcon size="16">ri-arrow-go-forward-line</VIcon></VBtn>
                      <VDivider vertical class="mx-1" style="height:24px" />
                      <!-- Source toggle -->
                      <VBtn
                        size="x-small"
                        :variant="showSource ? 'flat' : 'tonal'"
                        :color="showSource ? 'primary' : undefined"
                        @click="toggleSource"
                      >
                        <VIcon size="16">ri-code-line</VIcon>
                        <span class="ms-1">HTML</span>
                      </VBtn>
                      <VSpacer />
                      <VBtn size="x-small" variant="text" color="error" title="مسح التنسيق" @click="execCmd('removeFormat')">
                        <VIcon size="16">ri-eraser-line</VIcon>
                      </VBtn>
                    </div>

                    <VDivider />

                    <!-- WYSIWYG Editor -->
                    <div
                      v-show="!showSource"
                      ref="editorRef"
                      contenteditable="true"
                      class="editor-body pa-4"
                      dir="rtl"
                      @input="syncFromEditor"
                    />

                    <!-- Source Editor -->
                    <VTextarea
                      v-show="showSource"
                      v-model="sourceText"
                      class="editor-source font-monospace"
                      variant="plain"
                      hide-details
                      rows="20"
                      dir="ltr"
                    />
                  </div>

                  <div class="d-flex align-center gap-3 mt-4">
                    <VBtn color="primary" :loading="savingPrivacy" prepend-icon="ri-save-line" @click="savePrivacy">
                      حفظ سياسة الخصوصية
                    </VBtn>
                    <VBtn variant="tonal" color="secondary" :href="'/privacy-policy'" target="_blank" prepend-icon="ri-external-link-line">
                      معاينة الصفحة العامة
                    </VBtn>
                  </div>
                </VCol>

                <VCol cols="12" md="4">
                  <VCard variant="tonal" color="primary" rounded="lg" class="mb-4">
                    <VCardText class="pa-4">
                      <div class="d-flex gap-2 align-start">
                        <VIcon icon="ri-information-line" color="primary" size="22" class="mt-1" />
                        <div>
                          <div class="font-weight-bold text-primary mb-1">متطلب متجر أبل وأندرويد</div>
                          <p class="text-body-2 mb-2">سياسة الخصوصية مطلوبة لأي تطبيق يجمع بيانات المستخدمين. رابطها العام:</p>
                          <VChip
                            color="primary"
                            variant="outlined"
                            size="small"
                            :href="'/privacy-policy'"
                            target="_blank"
                            prepend-icon="ri-link"
                          >
                            /privacy-policy
                          </VChip>
                        </div>
                      </div>
                    </VCardText>
                  </VCard>

                  <VCard variant="outlined" rounded="lg">
                    <VCardText class="pa-4">
                      <div class="font-weight-bold mb-3 d-flex align-center gap-2">
                        <VIcon icon="ri-eye-line" size="18" />معاينة
                      </div>
                      <div
                        class="preview-body text-body-2"
                        dir="rtl"
                        v-html="privacyHtml"
                        style="max-height:300px;overflow-y:auto;"
                      />
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- ══════════════════════ App Update ══════════════════════ -->
            <VWindowItem value="update">
              <VAlert v-if="success" type="success" variant="tonal" density="compact" class="mb-4" closable @click:close="success=''">{{ success }}</VAlert>
              <VAlert v-if="error"   type="error"   variant="tonal" density="compact" class="mb-4" closable @click:close="error=''">{{ error }}</VAlert>

              <!-- Endpoint info -->
              <VAlert type="info" variant="tonal" class="mb-6" rounded="lg" density="compact">
                <strong>API للتطبيق:</strong>
                <code class="ms-2">GET /api/app/check-update?platform=android&version=1.0.0</code>
              </VAlert>

              <VRow>
                <!-- Android -->
                <VCol cols="12" md="6">
                  <VCard variant="outlined" rounded="lg" class="h-100">
                    <VCardItem>
                      <VCardTitle class="d-flex align-center gap-2">
                        <VIcon icon="ri-android-line" color="success" size="22" />Android
                      </VCardTitle>
                    </VCardItem>
                    <VCardText>
                      <VTextField
                        v-model="update.current_version_android"
                        label="الإصدار الحالي (Latest)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="1.0.0"
                        hint="الإصدار الأحدث المتاح في المتجر"
                        persistent-hint
                        dir="ltr"
                      />
                      <VTextField
                        v-model="update.min_version_android"
                        label="أقل إصدار مقبول (Min)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="1.0.0"
                        hint="أي إصدار أقل من هذا سيُجبر على التحديث"
                        persistent-hint
                        color="error"
                        dir="ltr"
                      />
                      <VTextField
                        v-model="update.update_url_android"
                        label="رابط التحديث (Google Play)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="https://play.google.com/store/apps/details?id=..."
                        dir="ltr"
                      />
                      <div class="d-flex align-center gap-2 mt-2">
                        <VSwitch
                          v-model="update.force_update_android"
                          color="error"
                          hide-details
                          density="compact"
                        />
                        <div>
                          <div class="font-weight-medium text-body-2">تفعيل التحديث الإجباري</div>
                          <div class="text-caption text-medium-emphasis">
                            {{ update.force_update_android ? 'مفعّل — المستخدمون دون الحد الأدنى مجبرون على التحديث' : 'معطّل' }}
                          </div>
                        </div>
                      </div>
                      <VAlert
                        v-if="update.force_update_android"
                        type="warning"
                        variant="tonal"
                        density="compact"
                        class="mt-3"
                        rounded="lg"
                      >
                        أي مستخدم يملك إصداراً أقل من <strong>{{ update.min_version_android }}</strong> لن يتمكن من استخدام التطبيق.
                      </VAlert>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- iOS -->
                <VCol cols="12" md="6">
                  <VCard variant="outlined" rounded="lg" class="h-100">
                    <VCardItem>
                      <VCardTitle class="d-flex align-center gap-2">
                        <VIcon icon="ri-apple-line" color="grey-darken-3" size="22" />iOS (iPhone)
                      </VCardTitle>
                    </VCardItem>
                    <VCardText>
                      <VTextField
                        v-model="update.current_version_ios"
                        label="الإصدار الحالي (Latest)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="1.0.0"
                        hint="الإصدار الأحدث المتاح في App Store"
                        persistent-hint
                        dir="ltr"
                      />
                      <VTextField
                        v-model="update.min_version_ios"
                        label="أقل إصدار مقبول (Min)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="1.0.0"
                        hint="أي إصدار أقل من هذا سيُجبر على التحديث"
                        persistent-hint
                        color="error"
                        dir="ltr"
                      />
                      <VTextField
                        v-model="update.update_url_ios"
                        label="رابط التحديث (App Store)"
                        variant="outlined"
                        density="compact"
                        class="mb-3"
                        placeholder="https://apps.apple.com/app/idXXXXXXXXX"
                        dir="ltr"
                      />
                      <div class="d-flex align-center gap-2 mt-2">
                        <VSwitch
                          v-model="update.force_update_ios"
                          color="error"
                          hide-details
                          density="compact"
                        />
                        <div>
                          <div class="font-weight-medium text-body-2">تفعيل التحديث الإجباري</div>
                          <div class="text-caption text-medium-emphasis">
                            {{ update.force_update_ios ? 'مفعّل — المستخدمون دون الحد الأدنى مجبرون على التحديث' : 'معطّل' }}
                          </div>
                        </div>
                      </div>
                      <VAlert
                        v-if="update.force_update_ios"
                        type="warning"
                        variant="tonal"
                        density="compact"
                        class="mt-3"
                        rounded="lg"
                      >
                        أي مستخدم يملك إصداراً أقل من <strong>{{ update.min_version_ios }}</strong> لن يتمكن من استخدام التطبيق.
                      </VAlert>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- API Response Sample -->
                <VCol cols="12">
                  <VCard variant="tonal" color="grey" rounded="lg">
                    <VCardItem>
                      <VCardTitle class="text-body-1 font-weight-bold d-flex align-center gap-2">
                        <VIcon icon="ri-code-box-line" size="20" />مثال على استجابة الـ API
                      </VCardTitle>
                    </VCardItem>
                    <VCardText>
                      <pre class="text-caption pa-3 rounded bg-grey-darken-4 text-white" dir="ltr" style="overflow-x:auto;">{
  "latest_version": "{{ update.current_version_android }}",
  "min_version":    "{{ update.min_version_android }}",
  "needs_update":   false,
  "has_update":     false,
  "force_update":   {{ update.force_update_android }},
  "update_url":     "{{ update.update_url_android || 'https://play.google.com/...' }}",
  "message":        "التطبيق محدث."
}</pre>
                      <div class="mt-4 d-flex justify-end">
                        <VBtn color="primary" :loading="savingUpdate" prepend-icon="ri-save-line" @click="saveUpdate">
                          حفظ إعدادات التحديث
                        </VBtn>
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VWindowItem>

          </VWindow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.editor-wrap {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.editor-toolbar {
  background: rgba(var(--v-theme-surface-variant), 0.4);
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-height: 44px;
}
.editor-body {
  min-height: 420px;
  outline: none;
  direction: rtl;
  text-align: right;
  line-height: 1.9;
  font-size: 14px;
}
.editor-body:focus {
  background: rgba(var(--v-theme-primary), 0.01);
}
.editor-body h2 { color: #1565c0; font-size: 1.1rem; margin: 1rem 0 .4rem; border-bottom: 2px solid #e3f2fd; padding-bottom: .2rem; }
.editor-body h3 { font-size: 1rem; margin: .8rem 0 .3rem; }
.editor-body p  { margin-bottom: .6rem; }
.editor-body ul, .editor-body ol { padding-right: 1.5rem; margin-bottom: .6rem; }
.preview-body h2 { color: #1565c0; font-size: 1rem; margin: .8rem 0 .3rem; border-bottom: 1px solid #e3f2fd; }
.preview-body p  { margin-bottom: .4rem; color: #555; }
.preview-body ul { padding-right: 1.2rem; }
</style>
