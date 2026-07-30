import type { User } from "lucide-react";

export type TxnIcon = 'coffee' | 'salary' | 'shop' | 'transfer' | 'subs' | 'ride';

export interface Transaction {
  id: string;
  name: string;
  meta: string;
  amount: number;
  type: 'in' | 'out';
  icon: TxnIcon;
}

export interface BudgetGoal {
  id: string;
  name: string;
  saved: number;
  target: number;
  variant?: 'jade' | 'amber';
}

export interface Bill {
  id: string;
  name: string;
  day: string;
  month: string;
  amount: number;
  method: string;
}

export interface SpendPoint {
  day: string;
  value: number;
}

export interface NavItem {
  id: string;
  label: string;
  icon: string;
  badge?: string;
  action?: () => void;
}

export interface FormState {
  name: string;
  email: string;
  password: string;
  agree: boolean;
}

export interface LoginFormState {
    email: string;
    password: string;
    remember: boolean;
}

export interface FormErrors {
  name?: string;
  email?: string;
  password?: string;
  agree?: string;
}

export interface LaravelErrorResponse {
  message?: string;
  error?: string;
  errors?: Record<string, string[]>;
}

export interface HandleAsyncOptions {
  successMessage?: string;
  errorMessage?: string;
  onSuccess?: () => void;
}

export interface CurrentUser {
  name: string;
  email: string;
  phone: string;
  address: string;
  dob: string;
  gender: string;
}

export type FieldType = "text" | "tel" | "email" | "date" | "select" | "textarea";

export interface FieldDef {
    key: keyof ProfileData;
    label: string;
    icon: typeof User;
    type: FieldType;
    placeholder?: string;
    options?: string[];
}

export interface ProfileData {
    fullName: string;
    mobile: string;
    gender: string;
    dob: string;
    email: string;
    address: string;
}
