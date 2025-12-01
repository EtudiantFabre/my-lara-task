<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <component
                            :is="project ? Pencil : FolderInput"
                            class="inline-block w-5 h-5 mr-2 text-primary-600"
                        />
                        {{ project ? "Modifier le projet" : "Nouveau Projet" }}
                    </h3>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Titre -->
                        <div>
                            <label
                                for="project-title"
                                class="block text-sm font-medium text-gray-700"
                                >Titre *</label
                            >
                            <input
                                id="project-title"
                                v-model="formData.title"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                for="project-description"
                                class="block text-sm font-medium text-gray-700"
                                >Description</label
                            >
                            <textarea
                                id="project-description"
                                v-model="formData.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Date de début -->
                            <div>
                                <label
                                    for="project-start-date"
                                    class="block text-sm font-medium text-gray-700"
                                    >Date de début *</label
                                >
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                    >
                                        <CalendarDays
                                            class="h-5 w-5 text-gray-400"
                                        />
                                    </div>
                                    <input
                                        id="project-start-date"
                                        v-model="formData.start_date"
                                        type="date"
                                        required
                                        class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Date d'échéance -->
                            <div>
                                <label
                                    for="project-deadline"
                                    class="block text-sm font-medium text-gray-700"
                                    >Date d'échéance *</label
                                >
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                    >
                                        <Calendar
                                            class="h-5 w-5 text-gray-400"
                                        />
                                    </div>
                                    <input
                                        id="project-deadline"
                                        v-model="formData.deadline"
                                        type="date"
                                        required
                                        :min="formData.start_date"
                                        class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Temps estimé -->
                            <div>
                                <label
                                    for="project-estimated-time"
                                    class="block text-sm font-medium text-gray-700"
                                    >Temps estimé (heures)</label
                                >
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                    >
                                        <Clock3 class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        id="project-estimated-time"
                                        v-model.number="formData.estimated_time"
                                        type="number"
                                        min="0"
                                        step="0.5"
                                        class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Statut -->
                            <div>
                                <label
                                    for="project-status"
                                    class="block text-sm font-medium text-gray-700"
                                    >Statut</label
                                >
                                <select
                                    id="project-status"
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
                                    <option value="cancelled">Annulé</option>
                                </select>
                            </div>
                        </div>

                        <!-- Progression (only for editing) -->
                        <div v-if="project" class="sm:col-span-2">
                            <label
                                for="project-progress"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Progression: {{ formData.progress || 0 }}%
                            </label>
                            <div class="mt-2">
                                <input
                                    id="project-progress"
                                    v-model.number="formData.progress"
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="1"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                />
                                <div
                                    class="mt-1 flex justify-between text-xs text-gray-500"
                                >
                                    <span>0%</span>
                                    <span>100%</span>
                                </div>
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
                                        project
                                            ? "Enregistrement..."
                                            : "Création..."
                                    }}
                                </span>
                                <span v-else>{{
                                    project
                                        ? "Enregistrer les modifications"
                                        : "Créer le projet"
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
import {
    FolderInput,
    Pencil,
    CalendarDays,
    Calendar,
    Clock3,
} from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    project: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "submit"]);

const isSubmitting = ref(false);
const formData = ref({
    title: "",
    description: "",
    status: "not_started",
    start_date: new Date().toISOString().split("T")[0],
    deadline: "",
    estimated_time: null,
    progress: 0,
});

// Watch for project changes to populate form
watch(
    () => props.project,
    (newProject) => {
        if (newProject) {
            formData.value = {
                title: newProject.title || "",
                description: newProject.description || "",
                status: newProject.status || "not_started",
                start_date: formatDateForInput(newProject.start_date),
                deadline: formatDateForInput(newProject.deadline),
                estimated_time: newProject.estimated_time || null,
                progress: newProject.progress || 0,
            };
        } else {
            // Reset form for new project
            formData.value = {
                title: "",
                description: "",
                status: "not_started",
                start_date: new Date().toISOString().split("T")[0],
                deadline: "",
                estimated_time: null,
                progress: 0,
            };
        }
    },
    { immediate: true }
);

const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toISOString().split("T")[0];
};

const handleSubmit = () => {
    isSubmitting.value = true;
    emit("submit", {
        ...formData.value,
        id: props.project?.id,
    });
    // Reset after a delay to allow parent to handle
    setTimeout(() => {
        isSubmitting.value = false;
    }, 1000);
};
</script>
