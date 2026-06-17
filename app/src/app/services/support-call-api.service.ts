import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { environment } from '../../environments/environment.development';
import { ApiResponse, PaginatedResponse } from '../models/api-response';
import {
  CreateSupportCallPayload,
  SupportCall,
  SupportCallListParams,
  UpdateSupportCallPayload,
  UpdateSupportCallStatusPayload,
} from '../models/support-call';

@Injectable({
  providedIn: 'root',
})
export class SupportCallApiService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${environment.apiBaseUrl}/support-calls`;

  list(params: SupportCallListParams): Observable<PaginatedResponse<SupportCall>> {
    return this.http.get<PaginatedResponse<SupportCall>>(this.endpoint, {
      params: this.buildListParams(params),
    });
  }

  create(payload: CreateSupportCallPayload): Observable<ApiResponse<SupportCall>> {
    return this.http.post<ApiResponse<SupportCall>>(this.endpoint, payload);
  }

  show(id: number): Observable<ApiResponse<SupportCall>> {
    return this.http.get<ApiResponse<SupportCall>>(`${this.endpoint}/${id}`);
  }

  update(id: number, payload: UpdateSupportCallPayload): Observable<ApiResponse<SupportCall>> {
    return this.http.put<ApiResponse<SupportCall>>(`${this.endpoint}/${id}`, payload);
  }

  updateStatus(id: number, payload: UpdateSupportCallStatusPayload): Observable<ApiResponse<SupportCall>> {
    return this.http.patch<ApiResponse<SupportCall>>(`${this.endpoint}/${id}`, payload);
  }

  delete(id: number): Observable<ApiResponse<never>> {
    return this.http.delete<ApiResponse<never>>(`${this.endpoint}/${id}`);
  }

  private buildListParams(params: SupportCallListParams): HttpParams {
    let httpParams = new HttpParams()
      .set('sort_by', params.sort_by ?? 'created_at')
      .set('sort_direction', params.sort_direction ?? 'desc')
      .set('per_page', String(params.per_page ?? 10))
      .set('page', String(params.page ?? 1));

    if (params.status !== undefined) {
      httpParams = httpParams.set('status', params.status);
    }

    if (params.priority !== undefined) {
      httpParams = httpParams.set('priority', params.priority);
    }

    return httpParams;
  }
}
