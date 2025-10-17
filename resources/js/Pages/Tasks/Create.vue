<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <h1 class="text-2xl font-bold">Nouvelle tâche</h1>

      <div class="bg-white p-6 rounded-lg shadow space-y-4">
        <div>
          <label class="text-sm text-gray-700">Titre <span class="text-red-500">*</span></label>
          <Input v-model="form.title" placeholder="Titre de la tâche" required />
          <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
        </div>
        <div>
          <label class="text-sm text-gray-700">Description</label>
          <Textarea v-model="form.description" placeholder="Décrire la tâche" />
        </div>
        <div>
          <label class="text-sm text-gray-700">Projet <span class="text-red-500">*</span></label>
          <Input v-model="form.project_id" placeholder="ID du projet" required />
          <p v-if="form.errors.project_id" class="text-sm text-red-600">{{ form.errors.project_id }}</p>
        </div>
        <div>
          <label class="text-sm text-gray-700">Échéance</label>
          <Input type="date" v-model="form.due_date" :min="new Date().toISOString().split('T')[0]" />
          <p v-if="form.errors.due_date" class="text-sm text-red-600">{{ form.errors.due_date }}</p>
        </div>
        <div>
          <label class="text-sm text-gray-700">Temps estimé (heures)</label>
          <Input type="number" min="0" step="0.25" v-model.number="form.estimated_time" />
        </div>
        <div class="flex justify-end space-x-2">
          <Button variant="outline" @click="$inertia.visit(route('projects.index'))">Annuler</Button>
          <Button @click="submit">Créer</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { useForm, router } from '@inertiajs/vue3';

const form = useForm({
  title: '',
  description: '',
  project_id: '',
  due_date: '',
  estimated_time: 0,
});

const submit = () => {
  router.post(route('projects.tasks.store', { project: form.project_id }), form, {
    onError: () => {},
  });
};
</script>
