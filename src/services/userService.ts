
import { apiService, ApiResponse } from './apiService';
import { API_CONFIG } from '@/config/api';

export interface User {
  id: number;
  email: string;
  status: 'verified' | 'pending';
  createdAt: string;
  pagesCount: number;
  lastActive: string;
}

export interface UserFilters {
  search?: string;
  status?: string;
  page?: number;
  limit?: number;
}

export interface UsersResponse {
  users: User[];
  total: number;
  page: number;
  limit: number;
}

class UserService {
  // Получение списка пользователей
  async getUsers(filters?: UserFilters): Promise<ApiResponse<UsersResponse>> {
    const params: Record<string, string> = {
      action: 'get_users'
    };

    if (filters) {
      if (filters.search) params.search = filters.search;
      if (filters.status) params.status = filters.status;
      if (filters.page) params.page = filters.page.toString();
      if (filters.limit) params.limit = filters.limit.toString();
    }

    return apiService.get<UsersResponse>(API_CONFIG.ENDPOINTS.USERS, params);
  }

  // Получение пользователя по ID
  async getUserById(id: number): Promise<ApiResponse<User>> {
    return apiService.get<User>(API_CONFIG.ENDPOINTS.USER_BY_ID, {
      action: 'get_user',
      id: id.toString()
    });
  }

  // Создание пользователя
  async createUser(userData: Omit<User, 'id' | 'createdAt' | 'lastActive'>): Promise<ApiResponse<User>> {
    return apiService.post<User>(API_CONFIG.ENDPOINTS.USERS, {
      action: 'create_user',
      ...userData
    });
  }

  // Обновление пользователя
  async updateUser(id: number, userData: Partial<User>): Promise<ApiResponse<User>> {
    return apiService.put<User>(API_CONFIG.ENDPOINTS.USERS, {
      action: 'update_user',
      id,
      ...userData
    });
  }

  // Удаление пользователя
  async deleteUser(id: number): Promise<ApiResponse> {
    return apiService.delete(API_CONFIG.ENDPOINTS.USERS + `?action=delete_user&id=${id}`);
  }
}

export const userService = new UserService();
