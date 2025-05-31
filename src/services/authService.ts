
import { apiService, ApiResponse } from './apiService';
import { API_CONFIG, setAuthToken, removeAuthToken } from '@/config/api';

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface AuthUser {
  id: number;
  username: string;
  email?: string;
  role: string;
}

export interface LoginResponse {
  user: AuthUser;
  token: string;
}

class AuthService {
  // Вход в систему
  async login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> {
    const response = await apiService.post<LoginResponse>(
      API_CONFIG.ENDPOINTS.LOGIN,
      {
        action: 'login',
        ...credentials
      }
    );

    if (response.success && response.data?.token) {
      setAuthToken(response.data.token);
    }

    return response;
  }

  // Выход из системы
  async logout(): Promise<ApiResponse> {
    const response = await apiService.post(API_CONFIG.ENDPOINTS.LOGOUT, {
      action: 'logout'
    });

    // Удаляем токен независимо от ответа сервера
    removeAuthToken();

    return response;
  }

  // Проверка токена
  async verifyToken(): Promise<ApiResponse<AuthUser>> {
    return apiService.post<AuthUser>(API_CONFIG.ENDPOINTS.VERIFY_TOKEN, {
      action: 'verify_token'
    });
  }
}

export const authService = new AuthService();
