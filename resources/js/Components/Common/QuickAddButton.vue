<!-- resources/js/Components/Common/QuickAddButton.vue -->
<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="default" size="icon" class="rounded-full h-10 w-10">
        <Plus class="h-4 w-4" />
        <span class="sr-only">Ajouter</span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuItem @click="$emit('addProject')">
        <FolderPlus class="mr-2 h-4 w-4" />
        <span>Nouveau projet</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup>
import { Plus, FolderPlus, ListPlus } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,

  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';


defineEmits(['addProject',]);
</script>