import { User } from './user';

export type SupportCallPriority = 'low' | 'medium' | 'high';
export type SupportCallStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
export type SortDirection = 'asc' | 'desc';

export interface SupportCall {
  id: number;
  title: string;
  description: string;
  priority: SupportCallPriority;
  status: SupportCallStatus;
  opened_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  responsible_user: User | null;
}

export interface SupportCallListParams {
  status?: SupportCallStatus;
  priority?: SupportCallPriority;
  sort_by?: 'created_at';
  sort_direction?: SortDirection;
  per_page?: number;
  page?: number;
}

export interface CreateSupportCallPayload {
  title: string;
  description: string;
  priority: SupportCallPriority;
  responsible_user_id?: number | null;
}

export interface UpdateSupportCallPayload {
  title?: string;
  description?: string;
  priority?: SupportCallPriority;
  responsible_user_id?: number | null;
}

export interface UpdateSupportCallStatusPayload {
  status: SupportCallStatus;
}
