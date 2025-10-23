export interface Project {
  id: string;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  status: 'not_started' | 'in_progress' | 'completed' | 'on_hold' | 'cancelled';
  progress: number;
  employee_id: string;
  manager_id: string;
  created_at: string;
  updated_at: string;
  tasks?: Task[];
}

export interface Task {
  id: string;
  project_id: string;
  title: string;
  description: string;
  estimated_hours: number;
  spent_hours: number;
  status: 'todo' | 'in_progress' | 'in_review' | 'done';
  priority: 'low' | 'medium' | 'high';
  due_date: string;
  created_at: string;
  updated_at: string;
  subtasks?: Subtask[];
}

export interface Subtask {
  id: string;
  task_id: string;
  title: string;
  description: string;
  is_completed: boolean;
  created_at: string;
  updated_at: string;
}
