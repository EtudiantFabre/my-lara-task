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
          <Button @click="$inertia.visit(route('tasks.create', { project_id: project.id }))">
            <Plus class="mr-2 h-4 w-4" />
            Nouvelle tâche
          </Button>
        </div>

        <div v-if="project.tasks && project.tasks.length > 0" class="space-y-2">
          <div 
            v-for="task in project.tasks" 
            :key="task.id" 
            class="p-4 border rounded-lg hover:bg-accent/50 transition-colors cursor-pointer"
            @click="$inertia.visit(route('tasks.show', task.id))"
          >
            <div class="flex items-center justify-between">
              <div class="font-medium">{{ task.title }}</div>
              <Badge :variant="getStatusVariant(task.status)" class="capitalize">
                {{ statusOptions[task.status] || task.status }}
              </Badge>
            </div>
            <p class="text-sm text-muted-foreground mt-1 line-clamp-2">
              {{ task.description || 'Aucune description' }}
            </p>
            <div class="flex items-center justify-between mt-2 text-xs text-muted-foreground">
              <span>Créé le {{ formatDate(task.created_at, 'dd/MM/yyyy') }}</span>
              <span v-if="task.due_date">Échéance: {{ formatDate(task.due_date, 'dd/MM/yyyy') }}</span>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
          <p class="text-muted-foreground">Aucune tâche pour le moment</p>
          <Button 
            variant="outline" 
            class="mt-4"
            @click="$inertia.visit(route('tasks.create', { project_id: project.id }))"
          >
            <Plus class="mr-2 h-4 w-4" />
            Créer une tâche
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
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
  Plus
} from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Progress } from '@/Components/ui/progress';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/Components/ui/select';
import { useToast } from '@/Components/ui/toast/use-toast';
import AppLayout from '@/Layouts/AppLayout.vue';

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

const form = useForm({
  title: props.project.title,
  description: props.project.description,
  start_date: props.project.start_date,
  deadline: props.project.deadline,
  status: props.project.status,
  progress: props.project.progress,
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