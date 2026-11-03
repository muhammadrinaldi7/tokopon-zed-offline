<?php

namespace App\Services;

use App\Models\AiChatHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExecutiveAiService
{
    protected ExecutiveMetricsService $metricsService;
    protected ExecutiveInventoryService $inventoryService;
    protected ExecutiveAiToolRegistry $toolRegistry;
    protected string $apiBase;
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected float $temperature;
    protected int $maxTokens;

    public function __construct(
        ExecutiveMetricsService $metricsService,
        ExecutiveInventoryService $inventoryService,
        ExecutiveAiToolRegistry $toolRegistry
    ) {
        $this->metricsService = $metricsService;
        $this->inventoryService = $inventoryService;
        $this->toolRegistry = $toolRegistry;

        $this->apiBase = rtrim((string) config('services.ninerouter.base_url', 'https://api.9router.com/v1'), '/');
        $this->apiKey = (string) config('services.ninerouter.api_key', '');
        $this->model = (string) config('services.ninerouter.model', 'groq/openai/gpt-oss-120b');
        $this->timeout = (int) config('services.ninerouter.timeout', 90);
        $this->temperature = (float) config('services.ninerouter.temperature', 0.1);
        $this->maxTokens = (int) config('services.ninerouter.max_tokens', 4096);
    }

    /**
     * Build clean, zero-hallucination executive system prompt grounded in server-verified baseline data.
     */
    public function buildSystemPrompt(
        ?string $activeTab = 'overview',
        array $activeFilters = [],
        ?User $user = null,
        string $extraFallbackContext = ''
    ): string {
        $now = now()->translatedFormat('l, d F Y H:i:s');
        $userName = $user ? $user->name : 'Bapak/Ibu Direksi';

        $lockedBuId = $this->toolRegistry->getLockedBusinessUnitId($user);
        $scopeNote = $lockedBuId
            ? "Terikat pada Unit Bisnis ID #{$lockedBuId} sesuai hak akses jabatan."
            : 'Akses Penuh Lintas Unit Bisnis (Direksi / Grup Konsolidasi).';

        $baselineSnapshot = $this->toolRegistry->buildActiveScreenBaseline($activeTab, $activeFilters, $user);
        if (!empty($extraFallbackContext)) {
            $baselineSnapshot .= "\n\n" . $extraFallbackContext;
        }

        return <<<PROMPT
Kamu adalah "Zed Executive Intelligence AI" — Asisten Intelijen Bisnis & Penasihat Strategis resmi untuk jajaran Direksi dan C-Level PT Syihab Store & GSK Group (Tokopon Zed).
Pengguna saat ini: {$userName} ({$scopeNote})
Waktu server saat ini: {$now}

PRINSIP AKURASI & ANTI-HALUSINASI MUTLAK (ZERO-HALLUCINATION POLICY):
1. DILARANG KERAS mengarang, menebak, mengestimasi, atau membulatkan sembarangan angka Rupiah, kuantitas stok, persentase margin, nama kasir, nama salesperson, maupun nomor nota/IMEI yang tidak tertulis eksplisit pada hasil Tool atau Snapshot Server.
2. WAJIB MEMANGGIL TOOL DATA yang tersedia apabila pertanyaan Direksi membutuhkan data di luar Snapshot Layar Aktif — seperti:
   - Audit Void Kasir / Pembatalan Nota / Buyback Overpay (`get_cashier_void_and_buyback_audit`)
   - Peringkat Salesperson / Staf / Kasir (`get_staff_and_salesperson_kpi`)
   - Evaluasi Biaya MDR / Mesin EDC / Metode Pembayaran (`get_payment_and_mdr_breakdown`)
   - Valuasi Persediaan, Nilai Modal Stok & Gudang Accurate Online (`get_accurate_inventory_valuation`)
   - Posisi Kas & Bank, Piutang Usaha (AR), dan Hutang Usaha (AP) Accurate Online (`get_accurate_financial_position`)
   - Piutang Toko vs Piutang Leasing Pending POS (`get_pos_piutang_and_leasing_pending`)
   - Performa Brand, Klaim Subsidi Promo Vendor, atau Penjualan Proyek (`get_brand_promo_and_project_analytics`)
   - Cek Stok/Harga model HP spesifik, Dead Stock, atau Lacak Nomor Seri/IMEI (`search_physical_stock_imei_and_deadstock`)
   - Komparasi lintas periode / bulan / tanggal spesifik (`get_sales_and_branch_performance` — boleh dipanggil lebih dari satu kali untuk membandingkan 2 periode berbeda).
3. ISOLASI FILTER & RIWAYAT PERCAKAPAN: Jika angka pada pesan riwayat chat sebelumnya berasal dari periode waktu atau filter cabang/kategori/proyek yang berbeda dengan permintaan saat ini, ABAIKAN angka lama tersebut dan gunakan murni data terbaru dari Tool / Snapshot Server. Jika suatu metrik bernilai Rp 0 atau kosong, laporkan secara jujur Rp 0 / belum ada transaksi.

STANDAR KOMUNIKASI & FORMATTING TABEL C-LEVEL:
1. GAYA BAHASA: Lugas, tajam, profesional, berorientasi keputusan eksekutif. Hindari basa-basi bertele-tele. Sebutkan filter/periode data yang diacu secara eksplisit di awal agar Direksi yakin pada konteks angka.
2. ATURAN TABEL MARKDOWN (AGAR RAPI DI DESKTOP & MOBILE):
   - Maksimal 4–5 kolom kompak per tabel.
   - Setiap baris tabel WAJIB berada pada baris baru (`\n`), diawali tepat satu pipa (`|`) dan diakhiri tepat satu pipa (`|`). DILARANG memakai pipa ganda (`||` atau `|||`) atau baris kosong di tengah tabel.
   - Jangan gunakan tanda bintang (`**`) di dalam sel baris biasa; penebalan (`**`) hanya boleh pada baris TOTAL di paling bawah.
   - Contoh kerangka format tabel:
     | No | Cabang | Omset Bersih (Share) | Margin (Laba) | Trx |
     |----|--------|----------------------|---------------|-----|
     | 1 | Nama Cabang | Rp XXX.XXX.XXX (XX,X%) | X,X% (Rp XX,X Jt) | XX |
3. STRUKTUR JAWABAN ANALITIS (SESUAIKAN DENGAN PERTANYAAN):
   - **📊 Sorotan Utama**: Ringkasan angka kunci dan konteks periode/filter aktif.
   - Tabel rincian yang rapi dan mudah dipindai (jika relevan).
   - **🏆 Performa Tertinggi** & **⚠️ Area Perlu Perhatian** (anomali margin tipis <5%, beban MDR tinggi, void kasir, atau stok mengendap).
   - **💡 Rekomendasi Tindakan Eksekutif**: Langkah taktis konkret yang siap ditindaklanjuti Direksi.

KAMUS STRUKTUR BISNIS TOKOPON ZED:
- UNIT BISNIS 1 — "Syihab" (`syihab`): Ritel HP/Gadget BARU resmi (Cabang: Banjarbaru, Martapura, Sultan Adam, Veteran, Premium).
- UNIT BISNIS 2 — "GSK Second" (`second`): Spesialis HP Second / Bekas & Tukar Tambah (Cabang berawalan "GSK - "). Setiap pertanyaan stok/penjualan HP Second merujuk ke GSK Second.
- UNIT BISNIS 3 — "GSK Distri" (`distri`): Distribusi / Grosir B2B.
- DEFINISI PIUTANG POS:
  1. "Piutang Toko": Barang diserahkan ke pelanggan langsung dengan status belum lunas (`order_status = PIUTANG`).
  2. "Piutang Leasing / Multi-Finance": Kredit via Kredivo, Akulaku, Home Credit (HCI), Samsung Finance, dll. Jika pembayaran `PENDING`, dana talangan leasing belum cair ke rekening perusahaan.

DATA SNAPSHOT TERVERIFIKASI SERVER SAAT INI:
{$baselineSnapshot}
PROMPT;
    }

    /**
     * Extract normalized active filters & active tab from request inputs (backward-compatible).
     */
    protected function normalizeContextInputs(?string $activeTab, ?array $activeFilters, ?array $legacyContextData): array
    {
        $resolvedTab = $activeTab ?: ($legacyContextData['active_tab'] ?? 'overview');
        $resolvedFilters = !empty($activeFilters)
            ? $activeFilters
            : ($legacyContextData['active_filters'] ?? []);

        if (empty($resolvedFilters['date_range']) && !empty($legacyContextData['period']['range'])) {
            $resolvedFilters['date_range'] = $legacyContextData['period']['range'];
        }
        if (empty($resolvedFilters['start_date']) && !empty($legacyContextData['period']['start_date'])) {
            $resolvedFilters['start_date'] = $legacyContextData['period']['start_date'];
        }
        if (empty($resolvedFilters['end_date']) && !empty($legacyContextData['period']['end_date'])) {
            $resolvedFilters['end_date'] = $legacyContextData['period']['end_date'];
        }

        return [$resolvedTab, is_array($resolvedFilters) ? $resolvedFilters : []];
    }

    /**
     * Build message array with anti-pollution history window.
     */
    protected function buildConversationMessages(
        int $adminId,
        string $sessionId,
        string $systemPrompt
    ): array {
        $pastMessages = AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->reverse();

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($pastMessages as $hist) {
            $messages[] = [
                'role' => $hist->role === 'assistant' ? 'assistant' : 'user',
                'content' => (string) $hist->message,
            ];
        }

        return $messages;
    }

    /**
     * Standard JSON chat completion with multi-turn LLM Tool Calling.
     */
    public function chat(
        string $message,
        string $sessionId,
        int $adminId,
        ?array $legacyContextData = null,
        ?User $user = null,
        ?string $activeTab = null,
        ?array $activeFilters = null
    ): array {
        [$resolvedTab, $resolvedFilters] = $this->normalizeContextInputs($activeTab, $activeFilters, $legacyContextData);

        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $message,
        ]);

        $systemPrompt = $this->buildSystemPrompt($resolvedTab, $resolvedFilters, $user);
        $messages = $this->buildConversationMessages($adminId, $sessionId, $systemPrompt);
        $tools = $this->toolRegistry->getToolDefinitions();

        $reply = $this->executeToolLoopSync($messages, $tools, $message, $resolvedTab, $resolvedFilters, $user);
        $reply = $this->normalizeMarkdownTables($reply);

        $assistantRecord = AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'message' => $reply,
        ]);

        return [
            'reply' => $reply,
            'session_id' => $sessionId,
            'created_at' => $assistantRecord->created_at->format('Y-m-d H:i:s'),
            'model' => $this->model,
        ];
    }

    /**
     * Real-time Server-Sent Events (SSE) streaming chat with live tool execution status updates.
     */
    public function streamChat(
        string $message,
        string $sessionId,
        int $adminId,
        callable $emitEvent,
        ?User $user = null,
        ?string $activeTab = null,
        ?array $activeFilters = null,
        ?array $legacyContextData = null
    ): void {
        [$resolvedTab, $resolvedFilters] = $this->normalizeContextInputs($activeTab, $activeFilters, $legacyContextData);

        $emitEvent([
            'type' => 'status',
            'text' => 'Memverifikasi filter & konteks data aktif...',
        ]);

        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $message,
        ]);

        $systemPrompt = $this->buildSystemPrompt($resolvedTab, $resolvedFilters, $user);
        $messages = $this->buildConversationMessages($adminId, $sessionId, $systemPrompt);
        $tools = $this->toolRegistry->getToolDefinitions();

        $fullReply = '';
        $maxRounds = 3;
        $useTools = true;

        for ($round = 0; $round < $maxRounds; $round++) {
            $emitEvent([
                'type' => 'status',
                'text' => $round === 0 ? 'Menganalisis pertanyaan Direksi...' : 'Menyusun sintesis eksekutif...',
            ]);

            $stepResult = $this->performSingleStreamingRound($messages, $useTools ? $tools : [], function (string $deltaText) use (&$fullReply, $emitEvent) {
                $fullReply .= $deltaText;
                $emitEvent([
                    'type' => 'delta',
                    'text' => $deltaText,
                ]);
            });

            if ($stepResult['tools_unsupported'] && $useTools) {
                // Gateway model rejected `tools`: hydrate server-side fallback and retry without tools
                $useTools = false;
                $emitEvent([
                    'type' => 'status',
                    'text' => 'Mengambil data lintas modul dari server...',
                ]);
                $fallbackData = $this->toolRegistry->hydrateFallbackToolsForQuery($message, $resolvedFilters, $user);
                $messages[0]['content'] = $this->buildSystemPrompt($resolvedTab, $resolvedFilters, $user, $fallbackData);
                continue;
            }

            $toolCalls = $stepResult['tool_calls'] ?? [];
            if (empty($toolCalls)) {
                if ($fullReply === '' && !empty($stepResult['content'])) {
                    $fullReply = $stepResult['content'];
                    $emitEvent([
                        'type' => 'delta',
                        'text' => $fullReply,
                    ]);
                }
                break;
            }

            // Append assistant tool_calls request message
            $messages[] = [
                'role' => 'assistant',
                'content' => $stepResult['content'] ?? '',
                'tool_calls' => array_values($toolCalls),
            ];

            // Execute each requested tool with live status feedback
            foreach ($toolCalls as $tc) {
                $fnName = $tc['function']['name'] ?? '';
                $rawArgs = $tc['function']['arguments'] ?? '{}';
                $decodedArgs = json_decode($rawArgs, true);
                if (!is_array($decodedArgs)) {
                    $decodedArgs = [];
                }

                $emitEvent([
                    'type' => 'status',
                    'text' => $this->toolRegistry->getToolStatusLabel($fnName),
                ]);

                $toolOutput = $this->toolRegistry->executeTool($fnName, $decodedArgs, $resolvedFilters, $user);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'] ?? ('call_' . uniqid()),
                    'name' => $fnName,
                    'content' => $toolOutput,
                ];
            }
        }

        if (trim($fullReply) === '') {
            $fullReply = 'Maaf, asisten tidak menghasilkan respons teks saat ini. Silakan ulangi pertanyaan Anda.';
            $emitEvent([
                'type' => 'delta',
                'text' => $fullReply,
            ]);
        }

        $normalizedReply = $this->normalizeMarkdownTables($fullReply);

        $assistantRecord = AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'message' => $normalizedReply,
        ]);

        $emitEvent([
            'type' => 'done',
            'reply' => $normalizedReply,
            'session_id' => $sessionId,
            'created_at' => $assistantRecord->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Execute synchronous multi-turn tool loop for non-streaming requests.
     */
    protected function executeToolLoopSync(
        array $messages,
        array $tools,
        string $originalMessage,
        ?string $activeTab,
        array $activeFilters,
        ?User $user
    ): string {
        $endpoint = $this->apiBase . '/chat/completions';
        $maxRounds = 3;
        $useTools = true;

        for ($round = 0; $round < $maxRounds; $round++) {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ];

            if ($useTools && !empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $request = Http::timeout($this->timeout);
            if (!empty($this->apiKey)) {
                $request = $request->withToken($this->apiKey);
            }

            $response = $request->post($endpoint, $payload);

            if ($response->failed()) {
                // Graceful fallback if upstream provider rejects `tools` parameter
                if ($useTools && in_array($response->status(), [400, 422], true)) {
                    Log::warning('ExecutiveAiService: Upstream rejected tools parameter, switching to server-side hydrator fallback.', [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 400),
                    ]);
                    $useTools = false;
                    $fallbackData = $this->toolRegistry->hydrateFallbackToolsForQuery($originalMessage, $activeFilters, $user);
                    $messages[0]['content'] = $this->buildSystemPrompt($activeTab, $activeFilters, $user, $fallbackData);
                    continue;
                }

                Log::error('ExecutiveAiService upstream HTTP failure', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 800),
                ]);
                throw new \RuntimeException('Upstream AI gateway error (HTTP ' . $response->status() . ')');
            }

            $toolCalls = $this->extractToolCallsFromResponse($response);
            if (empty($toolCalls)) {
                return $this->extractReplyFromResponse($response);
            }

            $assistantContent = '';
            $json = $response->json();
            if (is_array($json) && isset($json['choices'][0]['message']['content']) && is_string($json['choices'][0]['message']['content'])) {
                $assistantContent = $json['choices'][0]['message']['content'];
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantContent,
                'tool_calls' => array_values($toolCalls),
            ];

            foreach ($toolCalls as $tc) {
                $fnName = $tc['function']['name'] ?? '';
                $rawArgs = $tc['function']['arguments'] ?? '{}';
                $decodedArgs = json_decode($rawArgs, true);
                if (!is_array($decodedArgs)) {
                    $decodedArgs = [];
                }

                $toolResult = $this->toolRegistry->executeTool($fnName, $decodedArgs, $activeFilters, $user);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'] ?? ('call_' . uniqid()),
                    'name' => $fnName,
                    'content' => $toolResult,
                ];
            }
        }

        // Final synthesis call without tools if max tool rounds were reached
        $finalReq = Http::timeout($this->timeout);
        if (!empty($this->apiKey)) {
            $finalReq = $finalReq->withToken($this->apiKey);
        }
        $finalRes = $finalReq->post($endpoint, [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if ($finalRes->failed()) {
            Log::error('ExecutiveAiService final synthesis failure', [
                'status' => $finalRes->status(),
                'body' => substr($finalRes->body(), 0, 500),
            ]);
            throw new \RuntimeException('Upstream AI synthesis error');
        }

        return $this->extractReplyFromResponse($finalRes);
    }

    /**
     * Execute one streaming round against the OpenAI-compatible endpoint, supporting both streamed SSE chunks and plain JSON responses.
     */
    protected function performSingleStreamingRound(array $messages, array $tools, callable $onDelta): array
    {
        $endpoint = $this->apiBase . '/chat/completions';
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $request = Http::timeout($this->timeout)->withOptions(['stream' => true]);
        if (!empty($this->apiKey)) {
            $request = $request->withToken($this->apiKey);
        }

        $response = $request->post($endpoint, $payload);

        if ($response->failed()) {
            $errBody = (string) $response->getBody();
            if (!empty($tools) && in_array($response->status(), [400, 422], true)) {
                Log::warning('ExecutiveAiService stream: tools rejected by upstream, falling back.', [
                    'status' => $response->status(),
                    'body' => substr($errBody, 0, 400),
                ]);
                return ['tools_unsupported' => true, 'tool_calls' => [], 'content' => ''];
            }

            Log::error('ExecutiveAiService stream HTTP error', [
                'status' => $response->status(),
                'body' => substr($errBody, 0, 800),
            ]);
            throw new \RuntimeException('Upstream AI stream error (HTTP ' . $response->status() . ')');
        }

        $psrBody = $response->toPsrResponse()->getBody();
        $buffer = '';
        $rawAll = '';
        $collectedContent = '';
        $toolCallsAccum = [];
        $sawSseDataLine = false;

        while (!$psrBody->eof()) {
            if (connection_aborted()) {
                break;
            }

            $chunk = $psrBody->read(1024);
            if ($chunk === '') {
                usleep(15000);
                continue;
            }

            $rawAll .= $chunk;
            $buffer .= $chunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $newlinePos));
                $buffer = substr($buffer, $newlinePos + 1);

                if ($line === '' || !str_starts_with($line, 'data:')) {
                    continue;
                }

                $sawSseDataLine = true;
                $dataStr = trim(substr($line, 5));
                if ($dataStr === '' || $dataStr === '[DONE]') {
                    continue;
                }

                $decoded = json_decode($dataStr, true);
                if (!is_array($decoded) || !isset($decoded['choices'][0])) {
                    continue;
                }

                $choice = $decoded['choices'][0];
                $delta = $choice['delta'] ?? ($choice['message'] ?? []);

                if (!empty($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                    foreach ($delta['tool_calls'] as $idx => $tcDelta) {
                        $slot = $tcDelta['index'] ?? $idx;
                        if (!isset($toolCallsAccum[$slot])) {
                            $toolCallsAccum[$slot] = [
                                'id' => $tcDelta['id'] ?? ('call_' . uniqid()),
                                'type' => 'function',
                                'function' => [
                                    'name' => '',
                                    'arguments' => '',
                                ],
                            ];
                        }
                        if (!empty($tcDelta['id'])) {
                            $toolCallsAccum[$slot]['id'] = $tcDelta['id'];
                        }
                        if (!empty($tcDelta['function']['name'])) {
                            $toolCallsAccum[$slot]['function']['name'] .= $tcDelta['function']['name'];
                        }
                        if (isset($tcDelta['function']['arguments']) && is_string($tcDelta['function']['arguments'])) {
                            $toolCallsAccum[$slot]['function']['arguments'] .= $tcDelta['function']['arguments'];
                        }
                    }
                }

                if (isset($delta['content']) && is_string($delta['content']) && $delta['content'] !== '') {
                    $collectedContent .= $delta['content'];
                    if (empty($toolCallsAccum)) {
                        $onDelta($delta['content']);
                    }
                }
            }
        }

        // Handle case where proxy buffered response into standard non-SSE JSON object
        if (!$sawSseDataLine && trim($rawAll) !== '') {
            $cleaned = preg_replace('/\s*data:\s*\[DONE\]\s*$/s', '', trim($rawAll));
            $json = json_decode($cleaned, true);
            if (is_array($json) && isset($json['choices'][0]['message'])) {
                $msg = $json['choices'][0]['message'];
                if (!empty($msg['tool_calls']) && is_array($msg['tool_calls'])) {
                    return [
                        'tools_unsupported' => false,
                        'tool_calls' => $msg['tool_calls'],
                        'content' => is_string($msg['content'] ?? null) ? $msg['content'] : '',
                    ];
                }
                $txt = $msg['content'] ?? ($msg['reasoning_content'] ?? '');
                if (is_string($txt) && trim($txt) !== '') {
                    return [
                        'tools_unsupported' => false,
                        'tool_calls' => [],
                        'content' => trim($txt),
                    ];
                }
            }
        }

        return [
            'tools_unsupported' => false,
            'tool_calls' => array_values($toolCallsAccum),
            'content' => $collectedContent,
        ];
    }

    /**
     * Extract tool_calls from a standard JSON chat completion response if present.
     */
    protected function extractToolCallsFromResponse(\Illuminate\Http\Client\Response $response): array
    {
        $body = trim($response->body());
        $cleanedBody = preg_replace('/\s*data:\s*\[DONE\]\s*$/s', '', $body);
        $json = json_decode($cleanedBody, true) ?? $response->json();

        if (is_array($json) && !empty($json['choices'][0]['message']['tool_calls']) && is_array($json['choices'][0]['message']['tool_calls'])) {
            return $json['choices'][0]['message']['tool_calls'];
        }

        return [];
    }

    /**
     * Generate an on-demand 1-click strategic executive summary strictly from server-verified metrics.
     */
    public function generateExecutiveSummary(
        ?array $legacyContextData,
        int $adminId,
        ?User $user = null,
        ?array $activeFilters = null
    ): array {
        [, $resolvedFilters] = $this->normalizeContextInputs('overview', $activeFilters, $legacyContextData);

        $salesBlock = $this->toolRegistry->executeTool('get_sales_and_branch_performance', [], $resolvedFilters, $user);
        $mdrBlock = $this->toolRegistry->executeTool('get_payment_and_mdr_breakdown', [], $resolvedFilters, $user);
        $piutangBlock = $this->toolRegistry->executeTool('get_pos_piutang_and_leasing_pending', [], $resolvedFilters, $user);

        $combinedVerifiedData = implode("\n\n", [$salesBlock, $mdrBlock, $piutangBlock]);
        $systemPrompt = $this->buildSystemPrompt('overview', $resolvedFilters, $user, $combinedVerifiedData);

        $prompt = "Berikan Analisis Eksekutif & Ringkasan Strategis C-Level atas seluruh data terverifikasi server pada periode & filter aktif ini. Sajikan ringkas, tajam, dan akurat dalam 3 bagian terstruktur:\n1. 📊 Sorotan Kinerja Utama (Omset Bersih, Laba Kotor, Transaksi & Cabang Kontributor)\n2. ⚠️ Evaluasi Margin, Beban MDR EDC & Risiko Piutang/Leasing Pending\n3. 💡 Rekomendasi Langkah Strategis untuk Direksi.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];

        $endpoint = $this->apiBase . '/chat/completions';
        $request = Http::timeout($this->timeout);
        if (!empty($this->apiKey)) {
            $request = $request->withToken($this->apiKey);
        }

        $response = $request->post($endpoint, [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if ($response->failed()) {
            Log::error('ExecutiveAiService summarize HTTP failure', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new \RuntimeException('Upstream AI summary error');
        }

        $reply = $this->normalizeMarkdownTables($this->extractReplyFromResponse($response));

        return [
            'reply' => $reply,
            'session_id' => 'executive-summary-' . date('Ymd'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'model' => $this->model,
        ];
    }

    /**
     * Retrieve chat history for given session and director.
     */
    public function getHistory(int $adminId, ?string $sessionId = null, int $limit = 50): Collection
    {
        $targetSession = $sessionId ?: 'exec-drawer-session';

        return AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $targetSession)
            ->orderBy('id', 'asc')
            ->take($limit)
            ->get(['id', 'role', 'message', 'created_at'])
            ->map(function ($item) {
                if ($item->role === 'assistant') {
                    $item->message = $this->normalizeMarkdownTables((string) $item->message);
                }
                return $item;
            });
    }

    /**
     * Clear chat history for given session.
     */
    public function clearHistory(int $adminId, string $sessionId): bool
    {
        AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->delete();

        return true;
    }

    /**
     * Robustly extract textual reply from OpenAI-compatible response.
     */
    protected function extractReplyFromResponse(\Illuminate\Http\Client\Response $response): string
    {
        $body = trim($response->body());
        $cleanedBody = preg_replace('/\s*data:\s*\[DONE\]\s*$/s', '', $body);
        $json = json_decode($cleanedBody, true) ?? $response->json();

        if (is_array($json) && isset($json['choices'][0])) {
            $choice = $json['choices'][0];

            if (isset($choice['message']['content']) && is_string($choice['message']['content']) && trim($choice['message']['content']) !== '') {
                return trim($choice['message']['content']);
            }

            if (isset($choice['message']['content']) && is_array($choice['message']['content'])) {
                $textParts = [];
                foreach ($choice['message']['content'] as $block) {
                    if (is_string($block)) {
                        $textParts[] = $block;
                    } elseif (is_array($block) && isset($block['text'])) {
                        $textParts[] = $block['text'];
                    }
                }
                if (!empty($textParts)) {
                    return trim(implode("\n", $textParts));
                }
            }

            $reasoning = $choice['message']['reasoning_content'] ?? ($choice['message']['reasoning'] ?? null);
            if (!empty($reasoning) && is_string($reasoning) && trim($reasoning) !== '') {
                return trim($reasoning);
            }

            if (!empty($choice['text']) && is_string($choice['text'])) {
                return trim($choice['text']);
            }
        }

        if (str_contains($body, 'data:')) {
            $collectedText = '';
            $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $dataPayload = trim(substr($line, 5));
                if ($dataPayload === '' || $dataPayload === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($dataPayload, true);
                if (is_array($chunk) && isset($chunk['choices'][0]['delta'])) {
                    $delta = $chunk['choices'][0]['delta'];
                    $content = $delta['content'] ?? null;
                    if (is_string($content) && $content !== '') {
                        $collectedText .= $content;
                    }
                }
            }

            if (trim($collectedText) !== '') {
                return trim($collectedText);
            }
        }

        Log::warning('ExecutiveAiService: Response format unexpected or empty content', [
            'status' => $response->status(),
            'body_sample' => substr($body, 0, 500),
        ]);

        return 'Maaf, asisten tidak menghasilkan respons teks saat ini.';
    }

    /**
     * Robustly normalize and repair markdown tables from LLM.
     */
    public function normalizeMarkdownTables(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $lines = explode("\n", $text);
        $processedLines = [];
        $inCodeBlock = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                $inCodeBlock = !$inCodeBlock;
                $processedLines[] = $line;
                continue;
            }

            if ($inCodeBlock) {
                $processedLines[] = $line;
                continue;
            }

            $line = preg_replace('/^\|{2,}\s*/', '| ', $line);
            $line = preg_replace('/\|\s*\|\s*\|\s*/', "|\n| ", $line);
            $line = preg_replace('/\|\s*\|\s*([0-9A-Za-z#\-\:\*\—\–\w])/u', "|\n| $1", $line);
            $line = preg_replace('/\|{2,}\s*/', "|\n| ", $line);

            $subLines = explode("\n", $line);
            foreach ($subLines as $sub) {
                $subTrimmed = trim($sub);
                if (str_contains($subTrimmed, '|') && !str_starts_with($subTrimmed, '>') && !str_starts_with($subTrimmed, '#')) {
                    if (!str_starts_with($subTrimmed, '|')) {
                        $subTrimmed = '| ' . $subTrimmed;
                    }
                    if (!str_ends_with($subTrimmed, '|')) {
                        $subTrimmed = $subTrimmed . ' |';
                    }
                }
                $processedLines[] = $subTrimmed;
            }
        }

        $finalLines = [];
        $inTable = false;
        $inCodeBlock = false;

        for ($i = 0; $i < count($processedLines); $i++) {
            $line = $processedLines[$i];
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                $inCodeBlock = !$inCodeBlock;
                $finalLines[] = $line;
                continue;
            }

            if ($inCodeBlock) {
                $finalLines[] = $line;
                continue;
            }

            $isTableRow = str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|') && strlen($trimmed) > 2;

            if ($isTableRow) {
                if (!$inTable) {
                    if (!empty($finalLines) && end($finalLines) !== '') {
                        $finalLines[] = '';
                    }
                    $inTable = true;
                }
                $finalLines[] = $trimmed;
            } else {
                if ($inTable && $trimmed === '') {
                    $nextIsTable = false;
                    for ($j = $i + 1; $j < count($processedLines); $j++) {
                        $nextTrim = trim($processedLines[$j]);
                        if ($nextTrim === '') {
                            continue;
                        }
                        if (str_starts_with($nextTrim, '|') && str_ends_with($nextTrim, '|')) {
                            $nextIsTable = true;
                        }
                        break;
                    }
                    if ($nextIsTable) {
                        continue;
                    }
                    $inTable = false;
                } elseif ($inTable) {
                    $inTable = false;
                }
                $finalLines[] = $line;
            }
        }

        return implode("\n", $finalLines);
    }
}
