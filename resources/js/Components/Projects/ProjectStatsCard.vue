<template>
    <div :class="borderColor" class="p-5 bg-white rounded-lg shadow">
        <div class="flex items-center">
            <div
                :class="bgColor"
                class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full"
            >
                <component :is="icon" class="w-6 h-6 text-white" />
            </div>
            <div class="ml-5">
                <p class="text-sm font-medium text-gray-500 truncate">
                    {{ label }}
                </p>
                <p class="text-2xl font-semibold text-gray-900">{{ value }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: Number,
        required: true,
    },
    icon: {
        type: Object,
        required: true,
    },
    bgColor: {
        type: String,
        required: true,
    },
    borderColor: {
        type: String,
        required: true,
    },
});
</script>
