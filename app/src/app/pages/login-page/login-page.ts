import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { ApiErrorResponse } from '../../models/api-response';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login-page',
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './login-page.html',
  styleUrl: './login-page.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginPage {
  private readonly formBuilder = inject(FormBuilder);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly loginForm = this.formBuilder.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  submit(): void {
    this.errorMessage.set(null);

    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      this.errorMessage.set('Informe email e senha para entrar.');
      return;
    }

    const credentials = this.loginForm.getRawValue();

    this.loading.set(true);
    this.authService.login({
      email: credentials.email ?? '',
      password: credentials.password ?? '',
    }).subscribe({
      next: () => this.router.navigateByUrl('/chamados'),
      error: (error: HttpErrorResponse) => {
        this.errorMessage.set(this.resolveErrorMessage(error));
        this.loading.set(false);
      },
      complete: () => this.loading.set(false),
    });
  }

  private resolveErrorMessage(error: HttpErrorResponse): string {
    const payload = error.error as ApiErrorResponse | null;
    const firstError = payload?.errors === undefined
      ? undefined
      : Object.values(payload.errors).flat()[0];

    return firstError ?? payload?.message ?? 'Nao foi possivel realizar o login.';
  }
}
