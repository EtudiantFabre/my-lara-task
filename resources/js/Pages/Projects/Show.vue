<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Project Header -->
      <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ project.title }}</h1>
          <p class="text-muted-foreground">
            {{ formatDate(project.start_date) }} - {{ formatDate(project.end_date) }}
          </p>
        </div>
        <div class="flex space-x-2">
          <Button variant="outline" @click="$inertia.visit(route('projects.edit', project.id))">
            <Pencil class="mr-2 h-4 w-4" />
            Modifier
          </Button>
          <Button @click="showCreateTask = true">
            <Plus class="mr-2 h-4 w-4" />
            Nouvelle tâche
          </Button>
        </div>
      </div>

      <!-- Project Status and Progress -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Statut</CardTitle>
          </CardHeader>
          <CardContent>
            <Badge :variant="getStatusVariant(project.status)">
              {{ getStatusLabel(project.status) }}
            </Badge>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Progression</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span>Tâches complétées</span>
                <span class="font-medium">
                  {{ completedTasksCount }} / {{ project.tasks?.length || 0 }}
                </span>
              </div>
              <Progress :value="progressPercentage" class="h-2" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium">Temps restant</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ daysRemaining }} jours</div>
            <p class="text-xs text-muted-foreground">
              {{ isOverdue ? 'En retard de' : 'Jours restants' }} {{ Math.abs(daysRemaining) }} jours
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Project Description -->
      <Card>
        <CardHeader>
          <CardTitle>Description</CardTitle>
        </CardHeader>
        <CardContent>
          <p class="whitespace-pre-line text-muted-foreground">
            {{ project.description || 'Aucune description fournie.' }}
          </p>
        </CardContent>
      </Card>

      <!-- Tasks Section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Tâches</h2>
          <div class="flex space-x-2">
            <Select v-model="taskFilter">
              <SelectTrigger class="w-[180px]">
                <SelectValue placeholder="Filtrer par statut" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Toutes les tâches</SelectItem>
                <SelectItem value="todo">À faire</SelectItem>
                <SelectItem value="in_progress">En cours</SelectItem>
                <SelectItem value="in_review">En révision</SelectItem>
                <SelectItem value="done">Terminées</SelectItem>
              </SelectContent>
            </Select>
            <Select v-model="taskSort">
              <SelectTrigger class="w-[180px]">
                <SelectValue placeholder="Trier par" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="due_date_asc">Échéance (croissante)</SelectItem>
                <SelectItem value="due_date_desc">Échéance (décroissante)</SelectItem>
                <SelectItem value="priority_asc">Priorité (basse à haute)</SelectItem>
                <SelectItem value="priority_desc">Priorité (haute à basse)</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div v-if="filteredTasks.length > 0" class="space-y-2">
          <TaskItem
            v-for="task in sortedTasks"
            :key="task.id"
            :task="task"
            class="transition-all hover:shadow-md"
            @click="$inertia.visit(route('tasks.show', task.id))"
          />
        </div>
        <EmptyState
          v-else
          title="Aucune tâche trouvée"
          description="Commencez par créer votre première tâche."
          :icon="ClipboardList"
          action="Créer une tâche"
          @action="showCreateTask = true"
        />
      </div>
    </div>

    <!-- Create Task Dialog -->
    <Dialog :open="showCreateTask" @update:open="val => showCreateTask = val">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Nouvelle tâche</DialogTitle>
          <DialogDescription>
            Créez une nouvelle tâche pour ce projet.
          </DialogDescription>
        </DialogHeader>
        <TaskForm
          :initial-project-id="project.id"
          :projects="[project]"
          :submit-route="route('tasks.store')"
          :cancel-route="route('projects.show', project.id)"
          @success="showCreateTask = false"
        />
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { format, parseISO, differenceInDays, isAfter, isBefore } from 'date-fns';
import { fr } from 'date-fns/locale';
import { 
  Pencil, 
  Plus, 
  ClipboardList,
  ArrowUp,
  ArrowDown,
  Flag,
  AlertTriangle,
  CheckCircle2
} from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Progress } from '@/Components/ui/progress';
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
  DialogDescription, 
  DialogHeader, 
  DialogTitle 
} from '@/Components/ui/dialog';
import TaskForm from '@/Components/Tasks/TaskForm.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import TaskItem from '@/Components/Tasks/TaskItem.vue';

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
  tasks: {
    type: Array,
    default: () => [],
  },
});

const showCreateTask = ref(false);
const taskFilter = ref('all');
const taskSort = ref('due_date_asc');

const statusLabels = {
  not_started: 'Non commencé',
  in_progress: 'En cours',
  on_hold: 'En attente',
  completed: 'Terminé',
  cancelled: 'Annulé',
};

const statusVariants = {
  not_started: 'secondary',
  in_progress: 'default',
  on_hold: 'outline',
  completed: 'success',
  cancelled: 'destructive',
};

const getStatusLabel = (status) => statusLabels[status] || status;
const getStatusVariant = (status) => statusVariants[status] || 'outline';

const formatDate = (dateString) => {
  if (!dateString) return 'Non défini';
  return format(parseISO(dateString), 'PPP', { locale: fr });
};

const completedTasksCount = computed(() => {
  return props.tasks.filter(task => task.status === 'done').length;
});

const progressPercentage = computed(() => {
  if (!props.tasks.length) return 0;
  return Math.round((completedTasksCount.value / props.tasks.length) * 100);
});

const daysRemaining = computed(() => {
  if (!props.project.end_date) return 0;
  const endDate = parseISO(props.project.end_date);
  const today = new Date();
  return differenceInDays(endDate, today);
});

const isOverdue = computed(() => {
  if (!props.project.end_date) return false;
  const endDate = parseISO(props.project.end_date);
  return isBefore(endDate, new Date()) && props.project.status !== 'completed';
});

const filteredTasks = computed(() => {
  if (taskFilter.value === 'all') return props.tasks;
  return props.tasks.filter(task => task.status === taskFilter.value);
});

const sortedTasks = computed(() => {
  return [...filteredTasks.value].sort((a, b) => {
    switch (taskSort.value) {
      case 'due_date_asc':
        return new Date(a.due_date) - new Date(b.due_date);
      case 'due_date_desc':
        return new Date(b.due_date) - new Date(a.due_date);
      case 'priority_asc': {
        const priorityOrder = { low: 0, medium: 1, high: 2 };
        return priorityOrder[a.priority] - priorityOrder[b.priority];
      }
      case 'priority_desc': {
        const priorityOrder = { low: 0, medium: 1, high: 2 };
        return priorityOrder[b.priority] - priorityOrder[a.priority];
      }
      default:
        return 0;
    }
  });
});
</script>
