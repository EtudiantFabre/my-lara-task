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
        <InputError :message="form.errors.title" class="mt-2" />
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
        <InputError :message="form.errors.status" class="mt-2" />
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
        <InputError :message="form.errors.start_date" class="mt-2" />
      </div>

      <!-- Deadline -->
      <div class="space-y-2">
        <Label for="deadline">Date d'échéance</Label>
        <Input
          id="deadline"
          v-model="form.deadline"
          type="date"
          :min="form.start_date"
          :disabled="form.processing"
        />
        <InputError :message="form.errors.deadline" class="mt-2" />
      </div>

      <!-- Progress -->
      <div class="space-y-2">
        <Label for="progress">Progression (%)</Label>
        <Input
          id="progress"
          v-model="form.progress"
          type="number"
          min="0"
          max="100"
          required
          :disabled="form.processing"
        />
        <InputError :message="form.errors.progress" class="mt-2" />
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
        <InputError :message="form.errors.description" class="mt-2" />
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
import InputError from '@/Components/InputError.vue';
import { Loader2 } from 'lucide-vue-next';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { toast } from '../ui/toast/use-toast';
import { watch } from 'vue';

const props = defineProps({
  project: {
    type: Object,
    default: () => ({
      title: '',
      description: '',
      status: 'not_started',
      start_date: new Date().toISOString().split('T')[0],
      deadline: '',
      progress: 0,
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
  deadline: props.project.deadline,
  progress: props.project.progress,
});

const submit = () => {
  //console.log('Form submitted with data:', form.data());

  if (props.method === 'put') {
    form.put(props.submitRoute, {
      onSuccess: () => {
        toast({
          title: 'Succès',
          description: 'Le projet a été mis à jour avec succès.',
        });
      },
      onError: (errors) => {
        console.error('Error updating project:', errors);
        toast({
          title: 'Erreur',
          description: 'Une erreur est survenue lors de la mise à jour du projet.',
          variant: 'destructive',
        });
      },
    });
  } else {
    form.post(props.submitRoute, {
      onSuccess: () => {
        toast({
          title: 'Succès',
          description: 'Le projet a été créé avec succès.',
        });
        form.reset();
      },
      onError: (errors) => {
        console.error('Error creating project:', errors);
        toast({
          title: 'Erreur',
          description: 'Une erreur est survenue lors de la création du projet.',
          variant: 'destructive',
        });
      },
    });
  }
};

// Log form changes
watch(
  () => form.data(),
  (newValue) => {
    //console.log('Form data changed:', newValue);
  },
  { deep: true }
);
</script>
