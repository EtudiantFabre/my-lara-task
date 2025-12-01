<template>
    <template v-if="project && project.id">
        <tr
            class="transition-colors hover:bg-gray-50"
            :class="getRowClass(project)"
        >
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <button
                        @click="$emit('toggle-expansion', project.id)"
                        class="mr-2 text-gray-500 hover:text-gray-700 focus:outline-none"
                    >
                        <ChevronRight
                            class="w-5 h-5 transition-transform duration-200"
                            :class="{ 'transform rotate-90': isExpanded }"
                        />
                    </button>
                    <div>
                        <div class="font-medium text-gray-900">
                            {{ project.title }}
                        </div>
                        <div
                            class="text-sm text-gray-500 overflow-hidden text-ellipsis display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; line-clamp: 1;"
                        >
                            {{ project.description || "Aucune description" }}
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
                    <span class="text-sm text-gray-500"
                        >{{ project.progress }}%</span
                    >
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                <div class="flex items-center">
                    <Calendar class="w-4 h-4 mr-1 text-gray-400" />
                    <span
                        >{{ formatDate(project.start_date) }} -
                        {{ formatDate(project.deadline) }}</span
                    >
                </div>
            </td>
            <td
                class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
            >
                <ButtonGroup class="flex items-center justify-end">
                    <!-- Bouton pour ajouter une tâche -->
                    <Button
                        @click="$emit('add-task', project.id)"
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
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-8 w-8"
                            >
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuGroup>
                                <DropdownMenuItem
                                    @click="$emit('edit', project)"
                                >
                                    <Pencil class="mr-2 h-4 w-4" />
                                    <span>Modifier</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="$emit('delete', project)"
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
        <tr v-if="isExpanded" class="bg-gray-50">
            <td colspan="5" class="px-6 py-4">
                <div class="ml-8">
                    <h4 class="mb-3 text-sm font-medium text-gray-700">
                        Tâches du projet
                    </h4>

                    <!-- Liste des tâches -->
                    <div
                        v-if="project.tasks && project.tasks.length > 0"
                        class="space-y-3"
                    >
                        <TaskCard
                            v-for="task in project.tasks"
                            :key="task.id"
                            :task="task"
                            @toggle-completion="
                                $emit('toggle-task-completion', task)
                            "
                            @edit="$emit('edit-task', task)"
                            @delete="$emit('delete-task', task)"
                        />
                    </div>
                    <div
                        v-else
                        class="px-3 py-2 text-sm text-gray-500 bg-gray-100 rounded"
                    >
                        Aucune tâche pour l'instant
                    </div>
                </div>
            </td>
        </tr>
    </template>
</template>

<script setup>
import {
    ChevronRight,
    Calendar,
    Plus,
    MoreHorizontal,
    Pencil,
    Trash2,
} from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import { ButtonGroup } from "@/Components/ui/button-group";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import TaskCard from "./TaskCard.vue";

defineProps({
    project: {
        type: Object,
        required: true,
    },
    isExpanded: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    "toggle-expansion",
    "edit",
    "delete",
    "add-task",
    "toggle-task-completion",
    "edit-task",
    "delete-task",
]);

const getStatusLabel = (status) => {
    const statuses = {
        not_started: "Non commencé",
        in_progress: "En cours",
        on_hold: "En attente",
        completed: "Terminé",
        cancelled: "Annulé",
    };
    return statuses[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        not_started: "bg-gray-100 text-gray-800",
        in_progress: "bg-blue-100 text-blue-800",
        on_hold: "bg-yellow-100 text-yellow-800",
        completed: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getRowClass = (project) => {
    if (project.status === "completed") return "bg-green-50";
    if (project.status === "in_progress") return "bg-blue-50";
    if (project.status === "on_hold") return "bg-yellow-50";
    if (project.status === "cancelled") return "bg-red-50";
    return "bg-white";
};

const getProgressBarColor = (progress) => {
    if (progress < 25) return "bg-red-500";
    if (progress < 75) return "bg-yellow-500";
    return "bg-green-500";
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("fr-FR");
};
</script>
