<template>
  <VApp>
    <RouterView v-slot="{ Component, route }">
      <Transition :name="route.meta.transition as string || 'page-fade'" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </RouterView>
  </VApp>
</template>
