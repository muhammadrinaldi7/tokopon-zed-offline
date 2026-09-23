const fs = require('fs');
const path = require('path');

const targetDir = 'd:\\APP\\executive-app';

// 1. Create ProjectSalesTab.tsx
const tabPath = path.join(targetDir, 'src', 'components', 'tabs', 'ProjectSalesTab.tsx');

const tabContent = `import React, { useEffect, useState, useMemo } from 'react';
import { useFilter } from '../../context/FilterContext';
import { executiveApi } from '../../api/executive';
import type {
  ProjectSalesReportResponse,
  ProjectBreakdownItem,
  ProjectSalesDetailItem,
} from '../../types';
import { formatCurrency, formatNumber } from '../../utils/formatters';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip as RechartsTooltip,
  CartesianGrid,
  Cell,
} from 'recharts';
import {
  FolderKanban,
  Layers,
  TrendingUp,
  Download,
  Search,
  X,
  Calendar,
  DollarSign,
  Package,
  Percent,
  SlidersHorizontal,
  ChevronDown,
  ChevronUp,
  ArrowRight,
  Sparkles,
} from 'lucide-react';

const PROJECT_PALETTE = [
  '#6366f1', // Indigo
  '#06b6d4', // Cyan
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#ec4899', // Pink
  '#8b5cf6', // Purple
  '#3b82f6', // Blue
  '#14b8a6', // Teal
  '#f97316', // Orange
  '#64748b', // Slate
];

export const ProjectSalesTab: React.FC = () => {
  const { filters, refreshKey } = useFilter();

  const [data, setData] = useState<ProjectSalesReportResponse | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // View mode: 'nominal' (Rupiah) or 'qty' (Units)
  const [valueMode, setValueMode] = useState<'nominal' | 'qty'>('nominal');

  // Filter columns to display
  const [projectSearch, setProjectSearch] = useState<string>('');

  // Drilldown modal state
  const [modalOpen, setModalOpen] = useState<boolean>(false);
  const [selectedCell, setSelectedCell] = useState<{
    date: string;
    displayDate: string;
    project: string;
  } | null>(null);
  const [drilldownLoading, setDrilldownLoading] = useState<boolean>(false);
  const [drilldownItems, setDrilldownItems] = useState<ProjectSalesDetailItem[]>([]);
  const [drilldownSearch, setDrilldownSearch] = useState<string>('');

  // Fetch matrix and breakdown
  useEffect(() => {
    let isMounted = true;
    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const res = await executiveApi.getProjectSales(filters);
        if (isMounted && res.success && res.data) {
          setData(res.data);
        }
      } catch (err: unknown) {
        if (!isMounted) return;
        const axiosErr = err as { response?: { data?: { message?: string } } };
        setError(axiosErr.response?.data?.message || 'Gagal memuat laporan penjualan per proyek.');
      } finally {
        if (isMounted) setIsLoading(false);
      }
    };

    fetchData();
    return () => {
      isMounted = false;
    };
  }, [filters, refreshKey]);

  // Open drilldown modal
  const handleOpenDrilldown = async (date: string, displayDate: string, project: string) => {
    setSelectedCell({ date, displayDate, project });
    setModalOpen(true);
    setDrilldownLoading(true);
    setDrilldownSearch('');

    try {
      const res = await executiveApi.getProjectSalesDetail({
        date,
        project,
        branch: filters.branch,
        business_unit_id: filters.business_unit_id,
      });
      if (res.success && res.data) {
        setDrilldownItems(res.data);
      } else {
        setDrilldownItems([]);
      }
    } catch (err) {
      console.error('Failed to load project drilldown detail:', err);
      setDrilldownItems([]);
    } finally {
      setDrilldownLoading(false);
    }
  };

  // Filtered drilldown items by search inside modal
  const filteredDrilldown = useMemo(() => {
    if (!drilldownSearch.trim()) return drilldownItems;
    const q = drilldownSearch.toLowerCase();
    return drilldownItems.filter(
      (item) =>
        item.order_number.toLowerCase().includes(q) ||
        (item.invoice_no && item.invoice_no.toLowerCase().includes(q)) ||
        item.product_name.toLowerCase().includes(q) ||
        (item.sku && item.sku.toLowerCase().includes(q)) ||
        (item.serial_number && item.serial_number.toLowerCase().includes(q)) ||
        item.customer_name.toLowerCase().includes(q) ||
        item.sales_name.toLowerCase().includes(q)
    );
  }, [drilldownItems, drilldownSearch]);

  // Columns to show in matrix based on optional search
  const visibleColumns = useMemo(() => {
    if (!data) return [];
    if (!projectSearch.trim()) return data.columns;
    const q = projectSearch.toLowerCase();
    return data.columns.filter((c) => c.toLowerCase().includes(q));
  }, [data, projectSearch]);

  // Export matrix to CSV
  const handleExportMatrixCsv = () => {
    if (!data) return;

    const lines: string[] = [];
    lines.push(\`"TOKOPON ZED - LAPORAN PENJUALAN PER PROYEK"\`);
    lines.push(\`"Periode: \${data.period.start_date} s/d \${data.period.end_date} (\${data.period.range})"\`);
    lines.push('');

    // Summary
    lines.push(\`"=== RINGKASAN PROYEK ==="\`);
    lines.push(
      \`"Nama Proyek","Unit Terjual","Omzet Bersih","HPP","Laba Kotor","Margin %","Kontribusi %"\`
    );
    data.project_breakdown.forEach((pb) => {
      lines.push(
        \`"\${pb.project}","\${pb.total_qty}","\${pb.net_sales}","\${pb.total_hpp}","\${pb.gross_profit}","\${pb.margin_percentage}%","\${pb.contribution_percentage}%"\`
      );
    });
    lines.push('');

    // Matrix
    lines.push(\`"=== MATRIKS HARIAN (\${valueMode === 'nominal' ? 'NOMINAL RP' : 'UNIT QTY'}) ==="\`);
    const headerCols = ['Tanggal', 'Hari', ...visibleColumns, 'Total Harian'];
    lines.push(headerCols.map((c) => \`"\${c}"\`).join(','));

    data.dates.forEach((d) => {
      const rowVal: (string | number)[] = [d.display, d.day_name];
      visibleColumns.forEach((col) => {
        const cell = data.matrix[d.raw]?.[col];
        if (valueMode === 'nominal') {
          rowVal.push(cell ? cell.nominal : 0);
        } else {
          rowVal.push(cell ? cell.qty : 0);
        }
      });
      const rowTotal = data.row_totals[d.raw];
      rowVal.push(rowTotal ? (valueMode === 'nominal' ? rowTotal.nominal : rowTotal.qty) : 0);
      lines.push(rowVal.map((v) => \`"\${v}"\`).join(','));
    });

    // Grand total row
    const totalRowVal: (string | number)[] = ['TOTAL', '-'];
    visibleColumns.forEach((col) => {
      const colTot = data.column_totals[col];
      totalRowVal.push(colTot ? (valueMode === 'nominal' ? colTot.nominal : colTot.qty) : 0);
    });
    totalRowVal.push(
      valueMode === 'nominal' ? data.grand_total.nominal : data.grand_total.qty
    );
    lines.push(totalRowVal.map((v) => \`"\${v}"\`).join(','));

    const csvContent = '\\uFEFF' + lines.join('\\r\\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = \`Laporan_Penjualan_Proyek_\${data.period.start_date}_\${data.period.end_date}.csv\`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // Export drilldown to CSV
  const handleExportDrilldownCsv = () => {
    if (!selectedCell || filteredDrilldown.length === 0) return;

    const lines: string[] = [];
    lines.push(\`"RINCIAN TRANSAKSI PROYEK \${selectedCell.project}"\`);
    lines.push(\`"Tanggal: \${selectedCell.displayDate}"\`);
    lines.push('');

    const headers = [
      'Order Number',
      'Invoice Accurate',
      'Waktu',
      'Cabang',
      'Pelanggan',
      'Sales',
      'Kasir/PIC',
      'Produk',
      'SKU',
      'IMEI / Serial Number',
      'Qty',
      'Harga',
      'Diskon',
      'Subtotal Bersih',
      'Metode Bayar',
    ];
    lines.push(headers.map((h) => \`"\${h}"\`).join(','));

    filteredDrilldown.forEach((item) => {
      lines.push(
        [
          \`"\${item.order_number}"\`,
          \`"\${item.invoice_no}"\`,
          \`"\${item.time}"\`,
          \`"\${item.branch}"\`,
          \`"\${item.customer_name}"\`,
          \`"\${item.sales_name}"\`,
          \`"\${item.handled_by}"\`,
          \`"\${item.product_name.replace(/"/g, '""')}"\`,
          \`"\${item.sku}"\`,
          \`"\${item.serial_number}"\`,
          \`"\${item.qty}"\`,
          \`"\${item.price}"\`,
          \`"\${item.discount}"\`,
          \`"\${item.subtotal}"\`,
          \`"\${item.payment_method}"\`,
        ].join(',')
      );
    });

    const csvContent = '\\uFEFF' + lines.join('\\r\\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = \`Detail_Proyek_\${selectedCell.project}_\${selectedCell.date}.csv\`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  if (isLoading) {
    return (
      <div className="space-y-6 animate-pulse">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 rounded-2xl bg-slate-900/60 border border-slate-800" />
          ))}
        </div>
        <div className="h-96 rounded-2xl bg-slate-900/60 border border-slate-800" />
        <div className="h-80 rounded-2xl bg-slate-900/60 border border-slate-800" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="p-8 text-center glass-card rounded-2xl border border-rose-500/20 text-rose-300">
        <FolderKanban className="w-10 h-10 text-rose-400 mx-auto mb-3" />
        <h3 className="text-base font-bold text-white mb-1">Gagal Memuat Laporan Proyek</h3>
        <p className="text-xs text-slate-400 max-w-md mx-auto">{error || 'Tidak ada data proyek tersedia.'}</p>
      </div>
    );
  }

  const { summary, project_breakdown, dates, matrix, row_totals, column_totals, grand_total } = data;

  return (
    <div className="space-y-6">
      {/* 1. Header Toolbar & View Toggle */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-2xl glass-card border border-slate-800/80">
        <div className="flex items-center gap-3">
          <div className="p-2.5 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 text-indigo-400">
            <FolderKanban className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-base font-bold text-white flex items-center gap-2">
              Laporan Penjualan per Proyek & Matriks Harian
              <span className="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                {summary.total_projects_count} Proyek
              </span>
            </h2>
            <p className="text-xs text-slate-400 mt-0.5">
              Klasifikasi transaksi proyek (Resmi, Inter, Bea Cukai, Non-Proyek, dll.) dengan drill-down harian
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2.5">
          {/* View Mode Toggle */}
          <div className="flex items-center bg-slate-900/90 p-1 rounded-xl border border-slate-800">
            <button
              onClick={() => setValueMode('nominal')}
              className={\`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all \${
                valueMode === 'nominal'
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                  : 'text-slate-400 hover:text-slate-200'
              }\`}
            >
              <DollarSign className="w-3.5 h-3.5" />
              <span>Nominal (Rp)</span>
            </button>
            <button
              onClick={() => setValueMode('qty')}
              className={\`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all \${
                valueMode === 'qty'
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                  : 'text-slate-400 hover:text-slate-200'
              }\`}
            >
              <Package className="w-3.5 h-3.5" />
              <span>Unit (Qty)</span>
            </button>
          </div>

          {/* Export CSV Button */}
          <button
            onClick={handleExportMatrixCsv}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700/80 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"
            title="Download CSV Matriks Proyek"
          >
            <Download className="w-3.5 h-3.5 text-indigo-400" />
            <span className="hidden sm:inline">Export CSV</span>
          </button>
        </div>
      </div>

      {/* 2. Top Executive KPI Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1: Total Omzet */}
        <div className="glass-card p-4 rounded-2xl border border-slate-800/80 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
          <div className="flex items-center justify-between text-slate-400 text-xs mb-2">
            <span className="font-medium">Total Omzet Bersih</span>
            <span className="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400">
              <DollarSign className="w-4 h-4" />
            </span>
          </div>
          <p className="text-xl font-extrabold text-white tracking-tight">
            {formatCurrency(summary.total_net_sales)}
          </p>
          <div className="flex items-center gap-2 mt-2 text-[11px] text-slate-400">
            <span className="text-emerald-400 font-semibold flex items-center gap-0.5">
              <TrendingUp className="w-3 h-3" />
              Rata-rata:
            </span>
            <span>{formatCurrency(summary.daily_average_sales)} / hari</span>
          </div>
        </div>

        {/* Card 2: Total Volume Qty */}
        <div className="glass-card p-4 rounded-2xl border border-slate-800/80 relative overflow-hidden group hover:border-cyan-500/40 transition-all">
          <div className="flex items-center justify-between text-slate-400 text-xs mb-2">
            <span className="font-medium">Total Unit Terjual</span>
            <span className="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-400">
              <Package className="w-4 h-4" />
            </span>
          </div>
          <p className="text-xl font-extrabold text-white tracking-tight">
            {formatNumber(summary.total_qty)}{' '}
            <span className="text-xs font-normal text-slate-400">Unit</span>
          </p>
          <div className="flex items-center gap-2 mt-2 text-[11px] text-slate-400">
            <span className="text-cyan-400 font-semibold">{summary.daily_average_qty} unit / hari</span>
            <span>• {dates.length} hari terlapor</span>
          </div>
        </div>

        {/* Card 3: Gross Profit & Margin */}
        <div className="glass-card p-4 rounded-2xl border border-slate-800/80 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
          <div className="flex items-center justify-between text-slate-400 text-xs mb-2">
            <span className="font-medium">Laba Kotor & Margin</span>
            <span className="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400">
              <Percent className="w-4 h-4" />
            </span>
          </div>
          <div className="flex items-baseline justify-between">
            <p className="text-xl font-extrabold text-emerald-400 tracking-tight">
              {formatCurrency(summary.gross_profit)}
            </p>
            <span className="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
              {summary.profit_margin}%
            </span>
          </div>
          <div className="mt-2 text-[11px] text-slate-400">
            <span>Estimasi HPP: {formatCurrency(summary.total_hpp)}</span>
          </div>
        </div>

        {/* Card 4: Active Projects */}
        <div className="glass-card p-4 rounded-2xl border border-slate-800/80 relative overflow-hidden group hover:border-purple-500/40 transition-all">
          <div className="flex items-center justify-between text-slate-400 text-xs mb-2">
            <span className="font-medium">Proyek Aktif Terjual</span>
            <span className="p-1.5 rounded-lg bg-purple-500/10 text-purple-400">
              <Layers className="w-4 h-4" />
            </span>
          </div>
          <p className="text-xl font-extrabold text-white tracking-tight">
            {summary.total_projects_count}{' '}
            <span className="text-xs font-normal text-slate-400">Klasifikasi</span>
          </p>
          <div className="mt-2 text-[11px] text-purple-300 truncate font-medium">
            Top: {project_breakdown[0]?.project || '-'} ({project_breakdown[0]?.contribution_percentage || 0}%)
          </div>
        </div>
      </div>

      {/* 3. Daily Sales Matrix Table (Interactive with frozen date column) */}
      <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
        <div className="p-4 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h3 className="text-base font-bold text-white flex items-center gap-2">
              <span>Matriks Penjualan Harian per Proyek</span>
              <span className="text-[11px] font-normal text-slate-400">
                (Klik cell berangka untuk melihat rincian nota & IMEI)
              </span>
            </h3>
            <p className="text-xs text-slate-400 mt-0.5">
              Tampilan:{' '}
              <span className="font-semibold text-indigo-400 uppercase">
                {valueMode === 'nominal' ? 'Nominal Penjualan Bersih (Rp)' : 'Volume Terjual (Unit)'}
              </span>
            </p>
          </div>

          {/* Quick column filter */}
          <div className="relative w-full sm:w-60">
            <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari kolom proyek..."
              value={projectSearch}
              onChange={(e) => setProjectSearch(e.target.value)}
              className="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-900 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
            {projectSearch && (
              <button
                onClick={() => setProjectSearch('')}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
              >
                <X className="w-3 h-3" />
              </button>
            )}
          </div>
        </div>

        <div className="overflow-x-auto max-h-[520px] relative scrollbar-thin scrollbar-thumb-slate-700">
          <table className="w-full text-left text-xs text-slate-300 border-collapse">
            <thead className="bg-slate-900/95 sticky top-0 z-10 backdrop-blur-md text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th className="py-3 px-4 w-32 sticky left-0 z-20 bg-slate-900 shadow-sm border-r border-slate-800/80">
                  Tanggal
                </th>
                <th className="py-3 px-3 w-28 text-slate-400 border-r border-slate-800/60">
                  Hari
                </th>
                {visibleColumns.map((col, idx) => (
                  <th
                    key={col}
                    className="py-3 px-3 text-right border-r border-slate-800/40 min-w-[130px]"
                  >
                    <span
                      className="inline-block px-2 py-0.5 rounded text-[10px] font-bold border"
                      style={{
                        backgroundColor: \`\${PROJECT_PALETTE[idx % PROJECT_PALETTE.length]}15\`,
                        color: PROJECT_PALETTE[idx % PROJECT_PALETTE.length],
                        borderColor: \`\${PROJECT_PALETTE[idx % PROJECT_PALETTE.length]}40\`,
                      }}
                    >
                      {col}
                    </span>
                  </th>
                ))}
                <th className="py-3 px-4 text-right bg-slate-900/90 font-bold text-white min-w-[140px]">
                  Total Harian
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/40">
              {dates.map((d) => {
                const rowTotal = row_totals[d.raw] || { nominal: 0, qty: 0, profit: 0 };
                const hasDailyActivity = rowTotal.qty > 0;

                return (
                  <tr
                    key={d.raw}
                    className={\`transition-colors \${
                      hasDailyActivity
                        ? 'hover:bg-slate-800/40'
                        : 'text-slate-600 hover:bg-slate-900/30'
                    }\`}
                  >
                    {/* Date Frozen Column */}
                    <td className="py-2.5 px-4 font-semibold text-white whitespace-nowrap sticky left-0 bg-slate-950/90 backdrop-blur-sm border-r border-slate-800/80 z-10">
                      {d.display}
                    </td>
                    <td className="py-2.5 px-3 text-slate-400 whitespace-nowrap border-r border-slate-800/60">
                      {d.day_name}
                    </td>

                    {/* Project Columns */}
                    {visibleColumns.map((col) => {
                      const cell = matrix[d.raw]?.[col] || {
                        nominal: 0,
                        qty: 0,
                        count: 0,
                        hpp: 0,
                        profit: 0,
                      };
                      const hasValue = valueMode === 'nominal' ? cell.nominal > 0 : cell.qty > 0;

                      return (
                        <td
                          key={col}
                          className="py-2 px-3 text-right border-r border-slate-800/30"
                        >
                          {hasValue ? (
                            <button
                              onClick={() => handleOpenDrilldown(d.raw, d.display, col)}
                              className="group/btn inline-flex flex-col items-end px-2 py-1 rounded-lg hover:bg-indigo-600/20 hover:border-indigo-500/40 border border-transparent transition-all cursor-pointer text-right w-full"
                              title={\`Klik untuk melihat rincian item \${col} pada \${d.display}\`}
                            >
                              <span className="font-semibold text-indigo-200 group-hover/btn:text-white transition-colors">
                                {valueMode === 'nominal'
                                  ? formatCurrency(cell.nominal)
                                  : \`\${formatNumber(cell.qty)} unit\`}
                              </span>
                              {valueMode === 'nominal' && cell.qty > 0 && (
                                <span className="text-[10px] text-slate-500 group-hover/btn:text-indigo-300">
                                  {formatNumber(cell.qty)} unit
                                </span>
                              )}
                            </button>
                          ) : (
                            <span className="text-slate-600 font-mono text-[11px]">-</span>
                          )}
                        </td>
                      );
                    })}

                    {/* Total Row */}
                    <td className="py-2.5 px-4 text-right font-bold text-white bg-slate-900/30">
                      {valueMode === 'nominal'
                        ? formatCurrency(rowTotal.nominal)
                        : \`\${formatNumber(rowTotal.qty)} unit\`}
                    </td>
                  </tr>
                );
              })}
            </tbody>
            {/* Grand Total Footer Row */}
            <tfoot className="bg-slate-900/95 sticky bottom-0 z-10 backdrop-blur-md border-t-2 border-slate-700 text-xs font-bold text-white">
              <tr>
                <td className="py-3.5 px-4 sticky left-0 bg-slate-900 border-r border-slate-800 z-10">
                  TOTAL PERIODE
                </td>
                <td className="py-3.5 px-3 text-slate-400 border-r border-slate-800">
                  {dates.length} Hari
                </td>
                {visibleColumns.map((col) => {
                  const colTotal = column_totals[col] || { nominal: 0, qty: 0, profit: 0 };
                  return (
                    <td
                      key={col}
                      className="py-3.5 px-3 text-right border-r border-slate-800/50 font-bold text-emerald-400"
                    >
                      <div>
                        {valueMode === 'nominal'
                          ? formatCurrency(colTotal.nominal)
                          : \`\${formatNumber(colTotal.qty)} unit\`}
                      </div>
                      {valueMode === 'nominal' && (
                        <div className="text-[10px] text-slate-400 font-normal">
                          {formatNumber(colTotal.qty)} unit
                        </div>
                      )}
                    </td>
                  );
                })}
                <td className="py-3.5 px-4 text-right font-black text-indigo-400 bg-slate-900/95">
                  <div className="text-sm">
                    {valueMode === 'nominal'
                      ? formatCurrency(grand_total.nominal)
                      : \`\${formatNumber(grand_total.qty)} unit\`}
                  </div>
                  {valueMode === 'nominal' && (
                    <div className="text-[10px] text-slate-400 font-normal">
                      Total: {formatNumber(grand_total.qty)} unit
                    </div>
                  )}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      {/* 4. Project Performance Breakdown Chart & Table */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Visual Chart: Omzet Contribution */}
        <div className="glass-card p-5 rounded-2xl border border-slate-800/80 lg:col-span-1 flex flex-col">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-sm font-bold text-white flex items-center gap-1.5">
                <TrendingUp className="w-4 h-4 text-indigo-400" />
                Kontribusi Omzet Proyek
              </h3>
              <p className="text-[11px] text-slate-400">Pangsa pasar per proyek periode ini</p>
            </div>
          </div>

          <div className="h-64 flex-1">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={project_breakdown.slice(0, 6)}
                layout="vertical"
                margin={{ top: 5, right: 20, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" stroke="#1e293b" horizontal={false} />
                <XAxis
                  type="number"
                  stroke="#64748b"
                  fontSize={10}
                  tickFormatter={(v) => \`\${v}%\`}
                  domain={[0, 100]}
                />
                <YAxis
                  type="category"
                  dataKey="project"
                  stroke="#94a3b8"
                  fontSize={10}
                  tickLine={false}
                  width={80}
                />
                <RechartsTooltip
                  content={({ active, payload }) => {
                    if (active && payload && payload.length) {
                      const item = payload[0].payload as ProjectBreakdownItem;
                      return (
                        <div className="bg-slate-900 border border-slate-700 p-2.5 rounded-xl shadow-xl text-xs">
                          <p className="font-bold text-white">{item.project}</p>
                          <p className="text-indigo-400 mt-1">
                            Kontribusi: <span className="font-bold">{item.contribution_percentage}%</span>
                          </p>
                          <p className="text-slate-300">
                            Omzet: {formatCurrency(item.net_sales)}
                          </p>
                          <p className="text-slate-400 text-[10px]">
                            Qty: {formatNumber(item.total_qty)} unit
                          </p>
                        </div>
                      );
                    }
                    return null;
                  }}
                />
                <Bar dataKey="contribution_percentage" radius={[0, 4, 4, 0]}>
                  {project_breakdown.slice(0, 6).map((_, index) => (
                    <Cell
                      key={\`cell-\${index}\`}
                      fill={PROJECT_PALETTE[index % PROJECT_PALETTE.length]}
                    />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Detailed Project Breakdown Table */}
        <div className="glass-card rounded-2xl border border-slate-800/80 overflow-hidden lg:col-span-2">
          <div className="p-4 border-b border-slate-800/80 flex items-center justify-between">
            <div>
              <h3 className="text-sm font-bold text-white">Rincian Performa & Profitabilitas Proyek</h3>
              <p className="text-[11px] text-slate-400">
                Peringkat omset, modal (HPP), laba kotor, dan rasio margin tiap proyek
              </p>
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="bg-slate-900/80 text-[10px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <tr>
                  <th className="py-2.5 px-3 w-10 text-center">No</th>
                  <th className="py-2.5 px-3">Nama Proyek</th>
                  <th className="py-2.5 px-3 text-center">Unit</th>
                  <th className="py-2.5 px-3 text-right">Omzet Bersih</th>
                  <th className="py-2.5 px-3 text-right">Estimasi HPP</th>
                  <th className="py-2.5 px-3 text-right">Laba Kotor</th>
                  <th className="py-2.5 px-3 text-center">Margin</th>
                  <th className="py-2.5 px-3 text-right">Kontribusi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/40">
                {project_breakdown.map((pb, idx) => {
                  const marginColor =
                    pb.margin_percentage >= 15
                      ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                      : pb.margin_percentage >= 8
                      ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30'
                      : pb.margin_percentage >= 4
                      ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                      : 'bg-rose-500/10 text-rose-400 border-rose-500/30';

                  return (
                    <tr key={pb.project} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-2.5 px-3 text-center text-slate-500 font-semibold">
                        {idx + 1}
                      </td>
                      <td className="py-2.5 px-3 font-semibold text-white">
                        <span className="flex items-center gap-1.5">
                          <span
                            className="w-2 h-2 rounded-full"
                            style={{
                              backgroundColor: PROJECT_PALETTE[idx % PROJECT_PALETTE.length],
                            }}
                          />
                          {pb.project}
                        </span>
                      </td>
                      <td className="py-2.5 px-3 text-center font-medium text-slate-300">
                        {formatNumber(pb.total_qty)}
                      </td>
                      <td className="py-2.5 px-3 text-right font-bold text-slate-200">
                        {formatCurrency(pb.net_sales)}
                      </td>
                      <td className="py-2.5 px-3 text-right text-slate-400">
                        {formatCurrency(pb.total_hpp)}
                      </td>
                      <td className="py-2.5 px-3 text-right font-bold text-emerald-400">
                        {formatCurrency(pb.gross_profit)}
                      </td>
                      <td className="py-2.5 px-3 text-center">
                        <span className={\`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border \${marginColor}\`}>
                          {pb.margin_percentage}%
                        </span>
                      </td>
                      <td className="py-2.5 px-3 text-right font-semibold text-indigo-300">
                        {pb.contribution_percentage}%
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* 5. Drill-Down Transaction Items Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-5xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
            {/* Modal Header */}
            <div className="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                  <FolderKanban className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-white flex items-center gap-2">
                    Rincian Item Proyek: <span className="text-indigo-400">{selectedCell?.project}</span>
                    <span className="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-normal border border-slate-700">
                      {selectedCell?.displayDate}
                    </span>
                  </h3>
                  <p className="text-xs text-slate-400 mt-0.5">
                    Daftar faktur, nomor seri/IMEI, produk, dan nilai transaksi yang tercatat
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={handleExportDrilldownCsv}
                  disabled={filteredDrilldown.length === 0}
                  className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors disabled:opacity-50"
                >
                  <Download className="w-3.5 h-3.5 text-indigo-400" />
                  <span>Export CSV</span>
                </button>
                <button
                  onClick={() => setModalOpen(false)}
                  className="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Modal Search Toolbar */}
            <div className="p-3 bg-slate-950/60 border-b border-slate-800 flex items-center justify-between gap-3">
              <div className="relative flex-1">
                <Search className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  placeholder="Cari order #, invoice #, nama barang, IMEI, atau pelanggan..."
                  value={drilldownSearch}
                  onChange={(e) => setDrilldownSearch(e.target.value)}
                  className="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-900 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div className="text-xs text-slate-400 flex items-center gap-3">
                <span>
                  Total Item: <b className="text-white">{filteredDrilldown.length}</b>
                </span>
                <span>
                  Total Qty:{' '}
                  <b className="text-white">
                    {filteredDrilldown.reduce((sum, it) => sum + it.qty, 0)} unit
                  </b>
                </span>
                <span>
                  Total Subtotal:{' '}
                  <b className="text-emerald-400">
                    {formatCurrency(filteredDrilldown.reduce((sum, it) => sum + it.subtotal, 0))}
                  </b>
                </span>
              </div>
            </div>

            {/* Modal Table Content */}
            <div className="flex-1 overflow-y-auto p-0 scrollbar-thin scrollbar-thumb-slate-700">
              {drilldownLoading ? (
                <div className="p-12 text-center text-slate-400 text-xs">
                  <div className="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
                  Mengambil data item transaksi...
                </div>
              ) : filteredDrilldown.length === 0 ? (
                <div className="p-12 text-center text-slate-500 text-xs">
                  Tidak ada transaksi item yang cocok dengan kriteria pencarian.
                </div>
              ) : (
                <table className="w-full text-left text-xs text-slate-300">
                  <thead className="bg-slate-900/90 sticky top-0 text-[10px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                      <th className="py-2.5 px-3">Order / Faktur</th>
                      <th className="py-2.5 px-3">Cabang & Sales</th>
                      <th className="py-2.5 px-3">Nama Produk & SKU</th>
                      <th className="py-2.5 px-3">Nomor Seri / IMEI</th>
                      <th className="py-2.5 px-3 text-center">Qty</th>
                      <th className="py-2.5 px-3 text-right">Harga</th>
                      <th className="py-2.5 px-3 text-right">Diskon</th>
                      <th className="py-2.5 px-3 text-right">Subtotal</th>
                      <th className="py-2.5 px-3">Metode Bayar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-800/40">
                    {filteredDrilldown.map((item, idx) => (
                      <tr key={idx} className="hover:bg-slate-800/40 transition-colors">
                        <td className="py-2.5 px-3">
                          <div className="font-semibold text-white">{item.order_number}</div>
                          <div className="text-[10px] text-slate-500 font-mono">
                            Accurate: {item.invoice_no || '-'}
                          </div>
                          <div className="text-[10px] text-slate-500">{item.time}</div>
                        </td>
                        <td className="py-2.5 px-3">
                          <div className="font-medium text-slate-200">{item.branch}</div>
                          <div className="text-[10px] text-slate-400">
                            Sales: <span className="text-slate-300">{item.sales_name || '-'}</span>
                          </div>
                          <div className="text-[10px] text-slate-500">
                            Pelanggan: {item.customer_name}
                          </div>
                        </td>
                        <td className="py-2.5 px-3">
                          <div className="font-medium text-white max-w-xs">{item.product_name}</div>
                          <div className="text-[10px] text-slate-500 font-mono">
                            SKU: {item.sku}
                          </div>
                        </td>
                        <td className="py-2.5 px-3 font-mono text-[11px] text-amber-300">
                          {item.serial_number && item.serial_number !== '-' ? (
                            <span className="px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                              {item.serial_number}
                            </span>
                          ) : (
                            <span className="text-slate-600">-</span>
                          )}
                        </td>
                        <td className="py-2.5 px-3 text-center font-bold text-white">
                          {item.qty}
                        </td>
                        <td className="py-2.5 px-3 text-right text-slate-400">
                          {formatCurrency(item.price)}
                        </td>
                        <td className="py-2.5 px-3 text-right text-rose-400">
                          {item.discount > 0 ? \`-\${formatCurrency(item.discount)}\` : '-'}
                        </td>
                        <td className="py-2.5 px-3 text-right font-bold text-emerald-400">
                          {formatCurrency(item.subtotal)}
                        </td>
                        <td className="py-2.5 px-3 text-[11px] text-slate-300">
                          <span className="px-2 py-0.5 rounded-md bg-slate-800 border border-slate-700 text-slate-300">
                            {item.payment_method}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>

            {/* Modal Footer */}
            <div className="p-3 bg-slate-900 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
              <div>
                Menampilkan {filteredDrilldown.length} item dari transaksi terpilih
              </div>
              <button
                onClick={() => setModalOpen(false)}
                className="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
`;

