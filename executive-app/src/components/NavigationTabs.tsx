import React from 'react';
import type { ExecutiveTab } from '../types';
import {
  LayoutDashboard,
  Users,
  Tag,
  ShieldAlert,
  BadgePercent,
} from 'lucide-react';

interface NavigationTabsProps {
  activeTab: ExecutiveTab;
  onTabChange: (tab: ExecutiveTab) => void;
}

interface TabConfig {
  id: ExecutiveTab;
  label: string;
  sublabel: string;
  icon: React.ElementType;
  badge?: string;
  badgeColor?: string;
}

export const NavigationTabs: React.FC<NavigationTabsProps> = ({
  activeTab,
  onTabChange,
}) => {
  const tabs: TabConfig[] = [
    {
      id: 'overview',
      label: 'Finansial & Tren',
      sublabel: 'Overview & Arus Kas',
      icon: LayoutDashboard,
    },
    {
      id: 'staff',
      label: 'KPI Tim & Kasir',
      sublabel: 'Sales vs Kasir',
      icon: Users,
    },
    {
      id: 'brands',
      label: 'Penjualan per Brand',
      sublabel: 'Market Share & Margin',
      icon: Tag,
    },
    {
      id: 'audit',
      label: 'Audit Kasir & Beli HP',
      sublabel: 'Pembatalan & Overpay',
      icon: ShieldAlert,
      badge: 'Risiko',
      badgeColor: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
    },
    {
      id: 'promos',
      label: 'Klaim Promo & Subsidi',
      sublabel: 'Program Diskon Toko',
      icon: BadgePercent,
    },
  ];

  return (
    <div className="w-full bg-slate-950/60 backdrop-blur-md border-b border-slate-900 sticky top-16 z-20 px-4 sm:px-6 lg:px-8 py-3">
      <div className="max-w-7xl mx-auto flex items-center justify-between gap-4 overflow-x-auto no-scrollbar">
        <nav className="flex space-x-2" aria-label="Tabs">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;

            return (
              <button
                key={tab.id}
                onClick={() => onTabChange(tab.id)}
                className={`group relative flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 whitespace-nowrap ${
                  isActive
                    ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-lg shadow-indigo-600/25 border border-indigo-500/30'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80 border border-transparent'
                }`}
              >
                <Icon
                  className={`w-4 h-4 transition-transform duration-200 group-hover:scale-110 ${
                    isActive ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'
                  }`}
                />
                <div className="flex flex-col text-left">
                  <span className="font-semibold">{tab.label}</span>
                  <span
                    className={`text-[10px] leading-tight ${
                      isActive ? 'text-indigo-200' : 'text-slate-500'
                    }`}
                  >
                    {tab.sublabel}
                  </span>
                </div>

                {tab.badge && (
                  <span
                    className={`ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold border uppercase tracking-wider ${
                      tab.badgeColor || 'bg-slate-800 text-slate-300'
                    }`}
                  >
                    {tab.badge}
                  </span>
                )}
              </button>
            );
          })}
        </nav>
      </div>
    </div>
  );
};
