<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <ListTodo
                            class="inline-block w-5 h-5 mr-2 text-primary-600"
                        />
                        {{ task ? "Modifier la tâche" : "Nouvelle Tâche" }}
                    </h3>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Titre -->
                        <div>
                            <label
                                for="task-title"
                                class="block text-sm font-medium text-gray-700"
                                >Titre *</label
                            >
                            <input
                                id="task-title"
                                v-model="formData.title"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                for="task-description"
                                class="block text-sm font-medium text-gray-700"
                                >Description</label
                            >
                            <textarea
                                id="task-description"
                                v-model="formData.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Statut -->
                            <div>
                                <label
                                    for="task-status"
                                    class="block text-sm font-medium text-gray-700"
                                    >Statut</label
                                >
                                <select
                                    id="task-status"
                                    v-model="formData.status"
                                    class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                                >
                                    <option value="not_started">
                                        Non commencé
                                    </option>
                                    <option value="in_progress">
                                        En cours
                                    </option>
                                    <option value="on_hold">En attente</option>
                                    <option value="completed">Terminé</option>
                                    <option value="blocked">Bloqué</option>
                                </select>
                            </div>

                            <!-- Temps estimé -->
                            <div>
                                <label
                                    for="task-estimated-time"
                                    class="block text-sm font-medium text-gray-700"
                                    >Temps estimé (heures)</label
                                >
                                <input
                                    id="task-estimated-time"
                                    v-model.number="formData.estimated_time"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                />
                            </div>

                            <!-- Date d'échéance -->
                            <div>
                                <label
                                    for="task-due-date"
                                    class="block text-sm font-medium text-gray-700"
                                    >Date d'échéance</label
                                >
                                <input
                                    id="task-due-date"
                                    v-model="formData.due_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                />
                            </div>

                            <!-- Assigné à -->
                            <div v-if="users && users.length > 0">
                                <label
                                    for="task-assignee"
                                    class="block text-sm font-medium text-gray-700"
                                    >Assigné à</label
                                >
                                <select
                                    id="task-assignee"
                                    v-model="formData.assigned_to"
                                    class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                                >
                                    <option :value="null">Non assigné</option>
                                    <option
                                        v-for="user in users"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="py-3 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span
                                    v-if="isSubmitting"
                                    class="flex items-center"
                                >
                                    <svg
                                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    {{
                                        task
                                            ? "Enregistrement..."
                                            : "Création..."
                                    }}
                                </span>
                                <span v-else>{{
                                    task
                                        ? "Enregistrer les modifications"
                                        : "Créer la tâche"
                                }}</span>
                            </button>
                            <button
                                type="button"
                                @click="$emit('close')"
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
import { ref, watch } from "vue";
import { ListTodo } from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    task: {
        type: Object,
        default: null,
    },
    projectId: {
        type: Number,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "submit"]);

const isSubmitting = ref(false);
const formData = ref({
    title: "",
    description: "",
    status: "not_started",
    estimated_time: null,
    due_date: "",
    assigned_to: null,
});

// Watch for task changes to populate form
watch(
    () => props.task,
    (newTask) => {
        if (newTask) {
            formData.value = {
                title: newTask.title || "",
                description: newTask.description || "",
                status: newTask.status || "not_started",
                estimated_time: newTask.estimated_time || null,
                due_date: newTask.due_date || "",
                assigned_to: newTask.assigned_to?.id || null,
            };
        } else {
            // Reset form for new task
            formData.value = {
                title: "",
                description: "",
                status: "not_started",
                estimated_time: null,
                due_date: "",
                assigned_to: null,
            };
        }
    },
    { immediate: true }
);

const handleSubmit = () => {
    isSubmitting.value = true;
    emit("submit", {
        ...formData.value,
        project_id: props.projectId,
        id: props.task?.id,
    });
    // Reset after a delay to allow parent to handle
    setTimeout(() => {
        isSubmitting.value = false;
    }, 1000);
};
</script>
