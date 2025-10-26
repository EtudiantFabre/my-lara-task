<template>
  <AppLayout>
    <div class="py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold leading-tight text-gray-800">Mes Projets</h2>
        <button 
          @click="() => { console.log('Bouton cliqué'); showCreateModal = true; }"
          class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
        >
          Nouveau Projet
        </button>
      </div>
      <!-- Message de débogage -->
      <div v-if="false" class="p-2 mb-4 text-sm text-red-600 bg-red-100 rounded">
        Bouton Nouveau Projet - showCreateModal: {{ showCreateModal }}
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

        <!-- Tableau des projets -->
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
          <!-- En-tête du tableau -->
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
                  <template v-for="project in filteredProjects" :key="project.id">
                    <tr 
                      v-if="project && project.id"
                      class="transition-colors hover:bg-gray-50"
                      :class="getRowClass(project)"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                        <button 
                          @click="toggleProjectExpansion(project.id)"
                          class="mr-2 text-gray-500 hover:text-gray-700 focus:outline-none"
                        >
                          <ChevronRight 
                            class="w-5 h-5 transition-transform duration-200"
                            :class="{ 'transform rotate-90': expandedProjects.includes(project.id) }"
                          />
                        </button>
                        <div>
                          <div class="font-medium text-gray-900">{{ project.title }}</div>
                          <div 
                            class="text-sm text-gray-500 overflow-hidden text-ellipsis display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; line-clamp: 1;"
                          >
                            {{ project.description || 'Aucune description' }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span 
                        :class="getStatusBadgeClass(project.status)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ getStatusLabel(project.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="w-32 mr-2">
                          <div class="w-full h-2 bg-gray-200 rounded-full">
                            <div 
                              :class="getProgressBarColor(project.progress)"
                              class="h-full rounded-full" 
                              :style="{ width: `${project.progress}%` }"
                            ></div>
                          </div>
                        </div>
                        <span class="text-sm text-gray-500">{{ project.progress }}%</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                      <div class="flex items-center">
                        <Calendar class="w-4 h-4 mr-1 text-gray-400" />
                        <span>{{ formatDate(project.start_date) }} - {{ formatDate(project.deadline) }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                      <ButtonGroup class="flex items-center justify-end">
                        <!-- Bouton pour ajouter une tâche -->
                        <Button 
                          @click="openTaskModal(project.id)" 
                          variant="outline" 
                          size="sm"
                          class="flex items-center gap-1"
                        >
                          <Plus class="w-4 h-4" />
                          <span class="hidden sm:inline">Tâche</span>
                        </Button>
                        
                        <!-- Menu déroulant pour les actions supplémentaires -->
                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon" class="h-8 w-8">
                              <MoreHorizontal class="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuGroup>
                              <DropdownMenuItem @click="editProject(project)">
                                <Pencil class="mr-2 h-4 w-4" />
                                <span>Modifier</span>
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                @click="confirmDelete(project)" 
                                class="text-red-600 focus:text-red-700"
                              >
                                <Trash2 class="mr-2 h-4 w-4" />
                                <span>Supprimer</span>
                              </DropdownMenuItem>
                            </DropdownMenuGroup>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </ButtonGroup>
                    </td>
                  </tr>
                  
                  <!-- Ligne des tâches (affichée lors de l'expansion) -->
                  <tr v-if="expandedProjects.includes(project.id)" class="bg-gray-50">
                    <td colspan="5" class="px-6 py-4">
                      <div class="ml-8">
                        <h4 class="mb-3 text-sm font-medium text-gray-700">Tâches du projet</h4>
                        
                        
                        <!-- Liste des tâches -->
                        <div v-if="project.tasks && project.tasks.length > 0" class="space-y-3">
                          <div 
                            v-for="task in project.tasks" 
                            :key="task.id" 
                            class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 group"
                            :class="{
                              'border-l-4 border-green-500': task.status === 'completed',
                              'border-l-4 border-blue-500': task.status === 'in_progress',
                              'border-l-4 border-yellow-500': task.status === 'on_hold',
                              'border-l-4 border-red-500': task.status === 'blocked' || (task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed')
                            }"
                          >
                            <div class="flex items-start justify-between">
                              <div class="flex-1 min-w-0">
                                <div class="flex items-center">
                                  <input 
                                    type="checkbox" 
                                    :checked="task.status === 'completed'"
                                    @change="toggleTaskCompletion(task)"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                  >
                                  <h4 class="ml-2 text-base font-medium text-gray-900 truncate">
                                    {{ task.title }}
                                    <span v-if="task.priority === 'high'" class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                      Haute priorité
                                    </span>
                                    <span v-else-if="task.priority === 'medium'" class="ml-2 px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                      Priorité moyenne
                                    </span>
                                    <span v-else-if="task.priority === 'low'" class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                      Basse priorité
                                    </span>
                                  </h4>
                                </div>
                                
                                <p v-if="task.description" class="mt-1 ml-6 text-sm text-gray-600 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2;">
                                  {{ task.description }}
                                </p>
                                
                                <div class="mt-2 ml-6 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                  <!-- Assigné -->
                                  <div v-if="task.assignee" class="flex items-center" :title="`Assigné à ${task.assignee.name}`">
                                    <User class="h-4 w-4 mr-1 shrink-0" />
                                    <span class="truncate max-w-[120px]">{{ task.assignee.name }}</span>
                                  </div>
                                  
                                  <!-- Date d'échéance avec indicateur de retard -->
                                  <div 
                                    v-if="task.due_date" 
                                    class="flex items-center"
                                    :class="{ 'text-red-600': new Date(task.due_date) < new Date() && task.status !== 'completed' }"
                                    :title="`Échéance: ${formatDate(task.due_date)}${new Date(task.due_date) < new Date() && task.status !== 'completed' ? ' (En retard)' : ''}"
                                  >
                                    <Calendar class="h-4 w-4 mr-1 shrink-0" />
                                    <span>{{ formatDate(task.due_date) }}</span>
                                    <span v-if="new Date(task.due_date) < new Date() && task.status !== 'completed'" class="ml-1 text-red-500">
                                      <AlertTriangle class="h-3.5 w-3.5 inline-block" />
                                    </span>
                                  </div>
                                  
                                  <!-- Temps estimé -->
                                  <div v-if="task.estimated_time" class="flex items-center" title="Temps estimé">
                                    <Clock3 class="h-4 w-4 mr-1 shrink-0" />
                                    <span>{{ task.estimated_time }}h</span>
                                  </div>
                                  
                                  <!-- Tags -->
                                  <div v-if="task.tags && task.tags.length > 0" class="flex items-center flex-wrap gap-1 mt-1">
                                    <span 
                                      v-for="tag in task.tags" 
                                      :key="tag.id"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                      :style="{ backgroundColor: `${tag.color}20`, color: tag.color }"
                                    >
                                      {{ tag.name }}
                                    </span>
                                  </div>
                                </div>
                                
                                <!-- Progression -->
                                <div v-if="task.status !== 'completed'" class="mt-2 ml-6">
                                  <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progression</span>
                                    <span>{{ task.progress || 0 }}%</span>
                                  </div>
                                  <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div 
                                      class="bg-blue-600 h-1.5 rounded-full" 
                                      :class="{
                                        'bg-red-500': task.progress < 30,
                                        'bg-yellow-500': task.progress >= 30 && task.progress < 70,
                                        'bg-green-500': task.progress >= 70
                                      }"
                                      :style="{ width: `${task.progress || 0}%` }"
                                    ></div>
                                  </div>
                                </div>
                              </div>
                              
                              <div class="flex items-start space-x-2 pl-2">
                                <!-- Statut -->
                                <div class="text-right">
                                  <span 
                                    :class="getStatusBadgeClass(task.status)" 
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap mb-2"
                                  >
                                    <span class="w-2 h-2 rounded-full mr-1.5" :class="{
                                      'bg-green-500': task.status === 'completed',
                                      'bg-blue-500': task.status === 'in_progress',
                                      'bg-yellow-500': task.status === 'on_hold',
                                      'bg-red-500': task.status === 'blocked',
                                      'bg-gray-400': task.status === 'not_started'
                                    }"></span>
                                    {{ getStatusLabel(task.status) }}
                                  </span>
                                  
                                  <!-- Date de création -->
                                  <div v-if="task.created_at" class="text-xs text-gray-400 mt-1 whitespace-nowrap">
                                    Créé le {{ formatDate(task.created_at) }}
                                  </div>
                                </div>
                                
                                <!-- Menu d'actions -->
                                <DropdownMenu>
                                  <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                      <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                  </DropdownMenuTrigger>
                                  <DropdownMenuContent align="end" class="w-48">
                                    <DropdownMenuGroup>
                                      <DropdownMenuItem @click="openEditTaskModal(task)">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        <span>Modifier</span>
                                      </DropdownMenuItem>
                                      <DropdownMenuItem 
                                        @click="confirmDeleteTask(task)" 
                                        class="text-red-600 focus:text-red-700"
                                      >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        <span>Supprimer</span>
                                      </DropdownMenuItem>
                                    </DropdownMenuGroup>
                                  </DropdownMenuContent>
                                </DropdownMenu>
                              </div>
                            </div>
                            
                            <!-- Sous-tâches -->
                            <div v-if="expandedSubtasks[task.id]" class="mt-2 ml-7 space-y-2">
                              <!-- Formulaire d'ajout de sous-tâche -->
                              <form @submit.prevent="addSubtask(project, task)" class="flex mb-2 space-x-2">
                                <input
                                  v-model="newSubtasks[task.id]"
                                  type="text"
                                  placeholder="Nouvelle sous-tâche"
                                  class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                                <button
                                  type="submit"
                                  class="px-2 py-1 text-xs text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                  Ajouter
                                </button>
                              </form>
                              
                              <!-- Liste des sous-tâches -->
                              <div v-if="task.subtasks && task.subtasks.length > 0" class="space-y-1">
                                <div 
                                  v-for="subtask in task.subtasks" 
                                  :key="subtask.id"
                                  class="flex items-center justify-between p-2 text-sm bg-gray-50 rounded-md"
                                >
                                  <div class="flex items-center">
                                    <input 
                                      type="checkbox" 
                                      v-model="subtask.completed"
                                      @change="toggleSubtaskCompletion(task, subtask)"
                                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    />
                                    <span 
                                      class="ml-2"
                                      :class="{ 'line-through text-gray-400': subtask.completed }"
                                    >
                                      {{ subtask.title }}
                                    </span>
                                  </div>
                                  <button 
                                    @click="deleteSubtask(task, subtask.id)"
                                    class="p-1 text-red-400 rounded-full hover:bg-red-50"
                                  >
                                    <X class="w-3.5 h-3.5" />
                                  </button>
                                </div>
                              </div>
                              <div v-else class="px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded">
                                Aucune sous-tâche
                              </div>
                            </div>
                          </div>
                        </div>
                        <div v-else class="px-3 py-2 text-sm text-gray-500 bg-gray-100 rounded">
                          Aucune tâche pour l'instant
                        </div>
                      </div>
                    </td>
                  </tr>
                  </template>
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

  <!-- Modal d'ajout de tâche -->
  <Modal :show="showTaskModal" @close="showTaskModal = false" max-width="2xl">
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="w-full">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            <ListTodo class="inline-block w-5 h-5 mr-2 text-primary-600" />
            Nouvelle Tâche
          </h3>
          
          <form @submit.prevent="addTask(newTask.project_id)" class="space-y-4">
            <!-- Titre -->
            <div>
              <label for="task-title" class="block text-sm font-medium text-gray-700">Titre *</label>
              <input
                id="task-title"
                v-model="newTask.title"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <!-- Description -->
            <div>
              <label for="task-description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                id="task-description"
                v-model="newTask.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>
            
            <!-- Statut -->
            <div>
              <label for="task-status" class="block text-sm font-medium text-gray-700">Statut</label>
              <select
                id="task-status"
                v-model="newTask.status"
                class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
              >
                <option value="not_started">Non commencé</option>
                <option value="in_progress">En cours</option>
                <option value="in_review">En revue</option>
                <option value="completed">Terminé</option>
                <option value="blocked">Bloqué</option>
              </select>
            </div>
            
            <!-- Temps estimé -->
            <div>
              <label for="task-estimated-time" class="block text-sm font-medium text-gray-700">Temps estimé (heures)</label>
              <input
                id="task-estimated-time"
                v-model.number="newTask.estimated_time"
                type="number"
                min="0"
                step="0.5"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <!-- Date d'échéance -->
            <div>
              <label for="task-due-date" class="block text-sm font-medium text-gray-700">Date d'échéance</label>
              <input
                id="task-due-date"
                v-model="newTask.due_date"
                type="date"
                :min="new Date().toISOString().split('T')[0]"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <div class="py-3 sm:flex sm:flex-row-reverse">
              <button
                type="submit"
                :disabled="isAddingTask"
                class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isAddingTask" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Création...
                </span>
                <span v-else>Créer la tâche</span>
              </button>
              <button
                type="button"
                @click="showTaskModal = false"
                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Modal>
  
  <!-- Modal d'édition de projet -->
  <Modal :show="showEditModal" @close="closeEditModal" max-width="2xl">
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="w-full">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            <Pencil class="inline-block w-5 h-5 mr-2 text-primary-600" />
            Modifier le projet
          </h3>
          
          <div class="space-y-4" v-if="editingProject">
            <!-- Titre -->
            <div>
              <label for="edit-title" class="block text-sm font-medium text-gray-700">Titre *</label>
              <input
                id="edit-title"
                v-model="editingProject.title"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <!-- Description -->
            <div>
              <label for="edit-description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                id="edit-description"
                v-model="editingProject.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Date de début -->
              <div>
                <label for="edit-start-date" class="block text-sm font-medium text-gray-700">Date de début</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <CalendarDays class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="edit-start-date"
                    v-model="editingProject.start_date"
                    type="date"
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Date d'échéance -->
              <div>
                <label for="edit-deadline" class="block text-sm font-medium text-gray-700">Date d'échéance</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Calendar class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="edit-deadline"
                    v-model="editingProject.deadline"
                    type="date"
                    :min="editingProject.start_date"
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Temps estimé -->
              <div>
                <label for="edit-estimated-time" class="block text-sm font-medium text-gray-700">Temps estimé (heures)</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Clock3 class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="edit-estimated-time"
                    v-model.number="editingProject.estimated_time"
                    type="number"
                    min="0"
                    step="0.5"
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Statut -->
              <div>
                <label for="edit-status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select
                  id="edit-status"
                  v-model="editingProject.status"
                  class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                >
                  <option value="not_started">Non commencé</option>
                  <option value="in_progress">En cours</option>
                  <option value="in_review">En revue</option>
                  <option value="completed">Terminé</option>
                  <option value="blocked">Bloqué</option>
                </select>
              </div>
              
              <!-- Progression -->
              <div class="sm:col-span-2">
                <label for="edit-progress" class="block text-sm font-medium text-gray-700">
                  Progression: {{ editingProject.progress || 0 }}%
                </label>
                <div class="mt-2">
                  <input
                    id="edit-progress"
                    v-model.number="editingProject.progress"
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                  />
                  <div class="mt-1 flex justify-between text-xs text-gray-500">
                    <span>0%</span>
                    <span>100%</span>
                  </div>
                </div>
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>
    
    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
      <button
        type="button"
        @click="updateProject"
        class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
      >
        Enregistrer les modifications
      </button>
      <button
        type="button"
        @click="closeEditModal"
        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
      >
        Annuler
      </button>
    </div>
  </Modal>

  <!-- Modal d'édition de tâche -->
  <Modal :show="showEditTaskModal" @close="showEditTaskModal = false" max-width="2xl">
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="w-full">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            <Pencil class="inline-block w-5 h-5 mr-2 text-primary-600" />
            Modifier la tâche
          </h3>
          
          <form @submit.prevent="updateTask" class="space-y-4" v-if="editingTask">
            <!-- Titre -->
            <div>
              <label for="edit-task-title" class="block text-sm font-medium text-gray-700">Titre *</label>
              <input
                id="edit-task-title"
                v-model="editingTask.title"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <!-- Description -->
            <div>
              <label for="edit-task-description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                id="edit-task-description"
                v-model="editingTask.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Statut -->
              <div>
                <label for="edit-task-status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select
                  id="edit-task-status"
                  v-model="editingTask.status"
                  class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                >
                  <option value="not_started">Non commencé</option>
                  <option value="in_progress">En cours</option>
                  <option value="on_hold">En attente</option>
                  <option value="completed">Terminé</option>
                  <option value="cancelled">Annulé</option>
                </select>
              </div>
              
              <!-- Temps estimé -->
              <div>
                <label for="edit-task-estimated-time" class="block text-sm font-medium text-gray-700">Temps estimé (heures)</label>
                <input
                  id="edit-task-estimated-time"
                  v-model.number="editingTask.estimated_time"
                  type="number"
                  min="0"
                  step="0.5"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
              </div>
              
              <!-- Date d'échéance -->
              <div>
                <label for="edit-task-due-date" class="block text-sm font-medium text-gray-700">Date d'échéance</label>
                <input
                  id="edit-task-due-date"
                  v-model="editingTask.due_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
              </div>
              
              <!-- Assigné à -->
              <div v-if="props.users && props.users.length > 0">
                <label for="edit-task-assignee" class="block text-sm font-medium text-gray-700">Assigné à</label>
                <select
                  id="edit-task-assignee"
                  v-model="editingTask.assigned_to"
                  class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                >
                  <option :value="null">Non assigné</option>
                  <option v-for="user in props.users" :key="user.id" :value="user.id">
                    {{ user.name }}
                  </option>
                </select>
              </div>
            </div>
            
            <div class="py-3 sm:flex sm:flex-row-reverse">
              <button
                type="submit"
                :disabled="isAddingTask"
                class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isAddingTask" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Enregistrement...
                </span>
                <span v-else>Enregistrer les modifications</span>
              </button>
              <button
                type="button"
                @click="showEditTaskModal = false"
                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Modal>

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
  <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
      <div class="sm:flex sm:items-start">
        <div class="w-full">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            <FolderInput class="inline-block w-5 h-5 mr-2 text-primary-600" />
            Nouveau Projet
          </h3>
          
          <form @submit.prevent="addProject" class="space-y-4">
            <!-- Titre -->
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">Titre *</label>
              <input
                id="title"
                v-model="newProject.title"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            
            <!-- Description -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                id="description"
                v-model="newProject.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Date de début -->
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Date de début *</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <CalendarDays class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="start_date"
                    v-model="newProject.start_date"
                    type="date"
                    required
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Date d'échéance -->
              <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700">Date d'échéance *</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Calendar class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="deadline"
                    v-model="newProject.deadline"
                    type="date"
                    required
                    :min="newProject.start_date"
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Temps estimé -->
              <div>
                <label for="estimated_time" class="block text-sm font-medium text-gray-700">Temps estimé (heures)</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Clock3 class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    id="estimated_time"
                    v-model.number="newProject.estimated_time"
                    type="number"
                    min="0"
                    step="0.5"
                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>
              </div>
              
              <!-- Statut -->
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select
                  id="status"
                  v-model="newProject.status"
                  class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                >
                  <option value="not_started">Non commencé</option>
                  <option value="in_progress">En cours</option>
                  <option value="on_hold">En attente</option>
                  <option value="completed">Terminé</option>
                  <option value="cancelled">Annulé</option>
                </select>
              </div>
            </div>
            
            <div class="py-3 sm:flex sm:flex-row-reverse ">
              <button
                type="submit"
                class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Créer le projet
              </button>
              <button
                type="button"
                @click="() => { showCreateModal = false; resetProjectForm(); }"
                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuGroup, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/Components/ui/dropdown-menu';
import { useToast } from '@/Components/ui/toast/use-toast';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
  Plus, 
  Search, 
  ChevronRight, 
  ChevronDown,
  Calendar,
  FolderOpen,
  FolderInput,
  Clock,
  CheckCircle,
  Pause,
  XCircle,
  ListTodo,
  MoreHorizontal,
  Trash2,
  Pencil,
  CheckCheck,
  PauseCircle,
  X,
  User,
  Users,
  Clock3,
  CalendarDays,
  FileText
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';

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
const expandedSubtasks = ref({});
const newProject = reactive({
  title: '',
  description: '',
  status: 'not_started',
  start_date: new Date().toISOString().split('T')[0],
  deadline: '',
  estimated_time: null
});

const resetProjectForm = () => {
  newProject.title = '';
  newProject.description = '';
  newProject.status = 'not_started';
  newProject.start_date = new Date().toISOString().split('T')[0];
  newProject.deadline = '';
  newProject.estimated_time = null;
};

// État pour la création de tâche
const newTasks = reactive({}); // Pour stocker les tâches en cours de création par projet

const newTask = reactive({
  title: '',
  description: '',
  status: 'not_started',
  estimated_time: null,
  due_date: '',
  project_id: ''
});

const showTaskModal = ref(false);
const showTaskForm = ref(null);
const isAddingTask = ref(false);

const openTaskModal = (projectId) => {
  // Réinitialiser le formulaire
  resetTaskForm();
  // Définir l'ID du projet
  newTask.project_id = projectId;
  // Afficher le modal
  showTaskModal.value = true;
  console.log('Ouverture du modal pour le projet:', projectId);
};

const resetTaskForm = () => {
  newTask.title = '';
  newTask.description = '';
  newTask.status = 'not_started';
  newTask.estimated_time = null;
  newTask.due_date = '';
  newTask.status = 'not_started';
  newTask.estimated_time = null;
  newTask.due_date = '';
  newTask.assigned_to = '';
};

const newSubtasks = reactive({});
const editingProject = ref(null);
const showEditModal = ref(false);
const showCreateModal = ref(false);
const { toast } = useToast();

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
  console.log('Projets reçus:', props.projects);
  
  const filtered = props.projects.filter(project => {
    // Vérifier si project est défini et a une propriété title
    if (!project || !project.title) return false;
    
    const matchesSearch = project.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        (project.description && project.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchesStatus = !statusFilter.value || project.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  }).sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at)); // Tri par date de mise à jour
  
  console.log('Projets filtrés:', filtered);
  return filtered;
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

