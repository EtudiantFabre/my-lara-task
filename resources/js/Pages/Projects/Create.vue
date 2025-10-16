<template>
  <AppLayout title="Nouveau Projet">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Nouveau Projet
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 gap-6">
                <!-- Titre du projet -->
                <div>
                  <InputLabel for="title" value="Titre du projet" />
                  <TextInput
                    id="title"
                    v-model="form.title"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                  />
                  <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <!-- Description -->
                <div>
                  <InputLabel for="description" value="Description" />
                  <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  ></textarea>
                  <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <!-- Date de début -->
                <div>
                  <InputLabel for="start_date" value="Date de début" />
                  <TextInput
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError :message="form.errors.start_date" class="mt-2" />
                </div>

                <!-- Date d'échéance -->
                <div>
                  <InputLabel for="deadline" value="Date d'échéance" />
                  <TextInput
                    id="deadline"
                    v-model="form.deadline"
                    type="date"
                    class="mt-1 block w-full"
                    :min="form.start_date"
                    required
                  />
                  <InputError :message="form.errors.deadline" class="mt-2" />
                </div>

                <!-- Progression -->
                <div>
                  <InputLabel for="progress" value="Progression (%)" />
                  <input
                    id="progress"
                    v-model.number="form.progress"
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    class="mt-1 block w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                  />
                  <div class="text-right text-sm text-gray-500">{{ form.progress }}%</div>
                  <InputError :message="form.errors.progress" class="mt-2" />
                </div>

                <!-- Statut -->
                <div>
                  <InputLabel for="status" value="Statut" />
                  <select
                    id="status"
                    v-model="form.status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                  >
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                  <InputError :message="form.errors.status" class="mt-2" />
                </div>

                <!-- Boutons d'action -->
                <div class="flex items-center justify-end mt-4">
                  <Link
                    :href="route('projects.index')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Annuler
                  </Link>
                  <PrimaryButton
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                  >
                    Créer le projet
                  </PrimaryButton>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from '@/Components/ui/toast/use-toast';

const { toast } = useToast();

const form = useForm({
  title: '',
  description: '',
  start_date: new Date().toISOString().split('T')[0],
  deadline: '',
  progress: 0,
  status: 'not_started',
  employee_id: null,
  manager_id: null,
});

// Options pour les statuts correspondant à la migration
const statusOptions = [
  { value: 'not_started', label: 'Non commencé' },
  { value: 'in_progress', label: 'En cours' },
  { value: 'on_hold', label: 'En attente' },
  { value: 'completed', label: 'Terminé' },
  { value: 'cancelled', label: 'Annulé' },
];

const submit = () => {
  form.post(route('projects.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Afficher une notification de succès
      toast({
        title: 'Succès',
        description: 'Le projet a été créé avec succès',
        variant: 'success',
      });
      
      // Rediriger vers la liste des projets
      // Le contrôleur devrait déjà gérer la redirection avec l'ID du projet
      router.visit(route('projects.index'));
    },
    onError: (errors) => {
      let errorMessage = 'Une erreur est survenue lors de la création du projet';
      
      if (errors.message) {
        errorMessage += `: ${errors.message}`;
      } else if (errors.errors) {
        // Si nous avons des erreurs de validation, les afficher
        errorMessage = Object.values(errors.errors).flat().join('\n');
      }
      
      toast({
        title: 'Erreur',
        description: errorMessage,
        variant: 'destructive',
      });
    }
  });
};


// Mettre à jour la date d'échéance minimale quand la date de début change
watch(() => form.start_date, (newStartDate) => {
  if (form.deadline && new Date(form.deadline) < new Date(newStartDate)) {
    form.deadline = newStartDate;
  }
});
</script>
