<!-- resources/js/Components/Common/EmptyState.vue -->
<template>
  <div class="text-center p-8">
    <component 
      :is="icon" 
      class="mx-auto h-12 w-12 text-muted-foreground" 
      aria-hidden="true" 
    />
    <h3 class="mt-2 text-sm font-medium text-foreground">{{ title }}</h3>
    <p class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
    <div v-if="action" class="mt-6">
      <Button @click="$emit('action')">
        <component :is="actionIcon" v-if="actionIcon" class="mr-2 h-4 w-4" />
        {{ action }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { Button } from '@/Components/ui/button';

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  icon: {
    type: [String, Object],
    required: true,
  },
  action: String,
  actionIcon: [String, Object],
});

defineEmits(['action']);
</script>