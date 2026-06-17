import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { ApiErrorResponse } from '../../models/api-response';
import {
  SupportCall,
  SupportCallPriority,
  SupportCallStatus,
  UpdateSupportCallPayload,
} from '../../models/support-call';
import { User } from '../../models/user';
import { SupportCallApiService } from '../../services/support-call-api.service';
import { UserApiService } from '../../services/user-api.service';

interface StatusAction {
  status: SupportCallStatus;
  label: string;
}

@Component({
  selector: 'app-support-call-detail-page',
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './support-call-detail-page.html',
  styleUrl: './support-call-detail-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SupportCallDetailPage {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly formBuilder = inject(FormBuilder);
  private readonly supportCallApiService = inject(SupportCallApiService);
  private readonly userApiService = inject(UserApiService);
  private readonly supportCallId = Number(this.route.snapshot.paramMap.get('id'));

  readonly supportCall = signal<SupportCall | null>(null);
  readonly users = signal<User[]>([]);
  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly changingStatus = signal(false);
  readonly deleting = signal(false);
  readonly editing = signal(false);
  readonly feedbackMessage = signal<string | null>(null);
  readonly errorMessage = signal<string | null>(null);

  readonly editForm = this.formBuilder.group({
    title: ['', [Validators.required, Validators.maxLength(255)]],
    description: ['', [Validators.required]],
    priority: ['medium' as SupportCallPriority, [Validators.required]],
    responsible_user_id: [''],
  });

  constructor() {
    this.loadUsers();
    this.loadSupportCall();
  }

  startEditing(): void {
    const supportCall = this.supportCall();

    if (supportCall === null) {
      return;
    }

    this.editing.set(true);
    this.editForm.reset({
      title: supportCall.title,
      description: supportCall.description,
      priority: supportCall.priority,
      responsible_user_id: supportCall.responsible_user?.id?.toString() ?? '',
    });
  }

  cancelEditing(): void {
    this.editing.set(false);
    this.errorMessage.set(null);
  }

  saveChanges(): void {
    this.feedbackMessage.set(null);
    this.errorMessage.set(null);

    if (this.editForm.invalid) {
      this.editForm.markAllAsTouched();
      this.errorMessage.set('Confira os campos obrigatorios antes de salvar.');
      return;
    }

    const formValue = this.editForm.getRawValue();
    const responsibleUserId = formValue.responsible_user_id;
    const payload: UpdateSupportCallPayload = {
      title: formValue.title ?? '',
      description: formValue.description ?? '',
      priority: formValue.priority ?? 'medium',
      responsible_user_id: responsibleUserId === '' || responsibleUserId === null
        ? null
        : Number(responsibleUserId),
    };

    this.saving.set(true);
    this.supportCallApiService.update(this.supportCallId, payload).subscribe({
      next: (response) => {
        this.supportCall.set(response.data);
        this.feedbackMessage.set(response.message ?? 'Chamado atualizado com sucesso.');
        this.editing.set(false);
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel atualizar o chamado.'));
        this.saving.set(false);
      },
      complete: () => this.saving.set(false),
    });
  }

  advanceStatus(status: SupportCallStatus): void {
    this.feedbackMessage.set(null);
    this.errorMessage.set(null);
    this.changingStatus.set(true);

    this.supportCallApiService.updateStatus(this.supportCallId, { status }).subscribe({
      next: (response) => {
        this.supportCall.set(response.data);
        this.feedbackMessage.set(response.message ?? 'Status atualizado com sucesso.');
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel atualizar o status.'));
        this.changingStatus.set(false);
      },
      complete: () => this.changingStatus.set(false),
    });
  }

  deleteSupportCall(): void {
    const supportCall = this.supportCall();

    if (supportCall === null || !confirm(`Excluir o chamado "${supportCall.title}"?`)) {
      return;
    }

    this.deleting.set(true);
    this.supportCallApiService.delete(this.supportCallId).subscribe({
      next: () => this.router.navigateByUrl('/chamados'),
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel excluir o chamado.'));
        this.deleting.set(false);
      },
      complete: () => this.deleting.set(false),
    });
  }

  progressOptions(status: SupportCallStatus): StatusAction[] {
    const actions: Record<SupportCallStatus, StatusAction[]> = {
      open: [
        { status: 'in_progress', label: 'Iniciar atendimento' },
        { status: 'closed', label: 'Fechar chamado' },
      ],
      in_progress: [
        { status: 'resolved', label: 'Marcar como resolvido' },
        { status: 'closed', label: 'Fechar chamado' },
      ],
      resolved: [],
      closed: [],
    };

    return actions[status];
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

  private loadSupportCall(): void {
    this.loading.set(true);
    this.supportCallApiService.show(this.supportCallId).subscribe({
      next: (response) => this.supportCall.set(response.data),
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error, 'Nao foi possivel carregar o chamado.'));
        this.loading.set(false);
      },
      complete: () => this.loading.set(false),
    });
  }

  private loadUsers(): void {
    this.userApiService.list().subscribe({
      next: (response) => this.users.set(response.data),
      error: () => this.users.set([]),
    });
  }

  private resolveErrorMessage(error: HttpErrorResponse, fallback: string): string {
    const payload = error.error as ApiErrorResponse | null;
    const firstError = payload?.errors === undefined
      ? undefined
      : Object.values(payload.errors).flat()[0];

    return firstError ?? payload?.message ?? fallback;
  }
}
