<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revue du projet</h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h3 class="text-lg font-medium mb-4">{{ project.title }}</h3>
          <p class="text-sm text-gray-600 mb-6">Statut: {{ project.status }} · Progression: {{ project.progress }}%</p>

          <div class="space-y-4">
            <div v-for="task in project.tasks" :key="task.id" class="border rounded p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-semibold">{{ task.title }}</h4>
                  <p class="text-xs text-gray-600">Progression: {{ task.progress }}%</p>
                </div>
                <span class="text-sm" :class="task.progress >= 100 ? 'text-green-600' : 'text-amber-600'">
                  {{ task.progress >= 100 ? 'Complète' : 'Incomplète' }}
                </span>
              </div>

              <ul class="mt-3 space-y-1 list-disc list-inside">
                <li v-for="st in task.sub_tasks" :key="st.id" class="flex items-center gap-2">
                  <input type="checkbox" :checked="st.status === 'completed'" disabled />
                  <span>{{ st.title }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-between">
            <div>
              <p v-if="!allTasksCompleted" class="text-sm text-amber-700">Toutes les tâches doivent être complétées pour valider le projet.</p>
              <p v-else-if="isReviewed" class="text-sm text-green-700">Projet déjà validé à 100%.</p>
              <p v-else class="text-sm text-gray-600">Vous pouvez maintenant confirmer la finalisation du projet.</p>
            </div>
            <form v-if="allTasksCompleted && !isReviewed" :action="route('projects.review.complete', project.id)" method="post">
              <input type="hidden" name="_token" :value="csrfToken" />
              <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirmer la finalisation</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed } from 'vue'

const props = defineProps<{ project: any, allTasksCompleted: boolean, isReviewed: boolean }>()

const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''

const project = computed(() => props.project)
const allTasksCompleted = computed(() => props.allTasksCompleted)
const isReviewed = computed(() => props.isReviewed)
</script>
