<!-- resources/js/Components/Projects/ProjectItem.vue -->
<template>
  <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors">
    <div class="flex items-center space-x-4">
      <div class="flex-shrink-0">
        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
          <Folder class="h-5 w-5 text-primary" />
        </div>
      </div>
      <div class="min-w-0 flex-1">
        <h3 class="text-sm font-medium truncate">
          <Link :href="route('projects.show', project.id)" class="hover:underline">
            {{ project.title }}
          </Link>
        </h3>
        <p class="text-sm text-muted-foreground">
          {{ project.tasks_count }} tâche{{ project.tasks_count !== 1 ? 's' : '' }}
        </p>
      </div>
    </div>
    <div class="ml-4 flex-shrink-0">
      <Button variant="ghost" size="icon" as-child>
        <Link :href="route('projects.show', project.id)">
          <ArrowRight class="h-4 w-4" />
          <span class="sr-only">Voir le projet</span>
        </Link>
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Folder, ArrowRight } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/vue3';

defineProps({
  project: {
    type: Object,
    required: true,
  },
});
</script>