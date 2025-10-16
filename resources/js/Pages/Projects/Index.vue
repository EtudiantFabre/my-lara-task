<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold leading-tight text-gray-800">Mes Projets</h2>
        <Link 
          :href="route('projects.create')" 
          class="flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors"
        >
          <Plus class="w-5 h-5 mr-2" />
          Nouveau Projet
        </Link>
      </div>
      
      <!-- Barre de recherche et filtres -->
      <div class="flex flex-col mt-6 space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
        <div class="relative flex-1">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <Search class="w-5 h-5 text-gray-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher un projet..."
            class="block w-full py-2 pl-10 pr-3 text-sm bg-white border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        
        <select 
          v-model="statusFilter"
          class="block w-full px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:w-48"
        >
          <option value="">Tous les statuts</option>
          <option value="not_started">Non commencé</option>
          <option value="in_progress">En cours</option>
          <option value="on_hold">En attente</option>
          <option value="completed">Terminé</option>
          <option value="cancelled">Annulé</option>
        </select>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 gap-5 mb-6 sm:grid-cols-2 lg:grid-cols-4">
          <div 
            v-for="stat in stats" 
            :key="stat.label"
            class="p-5 bg-white rounded-lg shadow"
            :class="stat.borderColor"
          >
            <div class="flex items-center">
              <div :class="stat.bgColor" class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full">
                <component :is="stat.icon" class="w-6 h-6 text-white" />
              </div>
              <div class="ml-5">
                <p class="text-sm font-medium text-gray-500 truncate">{{ stat.label }}</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stat.value }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des projets -->
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <div v-if="filteredProjects.length > 0" class="divide-y divide-gray-200">
            <div 
              v-for="project in filteredProjects" 
              :key="project.id" 
              class="transition-colors hover:bg-gray-50"
            >
              <Link :href="route('projects.show', project.id)" class="block">
                <div class="px-6 py-5">
                  <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-medium text-gray-900">{{ project.title }}</h3>
                        <span 
                          :class="getStatusBadgeClass(project.status)"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                          {{ getStatusLabel(project.status) }}
                        </span>
                      </div>
                      <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                        {{ project.description || 'Aucune description' }}
                      </p>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                      <ChevronRight class="w-5 h-5 text-gray-400" />
                    </div>
                  </div>
                  
                  <div class="mt-4">
                    <!-- Barre de progression -->
                    <div class="w-full h-2 mb-2 bg-gray-200 rounded-full">
                      <div 
                        :class="getProgressBarColor(project.progress)"
                        class="h-full rounded-full" 
                        :style="{ width: `${project.progress}%` }"
                      ></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                      <span>Progression: {{ project.progress }}%</span>
                      <div class="flex space-x-2">
                        <span class="flex items-center">
                          <Calendar class="w-4 h-4 mr-1" />
                          {{ formatDate(project.start_date) }} - {{ formatDate(project.deadline) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </Link>
            </div>
          </div>
          
          <!-- État vide -->
          <div v-else class="p-12 text-center">
            <FolderOpen class="w-12 h-12 mx-auto text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun projet trouvé</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ searchQuery || statusFilter ? 'Aucun projet ne correspond à vos critères.' : 'Commencez par créer un nouveau projet.' }}
            </p>
            <div class="mt-6">
              <Link
                :href="route('projects.create')"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <Plus class="w-5 h-5 mr-2 -ml-1" />
                Nouveau Projet
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  Plus, 
  Search, 
  ChevronRight, 
  Calendar,
  FolderOpen,
  Clock,
  CheckCircle,
  Pause,
  XCircle,
  ListTodo,
  CheckCheck,
  PauseCircle
} from 'lucide-vue-next';

const props = defineProps({
  projects: {
    type: Array,
    required: true,
  },
});

const searchQuery = ref('');
const statusFilter = ref('');

// Statistiques
const stats = computed(() => [
  { 
    label: 'Total des projets', 
    value: props.projects.length,
    icon: FolderOpen,
    bgColor: 'bg-blue-500',
    borderColor: 'border-l-4 border-blue-500'
  },
  { 
    label: 'En cours', 
    value: props.projects.filter(p => p.status === 'in_progress').length,
    icon: Clock,
    bgColor: 'bg-yellow-500',
    borderColor: 'border-l-4 border-yellow-500'
  },
  { 
    label: 'Terminés', 
    value: props.projects.filter(p => p.status === 'completed').length,
    icon: CheckCircle,
    bgColor: 'bg-green-500',
    borderColor: 'border-l-4 border-green-500'
  },
  { 
    label: 'En attente', 
    value: props.projects.filter(p => p.status === 'on_hold').length,
    icon: Pause,
    bgColor: 'bg-purple-500',
    borderColor: 'border-l-4 border-purple-500'
  },
]);

// Filtrage des projets
const filteredProjects = computed(() => {
  return props.projects.filter(project => {
    const matchesSearch = project.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        (project.description && project.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchesStatus = !statusFilter.value || project.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  }).sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at)); // Tri par date de mise à jour
});

// Fonctions utilitaires
const getStatusLabel = (status) => {
  const statusMap = {
    'not_started': 'Non commencé',
    'in_progress': 'En cours',
    'on_hold': 'En attente',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };
  return statusMap[status] || status;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    'not_started': 'bg-gray-100 text-gray-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'on_hold': 'bg-yellow-100 text-yellow-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getProgressBarColor = (progress) => {
  if (progress < 30) return 'bg-red-500';
  if (progress < 70) return 'bg-yellow-500';
  return 'bg-green-500';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('fr-FR', options);
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