const addProject = async () => {
  try {
    const projectData = {
      title: newProject.title,
      description: newProject.description,
      status: newProject.status,
      start_date: newProject.start_date,
      deadline: newProject.deadline,
      estimated_time: newProject.estimated_time ? parseFloat(newProject.estimated_time) : null,
      progress: 0
    };

    // Envoyer la requête
    await router.post(route('projects.store'), projectData, {
      onSuccess: () => {
        // Fermer le modal
        showCreateModal.value = false;
        resetProjectForm();
        
        // Afficher le message de succès
        toast({
          title: 'Succès',
          description: 'Le projet a été créé avec succès',
          variant: 'default',
          duration: 3000
        });
      },
      onError: (errors) => {
        // Afficher l'erreur
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
    
    // Afficher l'erreur
    const errorMessage = error.response?.data?.message || error.message || 'Une erreur est survenue';
    
    toast({
      title: 'Erreur',
      description: errorMessage,
      variant: 'destructive',
      duration: 5000
    });
  }
};

const editProject = (project) => {
  // Formater les dates pour l'affichage dans les champs de type date
  const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
  };

  editingProject.value = { 
    ...project,
    start_date: formatDateForInput(project.start_date),
    deadline: formatDateForInput(project.deadline),
    team: project.team || []
  };
  
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editingProject.value = null;
};

const updateProject = () => {
  if (!editingProject.value) return;
  
  router.put(route('projects.update', editingProject.value.id), editingProject.value, {
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
  })
}

const projectToDelete = ref(null);
const showDeleteModal = ref(false);

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
const showEditTaskModal = ref(false);
const editingTask = ref(null);

const openEditTaskModal = (task) => {
  editingTask.value = { ...task };
  showEditTaskModal.value = true;
};

const updateTask = async () => {
  if (!editingTask.value) return;
  
  try {
    isAddingTask.value = true;
    
    await axios.put(route('tasks.update', editingTask.value.id), {
      title: editingTask.value.title,
      description: editingTask.value.description,
      status: editingTask.value.status,
      estimated_time: editingTask.value.estimated_time,
      due_date: editingTask.value.due_date,
      assigned_to: editingTask.value.assigned_to?.id || null
    });
    
    // Mettre à jour la tâche dans la liste
    const project = props.projects.find(p => p.id === editingTask.value.project_id);
    if (project && project.tasks) {
      const taskIndex = project.tasks.findIndex(t => t.id === editingTask.value.id);
      if (taskIndex !== -1) {
        project.tasks[taskIndex] = { ...project.tasks[taskIndex], ...editingTask.value };
      }
    }
    
    showEditTaskModal.value = false;
    editingTask.value = null;
    
    toast({
      title: 'Succès',
      description: 'La tâche a été mise à jour avec succès',
      variant: 'default',
      duration: 3000
    });
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la tâche:', error);
    
    let errorMessage = 'Une erreur est survenue lors de la mise à jour de la tâche';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }
    
    toast({
      title: 'Erreur',
      description: errorMessage,
      variant: 'destructive',
      duration: 5000
    });
  } finally {
    isAddingTask.value = false;
  }
};

const confirmDeleteTask = (task) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ? Cette action est irréversible.')) {
    deleteTask(task.project_id, task.id);
  }
};

