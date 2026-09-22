import React from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import { FilterProvider } from './context/FilterContext';
import { LoginPage } from './pages/LoginPage';
import { DashboardPage } from './pages/DashboardPage';
import { TrendingUp } from 'lucide-react';

const MainApp: React.FC = () => {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4">
        <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-emerald-400 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30 animate-pulse mb-4">
          <TrendingUp className="w-6 h-6 stroke-[2.5]" />
        </div>
        <div className="text-white font-bold text-base tracking-tight">
          Tokopon Executive Intelligence
        </div>
        <div className="text-slate-400 text-xs mt-1">
          Memuat sesi otorisasi direksi...
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <LoginPage />;
  }

  return (
    <FilterProvider>
      <DashboardPage />
    </FilterProvider>
  );
};

export function App() {
  return (
    <AuthProvider>
      <MainApp />
    </AuthProvider>
  );
}

export default App;
