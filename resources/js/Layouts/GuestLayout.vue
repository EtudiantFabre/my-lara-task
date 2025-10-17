<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Toaster } from '@/Components/ui/toast';
import { useToast } from '@/Components/ui/toast/use-toast';
import { watch } from 'vue';

const { toast } = useToast();
const page = usePage();

watch(
  () => page.props.flash as any,
  (flash: any) => {
    if (!flash) return;
    if (flash.success) {
      toast({ title: 'Succès', description: String(flash.success), variant: 'success' });
    }
    if (flash.error) {
      toast({ title: 'Erreur', description: String(flash.error), variant: 'destructive' });
    }
  },
  { immediate: true, deep: true }
);
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    <Toaster />
    </div>
</template>
