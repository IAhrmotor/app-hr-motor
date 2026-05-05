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

        DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement("SET SESSION character_set_connection = 'utf8mb4'");
        DB::statement("SET SESSION character_set_results = 'utf8mb4'");
        DB::statement("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

        DB::statement(
            'ALTER TABLE google_business_profile_reviews CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        DB::table('google_business_profile_reviews')
            ->select(['id', 'comment', 'raw_payload'])
            ->orderBy('id')
            ->chunkById(250, function ($reviews): void {
                foreach ($reviews as $review) {
                    $cleanComment = $this->sanitizeReviewComment($review->comment);
                    $rawPayload = $this->sanitizeReviewPayload($review->raw_payload);

                    $rawPayloadJson = null;
                    if ($rawPayload !== null) {
                        $rawPayloadJson = json_encode(
                            $rawPayload,
                            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                        );
                    }

                    $needsUpdate = $review->comment !== $cleanComment
                        || ($review->raw_payload ?? null) !== $rawPayloadJson;

                    if (! $needsUpdate) {
                        continue;
                    }

                    DB::table('google_business_profile_reviews')
                        ->where('id', $review->id)
                        ->update([
                            'comment' => $cleanComment,
                            'raw_payload' => $rawPayloadJson,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function sanitizeReviewPayload(mixed $rawPayload): ?array
    {
        if (is_string($rawPayload)) {
            $decoded = json_decode($rawPayload, true);
            $rawPayload = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($rawPayload)) {
            return null;
        }

        if (array_key_exists('comment', $rawPayload)) {
            $rawPayload['comment'] = $this->sanitizeReviewComment($rawPayload['comment']);
        }

        return $this->sanitizeUtf8Recursive($rawPayload);
    }

    private function sanitizeReviewComment(mixed $comment): ?string
    {
        if (! is_string($comment)) {
            return $comment === null ? null : trim((string) $comment);
        }

        $comment = trim($comment);

        if ($comment === '') {
            return null;
        }

        $parts = preg_split('/\R*\(Translated by Google\)\R*/i', $comment, 2);
        $cleanComment = trim((string) ($parts[0] ?? $comment));

        return $cleanComment === '' ? null : $cleanComment;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeUtf8Recursive(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitizeUtf8Recursive($value);
            }
        }

        return $payload;
    }
};
