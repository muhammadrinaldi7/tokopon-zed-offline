import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import type { FilterParams } from '../types';

interface FilterContextType {
  filters: FilterParams;
  setDateRange: (range: string) => void;
  setCustomDateRange: (start: string, end: string) => void;
  setBusinessUnitId: (buId: string | number | null) => void;
  setBranch: (branch: string | null) => void;
  setSortBy: (sort: 'revenue' | 'qty') => void;
  refreshKey: number;
  lastRefreshed: Date;
  refreshData: () => void;
  isAutoRefresh: boolean;
  setIsAutoRefresh: (auto: boolean) => void;
}

const FilterContext = createContext<FilterContextType | undefined>(undefined);

export const FilterProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [filters, setFilters] = useState<FilterParams>({
    date_range: 'this_month',
    business_unit_id: null,
    branch: null,
    sort_by: 'revenue',
    limit: 10,
  });

  const [refreshKey, setRefreshKey] = useState<number>(0);
  const [lastRefreshed, setLastRefreshed] = useState<Date>(new Date());
  const [isAutoRefresh, setIsAutoRefresh] = useState<boolean>(false);

  const refreshData = useCallback(() => {
    setRefreshKey((prev) => prev + 1);
    setLastRefreshed(new Date());
  }, []);

  // Handle auto-refresh interval
  useEffect(() => {
    if (!isAutoRefresh) return;
    const interval = setInterval(() => {
      refreshData();
    }, 60000); // every 60 seconds
    return () => clearInterval(interval);
  }, [isAutoRefresh, refreshData]);

  const setDateRange = (range: string) => {
    setFilters((prev) => ({
      ...prev,
      date_range: range,
      start_date: undefined,
      end_date: undefined,
    }));
    refreshData();
  };

  const setCustomDateRange = (start: string, end: string) => {
    setFilters((prev) => ({
      ...prev,
      date_range: 'custom',
      start_date: start,
      end_date: end,
    }));
    refreshData();
  };

  const setBusinessUnitId = (buId: string | number | null) => {
    setFilters((prev) => ({
      ...prev,
      business_unit_id: buId === '' ? null : buId,
      branch: null, // Reset branch when BU changes
    }));
    refreshData();
  };

  const setBranch = (branch: string | null) => {
    setFilters((prev) => ({
      ...prev,
      branch: branch === '' ? null : branch,
    }));
    refreshData();
  };

  const setSortBy = (sort: 'revenue' | 'qty') => {
    setFilters((prev) => ({
      ...prev,
      sort_by: sort,
    }));
    refreshData();
  };

  return (
    <FilterContext.Provider
      value={{
        filters,
        setDateRange,
        setCustomDateRange,
        setBusinessUnitId,
        setBranch,
        setSortBy,
        refreshKey,
        lastRefreshed,
        refreshData,
        isAutoRefresh,
        setIsAutoRefresh,
      }}
    >
      {children}
    </FilterContext.Provider>
  );
};

export const useFilter = () => {
  const context = useContext(FilterContext);
  if (!context) {
    throw new Error('useFilter must be used within a FilterProvider');
  }
  return context;
};
