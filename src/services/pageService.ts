
import { apiService, ApiResponse } from './apiService';
import { API_CONFIG } from '@/config/api';

export interface Page {
  id: number;
  user_id: number;
  title: string;
  content: string;
  createdAt: string;
  updatedAt: string;
  userEmail: string;
}

export interface PageFilters {
  search?: string;
  user_id?: number;
  page?: number;
  limit?: number;
}

export interface PagesResponse {
  pages: Page[];
  total: number;
  page: number;
  limit: number;
}

class PageService {
  // Получение списка страниц
  async getPages(filters?: PageFilters): Promise<ApiResponse<PagesResponse>> {
    const params: Record<string, string> = {
      action: 'get_pages'
    };

    if (filters) {
      if (filters.search) params.search = filters.search;
      if (filters.user_id) params.user_id = filters.user_id.toString();
      if (filters.page) params.page = filters.page.toString();
      if (filters.limit) params.limit = filters.limit.toString();
    }

    return apiService.get<PagesResponse>(API_CONFIG.ENDPOINTS.PAGES, params);
  }

  // Получение страницы по ID
  async getPageById(id: number): Promise<ApiResponse<Page>> {
    return apiService.get<Page>(API_CONFIG.ENDPOINTS.PAGES, {
      action: 'get_page',
      id: id.toString()
    });
  }

  // Создание страницы
  async createPage(pageData: Omit<Page, 'id' | 'createdAt' | 'updatedAt' | 'userEmail'>): Promise<ApiResponse<Page>> {
    return apiService.post<Page>(API_CONFIG.ENDPOINTS.PAGES, {
      action: 'create_page',
      ...pageData
    });
  }

  // Обновление страницы
  async updatePage(id: number, pageData: Partial<Page>): Promise<ApiResponse<Page>> {
    return apiService.put<Page>(API_CONFIG.ENDPOINTS.PAGES, {
      action: 'update_page',
      id,
      ...pageData
    });
  }

  // Удаление страницы
  async deletePage(id: number): Promise<ApiResponse> {
    return apiService.delete(API_CONFIG.ENDPOINTS.PAGES + `?action=delete_page&id=${id}`);
  }
}

export const pageService = new PageService();
