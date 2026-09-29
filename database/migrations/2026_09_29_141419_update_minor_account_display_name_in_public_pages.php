<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replacePageText('privacy', [
            'ينشئ وليّ الأمر حساب الابن المرتبط' => 'ينشئ وليّ الأمر حساب القائد (الابن أو الطالب) المرتبط',
            'أهلية إنشاء حساب القاصر.' => 'أهلية إنشاء حساب القائد لمن هم دون 18 سنة.',
        ]);

        $this->replacePageText('student-accounts', [
            'لا توجد خطوة رمز تحقق منفصلة لإنشاء حساب القاصر.' => 'لا توجد خطوة رمز تحقق منفصلة لإنشاء حساب القائد.',
        ]);
    }

    public function down(): void
    {
        $this->replacePageText('privacy', [
            'ينشئ وليّ الأمر حساب القائد (الابن أو الطالب) المرتبط' => 'ينشئ وليّ الأمر حساب الابن المرتبط',
            'أهلية إنشاء حساب القائد لمن هم دون 18 سنة.' => 'أهلية إنشاء حساب القاصر.',
        ]);

        $this->replacePageText('student-accounts', [
            'لا توجد خطوة رمز تحقق منفصلة لإنشاء حساب القائد.' => 'لا توجد خطوة رمز تحقق منفصلة لإنشاء حساب القاصر.',
        ]);
    }

    /** @param array<string, string> $replacements */
    private function replacePageText(string $key, array $replacements): void
    {
        $page = DB::table('public_pages')->where('key', $key)->first(['content']);

        if ($page === null) {
            return;
        }

        $content = str_replace(array_keys($replacements), array_values($replacements), $page->content);

        if ($content !== $page->content) {
            DB::table('public_pages')->where('key', $key)->update(['content' => $content, 'updated_at' => now()]);
        }
    }
};