fs.writeFileSync(tabPath, tabContent, 'utf8');
console.log('ProjectSalesTab.tsx created successfully');

// 2. Update NavigationTabs.tsx
const navTabsPath = path.join(targetDir, 'src', 'components', 'NavigationTabs.tsx');
let navTabsContent = fs.readFileSync(navTabsPath, 'utf8');

if (!navTabsContent.includes("id: 'projects'")) {
  // Add FolderKanban import
  if (!navTabsContent.includes('FolderKanban')) {
    navTabsContent = navTabsContent.replace(
      '  BadgePercent,\n} from \'lucide-react\';',
      '  BadgePercent,\n  FolderKanban,\n} from \'lucide-react\';'
    );
  }

  // Add project tab to tabs array
  const projectTabConfig = `    {
      id: 'projects',
      label: 'Penjualan per Proyek',
      sublabel: 'Matriks & Klasifikasi',
      icon: FolderKanban,
    },
  ];`;

  navTabsContent = navTabsContent.replace('  ];', projectTabConfig);
  fs.writeFileSync(navTabsPath, navTabsContent, 'utf8');
  console.log('NavigationTabs.tsx updated successfully');
} else {
  console.log('NavigationTabs.tsx already contains projects tab');
}

// 3. Update DashboardPage.tsx
const dashboardPagePath = path.join(targetDir, 'src', 'pages', 'DashboardPage.tsx');
let dashboardPageContent = fs.readFileSync(dashboardPagePath, 'utf8');

if (!dashboardPageContent.includes('ProjectSalesTab')) {
  dashboardPageContent = dashboardPageContent.replace(
    "import { PromoClaimsTab } from '../components/tabs/PromoClaimsTab';",
    "import { PromoClaimsTab } from '../components/tabs/PromoClaimsTab';\nimport { ProjectSalesTab } from '../components/tabs/ProjectSalesTab';"
  );

  dashboardPageContent = dashboardPageContent.replace(
    '        {activeTab === \'promos\' && <PromoClaimsTab />}',
    '        {activeTab === \'promos\' && <PromoClaimsTab />}\n\n        {activeTab === \'projects\' && <ProjectSalesTab />}'
  );

  fs.writeFileSync(dashboardPagePath, dashboardPageContent, 'utf8');
  console.log('DashboardPage.tsx updated successfully');
} else {
  console.log('DashboardPage.tsx already contains ProjectSalesTab');
}
