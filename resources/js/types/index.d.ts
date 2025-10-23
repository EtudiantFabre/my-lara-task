export interface Project {
    id: number;
    title: string;
    // Add other project properties as needed
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    projects?: Project[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
