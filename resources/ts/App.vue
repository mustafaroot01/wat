<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
const transitionName = computed(() => String(route.meta.transition || 'page-fade'))
</script>

<template>
  <VApp>
    <RouterView v-slot="{ Component }">
      <Transition :name="transitionName" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </RouterView>
  </VApp>
</template>
