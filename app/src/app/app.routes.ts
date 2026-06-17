import { Routes } from '@angular/router';

import { authGuard } from './guards/auth.guard';
import { LoginPage } from './pages/login-page/login-page';
import { SupportCallDetailPage } from './pages/support-call-detail-page/support-call-detail-page';
import { SupportCallsPage } from './pages/support-calls-page/support-calls-page';

export const routes: Routes = [
  {
    path: 'chamados',
    component: SupportCallsPage,
  },
  {
    path: 'chamados/:id',
    component: SupportCallDetailPage,
    canActivate: [authGuard],
  },
  {
    path: 'login',
    component: LoginPage,
  },
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'chamados',
  },
  {
    path: '**',
    redirectTo: 'chamados',
  },
];
