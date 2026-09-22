import React, { useState } from 'react';
import ReactMarkdown from 'react-markdown';
import { executiveApi } from '../api/executive';
import type { KpiResponseData, BranchMetric } from '../types';
import { formatCurrency, formatPercent } from '../utils/formatters';
import { Bot, Sparkles, Lightbulb, ChevronDown, ChevronUp, RefreshCw, Share2, Check } from 'lucide-react';

interface AiExecutiveInsightProps {
  kpi?: KpiResponseData | null;
  branches?: BranchMetric[] | null;
}

export const AiExecutiveInsight: React.FC<AiExecutiveInsightProps> = ({ kpi, branches }) => {
  const [aiSummary, setAiSummary] = useState<string | null>(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isExpanded, setIsExpanded] = useState(false);
  const [isCopiedWa, setIsCopiedWa] = useState(false);

  if (!kpi) return null;

  const summary = kpi.summary;
  const growth = kpi.mtd_comparison?.growth;
  const topBranch = branches && branches.length > 0 ? branches[0] : null;

  const handleGenerateSummary = async () => {
    if (isGenerating) return;

    setIsGenerating(true);
    setIsExpanded(true);

    try {
      const contextData = {
        period: kpi.period,
        summary: kpi.summary,
        branches: branches,
      };

      const res = await executiveApi.getAiSummary(contextData);
      if (res.success && res.data) {
        setAiSummary(res.data.reply);
      } else {
        throw new Error(res.message || 'Gagal menghasilkan ringkasan AI.');
      }
    } catch (err: unknown) {
      const errObj = err as { response?: { data?: { message?: string } }; message?: string };
      setAiSummary(
        `⚠️ **Gagal mengambil analisis:** ${
          errObj.response?.data?.message || errObj.message || 'Periksa API Key 9router di .env.'
        }`
      );
    } finally {
      setIsGenerating(false);
    }
  };

  const handleCopyForWhatsApp = () => {
    if (!aiSummary) return;

    const dateStr = new Date().toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });

    const waText = `*TOKOPON ZED - EXECUTIVE BRIEFING* 📊
_Laporan Direksi & Intelijen Bisnis C-Level_
📅 Tanggal: ${dateStr}

*Ringkasan Kinerja:*
• Net Sales: ${formatCurrency(summary.net_sales)}
• Gross Profit: ${formatCurrency(summary.gross_profit)} (Margin: ${summary.profit_margin.toFixed(1)}%)
• Kas Lunas: ${formatCurrency(summary.completed_amount || 0)}
• Piutang Berjalan: ${formatCurrency(summary.piutang_amount || 0)}
${topBranch ? `• Cabang Juara: ${topBranch.branch_name} (${topBranch.contribution_percentage}%)` : ''}

*Analisis & Rekomendasi AI (9router):*
${aiSummary.replace(/###?\s+/g, '*').replace(/-\s+/g, '• ')}

_Dihasilkan otomatis via Tokopon Zed Executive Intelligence_`;

    navigator.clipboard.writeText(waText);
    setIsCopiedWa(true);
    setTimeout(() => setIsCopiedWa(false), 2500);
  };

  return (
    <div className="rounded-2xl p-6 relative overflow-hidden bg-gradient-to-r from-indigo-950/60 via-purple-950/30 to-slate-900 border border-indigo-500/30 shadow-xl transition-all">
      {/* Ambient background glow */}
      <div className="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />

      <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
        <div className="flex items-start gap-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-indigo-500/30">
            <Bot className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h3 className="text-base font-bold text-white tracking-tight flex items-center gap-1.5">
                AI Executive Strategic Insight
              </h3>
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 uppercase">
                <Sparkles className="w-2.5 h-2.5 text-emerald-400" />
                9router AI
              </span>
            </div>

            <p className="text-xs text-slate-300 mt-1 max-w-3xl leading-relaxed">
              Berdasarkan data performa saat ini, pendapatan bersih tercatat sebesar{' '}
              <span className="text-white font-bold">{formatCurrency(summary.net_sales)}</span> dengan margin laba kotor{' '}
              <span className="text-emerald-400 font-bold">{summary.profit_margin.toFixed(1)}%</span>.
              {growth?.net_sales_pct !== undefined && (
                <>
                  {' '}Tren MTD menunjukkan pertumbuhan{' '}
                  <span className={growth.net_sales_pct >= 0 ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'}>
                    {formatPercent(growth.net_sales_pct)}
                  </span>{' '}
                  dibanding periode sama bulan lalu.
                </>
              )}
              {topBranch && (
                <>
                  {' '}Cabang <span className="text-indigo-300 font-bold">{topBranch.branch_name}</span> mendominasi dengan kontribusi{' '}
                  <span className="text-white font-bold">{topBranch.contribution_percentage}%</span> dari total omset perusahaan.
                </>
              )}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            onClick={handleGenerateSummary}
            disabled={isGenerating}
            className="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold shadow-lg shadow-indigo-500/25 transition-all cursor-pointer disabled:opacity-60 group"
          >
            {isGenerating ? (
              <>
                <RefreshCw className="w-4 h-4 animate-spin text-white" />
                <span>Menganalisis Data...</span>
              </>
            ) : (
              <>
                <Lightbulb className="w-4 h-4 text-amber-300" />
                <span>{aiSummary ? 'Analisis Ulang AI' : 'Eksplorasi Rekomendasi AI'}</span>
              </>
            )}
          </button>

          {aiSummary && (
            <>
              {/* Copy for WhatsApp Button */}
              <button
                onClick={handleCopyForWhatsApp}
                title="Salin format siap kirim ke grup WhatsApp Direksi"
                className={`flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ${
                  isCopiedWa
                    ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300'
                    : 'bg-slate-800/90 hover:bg-slate-700 border-slate-700 text-slate-200'
                }`}
              >
                {isCopiedWa ? (
                  <>
                    <Check className="w-3.5 h-3.5 text-emerald-400" />
                    <span>Tersalin!</span>
                  </>
                ) : (
                  <>
                    <Share2 className="w-3.5 h-3.5 text-emerald-400" />
                    <span className="hidden sm:inline">WhatsApp</span>
                  </>
                )}
              </button>

              <button
                onClick={() => setIsExpanded(!isExpanded)}
                className="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors cursor-pointer"
              >
                {isExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
              </button>
            </>
          )}
        </div>
      </div>

      {/* Expanded AI Summary Box */}
      {isExpanded && aiSummary && (
        <div className="mt-5 pt-4 border-t border-slate-800/80 relative z-10">
          <div className="bg-slate-900/90 rounded-xl p-4 border border-indigo-500/30 text-xs text-slate-200 leading-relaxed shadow-inner">
            <div className="font-bold text-white mb-2 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Bot className="w-4 h-4 text-indigo-400" />
                <span>Analisis Strategis & Rekomendasi Direksi (9router)</span>
              </div>
              <button
                onClick={handleCopyForWhatsApp}
                className="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer"
              >
                <Share2 className="w-3 h-3" />
                <span>Kirim ke WhatsApp/Telegram</span>
              </button>
            </div>
            <div className="prose prose-invert prose-xs max-w-none space-y-2">
              <ReactMarkdown>{aiSummary}</ReactMarkdown>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

