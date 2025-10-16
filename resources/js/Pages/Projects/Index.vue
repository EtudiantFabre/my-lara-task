<template>
  <AppLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Projets</h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between mb-6">
              <h3 class="text-lg font-medium">Liste des projets</h3>
              <Link 
                :href="route('projects.create')" 
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
              >
                Nouveau projet
              </Link>
            </div>
            
            <!-- Liste des projets -->
            <div class="space-y-4">
              <div v-for="project in projects" :key="project.id" class="p-4 border rounded-lg">
                <h4 class="font-medium">{{ project.title }}</h4>
                <p class="text-sm text-gray-600">{{ project.description }}</p>
                <div class="mt-2">
                  <Link 
                    :href="route('projects.show', project.id)" 
                    class="text-sm text-blue-600 hover:underline"
                  >
                    Voir les détails
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { defineProps } from 'vue';

defineProps({
  projects: {
    type: Array,
    required: true
  }
});
</script>
