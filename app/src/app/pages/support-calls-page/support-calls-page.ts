import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';

import { ApiErrorResponse, PaginatedResponse } from '../../models/api-response';
import {
  CreateSupportCallPayload,
  SortDirection,
  SupportCall,
  SupportCallPriority,
  SupportCallStatus,
} from '../../models/support-call';
import { User } from '../../models/user';
import { AuthService } from '../../services/auth.service';
import { SupportCallApiService } from '../../services/support-call-api.service';
import { UserApiService } from '../../services/user-api.service';

@Component({
  selector: 'app-support-calls-page',
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './support-calls-page.html',
  styleUrl: './support-calls-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SupportCallsPage {
  private readonly formBuilder = inject(FormBuilder);
  private readonly supportCallApiService = inject(SupportCallApiService);
  private readonly userApiService = inject(UserApiService);
  private readonly authService = inject(AuthService);

  readonly isAuthenticated = this.authService.isAuthenticated;
  readonly supportCalls = signal<SupportCall[]>([]);
  readonly users = signal<User[]>([]);
  readonly loading = signal(false);
  readonly creating = signal(false);
  readonly feedbackMessage = signal<string | null>(null);
  readonly errorMessage = signal<string | null>(null);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly perPage = signal(10);
  readonly sortDirection = signal<SortDirection>('desc');
  readonly visibleRange = computed(() => {
    const total = this.total();

    if (total === 0) {
      return '0 de 0';
    }

    const start = (this.currentPage() - 1) * this.perPage() + 1;
    const end = Math.min(start + this.supportCalls().length - 1, total);

    return `${start}-${end} de ${total}`;
  });

  readonly createForm = this.formBuilder.group({
    title: ['', [Validators.required, Validators.maxLength(255)]],
    description: ['', [Validators.required]],
    priority: ['medium' as SupportCallPriority, [Validators.required]],
    responsible_user_id: [''],
  });

  readonly filtersForm = this.formBuilder.group({
    status: [''],
    priority: [''],
  });

  constructor() {
    this.loadUsers();
    this.loadSupportCalls();
  }

  submitCreateForm(): void {
    this.feedbackMessage.set(null);
    this.errorMessage.set(null);

    if (this.createForm.invalid) {
      this.createForm.markAllAsTouched();
      this.errorMessage.set('Confira os campos obrigatorios antes de cadastrar o chamado.');
      return;
    }

    const formValue = this.createForm.getRawValue();
    const responsibleUserId = formValue.responsible_user_id;
    const payload: CreateSupportCallPayload = {
      title: formValue.title ?? '',
      description: formValue.description ?? '',
      priority: formValue.priority ?? 'medium',
      responsible_user_id: responsibleUserId === '' || responsibleUserId === null
        ? null
        : Number(responsibleUserId),
    };

    this.creating.set(true);
    this.supportCallApiService.create(payload).subscribe({
      next: (response) => {
        this.feedbackMessage.set(response.message ?? 'Chamado cadastrado com sucesso.');
        this.createForm.reset({
          title: '',
          description: '',
          priority: 'medium',
          responsible_user_id: '',
        });
        this.currentPage.set(1);
        this.loadSupportCalls();
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel cadastrar o chamado.'));
        this.creating.set(false);
      },
      complete: () => this.creating.set(false),
    });
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.loadSupportCalls();
  }

  clearFilters(): void {
    this.filtersForm.reset({
      status: '',
      priority: '',
    });
    this.currentPage.set(1);
    this.loadSupportCalls();
  }

  toggleSortDirection(): void {
    this.sortDirection.update((direction) => direction === 'asc' ? 'desc' : 'asc');
    this.currentPage.set(1);
    this.loadSupportCalls();
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage() || page === this.currentPage()) {
      return;
    }

    this.currentPage.set(page);
    this.loadSupportCalls();
  }

  loadSupportCalls(): void {
    const filters = this.filtersForm.getRawValue();

    this.loading.set(true);
    this.errorMessage.set(null);
    this.supportCallApiService.list({
      status: this.emptyToUndefined(filters.status) as SupportCallStatus | undefined,
      priority: this.emptyToUndefined(filters.priority) as SupportCallPriority | undefined,
      sort_by: 'created_at',
      sort_direction: this.sortDirection(),
      per_page: this.perPage(),
      page: this.currentPage(),
    }).subscribe({
      next: (response) => this.applyPaginationResponse(response),
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel carregar os chamados.'));
        this.loading.set(false);
      },
      complete: () => this.loading.set(false),
    });
  }

  priorityLabel(priority: SupportCallPriority): string {
    const labels: Record<SupportCallPriority, string> = {
      low: 'Baixa',
      medium: 'Media',
      high: 'Alta',
    };

    return labels[priority];
  }

  statusLabel(status: SupportCallStatus): string {
    const labels: Record<SupportCallStatus, string> = {
      open: 'Aberto',
      in_progress: 'Em andamento',
      resolved: 'Resolvido',
      closed: 'Fechado',
    };

    return labels[status];
  }

  formatDate(value: string | null): string {
    if (value === null) {
      return 'Nao informado';
    }

    return new Intl.DateTimeFormat('pt-BR', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(new Date(value));
  }

  private loadUsers(): void {
    this.userApiService.list().subscribe({
      next: (response) => this.users.set(response.data),
      error: () => this.users.set([]),
    });
  }

  private applyPaginationResponse(response: PaginatedResponse<SupportCall>): void {
    this.supportCalls.set(response.data);
    this.currentPage.set(response.current_page);
    this.lastPage.set(response.last_page);
    this.total.set(response.total);
    this.perPage.set(response.per_page);
  }

  private emptyToUndefined(value: string | null | undefined): string | undefined {
    return value === undefined || value === null || value === '' ? undefined : value;
  }

  private resolveErrorMessage(error: HttpErrorResponse, fallback: string): string {
    const payload = error.error as ApiErrorResponse | null;
    const firstError = payload?.errors === undefined
      ? undefined
      : Object.values(payload.errors).flat()[0];

    return firstError ?? payload?.message ?? fallback;
  }
}
