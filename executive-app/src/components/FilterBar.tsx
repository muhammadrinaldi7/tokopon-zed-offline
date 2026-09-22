import React, { useState } from 'react';
import { useFilter } from '../context/FilterContext';
import type { FilterOptions } from '../types';
import { Calendar, Store, RotateCcw } from 'lucide-react';

interface FilterBarProps {
  filterOptions?: FilterOptions | null;
}

const PRESET_PILLS = [
  { value: 'today', label: 'Hari Ini' },
  { value: 'yesterday', label: 'Kemarin' },
  { value: 'last_7_days', label: '7 Hari' },
  { value: 'this_month', label: 'Bulan Ini' },
  { value: 'last_month', label: 'Bulan Lalu' },
  { value: 'this_quarter', label: 'Kuartal Ini' },
  { value: 'this_year', label: 'Tahun Ini' },
  { value: 'custom', label: 'Kustom' },
];

export const FilterBar: React.FC<FilterBarProps> = ({ filterOptions }) => {
  const { filters, setDateRange, setCustomDateRange, setBranch } = useFilter();

  const [customStart, setCustomStart] = useState<string>(filters.start_date || '');
  const [customEnd, setCustomEnd] = useState<string>(filters.end_date || '');

  const handleApplyCustomDate = (e: React.FormEvent) => {
    e.preventDefault();
    if (customStart && customEnd) {
      setCustomDateRange(customStart, customEnd);
    }
  };

  const handleResetFilters = () => {
    setDateRange('this_month');
    setBranch(null);
    setCustomStart('');
    setCustomEnd('');
  };

  const isFiltered =
    filters.date_range !== 'this_month' ||
    filters.branch !== null ||
    filters.business_unit_id !== null;

  // Extract unique list of branch/store names
  const availableStores = filterOptions?.order_stores && filterOptions.order_stores.length > 0
    ? filterOptions.order_stores
    : filterOptions?.branches?.map((b) => b.name) || [];

  return (
    <div className="no-print w-full bg-slate-900/60 border-b border-slate-800/80 py-3 px-4 sm:px-6 lg:px-8 backdrop-blur-md">
      <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        {/* Left: Quick Date Range Pills */}
        <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none">
          <div className="flex items-center text-slate-400 text-xs mr-1 shrink-0 font-medium">
            <Calendar className="w-3.5 h-3.5 mr-1 text-indigo-400" />
            <span className="hidden sm:inline">Periode:</span>
          </div>

          {PRESET_PILLS.map((pill) => {
            const isActive = filters.date_range === pill.value;
            return (
              <button
                key={pill.value}
                onClick={() => setDateRange(pill.value)}
                className={`px-3 py-1 rounded-lg text-xs font-semibold shrink-0 transition-all cursor-pointer ${
                  isActive
                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                    : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-700/50'
                }`}
              >
                {pill.label}
              </button>
            );
          })}
        </div>

        {/* Right: Branch Filter & Custom Date Inputs */}
        <div className="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          {/* Custom Date Range Popover/Inputs */}
          {filters.date_range === 'custom' && (
            <form onSubmit={handleApplyCustomDate} className="flex items-center gap-1.5">
              <input
                type="date"
                value={customStart}
                onChange={(e) => setCustomStart(e.target.value)}
                className="px-2 py-1 bg-slate-950 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <span className="text-slate-500 text-xs font-bold">-</span>
              <input
                type="date"
                value={customEnd}
                onChange={(e) => setCustomEnd(e.target.value)}
                className="px-2 py-1 bg-slate-950 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
              <button
                type="submit"
                className="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-medium transition-colors cursor-pointer"
              >
                Terapkan
              </button>
            </form>
          )}

          {/* Branch / Store Dropdown */}
          <div className="relative flex items-center">
            <div className="absolute left-2.5 text-slate-400 pointer-events-none">
              <Store className="w-3.5 h-3.5" />
            </div>
            <select
              value={filters.branch ?? ''}
              onChange={(e) => setBranch(e.target.value || null)}
              className="pl-8 pr-7 py-1 bg-slate-950 border border-slate-700/80 rounded-lg text-xs font-medium text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer"
            >
              <option value="">Semua Cabang / Toko</option>
              {availableStores.map((store) => (
                <option key={store} value={store}>
                  {store}
                </option>
              ))}
            </select>
          </div>

          {/* Reset Filters Pill */}
          {isFiltered && (
            <button
              onClick={handleResetFilters}
              title="Kembalikan filter ke Bulan Ini (Semua Cabang)"
              className="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors cursor-pointer"
            >
              <RotateCcw className="w-3 h-3 text-slate-400" />
              <span>Reset</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
