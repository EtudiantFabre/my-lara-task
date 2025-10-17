<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6" v-if="task">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ task.title }}</h1>
        <Badge :variant="variant(task.status)">{{ task.status }}</Badge>
      </div>

      <div class="bg-white p-6 rounded-lg shadow space-y-4">
        <p class="text-gray-700">{{ task.description || 'Aucune description' }}</p>
        <div class="text-sm text-gray-500">Échéance: {{ task.due_date || '—' }}</div>
        <div class="text-sm text-gray-500">Estimation: {{ task.estimated_time ?? 0 }} h</div>
      </div>

      <div>
        <h2 class="font-semibold mb-2">Sous-tâches</h2>
        <div class="space-y-2">
          <div v-for="st in task.sub_tasks || []" :key="st.id" class="p-3 border rounded">
            <div class="flex items-center justify-between">
              <div>{{ st.title }}</div>
              <Button size="sm" @click="toggleSubtask(st)">{{ st.status === 'completed' ? 'Rouvrir' : 'Terminer' }}</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps({ task: { type: Object, required: true } });
const task = ref(props.task);

const variant = (s) => ({ not_started: 'secondary', in_progress: 'default', in_review: 'warning', completed: 'success', blocked: 'destructive' }[s] || 'default');

onMounted(async () => {});

const toggleSubtask = async (st) => {
  await axios.patch(`/tasks/${task.value.id}/subtasks/${st.id}/toggle`);
  st.status = st.status === 'completed' ? 'not_started' : 'completed';
};
</script>

