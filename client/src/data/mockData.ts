import type { Transaction, BudgetGoal, Bill, SpendPoint, NavItem } from '../types';

export const primaryNav: NavItem[] = [
  { id: 'overview', label: 'Overview', icon: 'grid' },
  { id: 'transactions', label: 'Transactions', icon: 'list' },
  { id: 'cards', label: 'Cards', icon: 'card', badge: '3' },
  { id: 'analytics', label: 'Analytics', icon: 'trending' },
  { id: 'budgets', label: 'Budgets', icon: 'clock' },
];

export const secondaryNav: NavItem[] = [
  { id: 'settings', label: 'Settings', icon: 'settings' },
  { id: 'logout', label: 'Log out', icon: 'logout' },
];

export const spendData: SpendPoint[] = [
  { day: 'Mon', value: 32 },
  { day: 'Tue', value: 54 },
  { day: 'Wed', value: 41 },
  { day: 'Thu', value: 68 },
  { day: 'Fri', value: 90 },
  { day: 'Sat', value: 47 },
  { day: 'Sun', value: 60 },
];

export const todayIndex = 4;

export const transactions: Transaction[] = [
  { id: 't1', name: 'Salary — HNG Foundry', meta: 'Today, 9:14 AM', amount: 640200, type: 'in', icon: 'salary' },
  { id: 't2', name: 'Starbucks Reserve', meta: 'Today, 8:02 AM', amount: -4200, type: 'out', icon: 'coffee' },
  { id: 't3', name: 'Transfer to Chidi O.', meta: 'Yesterday, 6:40 PM', amount: -25000, type: 'out', icon: 'transfer' },
  { id: 't4', name: 'Uber', meta: 'Yesterday, 4:12 PM', amount: -3150, type: 'out', icon: 'ride' },
  { id: 't5', name: 'Jumia Marketplace', meta: 'Jul 18, 2:30 PM', amount: -18990, type: 'out', icon: 'shop' },
  { id: 't6', name: 'Netflix', meta: 'Jul 17, 12:00 AM', amount: -4400, type: 'out', icon: 'subs' },
];

export const budgetGoals: BudgetGoal[] = [
  { id: 'g1', name: 'New MacBook', saved: 510000, target: 850000, variant: 'jade' },
  { id: 'g2', name: 'Emergency fund', saved: 180000, target: 300000, variant: 'amber' },
  { id: 'g3', name: 'Japan trip', saved: 92000, target: 600000, variant: 'jade' },
];

export const bills: Bill[] = [
  { id: 'b1', name: 'Internet — Spectranet', day: '24', month: 'Jul', amount: 15000, method: 'Auto-debit' },
  { id: 'b2', name: 'Netflix', day: '27', month: 'Jul', amount: 4400, method: 'Auto-debit' },
  { id: 'b3', name: 'Rent instalment', day: '02', month: 'Aug', amount: 120000, method: 'Manual transfer' },
];
