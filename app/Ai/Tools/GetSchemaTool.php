<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSchemaTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Gunakan tool ini untuk mendapatkan daftar kolom beserta tipe datanya dari sebuah tabel di database. Wajib digunakan sebelum merancang query SQL agar tidak salah menebak nama kolom.';
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'table_name' => $schema->string()
                ->description('Nama tabel di database MySQL, misal: users, transactions')
                ->required(),
        ];
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        // Mengambil parameter dari object Request (berdasarkan hasil dd)
        $arguments = $request->all();
        $tableName = $arguments['table_name'] ?? null;

        if (!$tableName) {
            return "Error: Parameter table_name tidak ditemukan.";
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            return "Error: Tabel '{$tableName}' tidak ditemukan di database.";
        }
        // Ambil daftar kolom tabel dan filter data yang penting saja (hemat token)
        $columns = collect(Schema::getColumns($tableName))->map(function ($col) {
            return [
                'name' => $col['name'],
                'type' => $col['type_name'],
            ];
        })->toArray();

        // Ubah array hasil kolom ke format JSON string agar bisa dibaca AI
        \Illuminate\Support\Facades\Log::info("AI Memanggil GetSchemaTool untuk tabel: " . $tableName);
        return json_encode($columns);
    }
}
