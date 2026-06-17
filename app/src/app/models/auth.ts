import { User } from './user';

export interface LoginPayload {
  email: string;
  password: string;
}

export interface AuthPayload {
  token: string;
  user: User;
}
