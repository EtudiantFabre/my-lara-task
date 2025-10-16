<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- Project Title -->
      <div class="space-y-2">
        <Label for="title">Titre du projet</Label>
        <Input
          id="title"
          v-model="form.title"
          type="text"
          required
          :disabled="form.processing"
        />
        <p v-if="form.errors.title" class="text-sm text-destructive">
          {{ form.errors.title }}
        </p>
      </div>

      <!-- Project Status -->
      <div class="space-y-2">
        <Label for="status">Statut</Label>
        <Select v-model="form.status" :disabled="form.processing">
          <SelectTrigger>
            <SelectValue placeholder="Sélectionner un statut" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="not_started">Non commencé</SelectItem>
            <SelectItem value="in_progress">En cours</SelectItem>
            <SelectItem value="on_hold">En attente</SelectItem>
            <SelectItem value="completed">Terminé</SelectItem>
            <SelectItem value="cancelled">Annulé</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Start Date -->
      <div class="space-y-2">
        <Label for="start_date">Date de début</Label>
        <Input
          id="start_date"
          v-model="form.start_date"
          type="date"
          required
          :disabled="form.processing"
        />
      </div>

      <!-- End Date -->
      <div class="space-y-2">
        <Label for="end_date">Date de fin prévue</Label>
        <Input
          id="end_date"
          v-model="form.end_date"
          type="date"
          :min="form.start_date"
          required
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
        @click="$inertia.visit(route('projects.index'))"
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
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Loader2 } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps({
  project: {
    type: Object,
    default: () => ({
      title: '',
      description: '',
      status: 'not_started',
      start_date: new Date().toISOString().split('T')[0],
      end_date: '',
    }),
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
});

const form = useForm({
  title: props.project.title,
  description: props.project.description,
  status: props.project.status,
  start_date: props.project.start_date,
  end_date: props.project.end_date,
});

const submit = () => {
  if (props.method === 'put') {
    form.put(props.submitRoute);
  } else {
    form.post(props.submitRoute);
  }
};
</script>
