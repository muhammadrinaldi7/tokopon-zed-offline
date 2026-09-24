<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetSchemaTool;
use App\Ai\Tools\RunQueryTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use Stringable;

#[MaxSteps(15)]
class DatabaseAnalyzerAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    protected $adminId;

    public function __construct($adminId = null)
    {
        $this->adminId = $adminId ?? \Illuminate\Support\Facades\Auth::id();
    }

    public function instructions(): Stringable|string
    {
        // Ambil pengaturan dari database
        $setting = \App\Models\AiSetting::first();

        $basePrompt = $setting && $setting->system_prompt
            ? $setting->system_prompt
            : "Anda adalah Agen AI Analis Database profesional untuk sistem aplikasi Tokopon. Tugas Anda adalah menjawab pertanyaan user dengan cara menganalisis database secara akurat.";

        $dbDriver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $dbName = $dbDriver === 'sqlite' ? 'SQLite' : ($dbDriver === 'mysql' ? 'MySQL' : $dbDriver);

        $schemaSummary = $this->getDatabaseSchemaSummary();

        return $basePrompt .
            "\n\nATURAN BAHASA & KONTEKS (SANGAT PENTING):" .
            "\n- Anda WAJIB memberikan jawaban akhir HANYA dalam Bahasa Indonesia yang natural." .
            "\n- DILARANG KERAS menjawab menggunakan bahasa Inggris." .
            "\n- Jelaskan hasil data secara langsung dan natural sesuai pertanyaan user." .
            "\n- JANGAN pernah menyebutkan proses teknis di belakang layar seperti 'tool called', 'query executed', atau format 'JSON' kepada user." .

            "\n\nKAMUS STRUKTUR UNIT BISNIS & ATURAN PRODUK TOKOPON (WAJIB DIPAHAMI):" .
            "\n- UNIT BISNIS 1: Syihab (Produk HP Baru / Retail Utama Resmi, database_source = 'syihab'). Cabang: Banjarbaru, Martapura, Sultan Adam, Veteran, Premium." .
            "\n- UNIT BISNIS 2: GSK Second (Produk HP Second / Bekas, database_source = 'second'). Cabang: GSK - Banjarbaru, GSK - Martapura, GSK - Sultan Adam, GSK - Veteran, GSK - Kayutangi, GSK - Sampit." .
            "\n  * ATURAN MUTLAK: SETIAP PERTANYAAN TERKAIT BARANG SECOND/BEKAS, QUERY SQL WAJIB MEMFILTER `business_unit_id = 2`!" .
            "\n- UNIT BISNIS 3: GSK Distri (Grosir / Distribusi B2B, database_source = 'distri')." .

            "\n\nPANDUAN QUERY STOK, HARGA & LOKASI CABANG:" .
            "\n- HARGA JUAL RESMI: Cari di tabel `product_accurates` (kolom `base_price` untuk harga jual, `base_cost` untuk HPP, `name` untuk nama produk, `item_no` untuk SKU)." .
            "\n- STOK FISIK & LOKASI CABANG (HP IMEI/SN): Cari di tabel `product_serial_numbers` di-JOIN ke tabel `warehouses` on `product_serial_numbers.warehouse_id = warehouses.id`." .
            "\n  * Selalu filter unit siap jual di toko: `product_serial_numbers.status = 'Available'`." .
            "\n  * Hitung kuantitas unit per toko dengan `COUNT(product_serial_numbers.id)` GROUP BY `warehouses.name`." .

            "\n\nATURAN DATABASE & SISTEM:" .
            "\n- Sistem database yang digunakan saat ini adalah **{$dbName}**." .
            "\n- Gunakan sintaks query yang sesuai dengan {$dbName} murni." .

            "\n\nRINGKASAN SKEMA TABEL DATABASE:\n" . $schemaSummary .

            "\n\nLANGKAH WAJIB YANG HARUS ANDA LAKUKAN:" .
            "\n1. Tentukan tabel dan relasi yang tepat dari ringkasan skema di atas." .
            "\n2. Eksekusi query SQL dengan tool 'RunQueryTool'." .
            "\n3. Setelah mendapatkan hasil, jelaskan jawaban secara ramah, lengkap, dan informatif kepada user." .

            "\n\nATURAN KETAT KEAMANAN:" .
            "\n- Anda HANYA BOLEH merancang dan menjalankan query untuk membaca data (seperti SELECT, PRAGMA, atau SHOW)." .
            "\n- Jangan pernah menjalankan query INSERT, UPDATE, DELETE, ALTER, atau DROP.";
    }

    private function getDatabaseSchemaSummary(): string
    {
        return \Illuminate\Support\Facades\Cache::remember('db_schema_summary', 86400, function () {
            try {
                $tables = \Illuminate\Support\Facades\Schema::getTables();
                $schemaStrings = [];

                foreach ($tables as $table) {
                    $tableName = $table['name'];
                    // Abaikan tabel sistem internal Laravel agar token lebih hemat
                    if (in_array($tableName, ['migrations', 'failed_jobs', 'sqlite_sequence', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
                        continue;
                    }

                    $columns = \Illuminate\Support\Facades\Schema::getColumns($tableName);
                    $colNames = array_map(function ($col) {
                        return $col['name'];
                    }, $columns);

                    $schemaStrings[] = "- {$tableName} (" . implode(', ', $colNames) . ")";
                }

                return implode("\n", $schemaStrings);
            } catch (\Exception $e) {
                return "Gagal memuat skema cache.";
            }
        });
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        $messages = [];

        if ($this->adminId) {
            $histories = \App\Models\AiChatHistory::where('admin_id', $this->adminId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            foreach ($histories as $history) {
                // Lewati pesan kosong
                if (empty(trim($history->message))) {
                    continue;
                }

                // Jangan kirim pesan error sistem ke history AI agar tidak bingung
                if (str_starts_with($history->message, 'Error:') || str_starts_with($history->message, '⚠️')) {
                    continue;
                }

                if ($history->role === 'user') {
                    $messages[] = new UserMessage($history->message);
                } else {
                    $messages[] = new \Laravel\Ai\Messages\AssistantMessage($history->message);
                }
            }
        }

        return $messages;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        // Daftarkan tool di sini agar AI bisa menggunakannya
        return [
            new GetSchemaTool(),
            new RunQueryTool(),
        ];
    }
}
