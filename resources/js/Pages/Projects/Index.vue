<template>
  <AppLayout>
    <div class="py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold leading-tight text-gray-800">Mes Projets</h2>
        <button 
          @click="showCreateModal = true"
          class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
        >
          Nouveau Projet
        </button>
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
    </div>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 gap-5 mb-6 sm:grid-cols-2 lg:grid-cols-4">
          <ProjectStatsCard
            v-for="stat in stats"
            :key="stat.label"
            :label="stat.label"
            :value="stat.value"
            :icon="stat.icon"
            :bg-color="stat.bgColor"
            :border-color="stat.borderColor"
          />
        </div>

        <!-- Tableau des projets -->
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Titre
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Progression
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dates
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <!-- Liste des projets -->
                <template v-if="filteredProjects.length > 0">
                  <ProjectTableRow
                    v-for="project in filteredProjects"
                    :key="project.id"
                    :project="project"
                    :is-expanded="expandedProjects.includes(project.id)"
                    @toggle-expansion="toggleProjectExpansion"
                    @edit="editProject"
                    @delete="confirmDelete"
                    @add-task="openTaskModal"
                    @toggle-task-completion="toggleTaskCompletion"
                    @edit-task="openEditTaskModal"
                    @delete-task="confirmDeleteTask"
                  />
                </template>
                
                <!-- État vide -->
                <tr v-else>
                  <td colspan="5" class="px-6 py-12 text-center">
                    <FolderOpen class="w-12 h-12 mx-auto text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun projet trouvé</h3>
                    <p class="mt-1 text-sm text-gray-500">
                      {{ searchQuery || statusFilter ? 'Aucun projet ne correspond à vos critères.' : 'Commencez par créer un nouveau projet.' }}
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- Modal d'ajout/édition de tâche -->
  <TaskFormModal
    :show="showTaskModal || showEditTaskModal"
    :task="editingTask"
    :project-id="newTask.project_id"
    :users="teamMembers"
    @close="closeTaskModal"
    @submit="handleTaskSubmit"
  />
  
  <!-- Modal d'édition de projet -->
  <ProjectFormModal
    :show="showEditModal"
    :project="editingProject"
    @close="closeEditModal"
    @submit="updateProject"
  />

  <!-- Modal de confirmation de suppression -->
  <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
          <AlertTriangle class="h-6 w-6 text-red-600" />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Supprimer le projet
          </h3>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Êtes-vous sûr de vouloir supprimer le projet "{{ projectToDelete?.title }}" ?
              Cette action est irréversible.
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
      <button
        type="button"
        @click="deleteProject"
        class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
      >
        Supprimer
      </button>
      <button
        type="button"
        @click="showDeleteModal = false"
        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
      >
        Annuler
      </button>
    </div>
  </Modal>

  <!-- Modal de création de projet -->
  <ProjectFormModal
    :show="showCreateModal"
    :project="null"
    @close="showCreateModal = false"
    @submit="addProject"
  />
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Components/ui/toast/use-toast';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  Search,
  FolderOpen,
  Clock,
  CheckCircle,
  Pause,
  AlertTriangle
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import ProjectStatsCard from '@/Components/Projects/ProjectStatsCard.vue';
import ProjectTableRow from '@/Components/Projects/ProjectTableRow.vue';
import TaskFormModal from '@/Components/Projects/TaskFormModal.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';

const props = defineProps({
  projects: {
    type: Array,
    required: true,
  },
  auth: {
    type: Object,
    required: true
  },
  teamMembers: {
    type: Array,
    default: () => []
  }
});

const searchQuery = ref('');
const statusFilter = ref('');
const expandedProjects = ref([]);
const showTaskModal = ref(false);
const showEditTaskModal = ref(false);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const editingProject = ref(null);
const editingTask = ref(null);
const projectToDelete = ref(null);
const { toast } = useToast();

const newTask = ref({
  title: '',
  description: '',
  status: 'not_started',
  estimated_time: null,
  due_date: '',
  project_id: ''
});

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
    if (!project || !project.title) return false;
    
    const matchesSearch = project.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        (project.description && project.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchesStatus = !statusFilter.value || project.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  }).sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
});

// Méthodes pour les projets
const toggleProjectExpansion = (projectId) => {
  const index = expandedProjects.value.indexOf(projectId);
  if (index > -1) {
    expandedProjects.value.splice(index, 1);
  } else {
    expandedProjects.value.push(projectId);
  }
};

const addProject = async (projectData) => {
  try {
    await router.post(route('projects.store'), {
      ...projectData,
      estimated_time: projectData.estimated_time ? parseFloat(projectData.estimated_time) : null,
      progress: 0
    }, {
      onSuccess: () => {
        showCreateModal.value = false;
        toast({
          title: 'Succès',
          description: 'Le projet a été créé avec succès',
          variant: 'default',
          duration: 3000
        });
      },
      onError: (errors) => {
        const errorMessage = errors.message || 'Une erreur est survenue lors de la création du projet';
        toast({
          title: 'Erreur',
          description: errorMessage,
          variant: 'destructive',
          duration: 5000
        });
      }
    });
  } catch (error) {
    console.error('Erreur lors de la création du projet:', error);
    toast({
      title: 'Erreur',
      description: error.response?.data?.message || error.message || 'Une erreur est survenue',
      variant: 'destructive',
      duration: 5000
    });
  }
};

