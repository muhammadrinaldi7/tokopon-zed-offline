<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 4000);
    ">

    <!-- Alpine Toast Notification -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="mb-6 px-4 py-3 rounded-2xl border flex items-center gap-3 text-sm font-medium shadow-md transition-all sticky top-4 z-50 backdrop-blur-md"
        :class="alert?.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800' :
            'bg-red-500/10 border-red-500/30 text-red-800'">
        <svg x-show="alert?.type === 'success'" class="w-5 h-5 text-emerald-600 shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg x-show="alert?.type === 'error'" class="w-5 h-5 text-red-600 shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="alert?.message"></span>
    </div>

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center shadow-xs">
                    <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397 0 11.983 0c3.192.001 6.192 1.242 8.447 3.498c2.256 2.255 3.497 5.255 3.497 8.447c-.004 6.585-5.342 11.93-11.93 11.93c-2.002-.001-3.973-.503-5.729-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451c5.436 0 9.86-4.42 9.864-9.858c.002-2.634-1.023-5.11-2.887-6.974c-1.864-1.864-4.341-2.887-6.973-2.889c-5.44 0-9.865 4.42-9.869 9.859c-.001 1.706.469 3.372 1.36 4.866l-.993 3.626l3.71-.973zm11.233-6.17c-.3-.149-1.774-.875-2.046-.974c-.272-.1-.471-.149-.669.149c-.198.299-.768.974-.941 1.173c-.173.199-.347.224-.647.075c-.3-.15-1.266-.466-2.41-1.487c-.89-.794-1.49-1.774-1.664-2.073c-.173-.3-.018-.462.13-.61c.134-.133.298-.348.446-.521c.15-.173.199-.298.298-.497c.099-.198.05-.372-.025-.521c-.075-.149-.669-1.612-.916-2.207c-.242-.579-.487-.501-.669-.51l-.57-.01c-.199 0-.52.074-.792.372c-.272.297-1.04 1.016-1.04 2.479c0 1.462 1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487c.709.306 1.263.489 1.694.626c.712.226 1.36.194 1.872.118c.571-.085 1.774-.726 2.022-1.392c.247-.667.247-1.241.173-1.392c-.074-.15-.272-.249-.571-.398z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Integrasi Gateway WhatsApp</h1>
                    <p class="text-xs text-gray-500">Pilih penyedia gateway aktif untuk pengiriman nota transaksi otomatis ke WhatsApp pelanggan.</p>
                </div>
            </div>
        </div>

        <!-- Quick Status Badge -->
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-2xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500">Gateway Aktif:</span>
            @if ($activeGateway === 'crm')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    Zed CRM WhatsApp
                </span>
            @elseif ($activeGateway === 'qontak')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Mekari Qontak
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    WhatsApp Dinonaktifkan (Hanya Email)
                </span>
            @endif
        </div>
    </div>

    <!-- Section 1: Selector Gateway Aktif -->
    <div class="mb-8">
        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
            <span>Pilih Mode / Gateway WhatsApp</span>
            <span class="text-[11px] font-normal text-gray-500 lowercase">(kasir dan sistem otomatis akan menyesuaikan pilihan ini)</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Option 1: Zed CRM WhatsApp -->
            <label class="relative flex flex-col p-6 bg-white rounded-3xl border-2 cursor-pointer transition-all duration-200 shadow-xs hover:shadow-md {{ $activeGateway === 'crm' ? 'border-teal-500 ring-4 ring-teal-500/10 bg-teal-50/20' : 'border-gray-200 hover:border-gray-300' }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-teal-500 text-white flex items-center justify-center font-black text-lg shadow-sm">
                            ZED
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-base">Zed CRM WA</h3>
                                <span class="bg-teal-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Aktif</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">CRM WhatsApp Zed Group</p>
                        </div>
                    </div>
                    <input type="radio" name="activeGateway" value="crm" wire:model.live="activeGateway" class="w-5 h-5 text-teal-600 focus:ring-teal-500 focus:ring-2">
                </div>

                <div class="mt-2 space-y-2 text-xs text-gray-600 border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Upload PDF langsung (aman server kasir lokal)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Proteksi duplikasi (Idempotency Key)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Autentikasi ringkas (Bearer Token)</span>
                    </div>
                </div>
            </label>

            <!-- Option 2: Mekari Qontak -->
            <label class="relative flex flex-col p-6 bg-white rounded-3xl border-2 cursor-pointer transition-all duration-200 shadow-xs hover:shadow-md {{ $activeGateway === 'qontak' ? 'border-emerald-500 ring-4 ring-emerald-500/10 bg-emerald-50/20' : 'border-gray-200 hover:border-gray-300' }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg shadow-sm">
                            Q
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-base">Mekari Qontak</h3>
                                <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Official</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Mekari Qontak Direct Broadcast</p>
                        </div>
                    </div>
                    <input type="radio" name="activeGateway" value="qontak" wire:model.live="activeGateway" class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 focus:ring-2">
                </div>

                <div class="mt-2 space-y-2 text-xs text-gray-600 border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>API resmi Mekari Qontak Broadcast</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Autentikasi HMAC-SHA256 signature</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Kirim via URL publik file PDF struk</span>
                    </div>
                </div>
            </label>

            <!-- Option 3: Matikan WhatsApp (Hanya Email) -->
            <label class="relative flex flex-col p-6 bg-white rounded-3xl border-2 cursor-pointer transition-all duration-200 shadow-xs hover:shadow-md {{ $activeGateway === 'none' ? 'border-rose-500 ring-4 ring-rose-500/10 bg-rose-50/20' : 'border-gray-200 hover:border-gray-300' }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center font-black text-lg shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-base">Matikan WhatsApp</h3>
                                <span class="bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Hanya Email</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Nonaktifkan semua pengiriman WhatsApp</p>
                        </div>
                    </div>
                    <input type="radio" name="activeGateway" value="none" wire:model.live="activeGateway" class="w-5 h-5 text-rose-600 focus:ring-rose-500 focus:ring-2">
                </div>

                <div class="mt-2 space-y-2 text-xs text-gray-600 border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Kasir hanya melihat tombol Email dan Cetak Struk</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Otomatisasi WhatsApp transaksi POS dihentikan sementara</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Dapat dinyalakan kembali kapan saja tanpa hilang settingan</span>
                    </div>
                </div>
            </label>
        </div>
    </div>

    <!-- Section 2: Aturan Otomasi Pengiriman (Auto-Send Rules) -->
    <div class="mb-8 bg-white rounded-3xl border border-gray-200 p-6 md:p-8 shadow-xs">
        <h2 class="text-base font-bold text-gray-900 mb-1">Pengaturan Pengiriman Otomatis</h2>
        <p class="text-xs text-gray-500 mb-6">Tentukan kapan nota otomatis dikirimkan ke WhatsApp customer tanpa kasir perlu mengklik tombol kirim manual.</p>

        <div class="divide-y divide-gray-100">
            <!-- Rule 1: Order POS -->
            <div class="py-4 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Otomatis Kirim Nota Penjualan Kasir (POS Order)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Nota langsung dikirim sesaat setelah kasir menyelesaikan transaksi pembayaran.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="autoSendOrder" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Rule 2: Sell Phone Buyback -->
            <div class="py-4 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Otomatis Kirim Tanda Terima Buyback (Sell Phone)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Tanda terima buyback langsung dikirim saat pengajuan HP selesai disetujui / diselesaikan.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="autoSendSellphone" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Rule 3: Payment Proof Sell Phone -->
            <div class="py-4 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Fitur Kirim Bukti Pembayaran Buyback via WA</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Mengaktifkan tombol kirim bukti transfer ke WhatsApp customer di halaman detail Sell Phone (/sell-phone/{id}/detail).</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="enablePaymentProof" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Section 3: Live Health Check (Ping Tool) -->
    <div class="mb-8 bg-white rounded-3xl border border-gray-200 p-6 md:p-8 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-base font-bold text-gray-900">Uji Koneksi Server (Live Health Check)</h2>
                <p class="text-xs text-gray-500 mt-0.5">Periksa apakah endpoint CRM WhatsApp Mas Zaini sedang online dan siap menerima data.</p>
            </div>
            <button wire:click="testPing" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-2xl transition disabled:opacity-50 shadow-sm cursor-pointer">
                <svg wire:loading.remove wire:target="testPing" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg wire:loading wire:target="testPing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Test Koneksi (Ping)</span>
            </button>
        </div>

        @if($pingResult)
            <div class="p-5 rounded-2xl border transition-all {{ ($pingResult['success'] ?? false) ? 'bg-emerald-50/50 border-emerald-200' : 'bg-red-50/50 border-red-200' }}">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ ($pingResult['success'] ?? false) ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                        @if($pingResult['success'] ?? false)
                            ✓
                        @else
                            ✕
                        @endif
                    </span>
                    <h3 class="font-bold text-sm {{ ($pingResult['success'] ?? false) ? 'text-emerald-900' : 'text-red-900' }}">
                        {{ ($pingResult['success'] ?? false) ? 'Server Online & Siap Digunakan (HTTP 200 OK)' : 'Koneksi Gagal / Server Offline' }}
                    </h3>
                </div>
                <div class="text-xs space-y-1 font-mono bg-white/80 p-3 rounded-xl border border-gray-200/50">
                    <p><span class="text-gray-400 font-sans">Pesan:</span> {{ $pingResult['message'] ?? '-' }}</p>
                    @if(isset($pingResult['status_code']))
                        <p><span class="text-gray-400 font-sans">Kode HTTP:</span> {{ $pingResult['status_code'] }}</p>
                    @endif
                    @if(isset($pingResult['response']))
                        <p class="text-gray-400 font-sans mt-2">Payload Respon:</p>
                        <pre class="text-[11px] overflow-x-auto text-gray-700 bg-gray-50 p-2 rounded-lg">{{ json_encode($pingResult['response'], JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </div>
            </div>
        @else
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 text-center text-xs text-gray-500">
                Klik tombol "Test Koneksi (Ping)" di atas untuk memeriksa kesiapan endpoint CRM secara langsung.
            </div>
        @endif
    </div>

    <!-- Section 4: Detail Konfigurasi Environment -->
    <div class="bg-gray-50 rounded-3xl border border-gray-200 p-6 md:p-8">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Ringkasan Konfigurasi Sistem (.env)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs font-mono">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-2">
                <p class="font-bold font-sans text-teal-800 text-sm mb-2">Konfigurasi Zed CRM</p>
                <p><span class="text-gray-400">Endpoint:</span> <span class="break-all">{{ $crmConfig['api_url'] }}</span></p>
                <p><span class="text-gray-400">Token:</span> {{ $crmConfig['token'] }}</p>
                <p><span class="text-gray-400">Channel ID:</span> {{ $crmConfig['channel_id'] }}</p>
                <p><span class="text-gray-400">Template ID:</span> {{ $crmConfig['template_id'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-2">
                <p class="font-bold font-sans text-emerald-800 text-sm mb-2">Konfigurasi Mekari Qontak</p>
                <p><span class="text-gray-400">Endpoint:</span> <span class="break-all">{{ $qontakConfig['api_url'] }}</span></p>
                <p><span class="text-gray-400">Client ID:</span> {{ $qontakConfig['client_id'] ?: '(Belum diatur)' }}</p>
                <p><span class="text-gray-400">Channel ID:</span> {{ $qontakConfig['channel_id'] }}</p>
                <p><span class="text-gray-400">Template ID:</span> {{ $qontakConfig['template_id'] }}</p>
            </div>
        </div>
    </div>
</div>
