
import { apiService, ApiResponse } from './apiService';
import { API_CONFIG } from '@/config/api';

export interface DashboardStats {
  totalUsers: number;
  verifiedUsers: number;
  totalPages: number;
  activityPercent: number;
}

export interface ChartData {
  month?: string;
  day?: string;
  users?: number;
  created?: number;
  updated?: number;
}

export interface DashboardData {
  stats: DashboardStats;
  usersChart: ChartData[];
  activityChart: ChartData[];
  recentUsers: any[];
}

class DashboardService {
  // Получение данных для дашборда
  async getDashboardData(): Promise<ApiResponse<DashboardData>> {
    return apiService.get<DashboardData>(API_CONFIG.ENDPOINTS.DASHBOARD_STATS, {
      action: 'get_dashboard_data'
    });
  }

  // Получение статистики
  async getStats(): Promise<ApiResponse<DashboardStats>> {
    return apiService.get<DashboardStats>(API_CONFIG.ENDPOINTS.DASHBOARD_STATS, {
      action: 'get_stats'
    });
  }
}

export const dashboardService = new DashboardService();
