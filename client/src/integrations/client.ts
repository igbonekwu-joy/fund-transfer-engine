import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosError, InternalAxiosRequestConfig } from 'axios';
import type { CurrentUser, LaravelErrorResponse } from './types';

const API_URL = import.meta.env.VITE_BACKEND_URL;

interface RetryableRequestConfig extends InternalAxiosRequestConfig {
    _retry?: boolean;
}

interface QueuedRequest {
    resolve: (value?: unknown) => void;
    reject: (reason?: unknown) => void;
}

// Endpoints that should never trigger a refresh attempt on 401 —
// login/register 401s mean bad credentials, and refresh 401s mean
// the refresh token itself is dead (retrying would loop forever).
const REFRESH_EXEMPT_PATHS = ['/auth/login', '/auth/register', '/auth/refresh'];

class LaravelClient {
    private axiosInstance: AxiosInstance;
    private isRefreshing = false;
    private refreshQueue: QueuedRequest[] = [];

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

    private isRefreshExempt(url?: string): boolean {
        if (!url) return false;
        return REFRESH_EXEMPT_PATHS.some((path) => url.includes(path));
    }

    private processQueue(error: unknown): void {
        this.refreshQueue.forEach(({ resolve, reject }) => {
            if (error) {
                reject(error);
            } else {
                resolve();
            }
        });
        this.refreshQueue = [];
    }

    private setupInterceptors(): void {
        // Request interceptor - Add CSRF token to mutating requests
        this.axiosInstance.interceptors.request.use(
            (config) => {
                const method = (config.method ?? "GET").toUpperCase();

                if (["POST", "PUT", "PATCH", "DELETE"].includes(method)) {
                    const token = this.getCookie("XSRF-TOKEN");
                    if (token) {
                        config.headers = config.headers ?? {};
                        config.headers["X-XSRF-TOKEN"] = token;
                    }
                }

                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        // Response interceptor - Handle errors globally, refresh on 401
        this.axiosInstance.interceptors.response.use(
            (response) => {
                return response;
            },
            async (error: AxiosError<LaravelErrorResponse>) => {
                const originalRequest = error.config as RetryableRequestConfig | undefined;

                const shouldAttemptRefresh =
                    error.response?.status === 401 &&
                    originalRequest &&
                    !originalRequest._retry &&
                    !this.isRefreshExempt(originalRequest.url);

                if (shouldAttemptRefresh && originalRequest) {
                    if (this.isRefreshing) {
                        // A refresh is already in flight — queue this request
                        // and retry it once that refresh settles.
                        return new Promise((resolve, reject) => {
                            this.refreshQueue.push({ resolve, reject });
                        })
                            .then(() => this.axiosInstance(originalRequest))
                            .catch((queueError) => Promise.reject(queueError));
                    }

                    originalRequest._retry = true;
                    this.isRefreshing = true;

                    try {
                        await this.axiosInstance.post('/auth/refresh');
                        this.processQueue(null);
                        return this.axiosInstance(originalRequest);
                    } catch (refreshError) {
                        this.processQueue(refreshError);
                        this.redirectToLogin();
                        return Promise.reject(refreshError);
                    } finally {
                        this.isRefreshing = false;
                    }
                }

                if (error.response?.status === 401) {
                    this.redirectToLogin();
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

    private redirectToLogin(): void {
        const publicPaths = ['/'];

        if (
            typeof window !== 'undefined' &&
            !window.location.pathname.includes('/login') &&
            !publicPaths.includes(window.location.pathname)
        ) {
            window.location.href = '/login';
        }
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
