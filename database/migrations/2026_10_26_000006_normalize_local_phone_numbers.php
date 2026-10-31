<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $local = fn (string $phone): string => preg_match('/^\+(970|972)(5[0-9]{8})$/D', $phone, $m) ? '0'.$m[2] : $phone;
        // Preflight uniqueness before changing any account. Never merge two identities.
        foreach (['staff', 'users'] as $table) {
            $seen = [];
            foreach (DB::table($table)->orderBy('id')->get(['id', 'phone']) as $row) {
                $phone = $local($row->phone);
                if (! preg_match('/^05[0-9]{8}$/D', $phone)) {
                    throw new RuntimeException("Unsupported existing local phone in {$table}: account ID {$row->id}. Update the account phone before rerunning migrate.");
                }
                if (isset($seen[$phone])) {
                    throw new RuntimeException("Local phone collision in {$table}: account IDs {$seen[$phone]} and {$row->id}. Resolve the duplicate identities before rerunning migrate.");
                }
                $seen[$phone] = $row->id;
            }
        }
        DB::transaction(function () use ($local) {
            foreach (['staff' => ['phone'], 'users' => ['phone'], 'pickup_addresses' => ['contact_phone'], 'orders' => ['recipient_phone', 'pickup_phone']] as $table => $fields) {
                DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $fields, $local) {
                    foreach ($rows as $row) {
                        $changes = [];
                        foreach ($fields as $field) {
                            $phone = $local($row->$field);
                            if ($phone !== $row->$field) {
                                $changes[$field] = $phone;
                            }
                        }
                        if ($changes) {
                            DB::table($table)->where('id', $row->id)->update($changes);
                        }
                    }
                });
            }
        });
    }

    public function down(): void
    {
        // The original international code cannot be reconstructed safely.
    }
};
