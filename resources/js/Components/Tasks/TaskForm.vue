<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- Task Title -->
      <div class="space-y-2 md:col-span-2">
        <Label for="title">Titre de la tâche</Label>
        <Input
          id="title"
          v-model="form.title"
          type="text"
          required
          :disabled="form.processing"
        />
        <InputError :message="form.errors.title" class="mt-2" />
      </div>

      <!-- Project Selection -->
      <div class="space-y-2">
        <Label for="project_id">Projet</Label>
        <Select 
          v-model="form.project_id" 
          :disabled="form.processing || !!initialProjectId"
          required
        >
          <SelectTrigger>
            <SelectValue placeholder="Sélectionner un projet" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem 
              v-for="project in projects" 
              :key="project.id" 
              :value="project.id"
            >
              {{ project.title }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Priority -->
      <div class="space-y-2">
        <Label>Priorité</Label>
        <div class="flex space-x-2">
          <Button
            v-for="priority in priorities"
            :key="priority.value"
            type="button"
            :variant="form.priority === priority.value ? 'default' : 'outline'"
            size="sm"
            class="flex-1"
            @click="form.priority = priority.value"
          >
            <component :is="priority.icon" class="mr-2 h-4 w-4" />
            {{ priority.label }}
          </Button>
        </div>
      </div>

      <!-- Due Date -->
      <div class="space-y-2">
        <Label for="due_date">Échéance</Label>
        <Input
          id="due_date"
          v-model="form.due_date"
          type="date"
          :min="minDueDate"
          :disabled="form.processing"
        />
      </div>

      <!-- Estimated Hours -->
      <div class="space-y-2">
        <Label for="estimated_hours">Temps estimé (heures)</Label>
        <Input
          id="estimated_hours"
          v-model.number="form.estimated_hours"
          type="number"
          min="0.5"
          step="0.5"
          :disabled="form.processing"
        />
      </div>

      <!-- Description -->
      <div class="space-y-2 md:col-span-2">
        <Label for="description">Description</Label>
        <Textarea
          id="description"
          v-model="form.description"
          rows="4"
          :disabled="form.processing"
        />
      </div>
    </div>

    <div class="flex justify-end space-x-4">
      <Button
        type="button"
        variant="outline"
        :disabled="form.processing"
        @click="$inertia.visit(cancelRoute)"
      >
        Annuler
      </Button>
      <Button type="submit" :disabled="form.processing">
        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
        {{ submitButtonText }}
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import InputError from '@/Components/InputError.vue';
import { Loader2, Flag, AlertTriangle, ArrowDown } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps({
  task: {
    type: Object,
    default: () => ({
      title: '',
      description: '',
      project_id: '',
      priority: 'medium',
      due_date: '',
      estimated_hours: 1,
    }),
  },
  projects: {
    type: Array,
    required: true,
  },
  initialProjectId: {
    type: String,
    default: null,
  },
  submitRoute: {
    type: String,
    required: true,
  },
  method: {
    type: String,
    default: 'post',
  },
  submitButtonText: {
    type: String,
    default: 'Créer',
  },
  cancelRoute: {
    type: String,
    default: '/tasks',
  },
});

const priorities = [
  { value: 'low', label: 'Basse', icon: ArrowDown },
  { value: 'medium', label: 'Moyenne', icon: Flag },
  { value: 'high', label: 'Haute', icon: AlertTriangle },
];

// Log pour déboguer
onMounted(() => {
  console.log('TaskForm - Projects reçus:', props.projects);
  console.log('TaskForm - Task reçue:', props.task);
});

const form = useForm({
  title: props.task.title,
  description: props.task.description,
  project_id: props.initialProjectId || props.task.project_id,
  priority: props.task.priority || 'medium',
  due_date: props.task.due_date || '',
  estimated_hours: props.task.estimated_hours || 1,
});

const minDueDate = computed(() => {
  return new Date().toISOString().split('T')[0];
});

const submit = () => {
  if (props.method === 'put') {
    form.put(props.submitRoute);
  } else {
    form.post(props.submitRoute, {
      onSuccess: () => form.reset(),
    });
  }
};
</script>
