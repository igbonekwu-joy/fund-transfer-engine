import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { lazy, Suspense } from 'react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
const Dashboard = lazy(() => import('@/pages/Dashboard'));
function App() {
  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
        <TooltipProvider>
            <BrowserRouter>
                <Suspense fallback={null}>
                    <Routes>
                        <Route path="/" element={<Dashboard />} />
                    </Routes>
                </Suspense>
            </BrowserRouter>
        </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
