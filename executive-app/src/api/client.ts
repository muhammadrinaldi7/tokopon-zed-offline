import axios from 'axios';

const apiBase = import.meta.env.VITE_API_BASE_URL 
  || (import.meta.env.PROD && import.meta.env.VITE_API_URL 
      ? `${import.meta.env.VITE_API_URL.replace(/\/$/, '')}/api/v1/executive` 
      : '/api/v1/executive');

export const apiClient = axios.create({
  baseURL: apiBase,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to attach Bearer token
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('executive_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => {
  return Promise.reject(error);
});

// Response interceptor to handle 401 Unauthorized / token expiration
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Clear token and fire logout event
      localStorage.removeItem('executive_token');
      localStorage.removeItem('executive_user');
      window.dispatchEvent(new Event('executive:unauthorized'));
    }
    return Promise.reject(error);
  }
);
