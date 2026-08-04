<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->after('librarian_id')->constrained('books');
        });

        // Backfill book_id from book_title
        $transactions = DB::select('SELECT id, book_title FROM library_transactions');
        foreach ($transactions as $txn) {
            $book = DB::table('books')->where('title', $txn->book_title)->first();
            if ($book) {
                DB::table('library_transactions')->where('id', $txn->id)->update(['book_id' => $book->id]);
            }
        }

        // Make book_id non-nullable after backfill
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn('book_id');
        });
    }
};
