<template>
    <div
        v-if="task"
        class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 group"
        :class="{
            'border-l-4 border-green-500': task.status === 'completed',
            'border-l-4 border-blue-500': task.status === 'in_progress',
            'border-l-4 border-yellow-500': task.status === 'on_hold',
            'border-l-4 border-red-500':
                task.status === 'blocked' ||
                (task.due_date &&
                    new Date(task.due_date) < new Date() &&
                    task.status !== 'completed'),
        }"
    >
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        :checked="task.status === 'completed'"
                        @change="$emit('toggle-completion', task)"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <h4
                        class="ml-2 text-base font-medium text-gray-900 truncate"
                    >
                        {{ task.title }}
                        <span
                            v-if="task.priority === 'high'"
                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full"
                        >
                            Haute priorité
                        </span>
                        <span
                            v-else-if="task.priority === 'medium'"
                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"
                        >
                            Priorité moyenne
                        </span>
                        <span
                            v-else-if="task.priority === 'low'"
                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                        >
                            Basse priorité
                        </span>
                    </h4>
                </div>

                <p
                    v-if="task.description"
                    class="mt-1 ml-6 text-sm text-gray-600 overflow-hidden"
                    style="
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        line-clamp: 2;
                    "
                >
                    {{ task.description }}
                </p>

                <div
                    class="mt-2 ml-6 flex flex-wrap items-center gap-4 text-sm text-gray-500"
                >
                    <!-- Assigné -->
                    <div
                        v-if="task.assignee"
                        class="flex items-center"
                        :title="`Assigné à ${task.assignee.name}`"
                    >
                        <User class="h-4 w-4 mr-1 shrink-0" />
                        <span class="truncate max-w-[120px]">{{
                            task.assignee.name
                        }}</span>
                    </div>

                    <!-- Date d'échéance avec indicateur de retard -->
                    <div
                        v-if="task.due_date"
                        class="flex items-center"
                        :class="{
                            'text-red-600':
                                new Date(task.due_date) < new Date() &&
                                task.status !== 'completed',
                        }"
                        :title="
                            `Échéance: ${formatDate(task.due_date)}` +
                            (new Date(task.due_date) < new Date() &&
                            task.status !== 'completed'
                                ? ' (En retard)'
                                : '')
                        "
                    >
                        <Calendar class="h-4 w-4 mr-1 shrink-0" />
                        <span>{{ formatDate(task.due_date) }}</span>
                        <span
                            v-if="
                                new Date(task.due_date) < new Date() &&
                                task.status !== 'completed'
                            "
                            class="ml-1 text-red-500"
                        >
                            <AlertTriangle class="h-3.5 w-3.5 inline-block" />
                        </span>
                    </div>

                    <!-- Temps estimé -->
                    <div
                        v-if="task.estimated_time"
                        class="flex items-center"
                        title="Temps estimé"
                    >
                        <Clock3 class="h-4 w-4 mr-1 shrink-0" />
                        <span>{{ task.estimated_time }}h</span>
                    </div>

                    <!-- Tags -->
                    <div
                        v-if="task.tags && task.tags.length > 0"
                        class="flex items-center flex-wrap gap-1 mt-1"
                    >
                        <span
                            v-for="tag in task.tags"
                            :key="tag.id"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                            :style="{
                                backgroundColor: `${tag.color}20`,
                                color: tag.color,
                            }"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <!-- Progression -->
                <div v-if="task.status !== 'completed'" class="mt-2 ml-6">
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 mb-1"
                    >
                        <span>Progression</span>
                        <span>{{ task.progress || 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div
                            class="bg-blue-600 h-1.5 rounded-full"
                            :class="{
                                'bg-red-500': task.progress < 30,
                                'bg-yellow-500':
                                    task.progress >= 30 && task.progress < 70,
                                'bg-green-500': task.progress >= 70,
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
                        <span
                            class="w-2 h-2 rounded-full mr-1.5"
                            :class="{
                                'bg-green-500': task.status === 'completed',
                                'bg-blue-500': task.status === 'in_progress',
                                'bg-yellow-500': task.status === 'on_hold',
                                'bg-red-500': task.status === 'blocked',
                                'bg-gray-400': task.status === 'not_started',
                            }"
                        ></span>
                        {{ getStatusLabel(task.status) }}
                    </span>

                    <!-- Date de création -->
                    <div
                        v-if="task.created_at"
                        class="text-xs text-gray-400 mt-1 whitespace-nowrap"
                    >
                        Créé le {{ formatDate(task.created_at) }}
                    </div>
                </div>

                <!-- Menu d'actions -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <MoreHorizontal class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuGroup>
                            <DropdownMenuItem @click="$emit('edit', task)">
                                <Pencil class="mr-2 h-4 w-4" />
                                <span>Modifier</span>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                @click="$emit('delete', task)"
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
    </div>
</template>

<script setup>
import {
    User,
    Calendar,
    Clock3,
    AlertTriangle,
    MoreHorizontal,
    Pencil,
    Trash2,
} from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

defineProps({
    task: {
        type: Object,
        required: true,
    },
});

defineEmits(["toggle-completion", "edit", "delete"]);

const getStatusLabel = (status) => {
    const statuses = {
        not_started: "Non commencé",
        in_progress: "En cours",
        on_hold: "En attente",
        completed: "Terminé",
        cancelled: "Annulé",
        blocked: "Bloqué",
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
        blocked: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("fr-FR");
};
</script>
