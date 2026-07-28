import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosError } from 'axios';

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
            withCredentials: false, // Don't send cookies by default
        });

        this.setupInterceptors();
    }

    private setupInterceptors(): void {
        // Request interceptor - Add auth token to all requests
        this.axiosInstance.interceptors.request.use(
            (config) => {
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
            (error: AxiosError) => {
                // Handle 401 Unauthorized - Token expired or invalid
                if (error.response?.status === 401) {
                    const publicPaths = ['/'];

                    if (typeof window !== 'undefined' && !window.location.pathname.includes('/login') && !publicPaths.includes(window.location.pathname)) {
                        window.location.href = '/login';
                    }
                }

                // Extract error message from response
                const errorMessage =
                    (error.response?.data as any)?.message ||
                    (error.response?.data as any)?.error ||
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
            throw new Error('Network error occurred');
        }
    }

    async signUp(email: string, password: string, confirmPassword: string, name: string): Promise<{ user: string[]; token: string; message: string; }> {
        const response = await this.request<{ user: string[]; token: string; message: string }>('/auth/register', {
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

}

export const connect = new LaravelClient(API_URL);
