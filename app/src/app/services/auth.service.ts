import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';

import { environment } from '../../environments/environment.development';
import { ApiResponse } from '../models/api-response';
import { AuthPayload, LoginPayload } from '../models/auth';
import { User } from '../models/user';

const TOKEN_STORAGE_KEY = 'helpdesk_auth_token';
const USER_STORAGE_KEY = 'helpdesk_auth_user';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiBaseUrl = environment.apiBaseUrl;
  private readonly tokenSignal = signal<string | null>(this.readToken());
  private readonly userSignal = signal<User | null>(this.readUser());

  readonly token = this.tokenSignal.asReadonly();
  readonly user = this.userSignal.asReadonly();
  readonly isAuthenticated = computed(() => this.tokenSignal() !== null);

  login(payload: LoginPayload): Observable<ApiResponse<AuthPayload>> {
    return this.http.post<ApiResponse<AuthPayload>>(`${this.apiBaseUrl}/login`, payload).pipe(
      tap((response) => this.storeSession(response.data)),
    );
  }

  logout(): Observable<ApiResponse<never>> {
    return this.http.post<ApiResponse<never>>(`${this.apiBaseUrl}/logout`, {}).pipe(
      tap(() => this.clearSession()),
    );
  }

  clearSession(): void {
    this.tokenSignal.set(null);
    this.userSignal.set(null);
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    localStorage.removeItem(USER_STORAGE_KEY);
  }

  private storeSession(payload: AuthPayload): void {
    this.tokenSignal.set(payload.token);
    this.userSignal.set(payload.user);
    localStorage.setItem(TOKEN_STORAGE_KEY, payload.token);
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(payload.user));
  }

  private readToken(): string | null {
    return localStorage.getItem(TOKEN_STORAGE_KEY);
  }

  private readUser(): User | null {
    const storedUser = localStorage.getItem(USER_STORAGE_KEY);

    if (storedUser === null) {
      return null;
    }

    try {
      return JSON.parse(storedUser) as User;
    } catch {
      localStorage.removeItem(USER_STORAGE_KEY);
      return null;
    }
  }
}
