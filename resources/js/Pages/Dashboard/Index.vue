<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Statistiques -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    v-for="(value, key) in stats"
                    :key="key"
                    :title="formatKey(key)"
                    :value="value"
                    :icon="getIcon(key)"
                />
            </div>

            <!-- Projets récents -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Projets récents</CardTitle>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('projects.index')">Voir tout</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div v-if="recentProjects.length" class="space-y-4">
                        <ProjectItem
                            v-for="project in recentProjects"
                            :key="project.id"
                            :project="project"
                        />
                    </div>
                    <EmptyState
                        v-else
                        title="Aucun projet"
                        description="Commencez par créer votre premier projet."
                        :icon="FolderPlus"
                        action="Créer un projet"
                        @action="$inertia.visit(route('projects.create'))"
                    />
                </CardContent>
            </Card>

            <!-- Tâches à venir -->
            <Card>
                <CardHeader>
                    <CardTitle>Échéances proches</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingTasks.length" class="space-y-2">
                        <TaskItem
                            v-for="task in upcomingTasks"
                            :key="task.id"
                            :task="task"
                            :show-project="true"
                        />
                    </div>
                    <p v-else class="text-muted-foreground">
                        Aucune échéance à venir.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import ProjectItem from "@/Components/Projects/ProjectItem.vue";
import TaskItem from "@/Components/Tasks/TaskItem.vue";
import StatCard from "@/Components/Common/StatCard.vue";
import EmptyState from "@/Components/Common/EmptyState.vue";
import { FolderPlus } from 'lucide-vue-next';

interface Project {
    id: string | number;
    title: string;
    // Add other project properties here as needed
}

const props = defineProps<{
    stats: Record<string, number>;
    recentProjects: Project[];
    upcomingTasks: any[]; // Consider defining a proper type for tasks as well
}>();

const formatKey = (key: string): string => {
    return key
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const getIcon = (key: string): string => {
    const icons = {
        total_projects: "Folder",
        total_tasks: "ListTodo",
        completed_tasks: "CheckCircle",
    };
    return icons[key as keyof typeof icons] || "Circle";
};
</script>
