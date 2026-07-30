import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosError } from 'axios';
import type { CurrentUser, LaravelErrorResponse } from './types';

const API_URL = import.meta.env.VITE_BACKEND_URL;

class LaravelClient {
    private axiosInstance: AxiosInstance;

    constructor(baseUrl: string) {
        this.axiosInstance = axios.create({
            baseURL: baseUrl,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            timeout: 30000, // 30 seconds timeout
            withCredentials: true,
        });

        this.setupInterceptors();
    }

    private getCookie(name: string): string | null {
        const match = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
        return match ? decodeURIComponent(match[1]) : null;
    }

    private setupInterceptors(): void {
        // Request interceptor - Add auth token to all requests
        this.axiosInstance.interceptors.request.use(
            (config) => {
                const method = (config.method ?? "GET").toUpperCase();
                const headers = new Headers(config.headers);

                if (["POST", "PUT", "PATCH", "DELETE"].includes(method)) {
                    const token = this.getCookie("XSRF-TOKEN");
                    if (token) headers.set("X-XSRF-TOKEN", token);
                }

                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        // Response interceptor - Handle errors globally
        this.axiosInstance.interceptors.response.use(
            (response) => {
                return response;
            },
            (error: AxiosError<LaravelErrorResponse>) => {
                // Handle 401 Unauthorized - Token expired or invalid
                if (error.response?.status === 401) {
                    const publicPaths = ['/'];

                    if (typeof window !== 'undefined' && !window.location.pathname.includes('/login') && !publicPaths.includes(window.location.pathname)) {
                        window.location.href = '/login';
                    }
                }

                // Extract error message from response
                const errorMessage =
                    error.response?.data?.message ||
                    error.response?.data?.error ||
                    error.message ||
                    'An error occurred';

                return Promise.reject(new Error(errorMessage));
            }
        );
    }

    async request<T>(endpoint: string, options: AxiosRequestConfig = {}): Promise<T> {
        try {
            const isFormData = typeof FormData !== 'undefined' && options.data instanceof FormData;
            const headers = isFormData
                ? { ...(options.headers || {}), 'Content-Type': undefined }
                : { 'Content-Type': 'application/json', ...(options.headers || {}) };

            const response = await this.axiosInstance.request<T>({
                url: endpoint,
                ...options,
                headers,
            });

            return response.data;
        } catch (error) {
            if (error instanceof Error) {
                throw error;
            }
            throw new Error('Network error occurred', { cause: error });
        }
    }

    async getCurrentUser(): Promise<{ user: CurrentUser }> {
        return this.request<{ user: CurrentUser }>('/auth/user');
    }

    async signUp(email: string, password: string, confirmPassword: string, name: string): Promise<{ user: CurrentUser; token: string; message: string; }> {
        const response = await this.request<{ user: CurrentUser; token: string; message: string }>('/auth/register', {
            method: 'POST',
            data: {
                name: name,
                email,
                password,
                password_confirmation: confirmPassword,
            },
        });

        return response;
    }

    async signIn(email: string, password: string): Promise<{ user: CurrentUser; message: string; }> {
        const response = await this.request<{ user: CurrentUser; message: string }>('/auth/login', {
            method: 'POST',
            data: { email, password },
        });

        return response;
    }

    async signOut(): Promise<{ message: string }> {
        return this.request<{ message: string }>('/auth/logout', { method: 'POST' });
    }

}

export const connect = new LaravelClient(API_URL);
