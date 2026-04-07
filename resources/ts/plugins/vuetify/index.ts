import type { App } from 'vue'

import { createVuetify } from 'vuetify'
import { VBtn } from 'vuetify/components/VBtn'
import { ar } from 'vuetify/locale'
import defaults from './defaults'
import { icons } from './icons'
import { themes } from './theme'

// Styles

import '@core-scss/template/libs/vuetify/index.scss'
import 'vuetify/styles'

export default function (app: App) {
  const vuetify = createVuetify({
    aliases: {
      IconBtn: VBtn,
    },
    defaults,
    icons,
    locale: {
      locale: 'ar',
      fallback: 'en',
      messages: { ar },
      rtl: { ar: true },
    },
    theme: {
      defaultTheme: 'light',
      themes,
    },
  })

  app.use(vuetify)
}
