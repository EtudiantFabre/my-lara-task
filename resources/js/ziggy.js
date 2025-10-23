const Ziggy = {
    url: "http://localhost",
    port: null,
    defaults: {},
    routes: {
        "sanctum.csrf-cookie": {
            uri: "sanctum/csrf-cookie",
            methods: ["GET", "HEAD"],
        },
        home: { uri: "/", methods: ["GET", "HEAD"] },
        dashboard: { uri: "dashboard", methods: ["GET", "HEAD"] },
        "profile.edit": { uri: "profile", methods: ["GET", "HEAD"] },
        "profile.update": { uri: "profile", methods: ["PATCH"] },
        "profile.destroy": { uri: "profile", methods: ["DELETE"] },
        "projects.index": { uri: "projects", methods: ["GET", "HEAD"] },
        "projects.create": { uri: "projects/create", methods: ["GET", "HEAD"] },
        "projects.store": { uri: "projects", methods: ["POST"] },
        "projects.show": {
            uri: "projects/{project}",
            methods: ["GET", "HEAD"],
            parameters: ["project"],
            bindings: { project: "id" },
        },
        "projects.edit": {
            uri: "projects/{project}/edit",
            methods: ["GET", "HEAD"],
            parameters: ["project"],
        },
        "projects.update": {
            uri: "projects/{project}",
            methods: ["PUT", "PATCH"],
            parameters: ["project"],
            bindings: { project: "id" },
        },
        "projects.destroy": {
            uri: "projects/{project}",
            methods: ["DELETE"],
            parameters: ["project"],
            bindings: { project: "id" },
        },
        "projects.report": {
            uri: "projects/{project}/report",
            methods: ["GET", "HEAD"],
            parameters: ["project"],
        },
        "projects.tasks.index": {
            uri: "projects/{project}/tasks",
            methods: ["GET", "HEAD"],
            parameters: ["project"],
            bindings: { project: "id" },
        },
        "projects.tasks.store": {
            uri: "projects/{project}/tasks",
            methods: ["POST"],
            parameters: ["project"],
            bindings: { project: "id" },
        },
        "projects.tasks.update": {
            uri: "projects/{project}/tasks/{task}",
            methods: ["PUT", "PATCH"],
            parameters: ["project", "task"],
            bindings: { project: "id", task: "id" },
        },
        "projects.tasks.destroy": {
            uri: "projects/{project}/tasks/{task}",
            methods: ["DELETE"],
            parameters: ["project", "task"],
            bindings: { project: "id", task: "id" },
        },
        "tasks.create": { uri: "tasks/create", methods: ["GET", "HEAD"] },
        "tasks.show": {
            uri: "tasks/{task}",
            methods: ["GET", "HEAD"],
            parameters: ["task"],
            bindings: { task: "id" },
        },
        "subtasks.store": {
            uri: "tasks/{task}/subtasks",
            methods: ["POST"],
            parameters: ["task"],
            bindings: { task: "id" },
        },
        "subtasks.update": {
            uri: "tasks/{task}/subtasks/{subTask}",
            methods: ["PATCH"],
            parameters: ["task", "subTask"],
            bindings: { task: "id", subTask: "id" },
        },
        "subtasks.destroy": {
            uri: "tasks/{task}/subtasks/{subTask}",
            methods: ["DELETE"],
            parameters: ["task", "subTask"],
            bindings: { task: "id", subTask: "id" },
        },
        "subtasks.toggle": {
            uri: "tasks/{task}/subtasks/{subTask}/toggle",
            methods: ["PATCH"],
            parameters: ["task", "subTask"],
            bindings: { task: "id", subTask: "id" },
        },
        "time-entries.index": { uri: "time-entries", methods: ["GET", "HEAD"] },
        "time-entries.create": {
            uri: "time-entries/create",
            methods: ["GET", "HEAD"],
        },
        "time-entries.store": { uri: "time-entries", methods: ["POST"] },
        "time-entries.show": {
            uri: "time-entries/{time_entry}",
            methods: ["GET", "HEAD"],
            parameters: ["time_entry"],
        },
        "time-entries.edit": {
            uri: "time-entries/{time_entry}/edit",
            methods: ["GET", "HEAD"],
            parameters: ["time_entry"],
        },
        "time-entries.update": {
            uri: "time-entries/{time_entry}",
            methods: ["PUT", "PATCH"],
            parameters: ["time_entry"],
        },
        "time-entries.destroy": {
            uri: "time-entries/{time_entry}",
            methods: ["DELETE"],
            parameters: ["time_entry"],
        },
        "notifications.index": {
            uri: "notifications",
            methods: ["GET", "HEAD"],
        },
        "notifications.read": {
            uri: "notifications/{notification}/read",
            methods: ["POST"],
            parameters: ["notification"],
            bindings: { notification: "id" },
        },
        "notifications.read-all": {
            uri: "notifications/read-all",
            methods: ["POST"],
        },
        "reports.index": { uri: "reports", methods: ["GET", "HEAD"] },
        "reports.productivity": {
            uri: "reports/productivity",
            methods: ["GET", "HEAD"],
        },
        "reports.time-entries": {
            uri: "reports/time-entries",
            methods: ["GET", "HEAD"],
        },
        "reports.export": { uri: "reports/export", methods: ["GET", "HEAD"] },
        "google-calendar.connect": {
            uri: "google-calendar/connect",
            methods: ["GET", "HEAD"],
        },
        "google-calendar.callback": {
            uri: "google-calendar/callback",
            methods: ["GET", "HEAD"],
        },
        "google-calendar.disconnect": {
            uri: "google-calendar/disconnect",
            methods: ["DELETE"],
        },
        "google-calendar.sync": {
            uri: "google-calendar/sync/{project}",
            methods: ["POST"],
            parameters: ["project"],
        },
        "webhooks.email-opened": {
            uri: "webhooks/email-opened",
            methods: ["POST"],
        },
        register: { uri: "register", methods: ["GET", "HEAD"] },
        login: { uri: "login", methods: ["GET", "HEAD"] },
        "password.request": {
            uri: "forgot-password",
            methods: ["GET", "HEAD"],
        },
        "password.email": { uri: "forgot-password", methods: ["POST"] },
        "password.reset": {
            uri: "reset-password/{token}",
            methods: ["GET", "HEAD"],
            parameters: ["token"],
        },
        "password.store": { uri: "reset-password", methods: ["POST"] },
        "verification.notice": {
            uri: "verify-email",
            methods: ["GET", "HEAD"],
        },
        "verification.verify": {
            uri: "verify-email/{id}/{hash}",
            methods: ["GET", "HEAD"],
            parameters: ["id", "hash"],
        },
        "verification.send": {
            uri: "email/verification-notification",
            methods: ["POST"],
        },
        "password.confirm": {
            uri: "confirm-password",
            methods: ["GET", "HEAD"],
        },
        "password.update": { uri: "password", methods: ["PUT"] },
        logout: { uri: "logout", methods: ["POST"] },
        "storage.local": {
            uri: "storage/{path}",
            methods: ["GET", "HEAD"],
            wheres: { path: ".*" },
            parameters: ["path"],
        },
    },
};
if (typeof window !== "undefined" && typeof window.Ziggy !== "undefined") {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
