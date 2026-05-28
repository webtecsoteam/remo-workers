// src/services/api.js
const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiService {
  constructor() {
    this.baseUrl = BASE_URL;
  }

  getToken() {
    return localStorage.getItem('admin_token');
  }

  async request(method, endpoint, data = null, params = null) {
    const url = new URL(`${this.baseUrl}${endpoint}`);
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') url.searchParams.append(k, v);
      });
    }

    const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
    const token = this.getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const config = { method, headers };
    if (data) config.body = JSON.stringify(data);

    const res = await fetch(url.toString(), config);

    if (res.status === 401) {
      localStorage.removeItem('admin_token');
      window.location.href = '/login';
      return;
    }

    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Request failed');
    return json;
  }

  get(endpoint, params) { return this.request('GET', endpoint, null, params); }
  post(endpoint, data) { return this.request('POST', endpoint, data); }
  put(endpoint, data) { return this.request('PUT', endpoint, data); }
  patch(endpoint, data) { return this.request('PATCH', endpoint, data); }
  delete(endpoint) { return this.request('DELETE', endpoint); }
}

const api = new ApiService();

// Auth
export const login = (email, password) => api.post('/admin/login', { email, password });
export const logout = () => api.post('/admin/logout');
export const getMe = () => api.get('/admin/me');

// Dashboard
export const getDashboardStats = () => api.get('/admin/dashboard/stats');
export const getRevenueChart = (period = 30) => api.get('/admin/dashboard/revenue-chart', { period });
export const getRecentActivity = () => api.get('/admin/dashboard/recent-activity');

// Users
export const getUsers = (params) => api.get('/admin/users', params);
export const getUser = (id) => api.get(`/admin/users/${id}`);
export const updateUser = (id, data) => api.put(`/admin/users/${id}`, data);
export const deleteUser = (id) => api.delete(`/admin/users/${id}`);
export const suspendUser = (id) => api.patch(`/admin/users/${id}/suspend`);
export const activateUser = (id) => api.patch(`/admin/users/${id}/activate`);
export const verifyUser = (id) => api.patch(`/admin/users/${id}/verify`);
export const getUserJobs = (id, params) => api.get(`/admin/users/${id}/jobs`, params);
export const getUserPayments = (id, params) => api.get(`/admin/users/${id}/payments`, params);

// Jobs
export const getJobs = (params) => api.get('/admin/jobs', params);
export const getJob = (id) => api.get(`/admin/jobs/${id}`);
export const approveJob = (id) => api.patch(`/admin/jobs/${id}/approve`);
export const rejectJob = (id) => api.patch(`/admin/jobs/${id}/reject`);
export const closeJob = (id) => api.patch(`/admin/jobs/${id}/close`);
export const flagJob = (id) => api.patch(`/admin/jobs/${id}/flag`);
export const deleteJob = (id) => api.delete(`/admin/jobs/${id}`);

// Payments
export const getPayments = (params) => api.get('/admin/payments', params);
export const getPayment = (id) => api.get(`/admin/payments/${id}`);
export const refundPayment = (id, reason) => api.patch(`/admin/payments/${id}/refund`, { reason });
export const resolveDispute = (id, data) => api.patch(`/admin/payments/${id}/dispute`, data);
export const getPaymentSummary = () => api.get('/admin/payments/summary');

// Reports
export const getReportOverview = (period) => api.get('/admin/reports/overview', { period });
export const getUserReport = (period) => api.get('/admin/reports/users', { period });
export const getJobReport = (period) => api.get('/admin/reports/jobs', { period });
export const getRevenueReport = (period) => api.get('/admin/reports/revenue', { period });
export const exportReport = (params) => api.get('/admin/reports/export', params);

export default api;
