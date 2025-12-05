<template>
    <div class="space-y-4">
        <!-- Header with add button -->
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center">
                <ListChecks class="mr-2 h-5 w-5" />
                Tâches
            </h2>
            <Button @click="$emit('add-task')">
                <Plus class="mr-2 h-4 w-4" />
                Nouvelle tâche
            </Button>
        </div>

        <!-- Tasks table -->
        <div
            v-if="tasks && tasks.length > 0"
            class="border rounded-lg overflow-hidden"
        >
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Titre
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Statut
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Priorité
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Échéance
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Temps estimé
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="task in tasks" :key="task.id">
                        <!-- Task row -->
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <button
                                        @click="toggleExpanded(task.id)"
                                        class="mr-2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                        v-if="task.sub_tasks"
                                    >
                                        <component
                                            :is="
                                                isExpanded(task.id)
                                                    ? ChevronDown
                                                    : ChevronRight
                                            "
                                            class="w-4 h-4"
                                        />
                                    </button>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">
                                            {{ task.title }}
                                        </div>
                                        <div
                                            class="text-sm text-gray-500 line-clamp-1"
                                        >
                                            {{
                                                task.description ||
                                                "Aucune description"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge
                                    :variant="getStatusVariant(task.status)"
                                    class="capitalize"
                                >
                                    {{ getStatusLabel(task.status) }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge
                                    :variant="getPriorityVariant(task.priority)"
                                    class="capitalize"
                                    v-if="task.priority"
                                >
                                    {{ getPriorityLabel(task.priority) }}
                                </Badge>
                                <span v-else class="text-sm text-gray-400"
                                    >-</span
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    v-if="task.due_date"
                                    class="flex items-center text-sm"
                                >
                                    <Calendar
                                        class="mr-1 h-4 w-4"
                                        :class="
                                            isOverdue(task)
                                                ? 'text-red-500'
                                                : 'text-gray-400'
                                        "
                                    />
                                    <span
                                        :class="
                                            isOverdue(task)
                                                ? 'text-red-600 font-medium'
                                                : 'text-gray-700'
                                        "
                                    >
                                        {{ formatDate(task.due_date) }}
                                    </span>
                                    <AlertCircle
                                        v-if="isOverdue(task)"
                                        class="ml-1 h-4 w-4 text-red-500"
                                    />
                                </div>
                                <span v-else class="text-sm text-gray-400"
                                    >-</span
                                >
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"
                            >
                                <div
                                    v-if="task.estimated_time"
                                    class="flex items-center"
                                >
                                    <Clock class="mr-1 h-4 w-4 text-gray-400" />
                                    {{ task.estimated_time }}h
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="$emit('edit-task', task)"
                                    >
                                        <Pencil class="w-4 h-4 mr-1" />
                                        Modifier
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="$emit('delete-task', task)"
                                    >
                                        <Trash2 class="w-4 h-4 mr-1" />
                                        Supprimer
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <!-- Subtasks row (expanded) -->
                        <tr
                            v-if="isExpanded(task.id) && task.sub_tasks"
                            class="bg-gray-50"
                        >
                            <td colspan="6" class="px-6 py-4">
                                <SubTasksRow
                                    :sub-tasks="task.sub_tasks"
                                    :task-id="task.id"
                                    @toggle-subtask="
                                        (subtask) =>
                                            $emit(
                                                'toggle-subtask',
                                                task,
                                                subtask
                                            )
                                    "
                                    @edit-subtask="
                                        (subtask) =>
                                            $emit('edit-subtask', task, subtask)
                                    "
                                    @delete-subtask="
                                        (subtask) =>
                                            $emit(
                                                'delete-subtask',
                                                task,
                                                subtask
                                            )
                                    "
                                    @add-subtask="$emit('add-subtask', task)"
                                />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-8 border-2 border-dashed rounded-lg">
            <p class="text-muted-foreground">Aucune tâche pour le moment</p>
            <Button variant="outline" class="mt-4" @click="$emit('add-task')">
                <Plus class="mr-2 h-4 w-4" />
                Créer une tâche
            </Button>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { format } from "date-fns";
import { fr } from "date-fns/locale";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    ListChecks,
    Plus,
    ChevronRight,
    ChevronDown,
    Calendar,
    Clock,
    Pencil,
    Trash2,
    AlertCircle,
} from "lucide-vue-next";
import SubTasksRow from "./SubTasksRow.vue";

defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    projectId: {
        type: Number,
        required: true,
    },
});

defineEmits([
    "add-task",
    "edit-task",
    "delete-task",
    "toggle-subtask",
    "edit-subtask",
    "delete-subtask",
    "add-subtask",
]);

const expandedTaskIds = ref(new Set());

const isExpanded = (taskId) => expandedTaskIds.value.has(taskId);

const toggleExpanded = (taskId) => {
    if (expandedTaskIds.value.has(taskId)) {
        expandedTaskIds.value.delete(taskId);
    } else {
        expandedTaskIds.value.add(taskId);
    }
};

const getStatusVariant = (status) => {
    const variants = {
        not_started: "secondary",
        in_progress: "default",
        on_hold: "warning",
        completed: "success",
        cancelled: "destructive",
        blocked: "destructive",
    };
    return variants[status] || "default";
};

const getStatusLabel = (status) => {
    const labels = {
        not_started: "Non commencé",
        in_progress: "En cours",
        on_hold: "En attente",
        in_review: "En revue",
        completed: "Terminé",
        cancelled: "Annulé",
        blocked: "Bloqué",
    };
    return labels[status] || status;
};

const getPriorityVariant = (priority) => {
    const variants = {
        low: "secondary",
        medium: "warning",
        high: "destructive",
    };
    return variants[priority] || "secondary";
};

const getPriorityLabel = (priority) => {
    const labels = {
        low: "Basse",
        medium: "Moyenne",
        high: "Haute",
    };
    return labels[priority] || priority;
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    try {
        return format(new Date(dateString), "dd/MM/yyyy", { locale: fr });
    } catch (e) {
        return dateString;
    }
};

const isOverdue = (task) => {
    if (!task.due_date || task.status === "completed") return false;
    return new Date(task.due_date) < new Date();
};
</script>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
