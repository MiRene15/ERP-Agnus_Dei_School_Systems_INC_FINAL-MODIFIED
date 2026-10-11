<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
use App\Models\LibraryTransaction;
use Illuminate\Support\Collection;

class LibraryReportService
{
    /**
     * Shared library read for the directress tab and the librarian single.
     * Same live source both screens total, so the numbers always agree.
     *
     * Spec cashier-fee-rules.md: the Librarian views read the same Cashier
     * receipt — paid dues (paid_payment_id set) read paid; unpaid dues stay due.
     *
     * @return array{
     *     totalBooks: int,
     *     totalCopies: int,
     *     availableCopies: int,
     *     borrowedCount: int,
     *     overdueCount: int,
     *     totalTransactions: int,
     *     totalFines: float,
     *     unpaidFines: float,
     *     libraryCollected: float,
     *     recentTransactions: Collection,
     *     popularBooks: Collection
     * }
     */
    public function libraryData(): array
    {
        $totalBooks = Book::where('is_active', true)->count();
        $totalCopies = (int) Book::where('is_active', true)->sum('quantity');
        $availableCopies = (int) Book::where('is_active', true)->sum('available_quantity');
        $borrowedCount = LibraryTransaction::where('status', 'Borrowed')->count();
        $overdueCount = LibraryTransaction::where('status', 'Borrowed')
            ->where('return_date', '<', now())
            ->where('return_date', '>', '1970-01-02')
            ->whereNotNull('return_date')
            ->count();
        $totalTransactions = LibraryTransaction::count();
        $totalFines = (float) LibraryTransaction::where('fees_assessed', true)->sum('total_fees');
        $unpaidFines = (float) LibraryTransaction::where('fees_assessed', true)->whereNull('paid_payment_id')->sum('total_fees');
        $libraryCollected = round($totalFines - $unpaidFines, 2);

        $recentTransactions = LibraryTransaction::with(['student', 'book', 'paidPayment'])
            ->latest('borrow_date')
            ->take(10)
            ->get();

        $popularBooks = Book::withCount('borrowings')
            ->where('is_active', true)
            ->orderByDesc('borrowings_count')
            ->take(5)
            ->get();

        return [
            'totalBooks' => $totalBooks,
            'totalCopies' => $totalCopies,
            'availableCopies' => $availableCopies,
            'borrowedCount' => $borrowedCount,
            'overdueCount' => $overdueCount,
            'totalTransactions' => $totalTransactions,
            'totalFines' => $totalFines,
            'unpaidFines' => $unpaidFines,
            'libraryCollected' => $libraryCollected,
            'recentTransactions' => $recentTransactions,
            'popularBooks' => $popularBooks,
        ];
    }
}
