<template>
    <div class="ml-8 space-y-3">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-700">Sous-tâches</h3>
            <Button size="sm" variant="outline" @click="$emit('add-subtask')">
                <Plus class="w-4 h-4 mr-1" />
                Nouvelle sous-tâche
            </Button>
        </div>

        <div v-if="subTasks && subTasks.length > 0" class="space-y-2">
            <div
                v-for="subtask in subTasks"
                :key="subtask.id"
                class="flex items-center justify-between p-3 rounded border bg-white hover:bg-gray-50 transition-colors"
            >
                <div class="flex items-center gap-3 flex-1">
                    <input
                        type="checkbox"
                        :checked="subtask.status === 'completed'"
                        @change="$emit('toggle-subtask', subtask)"
                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded cursor-pointer"
                    />
                    <div class="flex-1">
                        <div
                            class="font-medium text-sm"
                            :class="{
                                'line-through text-gray-400':
                                    subtask.status === 'completed',
                            }"
                        >
                            {{ subtask.title }}
                        </div>
                        <div
                            v-if="subtask.description"
                            class="text-xs text-gray-500 mt-0.5 line-clamp-1"
                            :class="{
                                'line-through': subtask.status === 'completed',
                            }"
                        >
                            {{ subtask.description }}
                        </div>
                        <div
                            class="flex items-center gap-3 mt-1 text-xs text-gray-500"
                        >
                            <span
                                v-if="subtask.due_date"
                                class="flex items-center"
                            >
                                <Calendar class="w-3 h-3 mr-1" />
                                {{ formatDate(subtask.due_date) }}
                            </span>
                            <span
                                v-if="subtask.estimated_time"
                                class="flex items-center"
                            >
                                <Clock class="w-3 h-3 mr-1" />
                                {{ subtask.estimated_time }}h
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="$emit('edit-subtask', subtask)"
                    >
                        <Pencil class="w-3 h-3" />
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="$emit('delete-subtask', subtask)"
                    >
                        <Trash2 class="w-3 h-3" />
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-else
            class="text-sm text-gray-500 p-3 bg-gray-100 rounded border border-dashed"
        >
            Aucune sous-tâche. Créez-en une.
        </div>
    </div>
</template>

<script setup>
import { format } from "date-fns";
import { fr } from "date-fns/locale";
import { Button } from "@/Components/ui/button";
import { Plus, Pencil, Trash2, Calendar, Clock } from "lucide-vue-next";

defineProps({
    subTasks: {
        type: Array,
        default: () => [],
    },
    taskId: {
        type: Number,
        required: true,
    },
});

defineEmits([
    "toggle-subtask",
    "edit-subtask",
    "delete-subtask",
    "add-subtask",
]);

const formatDate = (dateString) => {
    if (!dateString) return "";
    try {
        return format(new Date(dateString), "dd/MM/yyyy", { locale: fr });
    } catch (e) {
        return dateString;
    }
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
