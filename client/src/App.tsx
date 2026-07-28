import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { lazy, Suspense } from 'react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Toaster } from '@/components/ui/sonner';
import ProtectedRoute from './components/ProtectedRoute';
const Register = lazy(() => import('@/pages/Register'));
const Login = lazy(() => import('@/pages/Login'));
const Dashboard = lazy(() => import('@/pages/Dashboard'));
function App() {
  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
        <TooltipProvider>
            <Toaster />
            <BrowserRouter>
                <Suspense fallback={null}>
                    <Routes>
                        <Route path="/" element={
                            <ProtectedRoute>
                                <Dashboard />
                            </ProtectedRoute>} />
                        <Route path="/login" element={
                            <ProtectedRoute>
                                <Login />
                            </ProtectedRoute>} />
                        <Route path="/register" element={<Register />} />
                    </Routes>
                </Suspense>
            </BrowserRouter>
        </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