const deleteTask = async (projectId, taskId) => {
  try {
    await axios.delete(route('tasks.destroy', taskId));
    
    // Supprimer la tâche de la liste
    const project = props.projects.find(p => p.id === projectId);
    if (project && project.tasks) {
      project.tasks = project.tasks.filter(t => t.id !== taskId);
    }
    
    toast({
      title: 'Succès',
      description: 'La tâche a été supprimée avec succès',
      variant: 'default',
      duration: 3000
    });
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

const addTask = async (projectId) => {
  try {
    isAddingTask.value = true;
    
    // Préparer les données de la tâche
    const taskData = {
      title: newTask.title,
      description: newTask.description,
      status: newTask.status,
      estimated_time: newTask.estimated_time,
      due_date: newTask.due_date,
      project_id: projectId || newTask.project_id,
      created_by: props.auth.user.id
    };
    
    // Envoyer la requête
    await axios.post(route('projects.tasks.store', { project: projectId }), taskData);

    // Fermer le modal et réinitialiser le formulaire
    showTaskModal.value = false;
    resetTaskForm();
    
    // Recharger la page pour afficher la nouvelle tâche
    router.reload({ only: ['projects'] });
    
    toast({
      title: 'Succès',
      description: 'La tâche a été créée avec succès',
      variant: 'default',
      duration: 3000
    });
  } catch (error) {
    console.error('Erreur lors de la création de la tâche:', error);
    
    let errorMessage = 'Une erreur est survenue lors de la création de la tâche';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }
    
    toast({
      title: 'Erreur',
      description: errorMessage,
      variant: 'destructive',
      duration: 5000
    });
  }
};

const toggleTaskCompletion = (task) => {
  router.put(route('tasks.update', task.id), {
    completed: task.completed
  }, {
    preserveScroll: true
  });
};

// const deleteTask = (project, taskId) => {
//   if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
//     router.delete(route('tasks.destroy', taskId), {
//       preserveScroll: true
//     });
//   }
// };

// Méthodes pour les sous-tâches
const toggleSubtasks = (projectId, taskId) => {
  if (!expandedSubtasks.value[taskId]) {
    expandedSubtasks.value[taskId] = true;
  } else {
    delete expandedSubtasks.value[taskId];
  }
};

const addSubtask = async (project, task) => {
  if (!newSubtasks[task.id]?.trim()) return;
  
  try {
    await router.post(route('subtasks.store'), {
      title: newSubtasks[task.id],
      task_id: task.id
    }, {
      onSuccess: () => {
        newSubtasks[task.id] = '';
      },
      preserveScroll: true
    });
  } catch (error) {
    console.error('Erreur lors de l\'ajout de la sous-tâche:', error);
  }
};

const toggleSubtaskCompletion = (task, subtask) => {
  router.put(route('subtasks.update', subtask.id), {
    completed: subtask.completed
  }, {
    preserveScroll: true
  });
};

const deleteSubtask = (task, subtaskId) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette sous-tâche ?')) {
    router.delete(route('subtasks.destroy', subtaskId), {
      preserveScroll: true
    });
  }
};

