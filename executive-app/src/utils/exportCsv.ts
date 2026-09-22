import type { DashboardOverviewData } from '../types';

/**
 * Clean string for CSV cell and escape quotes.
 */
function escapeCsv(value: string | number | null | undefined): string {
  if (value === null || value === undefined) return '""';
  const str = String(value).replace(/"/g, '""');
  return `"${str}"`;
}

/**
 * Format currency to Rupiah string for reports.
 */
function formatRupiah(val: number | null | undefined): string {
  if (val === null || val === undefined) return '0';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val);
}

/**
 * Export complete Executive Overview Data to CSV (Excel compatible).
 */
export function exportDashboardToCsv(overview: DashboardOverviewData, periodLabel = 'Laporan Eksekutif') {
  const lines: string[] = [];

  // Header Title
  lines.push(escapeCsv('TOKOPON ZED - LAPORAN INTELIJEN BISNIS & KINERJA DIREKSI'));
  lines.push(escapeCsv(`Periode: ${periodLabel} | Dicetak: ${new Date().toLocaleString('id-ID')}`));
  lines.push('');

  // 1. Ringkasan Eksekutif KPI
  lines.push(escapeCsv('=== RINGKASAN FINANSIAL & KPI UTAMA ==='));
  lines.push([
    escapeCsv('Metrik'),
    escapeCsv('Nilai Finansial'),
    escapeCsv('Keterangan'),
  ].join(','));

  const kpi = overview.kpi?.summary;
  if (kpi) {
    lines.push([escapeCsv('Omset Bersih (Net Sales)'), escapeCsv(formatRupiah(kpi.net_sales)), escapeCsv('Total penjualan setelah diskon')].join(','));
    lines.push([escapeCsv('Harga Pokok Penjualan (HPP)'), escapeCsv(formatRupiah(kpi.total_hpp)), escapeCsv('HPP dari nomor seri / accurate')].join(','));
    lines.push([escapeCsv('Laba Kotor (Gross Profit)'), escapeCsv(formatRupiah(kpi.gross_profit)), escapeCsv(`Margin: ${kpi.profit_margin.toFixed(1)}%`)].join(','));
    lines.push([escapeCsv('Total Transaksi Pesanan'), escapeCsv(`${kpi.total_orders} Pesanan`), escapeCsv('Volume transaksi lunas/berjalan')].join(','));
    lines.push([escapeCsv('Rata-rata Nilai Transaksi (AOV)'), escapeCsv(formatRupiah(kpi.average_order_value)), escapeCsv('Basket size per invoice')].join(','));
    lines.push([escapeCsv('Realisasi Kas Masuk (Lunas)'), escapeCsv(formatRupiah(kpi.completed_amount)), escapeCsv('Dana tunai / transfer terverifikasi')].join(','));
    lines.push([escapeCsv('Total Piutang Berjalan'), escapeCsv(formatRupiah(kpi.piutang_amount)), escapeCsv('Faktur belum lunas')].join(','));
  }
  lines.push('');

  // 2. Kinerja Cabang
  lines.push(escapeCsv('=== PERFORMA BENCHMARKING CABANG TOKO ==='));
  lines.push([
    escapeCsv('Peringkat'),
    escapeCsv('Nama Cabang / Toko'),
    escapeCsv('Omset Bersih'),
    escapeCsv('Laba Kotor'),
    escapeCsv('Margin (%)'),
    escapeCsv('Kontribusi (%)'),
    escapeCsv('Total Transaksi'),
  ].join(','));

  if (overview.branches && overview.branches.length > 0) {
    overview.branches.forEach((b, idx) => {
      lines.push([
        escapeCsv(`#${idx + 1}`),
        escapeCsv(b.branch_name),
        escapeCsv(formatRupiah(b.net_sales)),
        escapeCsv(formatRupiah(b.gross_profit)),
        escapeCsv(`${b.margin_percentage.toFixed(1)}%`),
        escapeCsv(`${b.contribution_percentage.toFixed(1)}%`),
        escapeCsv(`${b.orders_count} pesanan`),
      ].join(','));
    });
  } else {
    lines.push(escapeCsv('Tidak ada data cabang'));
  }
  lines.push('');

  // 3. Produk Terlaris
  lines.push(escapeCsv('=== TOP PRODUK TERLARIS (PERINGKAT OMSET) ==='));
  lines.push([
    escapeCsv('Peringkat'),
    escapeCsv('Nama Produk'),
    escapeCsv('Brand'),
    escapeCsv('Jumlah Terjual (Qty)'),
    escapeCsv('Total Omset Produk'),
  ].join(','));

  if (overview.top_products && overview.top_products.length > 0) {
    overview.top_products.forEach((p) => {
      lines.push([
        escapeCsv(`#${p.rank}`),
        escapeCsv(p.name),
        escapeCsv(p.brand || '-'),
        escapeCsv(`${p.qty_sold} unit`),
        escapeCsv(formatRupiah(p.revenue)),
      ].join(','));
    });
  } else {
    lines.push(escapeCsv('Tidak ada data produk'));
  }
  lines.push('');

  // 4. Komposisi Metode Pembayaran & Beban MDR
  lines.push(escapeCsv('=== KOMPOSISI PEMBAYARAN & BEBAN MDR ==='));
  lines.push([
    escapeCsv('Metode Pembayaran'),
    escapeCsv('Total Transaksi Masuk'),
    escapeCsv('Beban Biaya MDR'),
    escapeCsv('Dana Bersih Masuk'),
    escapeCsv('Pangsa Pasar (%)'),
  ].join(','));

  if (overview.payments && overview.payments.length > 0) {
    overview.payments.forEach((pay) => {
      lines.push([
        escapeCsv(pay.payment_method_name),
        escapeCsv(formatRupiah(pay.total_amount)),
        escapeCsv(formatRupiah(pay.total_mdr)),
        escapeCsv(formatRupiah(pay.net_amount)),
        escapeCsv(`${pay.share_percentage.toFixed(1)}%`),
      ].join(','));
    });
  }

  // Add UTF-8 BOM so Microsoft Excel renders accented characters and columns cleanly
  const csvContent = '\uFEFF' + lines.join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  
  const link = document.createElement('a');
  link.setAttribute('href', url);
  const fileName = `Laporan_Eksekutif_Tokopon_${new Date().toISOString().slice(0, 10)}.csv`;
  link.setAttribute('download', fileName);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}
