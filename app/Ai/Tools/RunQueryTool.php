<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RunQueryTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Jalankan query SQL pembacaan data (SELECT, PRAGMA) menggunakan tool ini.';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sql_query' => $schema->string()
                ->description('Query MySQL murni (hanya SELECT atau SHOW) yang akan dieksekusi.')
                ->required(),
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {

        // Mengambil parameter query dari object Request
        $arguments = $request->all();
        $query = $arguments['sql_query'] ?? null;

        if (!$query) {
            return "Error: Parameter sql_query tidak ditemukan.";
        }

        $queryUpper = strtoupper(trim($query));

        if (!str_starts_with($queryUpper, 'SELECT') && !str_starts_with($queryUpper, 'SHOW') && !str_starts_with($queryUpper, 'PRAGMA')) {
            return "Error: Tolak eksekusi! Hanya query pembacaan data (SELECT, SHOW, PRAGMA) yang diizinkan.";
        }

        try {
            // Eksekusi query
            $results = DB::select($query);
            \Illuminate\Support\Facades\Log::info("AI Mengeksekusi Query: " . $query);
            return json_encode($results);
        } catch (\Exception $e) {
            return "Error saat mengeksekusi query: " . $e->getMessage() . ". Periksa kembali nama tabel dan kolom Anda dari skema yang diberikan!";
        }
    }
}