// Fonctions utilitaires
const getStatusLabel = (status) => {
  const statuses = {
    'not_started': 'Non commencé',
    'in_progress': 'En cours',
    'on_hold': 'En attente',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };
  return statuses[status] || status;
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

const getRowClass = (project) => {
  if (project.status === 'completed') return 'bg-green-50';
  if (project.status === 'in_progress') return 'bg-blue-50';
  if (project.status === 'on_hold') return 'bg-yellow-50';
  if (project.status === 'cancelled') return 'bg-red-50';
  return 'bg-white';
};

const getProgressBarColor = (progress) => {
  if (progress < 25) return 'bg-red-500';
  if (progress < 75) return 'bg-yellow-500';
  return 'bg-green-500';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR');
};

// Initialisation des nouvelles tâches pour chaque projet
if (props.projects && Array.isArray(props.projects)) {
  props.projects.forEach(project => {
    if (project && project.id) {
      newTasks[project.id] = '';
      if (project.tasks && Array.isArray(project.tasks)) {
        project.tasks.forEach(task => {
          if (task && task.id) {
            newSubtasks[task.id] = '';
          }
        });
      }
    }
  });
}
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

/* Utilisation des couleurs primaires */
.bg-primary-50 { background-color: var(--color-primary-50); }
.bg-primary-100 { background-color: var(--color-primary-100); }
.bg-primary-200 { background-color: var(--color-primary-200); }
.bg-primary-300 { background-color: var(--color-primary-300); }
.bg-primary-400 { background-color: var(--color-primary-400); }
.bg-primary-500 { background-color: var(--color-primary-500); }
.bg-primary-600 { background-color: var(--color-primary-600); }
.bg-primary-700 { background-color: var(--color-primary-700); }
.bg-primary-800 { background-color: var(--color-primary-800); }
.bg-primary-900 { background-color: var(--color-primary-900); }

.text-primary-500 { color: var(--color-primary-500); }
.text-primary-600 { color: var(--color-primary-600); }
.text-primary-700 { color: var(--color-primary-700); }

.border-primary-300 { border-color: var(--color-primary-300); }
.border-primary-500 { border-color: var(--color-primary-500); }

.focus\:ring-primary-500:focus { --tw-ring-color: var(--color-primary-500); }
.focus\:border-primary-500:focus { border-color: var(--color-primary-500); }

.hover\:bg-primary-50:hover { background-color: var(--color-primary-50); }
.hover\:bg-primary-100:hover { background-color: var(--color-primary-100); }
.hover\:bg-primary-600:hover { background-color: var(--color-primary-600); }
.hover\:bg-primary-700:hover { background-color: var(--color-primary-700); }

/* Classes utilitaires */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Styles pour les statuts */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  line-height: 1rem;
  font-weight: 500;
}

.badge-not_started { 
  background-color: #f3f4f6;
  color: #1f2937;
}

.badge-in_progress { 
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-on_hold { 
  background-color: #fef3c7;
  color: #92400e;
}

.badge-completed { 
  background-color: #dcfce7;
  color: #166534;
}

.badge-cancelled { 
  background-color: #fee2e2;
  color: #991b1b;
}

/* Styles pour les boutons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  font-weight: 500;
  border-radius: 0.375rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  outline: 2px solid transparent;
  outline-offset: 2px;
}

.btn:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
  --tw-ring-offset-width: 2px;
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(14 165 233 / var(--tw-ring-opacity));
}

.btn-primary {
  color: white;
  background-color: var(--color-primary-600);
  border: 1px solid transparent;
}

.btn-primary:hover {
  background-color: var(--color-primary-700);
}

.btn-primary:focus {
  --tw-ring-color: var(--color-primary-500);
}

.btn-secondary {
  color: #374151;
  background-color: white;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

.btn-secondary:focus {
  --tw-ring-color: var(--color-primary-500);
}

.btn-danger {
  color: white;
  background-color: #dc2626;
  border: 1px solid transparent;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

.btn-danger:focus {
  --tw-ring-color: #ef4444;
}
</style>