const editProject = (project) => {
  editingProject.value = { ...project };
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingProject.value = null;
};

const updateProject = (projectData) => {
  if (!projectData.id) return;
  
  router.put(route('projects.update', projectData.id), projectData, {
    preserveScroll: true,
    onSuccess: () => {
      closeEditModal();
      toast({
        title: 'Succès',
        description: 'Le projet a été mis à jour avec succès',
        variant: 'default'
      });
    },
    onError: (errors) => {
      console.error('Erreur lors de la mise à jour du projet:', errors);
      toast({
        title: 'Erreur',
        description: 'Une erreur est survenue lors de la mise à jour du projet',
        variant: 'destructive'
      });
    }
  });
};

const confirmDelete = (project) => {
  projectToDelete.value = project;
  showDeleteModal.value = true;
};

const deleteProject = () => {
  if (!projectToDelete.value) return;
  
  router.delete(route('projects.destroy', projectToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      projectToDelete.value = null;
      toast({
        title: 'Succès',
        description: 'Le projet a été supprimé avec succès',
        variant: 'default'
      });
    },
    onError: (error) => {
      console.error('Erreur lors de la suppression du projet:', error);
      toast({
        title: 'Erreur',
        description: 'Une erreur est survenue lors de la suppression du projet',
        variant: 'destructive'
      });
    }
  });
};

// Méthodes pour les tâches
const openTaskModal = (projectId) => {
  newTask.value = {
    title: '',
    description: '',
    status: 'not_started',
    estimated_time: null,
    due_date: '',
    project_id: projectId
  };
  editingTask.value = null;
  showTaskModal.value = true;
};

const openEditTaskModal = (task) => {
  editingTask.value = { ...task };
  newTask.value.project_id = task.project_id;
  showEditTaskModal.value = true;
};

const closeTaskModal = () => {
  showTaskModal.value = false;
  showEditTaskModal.value = false;
  editingTask.value = null;
};

const handleTaskSubmit = async (taskData) => {
  try {
    if (taskData.id) {
      // Update existing task
      await axios.put(route('tasks.update', taskData.id), {
        title: taskData.title,
        description: taskData.description,
        status: taskData.status,
        estimated_time: taskData.estimated_time,
        due_date: taskData.due_date,
        assigned_to: taskData.assigned_to
      });
      
      toast({
        title: 'Succès',
        description: 'La tâche a été mise à jour avec succès',
        variant: 'default',
        duration: 3000
      });
    } else {
      // Create new task
      await axios.post(route('projects.tasks.store', { project: taskData.project_id }), {
        title: taskData.title,
        description: taskData.description,
        status: taskData.status,
        estimated_time: taskData.estimated_time,
        due_date: taskData.due_date,
        project_id: taskData.project_id,
        created_by: props.auth.user.id
      });
      
      toast({
        title: 'Succès',
        description: 'La tâche a été créée avec succès',
        variant: 'default',
        duration: 3000
      });
    }
    
    closeTaskModal();
    router.reload({ only: ['projects'] });
  } catch (error) {
    console.error('Erreur lors de la gestion de la tâche:', error);
    toast({
      title: 'Erreur',
      description: error.response?.data?.message || error.message || 'Une erreur est survenue',
      variant: 'destructive',
      duration: 5000
    });
  }
};

const toggleTaskCompletion = (task) => {
  router.put(route('tasks.update', task.id), {
    status: task.status === 'completed' ? 'in_progress' : 'completed'
  }, {
    preserveScroll: true
  });
};

const confirmDeleteTask = (task) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ? Cette action est irréversible.')) {
    deleteTask(task.id);
  }
};

const deleteTask = async (taskId) => {
  try {
    await axios.delete(route('tasks.destroy', taskId));
    
    toast({
      title: 'Succès',
      description: 'La tâche a été supprimée avec succès',
      variant: 'default',
      duration: 3000
    });
    
    router.reload({ only: ['projects'] });
  } catch (error) {
    console.error('Erreur lors de la suppression de la tâche:', error);
    toast({
      title: 'Erreur',
      description: 'Une erreur est survenue lors de la suppression de la tâche',
      variant: 'destructive',
      duration: 5000
    });
  }
};
</script>

<style>
:root {
  --color-primary-50: #f0f9ff;
  --color-primary-100: #e0f2fe;
  --color-primary-200: #bae6fd;
  --color-primary-300: #7dd3fc;
  --color-primary-400: #38bdf8;
  --color-primary-500: #0ea5e9;
  --color-primary-600: #0284c7;
  --color-primary-700: #0369a1;
  --color-primary-800: #075985;
  --color-primary-900: #0c4a6e;
}

.bg-primary-600 { background-color: var(--color-primary-600); }
.bg-primary-700 { background-color: var(--color-primary-700); }
.text-primary-600 { color: var(--color-primary-600); }
.border-primary-500 { border-color: var(--color-primary-500); }
.focus\:ring-primary-500:focus { --tw-ring-color: var(--color-primary-500); }
.focus\:border-primary-500:focus { border-color: var(--color-primary-500); }
.hover\:bg-primary-700:hover { background-color: var(--color-primary-700); }
</style>
