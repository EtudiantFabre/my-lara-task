<!-- resources/js/Components/Tasks/TaskItem.vue -->
<template>
  <div class="flex items-start space-x-4 p-3 border rounded-lg hover:bg-accent/50 transition-colors">
    <div class="flex-shrink-0 pt-0.5">
      <Checkbox 
        :id="'task-' + task.id" 
        :checked="!!task.completed_at"
        @update:checked="toggleComplete"
      />
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex items-center space-x-2">
        <h3 class="text-sm font-medium" :class="{ 'line-through text-muted-foreground': task.completed_at }">
          {{ task.title }}
        </h3>
        <Badge v-if="task.due_date" variant="outline" class="text-xs">
          {{ formatDate(task.due_date) }}
        </Badge>
      </div>
      <p v-if="task.description" class="text-sm text-muted-foreground line-clamp-2">
        {{ task.description }}
      </p>
      <div v-if="showProject && task.project" class="mt-1">
        <Badge variant="outline" class="text-xs">
          {{ task.project.name }}
        </Badge>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Checkbox } from '@/Components/ui/checkbox';
import { Badge } from '@/Components/ui/badge';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  showProject: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle']);

const formatDate = (dateString) => {
  return format(new Date(dateString), 'd MMM yyyy', { locale: fr });
};

const toggleComplete = async (checked) => {
  emit('toggle', props.task.id, checked);
};
</script>