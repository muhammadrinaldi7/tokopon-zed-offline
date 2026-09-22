import React from 'react';

export const PrintReportFooter: React.FC = () => {
  return (
    <div className="print-only mt-8 pt-4 border-t border-slate-300 break-inside-avoid">
      <div className="grid grid-cols-3 gap-8 text-center text-xs text-slate-700">
        <div>
          <p className="font-semibold text-slate-900 mb-16">Disiapkan Oleh (Finance):</p>
          <div className="border-b border-slate-400 w-36 mx-auto mb-1"></div>
          <p className="text-slate-500">Finance & Accounting</p>
        </div>
        <div>
          <p className="font-semibold text-slate-900 mb-16">Ditinjau Oleh (Operational):</p>
          <div className="border-b border-slate-400 w-36 mx-auto mb-1"></div>
          <p className="text-slate-500">General Manager / COO</p>
        </div>
        <div>
          <p className="font-semibold text-slate-900 mb-16">Disetujui Oleh (Direksi):</p>
          <div className="border-b border-slate-400 w-36 mx-auto mb-1"></div>
          <p className="text-slate-500">Direktur Utama / Owner</p>
        </div>
      </div>
      <p className="mt-6 text-[10px] text-center text-slate-400">
        Dokumen ini dihasilkan secara otomatis oleh Tokopon Zed Executive Intelligence System &bull; Bersifat Rahasia
      </p>
    </div>
  );
};
