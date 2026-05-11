<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('google_business_profile_reviews')) {
            return;
        }

        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement("SET SESSION character_set_connection = 'utf8mb4'");
        DB::statement("SET SESSION character_set_results = 'utf8mb4'");
        DB::statement("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

        DB::statement(
            'ALTER TABLE google_business_profile_reviews CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        DB::statement(
            <<<'SQL'
UPDATE google_business_profile_reviews
SET
    comment = CASE
        WHEN JSON_VALID(raw_payload) = 0 THEN comment
        WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment')) IS NULL THEN NULL
        WHEN LOCATE('(Translated by Google)', JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment'))) > 0
            THEN TRIM(SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment')), '(Translated by Google)', 1))
        ELSE JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment'))
    END,
    raw_payload = CASE
        WHEN raw_payload IS NULL THEN NULL
        WHEN JSON_VALID(raw_payload) = 0 THEN raw_payload
        ELSE JSON_SET(
            raw_payload,
            '$.comment',
            CASE
                WHEN JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment')) IS NULL THEN NULL
                WHEN LOCATE('(Translated by Google)', JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment'))) > 0
                    THEN TRIM(SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment')), '(Translated by Google)', 1))
                ELSE JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment'))
            END
        )
    END,
    updated_at = CURRENT_TIMESTAMP
WHERE
    JSON_VALID(raw_payload) = 1
    AND JSON_UNQUOTE(JSON_EXTRACT(raw_payload, '$.comment')) LIKE '%(Translated by Google)%'
SQL
        );
    }

    public function down(): void
    {
        //
    }
};
