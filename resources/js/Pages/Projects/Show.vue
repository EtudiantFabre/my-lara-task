<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Project Header -->
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
        <div v-if="!isEditing">
          <h1 class="text-2xl font-bold tracking-tight">{{ project.title }}</h1>
          <p class="text-muted-foreground flex items-center">
            <Calendar class="mr-2 h-4 w-4" />
            {{ formatDate(project.start_date) }} - {{ formatDate(project.deadline) }}
          </p>
        </div>
        
        <!-- Edit Mode Title -->
        <div v-else class="w-full">
          <Input v-model="form.title" placeholder="Titre du projet" class="text-2xl font-bold" />
        </div>

        <div class="flex space-x-2">
          <template v-if="!isEditing && canEdit">
            <Button variant="outline" @click="editProject">
              <Pencil class="mr-2 h-4 w-4" />
              Modifier
            </Button>
            <Button variant="destructive" @click="deleteProject">
              <Trash2 class="mr-2 h-4 w-4" />
              Supprimer
            </Button>
          </template>
          
          <template v-else-if="isEditing">
            <Button variant="outline" @click="isEditing = false">
              <X class="mr-2 h-4 w-4" />
              Annuler
            </Button>
            <Button @click="updateProject">
              <Save class="mr-2 h-4 w-4" />
              Enregistrer
            </Button>
          </template>
        </div>
      </div>

      <!-- Project Status and Progress -->
      <div class="grid gap-4 md:grid-cols-3">
        <!-- Status Card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center">
              <Clock class="mr-2 h-4 w-4" />
              Statut
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="!isEditing">
              <Badge :variant="getStatusVariant(project.status)" class="capitalize">
                {{ statusOptions[project.status] || project.status }}
              </Badge>
            </div>
            <Select v-else v-model="form.status">
              <SelectTrigger>
                <SelectValue placeholder="Sélectionner un statut" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="(label, value) in statusOptions" :key="value" :value="value">
                  {{ label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </CardContent>
        </Card>

        <!-- Progress Card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center">
              <ListChecks class="mr-2 h-4 w-4" />
              Progression
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="!isEditing" class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm">{{ project.progress }}%</span>
                <span class="text-xs text-muted-foreground">
                  {{ project.tasks_count || 0 }} tâche(s)
                </span>
              </div>
              <Progress :value="project.progress" class="h-2" />
            </div>
            <div v-else class="space-y-2">
              <div class="flex items-center space-x-2">
                <Input 
                  type="number" 
                  v-model.number="form.progress" 
                  min="0" 
                  max="100" 
                  class="w-20" 
                />
                <span class="text-sm text-muted-foreground">%</span>
              </div>
              <Progress :value="form.progress" class="h-2" />
            </div>
          </CardContent>
        </Card>

        <!-- Due Date Card -->
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium flex items-center">
              <Calendar class="mr-2 h-4 w-4" />
              Dates
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="!isEditing">
              <div class="text-sm">
                <div>Début: {{ formatDate(project.start_date) }}</div>
                <div>Échéance: {{ formatDate(project.deadline) }}</div>
              </div>
              <div v-if="isOverdue" class="mt-2 flex items-center text-destructive text-sm">
                <AlertCircle class="mr-1 h-4 w-4" />
                En retard de {{ Math.abs(daysRemaining) }} jours
              </div>
              <div v-else class="mt-2 flex items-center text-muted-foreground text-sm">
                <Check class="mr-1 h-4 w-4 text-green-500" />
                {{ daysRemaining }} jours restants
              </div>
            </div>
            <div v-else class="space-y-2">
              <div>
                <label class="text-xs text-muted-foreground">Date de début</label>
                <Input type="date" v-model="form.start_date" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground">Date d'échéance</label>
                <Input type="date" v-model="form.deadline" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Project Description -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center">
            <FileText class="mr-2 h-5 w-5" />
            Description
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="!isEditing">
            <p class="whitespace-pre-line text-muted-foreground">
              {{ project.description || 'Aucune description fournie.' }}
            </p>
          </div>
          <Textarea 
            v-else 
            v-model="form.description" 
            placeholder="Décrivez le projet..." 
            class="min-h-[100px]"
          />
        </CardContent>
      </Card>

      <!-- Tasks Section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold flex items-center">
            <ListChecks class="mr-2 h-5 w-5" />
            Tâches
          </h2>
          <Button @click="openTaskModal">
            <Plus class="mr-2 h-4 w-4" />
            Nouvelle tâche
          </Button>
        </div>

        <div v-if="project.tasks && project.tasks.length > 0" class="space-y-2">
          <div 
            v-for="task in project.tasks" 
            :key="task.id" 
            class="p-4 border rounded-lg transition-colors"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <button class="text-muted-foreground" @click="toggleExpanded(task.id)">
                    <component :is="isExpanded(task.id) ? ChevronDown : ChevronRight" class="w-4 h-4" />
                  </button>
                  <div class="font-medium">{{ task.title }}</div>
                  <Badge :variant="getStatusVariant(task.status)" class="capitalize ml-2">
                    {{ statusOptions[task.status] || task.status }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground mt-1 line-clamp-2">
                  {{ task.description || 'Aucune description' }}
                </p>
                <div class="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                  <span>Créé le {{ formatDate(task.created_at, 'dd/MM/yyyy') }}</span>
                  <span v-if="task.due_date">Échéance: {{ formatDate(task.due_date, 'dd/MM/yyyy') }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="openTaskEditModal(task)">
                  <Pencil class="w-4 h-4 mr-1" /> Modifier
                </Button>
                <Button variant="destructive" size="sm" @click="deleteTask(task)">
                  <Trash2 class="w-4 h-4 mr-1" /> Supprimer
                </Button>
              </div>
            </div>

            <div v-if="isExpanded(task.id)" class="mt-4 pl-6 border-l">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold">Sous-tâches</h3>
                <Button size="sm" variant="outline" @click="openSubTaskModal(task)">
                  <Plus class="w-4 h-4 mr-1" /> Nouvelle sous-tâche
                </Button>
              </div>
              <div v-if="task.sub_tasks && task.sub_tasks.length" class="space-y-2">
                <div v-for="st in task.sub_tasks" :key="st.id" class="flex items-center justify-between p-3 rounded border">
                  <div class="flex items-center gap-3">
                    <input type="checkbox" :checked="st.status === 'completed'" @change="toggleSubTask(task, st)" />
                    <div class="font-medium" :class="{ 'line-through text-muted-foreground': st.status === 'completed' }">{{ st.title }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Button size="sm" variant="outline" @click="openSubTaskEdit(task, st)">Modifier</Button>
                    <Button size="sm" variant="destructive" @click="deleteSubTask(task, st)">Supprimer</Button>
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-muted-foreground">Aucune sous-tâche. Créez-en une.</div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
          <p class="text-muted-foreground">Aucune tâche pour le moment</p>
          <Button 
            variant="outline" 
            class="mt-4"
            @click="openTaskModal"
          >
            <Plus class="mr-2 h-4 w-4" />
            Créer une tâche
          </Button>
        </div>
      </div>

      <!-- Modal de création de tâche -->
      <Dialog v-model:open="showTaskModal">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Nouvelle tâche</DialogTitle>
            <DialogDescription>
              Créez une nouvelle tâche pour ce projet.
            </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitTask">
            <div class="grid gap-4 py-4">
              <div class="space-y-2">
                <Label for="title" :required="true">Titre</Label>
                <Input id="title" v-model="taskForm.title" required />
                <p v-if="taskForm.errors.title" class="text-sm text-destructive">{{ taskForm.errors.title }}</p>
              </div>
              
              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="taskForm.description" class="min-h-[100px]" />
                <p v-if="taskForm.errors.description" class="text-sm text-destructive">{{ taskForm.errors.description }}</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="priority" :required="true">Priorité</Label>
                  <Select v-model="taskForm.priority" required>
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionnez une priorité" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Basse</SelectItem>
                      <SelectItem value="medium">Moyenne</SelectItem>
                      <SelectItem value="high">Haute</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <div class="space-y-2">
                  <Label for="due_date" :required="true">Date d'échéance</Label>
                  <Input 
                    id="due_date" 
                    type="date" 
                    v-model="taskForm.due_date" 
                    :min="format(new Date(), 'yyyy-MM-dd')"
                    required 
                  />
                  <p v-if="taskForm.errors.due_date" class="text-sm text-destructive">{{ taskForm.errors.due_date }}</p>
                </div>
              </div>
              
              <div class="space-y-2">
                <Label for="estimated_hours">Temps estimé (heures)</Label>
                <Input 
                  id="estimated_hours" 
                  type="number" 
                  v-model.number="taskForm.estimated_hours" 
                  min="0" 
                  step="0.5" 
                />
                <p v-if="taskForm.errors.estimated_hours" class="text-sm text-destructive">{{ taskForm.errors.estimated_hours }}</p>
              </div>
            </div>
            
            <div class="flex justify-end space-x-2 pt-4">
              <Button type="button" variant="outline" @click="showTaskModal = false">
                Annuler
              </Button>
              <Button type="submit" :disabled="taskForm.processing">
                <span v-if="taskForm.processing">Création...</span>
                <span v-else>Créer la tâche</span>
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Modal d'édition de tâche -->
      <Dialog v-model:open="showTaskEditModal">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Modifier la tâche</DialogTitle>
        <DialogDescription>
          Mettez à jour les informations de la tâche sélectionnée.
        </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitTaskEdit">
            <div class="grid gap-4 py-4">
              <div class="space-y-2">
                <Label for="title_edit" :required="true">Titre</Label>
                <Input id="title_edit" v-model="taskEditForm.title" required />
                <p v-if="taskEditForm.errors.title" class="text-sm text-destructive">{{ taskEditForm.errors.title }}</p>
              </div>
              <div class="space-y-2">
                <Label for="desc_edit">Description</Label>
                <Textarea id="desc_edit" v-model="taskEditForm.description" class="min-h-[100px]" />
                <p v-if="taskEditForm.errors.description" class="text-sm text-destructive">{{ taskEditForm.errors.description }}</p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="due_edit">Échéance</Label>
                  <Input id="due_edit" type="date" v-model="taskEditForm.due_date" :min="format(new Date(), 'yyyy-MM-dd')" />
                  <p v-if="taskEditForm.errors.due_date" class="text-sm text-destructive">{{ taskEditForm.errors.due_date }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="est_edit">Temps estimé (h)</Label>
                  <Input id="est_edit" type="number" min="0" step="0.5" v-model.number="taskEditForm.estimated_hours" />
                  <p v-if="taskEditForm.errors.estimated_hours" class="text-sm text-destructive">{{ taskEditForm.errors.estimated_hours }}</p>
                </div>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <Button type="button" variant="outline" @click="showTaskEditModal = false">Annuler</Button>
              <Button type="submit" :disabled="taskEditForm.processing">Enregistrer</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Modal de création/édition de sous‑tâche -->
      <Dialog v-model:open="showSubTaskModal">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Nouvelle sous‑tâche</DialogTitle>
        <DialogDescription>
          Ajoutez une sous‑tâche pour la tâche sélectionnée.
        </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitSubTask">
            <div class="grid gap-4 py-4">
              <div class="space-y-2">
                <Label for="st_title" :required="true">Titre</Label>
                <Input id="st_title" v-model="subTaskForm.title" required />
                <p v-if="subTaskForm.errors.title" class="text-sm text-destructive">{{ subTaskForm.errors.title }}</p>
              </div>
              <div class="space-y-2">
                <Label for="st_desc">Description</Label>
                <Textarea id="st_desc" v-model="subTaskForm.description" class="min-h-[100px]" />
                <p v-if="subTaskForm.errors.description" class="text-sm text-destructive">{{ subTaskForm.errors.description }}</p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="st_due">Échéance</Label>
                  <Input id="st_due" type="date" v-model="subTaskForm.due_date" :min="format(new Date(), 'yyyy-MM-dd')" />
                  <p v-if="subTaskForm.errors.due_date" class="text-sm text-destructive">{{ subTaskForm.errors.due_date }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="st_est">Temps estimé (h)</Label>
                  <Input id="st_est" type="number" min="0" step="0.5" v-model.number="subTaskForm.estimated_hours" />
                  <p v-if="subTaskForm.errors.estimated_hours" class="text-sm text-destructive">{{ subTaskForm.errors.estimated_hours }}</p>
                </div>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <Button type="button" variant="outline" @click="showSubTaskModal = false">Annuler</Button>
              <Button type="submit" :disabled="subTaskForm.processing">Créer</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Modal d'édition de sous‑tâche -->
      <Dialog v-model:open="showSubTaskEditModal">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Modifier la sous‑tâche</DialogTitle>
        <DialogDescription>
          Mettez à jour les informations de cette sous‑tâche.
        </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitSubTaskEdit">
            <div class="grid gap-4 py-4">
              <div class="space-y-2">
                <Label for="st_title_edit" :required="true">Titre</Label>
                <Input id="st_title_edit" v-model="subTaskEditForm.title" required />
                <p v-if="subTaskEditForm.errors.title" class="text-sm text-destructive">{{ subTaskEditForm.errors.title }}</p>
              </div>
              <div class="space-y-2">
                <Label for="st_desc_edit">Description</Label>
                <Textarea id="st_desc_edit" v-model="subTaskEditForm.description" class="min-h-[100px]" />
                <p v-if="subTaskEditForm.errors.description" class="text-sm text-destructive">{{ subTaskEditForm.errors.description }}</p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="st_due_edit">Échéance</Label>
                  <Input id="st_due_edit" type="date" v-model="subTaskEditForm.due_date" :min="format(new Date(), 'yyyy-MM-dd')" />
                  <p v-if="subTaskEditForm.errors.due_date" class="text-sm text-destructive">{{ subTaskEditForm.errors.due_date }}</p>
                </div>
                <div class="space-y-2">
                  <Label for="st_est_edit">Temps estimé (h)</Label>
                  <Input id="st_est_edit" type="number" min="0" step="0.5" v-model.number="subTaskEditForm.estimated_hours" />
                  <p v-if="subTaskEditForm.errors.estimated_hours" class="text-sm text-destructive">{{ subTaskEditForm.errors.estimated_hours }}</p>
                </div>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <Button type="button" variant="outline" @click="showSubTaskEditModal = false">Annuler</Button>
              <Button type="submit" :disabled="subTaskEditForm.processing">Enregistrer</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

// Composants UI
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Progress } from '@/Components/ui/progress';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/Components/ui/select';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription 
} from '@/Components/ui/dialog';
import { useToast } from '@/Components/ui/toast/use-toast';

// Icônes
import { 
  Pencil, 
  Save, 
  Trash2,
  Check,
  Clock,
  AlertCircle,
  X,
  Calendar,
  FileText,
  ListChecks,
  Plus,
  ChevronRight,
  ChevronDown
} from 'lucide-vue-next';

const { toast } = useToast();

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
  statusOptions: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
});

const isEditing = ref(false);
const showTaskModal = ref(false);
const showSubTaskModal = ref(false);
const showTaskEditModal = ref(false);
const showSubTaskEditModal = ref(false);
const expandedTaskIds = ref(new Set());
const selectedTask = ref(null);
const selectedSubTask = ref(null);

const isExpanded = (taskId) => expandedTaskIds.value.has(taskId);
const toggleExpanded = (taskId) => {
  if (expandedTaskIds.value.has(taskId)) expandedTaskIds.value.delete(taskId); 
  else expandedTaskIds.value.add(taskId);
};

const reloadProject = () => {
  try { router.reload({ only: ['project'], preserveScroll: true }); } 
  catch { router.visit(route('projects.show', props.project.id), { preserveScroll: true, replace: true }); }
};

// Formulaire de création de tâche
const taskForm = useForm({
  title: '',
  description: '',
  project_id: props.project.id,
  priority: 'medium',
  due_date: format(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd'), // 7 jours plus tard par défaut
  estimated_hours: null,
  status: 'not_started',
});

// Soumission du formulaire de tâche
const submitTask = () => {
  taskForm.post(route('projects.tasks.store', props.project.id), {
    preserveScroll: true,
    onSuccess: () => {
      showTaskModal.value = false;
      taskForm.reset();
      // Réinitialiser avec les valeurs par défaut
      taskForm.project_id = props.project.id;
      taskForm.due_date = format(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd');
      taskForm.priority = 'medium';
      
      // Afficher une notification de succès
      toast({
        title: 'Succès',
        description: 'La tâche a été créée avec succès',
        variant: 'success',
      });
      reloadProject();
    },
    onError: (errors) => {
      const msg = Object.values(errors || {}).flat().join('\n') || 'Une erreur est survenue lors de la création de la tâche';
      toast({ title: 'Erreur', description: msg, variant: 'destructive' });
    },
  });
};

// Ouvrir le modal de création de tâche
const openTaskModal = () => {
  // Réinitialiser le formulaire avec les valeurs par défaut
  taskForm.reset();
  taskForm.project_id = props.project.id;
  taskForm.due_date = format(new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd');
  taskForm.priority = 'medium';
  taskForm.status = 'not_started';
  
  // Afficher la modale
  showTaskModal.value = true;
};

const toInputDate = (d) => {
  if (!d) return '';
  try { return format(new Date(d), 'yyyy-MM-dd'); } catch { return d; }
};

console.log(`props.project: ${JSON.stringify(props.project)}`);

const form = useForm({
  title: props.project.title ?? '',
  description: props.project.description ?? '',
  start_date: toInputDate(props.project.start_date),
  deadline: toInputDate(props.project.deadline),
  status: props.project.status ?? 'not_started',
  progress: props.project.progress ?? 0,
});

const editProject = () => {
  isEditing.value = true;
};

const updateProject = () => {
  form.put(route('projects.update', props.project.id), {
    onSuccess: () => {
      isEditing.value = false;
      toast({
        title: 'Succès',
        description: 'Le projet a été mis à jour avec succès',
        variant: 'success',
      });
    },
    onError: (errors) => {
      toast({
        title: 'Erreur',
        description: Object.values(errors).flat().join('\n'),
        variant: 'destructive',
      });
    }
  });
};

const deleteProject = () => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.')) {
    router.delete(route('projects.destroy', props.project.id), {
      onSuccess: () => {
        toast({
          title: 'Succès',
          description: 'Le projet a été supprimé avec succès',
          variant: 'success',
        });      
      },
      onError: () => {
        toast({
          title: 'Erreur',
          description: 'Une erreur est survenue lors de la suppression du projet',
          variant: 'destructive',
        });
      }
    });
  }
};

// Subtask creation form
const subTaskForm = useForm({
  title: '',
  description: '',
  estimated_hours: null,
  due_date: ''
});

const openSubTaskModal = (task) => {
  selectedTask.value = task;
  subTaskForm.reset();
  showSubTaskModal.value = true;
};

const submitSubTask = () => {
  if (!selectedTask.value) return;
  subTaskForm.post(route('subtasks.store', { task: selectedTask.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showSubTaskModal.value = false;
      toast({ title: 'Succès', description: 'Sous-tâche créée', variant: 'success' });
      reloadProject();
    },
    onError: (errors) => {
      const msg = Object.values(errors || {}).flat().join('\n') || 'Création de sous-tâche échouée';
      toast({ title: 'Erreur', description: msg, variant: 'destructive' });
    }
  });
};

const toggleSubTask = async (task, st) => {
  try {
    await axios.patch(route('subtasks.toggle', { task: task.id, subTask: st.id }));
    reloadProject();
  } catch (e) {
    toast({ title: 'Erreur', description: "Impossible de changer le statut", variant: 'destructive' });
  }
};

const deleteTask = (task) => {
  if (!confirm('Supprimer cette tâche ?')) return;
  router.delete(route('projects.tasks.destroy', { project: props.project.id, task: task.id }), {
    preserveScroll: true,
    onSuccess: () => { toast({ title: 'Succès', description: 'Tâche supprimée', variant: 'success' }); reloadProject(); },
    onError: (errors) => { const msg = Object.values(errors || {}).flat().join('\n') || 'Suppression échouée'; toast({ title: 'Erreur', description: msg, variant: 'destructive' }); }
  });
};

const deleteSubTask = (task, st) => {
  if (!confirm('Supprimer cette sous‑tâche ?')) return;
  router.delete(route('subtasks.destroy', { task: task.id, subTask: st.id }), {
    preserveScroll: true,
    onSuccess: () => { toast({ title: 'Succès', description: 'Sous‑tâche supprimée', variant: 'success' }); reloadProject(); },
    onError: (errors) => { const msg = Object.values(errors || {}).flat().join('\n') || 'Suppression échouée'; toast({ title: 'Erreur', description: msg, variant: 'destructive' }); }
  });
};

// Subtask edit state
const subTaskEditForm = useForm({ title: '', description: '', due_date: '', estimated_hours: null });
const openSubTaskEdit = (task, st) => {
  selectedTask.value = task;
  selectedSubTask.value = st;
  subTaskEditForm.reset();
  subTaskEditForm.title = st.title || '';
  subTaskEditForm.description = st.description || '';
  subTaskEditForm.due_date = toInputDate(st.due_date);
  subTaskEditForm.estimated_hours = st.estimated_time ?? null;
  showSubTaskEditModal.value = true;
};

const submitSubTaskEdit = () => {
  if (!selectedTask.value || !selectedSubTask.value) return;
  subTaskEditForm.patch(route('subtasks.update', { task: selectedTask.value.id, subTask: selectedSubTask.value.id }), {
    preserveScroll: true,
    onSuccess: () => { showSubTaskEditModal.value = false; toast({ title: 'Succès', description: 'Sous‑tâche mise à jour', variant: 'success' }); reloadProject(); },
    onError: (errors) => { const msg = Object.values(errors || {}).flat().join('\n') || 'Mise à jour échouée'; toast({ title: 'Erreur', description: msg, variant: 'destructive' }); }
  });
};

// Task edit
const taskEditForm = useForm({ title: '', description: '', due_date: '', estimated_hours: null, status: 'not_started' });
const openTaskEditModal = (task) => {
  selectedTask.value = task;
  taskEditForm.reset();
  taskEditForm.title = task.title || '';
  taskEditForm.description = task.description || '';
  taskEditForm.due_date = toInputDate(task.due_date);
  taskEditForm.estimated_hours = task.estimated_time ?? null;
  taskEditForm.status = task.status || 'not_started';
  showTaskEditModal.value = true;
};

const submitTaskEdit = () => {
  if (!selectedTask.value) return;
  taskEditForm.put(route('projects.tasks.update', { project: props.project.id, task: selectedTask.value.id }), {
    preserveScroll: true,
    onSuccess: () => { showTaskEditModal.value = false; toast({ title: 'Succès', description: 'Tâche mise à jour', variant: 'success' }); reloadProject(); },
    onError: (errors) => { const msg = Object.values(errors || {}).flat().join('\n') || 'Mise à jour échouée'; toast({ title: 'Erreur', description: msg, variant: 'destructive' }); }
  });
};

// (duplicate removed)

// Computed properties
const daysRemaining = computed(() => {
  if (!props.project.deadline) return 0;
  const today = new Date();
  const deadline = new Date(props.project.deadline);
  return Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
});

const isOverdue = computed(() => {
  if (!props.project.deadline) return false;
  const today = new Date();
  const deadline = new Date(props.project.deadline);
  return today > deadline;
});

// Helper functions
const getStatusVariant = (status) => {
  const variants = {
    'not_started': 'secondary',
    'in_progress': 'default',
    'on_hold': 'warning',
    'completed': 'success',
    'cancelled': 'destructive'
  };
  return variants[status] || 'default';
};

const formatDate = (dateString, formatStr = 'PPP') => {
  if (!dateString) return '';
  try {
    return format(new Date(dateString), formatStr, { locale: fr });
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString;
  }
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