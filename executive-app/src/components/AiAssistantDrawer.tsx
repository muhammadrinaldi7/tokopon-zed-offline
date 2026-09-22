import React, { useState, useEffect, useRef } from 'react';
import ReactMarkdown from 'react-markdown';
import { executiveApi } from '../api/executive';
import { useFilter } from '../context/FilterContext';
import type { AiChatMessage, DashboardOverviewData } from '../types';
import {
  Bot,
  Sparkles,
  X,
  Send,
  Trash2,
  Copy,
  Check,
  RotateCcw,
  Zap,
} from 'lucide-react';

interface AiAssistantDrawerProps {
  overviewData?: DashboardOverviewData | null;
}

const QUICK_PROMPTS = [
  'Berapa omset dan laba kotor pada periode ini?',
  'Cabang mana yang performa margin labanya paling tipis?',
  'Apakah ada piutang aktif yang perlu segera ditagih?',
  'Bandingkan performa penjualan antar cabang toko.',
  'Produk apa yang memberikan kontribusi omset tertinggi?',
];

export const AiAssistantDrawer: React.FC<AiAssistantDrawerProps> = ({ overviewData }) => {
  const { filters } = useFilter();
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<AiChatMessage[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [copiedIdx, setCopiedIdx] = useState<number | null>(null);

  const messagesEndRef = useRef<HTMLDivElement>(null);
  const sessionId = 'exec-drawer-session';

  // Load chat history when drawer is opened for the first time
  useEffect(() => {
    if (isOpen && messages.length === 0) {
      loadHistory();
    }
  }, [isOpen]);

  // Auto-scroll to bottom of messages
  useEffect(() => {
    if (isOpen) {
      messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages, isLoading, isOpen]);

  const loadHistory = async () => {
    try {
      const res = await executiveApi.getAiHistory(sessionId);
      if (res.success && Array.isArray(res.data) && res.data.length > 0) {
        setMessages(res.data);
      } else {
        // Welcome message if no past history
        setMessages([
          {
            role: 'assistant',
            message:
              'Halo Bapak/Ibu Direksi! Saya **Zed Executive Intelligence AI**, terhubung langsung dengan gateway **9router**.\n\nSaya telah memuat data bisnis dan metrik aktif di dashboard. Anda dapat bertanya tentang omset, laba, efisiensi cabang, atau meminta analisis strategis kapan saja.',
          },
        ]);
      }
    } catch {
      // Fallback greeting if fetch fails
      setMessages([
        {
          role: 'assistant',
          message:
            'Halo Bapak/Ibu Direksi! Silakan ajukan pertanyaan seputar analisis omset, laba kotor, atau performa toko Tokopon Zed.',
        },
      ]);
    }
  };

  const handleSendMessage = async (textToSend?: string) => {
    const message = (textToSend || inputValue).trim();
    if (!message || isLoading) return;

    // Optimistically append user message
    const userMsg: AiChatMessage = {
      role: 'user',
      message,
      created_at: new Date().toISOString(),
    };
    setMessages((prev) => [...prev, userMsg]);
    setInputValue('');
    setIsLoading(true);

    try {
      // Package active dashboard context data
      const contextData = {
        period: overviewData?.kpi?.period || { range: filters.date_range },
        summary: overviewData?.kpi?.summary,
        branches: overviewData?.branches,
        top_products: overviewData?.top_products,
        active_filters: filters,
      };

      const res = await executiveApi.sendAiChat(message, sessionId, contextData);

      if (res.success && res.data) {
        setMessages((prev) => [
          ...prev,
          {
            role: 'assistant',
            message: res.data.reply,
            created_at: res.data.created_at,
          },
        ]);
      } else {
        throw new Error(res.message || 'Gagal memproses pesan.');
      }
    } catch (err: unknown) {
      const errObj = err as { response?: { data?: { message?: string } }; message?: string };
      const errMsg =
        errObj.response?.data?.message || errObj.message || 'Terjadi kesalahan pada AI gateway.';
      setMessages((prev) => [
        ...prev,
        {
          role: 'assistant',
          message: `⚠️ **Gagal terhubung:** ${errMsg}\n\n*Catatan: Pastikan NINEROUTER_API_KEY sudah diisi pada file .env backend.*`,
        },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleClearHistory = async () => {
    if (!window.confirm('Bersihkan riwayat percakapan sesi ini?')) return;
    try {
      await executiveApi.clearAiHistory(sessionId);
      setMessages([
        {
          role: 'assistant',
          message: 'Riwayat percakapan telah dibersihkan. Silakan ajukan pertanyaan baru!',
        },
      ]);
    } catch {
      alert('Gagal membersihkan riwayat chat.');
    }
  };

  const handleCopy = (text: string, idx: number) => {
    navigator.clipboard.writeText(text);
    setCopiedIdx(idx);
    setTimeout(() => setCopiedIdx(null), 2000);
  };

  const activePeriodLabel = filters.date_range?.replace('_', ' ').toUpperCase() || 'BULAN INI';
  const activeBranchLabel = filters.branch || 'Semua Cabang';

  return (
    <>
      {/* Floating Action Button (FAB) */}
      <button
        onClick={() => setIsOpen(true)}
        className={`no-print fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-full bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-xs shadow-2xl shadow-indigo-600/40 border border-indigo-400/40 transition-all duration-300 hover:scale-105 cursor-pointer group ${
          isOpen ? 'opacity-0 pointer-events-none' : 'opacity-100'
        }`}
      >
        <div className="relative">
          <Bot className="w-5 h-5 text-white" />
          <span className="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-400 rounded-full border-2 border-slate-900 animate-pulse" />
        </div>
        <span className="tracking-wide">AI Direksi</span>
        <Sparkles className="w-3.5 h-3.5 text-amber-300 animate-spin-slow" />
      </button>

      {/* Backdrop overlay on mobile */}
      {isOpen && (
        <div
          onClick={() => setIsOpen(false)}
          className="no-print fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 lg:hidden transition-opacity"
        />
      )}

      {/* Slide-over Drawer */}
      <div
        className={`no-print fixed top-0 right-0 h-full w-full sm:w-[440px] bg-slate-950/95 border-l border-slate-800/90 backdrop-blur-2xl z-50 flex flex-col shadow-2xl transition-transform duration-300 ease-in-out ${
          isOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        {/* Drawer Header */}
        <div className="p-4 border-b border-slate-800/80 bg-slate-900/70 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/25">
              <Bot className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm font-bold text-white tracking-tight">
                  Zed Executive AI
                </h3>
                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 uppercase">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                  9router
                </span>
              </div>
              <p className="text-[10px] text-slate-400">
                Intelijen Bisnis Real-time untuk Direksi
              </p>
            </div>
          </div>

          <div className="flex items-center gap-1">
            <button
              onClick={handleClearHistory}
              title="Bersihkan riwayat percakapan"
              className="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <Trash2 className="w-4 h-4" />
            </button>
            <button
              onClick={() => setIsOpen(false)}
              className="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Live Context Strip */}
        <div className="px-4 py-2 bg-slate-900/40 border-b border-slate-800/60 flex items-center justify-between text-[11px] text-slate-400">
          <div className="flex items-center gap-1.5 truncate">
            <Zap className="w-3 h-3 text-indigo-400 shrink-0" />
            <span className="truncate">
              Konteks: <strong className="text-slate-200">{activePeriodLabel}</strong> • {activeBranchLabel}
            </span>
          </div>
          <button
            onClick={() => loadHistory()}
            title="Muat ulang chat"
            className="p-1 hover:text-indigo-400 text-slate-500 transition-colors cursor-pointer shrink-0"
          >
            <RotateCcw className="w-3 h-3" />
          </button>
        </div>

        {/* Messages Stream */}
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {messages.map((msg, idx) => {
            const isUser = msg.role === 'user';
            return (
              <div
                key={idx}
                className={`flex gap-3 text-xs ${isUser ? 'justify-end' : 'justify-start'}`}
              >
                {!isUser && (
                  <div className="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shrink-0 shadow-sm mt-0.5">
                    <Bot className="w-4 h-4" />
                  </div>
                )}

                <div
                  className={`max-w-[85%] rounded-2xl p-3.5 relative group ${
                    isUser
                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 rounded-br-none'
                      : 'bg-slate-900/90 text-slate-200 border border-slate-800/90 rounded-bl-none'
                  }`}
                >
                  {isUser ? (
                    <p className="whitespace-pre-wrap leading-relaxed">{msg.message}</p>
                  ) : (
                    <div className="prose prose-invert prose-xs max-w-none space-y-2 leading-relaxed">
                      <ReactMarkdown>{msg.message}</ReactMarkdown>
                    </div>
                  )}

                  {!isUser && (
                    <button
                      onClick={() => handleCopy(msg.message, idx)}
                      title="Salin jawaban AI"
                      className="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-opacity cursor-pointer"
                    >
                      {copiedIdx === idx ? (
                        <Check className="w-3 h-3 text-emerald-400" />
                      ) : (
                        <Copy className="w-3 h-3" />
                      )}
                    </button>
                  )}
                </div>
              </div>
            );
          })}

          {/* Typing indicator */}
          {isLoading && (
            <div className="flex gap-3 items-start">
              <div className="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white shrink-0">
                <Bot className="w-4 h-4 animate-pulse" />
              </div>
              <div className="bg-slate-900 border border-slate-800 rounded-2xl rounded-bl-none p-3 text-xs text-slate-300 flex items-center gap-2">
                <div className="flex gap-1">
                  <span className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                  <span className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                  <span className="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
                </div>
                <span className="text-[11px] text-slate-400">Menganalisis data via 9router...</span>
              </div>
            </div>
          )}

          <div ref={messagesEndRef} />
        </div>

        {/* Quick Suggestion Prompts */}
        <div className="px-4 py-2 border-t border-slate-800/80 bg-slate-900/40">
          <div className="text-[10px] uppercase font-semibold text-slate-400 mb-1.5">
            Pertanyaan Rekomendasi
          </div>
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
            {QUICK_PROMPTS.map((prompt, idx) => (
              <button
                key={idx}
                onClick={() => handleSendMessage(prompt)}
                disabled={isLoading}
                className="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-indigo-600 hover:text-white border border-slate-700/60 text-slate-300 text-[11px] shrink-0 transition-colors cursor-pointer disabled:opacity-50"
              >
                {prompt}
              </button>
            ))}
          </div>
        </div>

        {/* Input Form */}
        <div className="p-3 border-t border-slate-800/80 bg-slate-900/80">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              handleSendMessage();
            }}
            className="flex items-center gap-2"
          >
            <input
              type="text"
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              placeholder="Tanyakan analisis bisnis direksi..."
              disabled={isLoading}
              className="flex-1 px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <button
              type="submit"
              disabled={isLoading || !inputValue.trim()}
              className="p-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/30 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <Send className="w-4 h-4" />
            </button>
          </form>
        </div>
      </div>
    </>
  );
};
