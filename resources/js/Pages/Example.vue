<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout header="Tableau de bord">
    <div class="space-y-6">
      <div class="rounded-lg border bg-card p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Bienvenue sur votre tableau de bord</h2>
        <p class="mt-2 text-muted-foreground">
          Commencez par explorer les différentes fonctionnalités de l'application.
        </p>
        <div class="mt-4 flex gap-2">
          <Button>Créer une tâche</Button>
          <Button variant="outline">Voir les statistiques</Button>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-lg border bg-card p-6">
          <h3 class="font-medium">Tâches récentes</h3>
          <p class="mt-2 text-sm text-muted-foreground">
            Aucune tâche récente pour le moment.
          </p>
        </div>
        <div class="rounded-lg border bg-card p-6">
          <h3 class="font-medium">Activité récente</h3>
          <p class="mt-2 text-sm text-muted-foreground">
            Aucune activité récente.
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>