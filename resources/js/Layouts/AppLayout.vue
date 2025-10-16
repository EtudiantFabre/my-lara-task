<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
  LayoutDashboard,
  ListTodo,
  Users,
  Settings,
  LogOut,
  Menu,
  X
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import QuickAddButton from '@/Components/Common/QuickAddButton.vue';


defineProps({
  header: {
    type: String,
    default: null,
  },
});

const isOpen = ref(false);

const navigation = [
  { name: 'Tableau de bord', href: route('dashboard'), icon: LayoutDashboard },
  { name: 'Profil', href: route('profile.edit'), icon: Settings },
];

const activeClass = 'bg-primary/10 text-primary';
const showNewProjectDialog = ref(false);
const showNewTaskDialog = ref(false);
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Mobile sidebar -->
    <div class="lg:hidden">
      <div class="flex items-center justify-between border-b p-4">
        <Link :href="route('dashboard')" class="text-xl font-bold">
          Mon Application
        </Link>
        <Button variant="ghost" size="icon" @click="isOpen = !isOpen">
          <Menu v-if="!isOpen" class="h-5 w-5" />
          <X v-else class="h-5 w-5" />
        </Button>
      </div>

      <div v-show="isOpen" class="border-b p-4">
        <nav class="space-y-2">
          <Link
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            :class="[
              'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium',
              route().current(item.href) ? activeClass : 'hover:bg-accent'
            ]"
          >
            <component :is="item.icon" class="h-5 w-5" />
            {{ item.name }}
          </Link>
        </nav>
      </div>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col">
      <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r bg-background px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
          <Link :href="route('dashboard')" class="text-xl font-bold">
            Mon Application
          </Link>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-1">
                <li v-for="item in navigation" :key="item.name">
                  <Link
                    :href="item.href"
                    :class="[
                      'group flex gap-x-3 rounded-md p-2 text-sm font-medium leading-6',
                      route().current(item.href) ? activeClass : 'hover:bg-accent'
                    ]"
                  >
                    <component
                      :is="item.icon"
                      :class="[
                        'h-6 w-6 shrink-0',
                        route().current(item.href) ? 'text-primary' : 'text-muted-foreground'
                      ]"
                    />
                    {{ item.name }}
                  </Link>
                </li>
              </ul>
            </li>
            <li class="mt-auto">
              <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="group -mx-2 flex w-full items-center gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-destructive hover:bg-destructive/10"
              >
                <LogOut class="h-5 w-5 shrink-0" />
                Déconnexion
              </Link>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Main content -->
    <div class="lg:pl-64">
      <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
          <h1 v-if="header" class="mb-8 text-2xl font-bold">
            {{ header }}
          </h1>
          <slot />
          <!-- Floating action button -->
        <div class="fixed bottom-6 right-6 z-50">
            <QuickAddButton 
            @add-project="showNewProjectDialog = true"
            @add-task="showNewTaskDialog = true"
            />
        </div>
        </div>
      </main>
    </div>
  </div>
</template>
