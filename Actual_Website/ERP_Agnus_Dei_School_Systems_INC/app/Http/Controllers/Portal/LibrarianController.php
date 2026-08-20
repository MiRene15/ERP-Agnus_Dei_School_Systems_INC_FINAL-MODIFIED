<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LibraryTransaction;
use App\Models\LibraryVisit;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibrarianController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $totalBooks = Book::where('is_active', true)->count();
        $availableBooks = Book::where('is_active', true)->sum('available_quantity');
        $borrowedBooks = LibraryTransaction::where('status', 'Borrowed')->count();
        $overdueBooks = LibraryTransaction::where('status', 'Borrowed')
            ->where('return_date', '<', now())
            ->count();

        $recentTransactions = LibraryTransaction::with('student', 'book')
            ->latest('borrow_date')
            ->take(5)
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.librarian.partials.dashboard-results', compact('totalBooks', 'availableBooks', 'borrowedBooks', 'overdueBooks', 'recentTransactions'))->render(),
            ]);
        }

        return view('portal.librarian.dashboard', compact('totalBooks', 'availableBooks', 'borrowedBooks', 'overdueBooks', 'recentTransactions'));
    }

    // ─── Book Management ─────────────────────────────────────────
    public function books()
    {
        $query = Book::query();

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if (request('publisher')) {
            $query->where('publisher', 'like', '%' . request('publisher') . '%');
        }

        if (request('availability') === 'available') {
            $query->where('available_quantity', '>', 0);
        } elseif (request('availability') === 'unavailable') {
            $query->where('available_quantity', 0);
        }

        if (request('active') === 'inactive') {
            $query->where('is_active', false);
        } elseif (request('active') !== 'all') {
            $query->where('is_active', true);
        }

        $books = $query->orderBy('title')->paginate(20)->withQueryString();

        return view('portal.librarian.books', compact('books'));
    }

    public function createBook()
    {
        return view('portal.librarian.create-book');
    }

    public function storeBook(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'serial_number' => 'nullable|string|max:50|unique:books,serial_number',
            'publisher' => 'nullable|string|max:255',
            'year_published' => 'nullable|integer|min:1900|max:' . date('Y'),
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ]);

        $data['available_quantity'] = $data['quantity'];
        $data['is_active'] = true;

        Book::create($data);

        return redirect()->route('librarian.books')->with('success', 'Book "' . $data['title'] . '" added successfully.');
    }

    public function editBook(Book $book)
    {
        return view('portal.librarian.edit-book', compact('book'));
    }

    public function updateBook(Request $request, Book $book)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $book->id,
            'serial_number' => 'nullable|string|max:50|unique:books,serial_number,' . $book->id,
            'publisher' => 'nullable|string|max:255',
            'year_published' => 'nullable|integer|min:1900|max:' . date('Y'),
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ]);

        $quantityDiff = $data['quantity'] - $book->quantity;
        $data['available_quantity'] = max(0, $book->available_quantity + $quantityDiff);

        $book->update($data);

        return redirect()->route('librarian.books')->with('success', 'Book "' . $book->title . '" updated.');
    }

    public function destroyBook(Book $book)
    {
        $book->delete();
        return back()->with('success', 'Book deleted.');
    }

    // ─── Inactive Books ──────────────────────────────────────────
    public function inactiveBooks(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Book::where('is_active', false);

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $books = $query->orderBy('inactive_at', 'desc')->paginate(20)->withQueryString();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.librarian.partials.inactive-logs-results', compact('books'))->render(),
            ]);
        }

        return view('portal.librarian.inactive-logs', compact('books'));
    }

    public function deactivateBook(Request $request, Book $book)
    {
        $data = $request->validate([
            'inactive_reason' => 'required|string|max:500',
        ]);

        $book->update([
            'is_active' => false,
            'inactive_reason' => $data['inactive_reason'],
            'inactive_at' => now(),
            'deactivated_by' => auth()->id(),
        ]);

        log_activity($book, 'Deactivated', 'Book "' . $book->title . '" deactivated: ' . $data['inactive_reason']);

        return back()->with('success', 'Book "' . $book->title . '" deactivated.');
    }

    public function reactivateBook(Book $book)
    {
        $book->update([
            'is_active' => true,
            'inactive_reason' => null,
            'inactive_at' => null,
            'deactivated_by' => null,
        ]);

        log_activity($book, 'Reactivated', 'Book "' . $book->title . '" reactivated.');

        return back()->with('success', 'Book "' . $book->title . '" reactivated.');
    }

    // ─── Loan Management ────────────────────────────────────────
    public function loans()
    {
        $query = LibraryTransaction::with('student', 'book');

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('overdue') === '1') {
            $query->where('status', 'Borrowed')
                ->where('return_date', '<', now());
        }

        if (request('date_from')) {
            $query->whereDate('borrow_date', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('borrow_date', '<=', request('date_to'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('book_title', 'like', "%{$search}%")
                    ->orWhereHas('book', function ($bq) use ($search) {
                        $bq->where('title', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query->latest('borrow_date')->paginate(20)->withQueryString();

        return view('portal.librarian.loans', compact('transactions'));
    }

    public function borrowForm()
    {
        $books = Book::where('is_active', true)->where('available_quantity', '>', 0)->orderBy('title')->get();
        $students = Student::with('user')->orderBy('last_name')->get();
        return view('portal.librarian.borrow', compact('books', 'students'));
    }

    public function storeBorrow(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'book_id' => 'required|exists:books,id',
            'borrow_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:borrow_date',
            'condition_at_borrow' => 'required|in:Good,Minor Damage,Major Damage,Lost',
        ]);

        $book = Book::find($data['book_id']);
        if ($book->available_quantity <= 0) {
            return back()->with('error', 'This book is not available for borrowing.');
        }

        LibraryTransaction::create([
            'student_id' => $data['student_id'],
            'librarian_id' => auth()->id(),
            'book_id' => $book->id,
            'book_title' => $book->title,
            'borrow_date' => $data['borrow_date'],
            'return_date' => $data['return_date'],
            'status' => 'Borrowed',
            'condition_at_borrow' => $data['condition_at_borrow'],
        ]);

        $book->decrement('available_quantity');

        return redirect()->route('librarian.loans')->with('success', 'Book borrowed successfully.');
    }

    public function searchStudents(Request $request)
    {
        $search = $request->input('search');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $students = Student::where('status', 'enrolled')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%")
                    ->orWhere('legacy_lrn', 'like', "%{$search}%");
            })
            ->select('id', 'first_name', 'last_name', 'student_number', 'legacy_lrn')
            ->limit(10)
            ->get();

        return response()->json($students);
    }

    public function returnForm(LibraryTransaction $transaction)
    {
        return view('portal.librarian.return-form', compact('transaction'));
    }

    public function processReturn(Request $request, LibraryTransaction $transaction)
    {
        $data = $request->validate([
            'condition_at_return' => 'required|in:Good,Minor Damage,Major Damage,Lost',
            'damage_notes' => 'nullable|string|max:500',
        ]);

        $transaction->update([
            'status' => 'Returned',
            'returned_at' => now(),
            'condition_at_return' => $data['condition_at_return'],
            'damage_notes' => $data['damage_notes'] ?? null,
        ]);

        $transaction->calculateFees(
            (float) (\App\Models\Setting::getValue('late_fee_per_day', '5.00')),
            [
                'minor' => (float) (\App\Models\Setting::getValue('damage_fee_minor', '50.00')),
                'major' => (float) (\App\Models\Setting::getValue('damage_fee_major', '200.00')),
                'lost' => (float) ($transaction->book?->price ?? 500.00),
            ]
        );
        $transaction->save();

        if ($transaction->total_fees > 0) {
            $student = $transaction->student;
            $ledger = $student->ledger;
            if ($ledger) {
                $ledger->total_assessed += $transaction->total_fees;
                $ledger->balance += $transaction->total_fees;
                $ledger->save();
            } else {
                \App\Models\StudentLedger::create([
                    'student_id' => $student->id,
                    'payment_plan' => 'installment',
                    'total_assessed' => $transaction->total_fees,
                    'total_paid' => 0,
                    'balance' => $transaction->total_fees,
                ]);
            }
            $transaction->update(['fees_assessed' => true]);
        }

        $book = $transaction->book;
        if ($book) {
            $book->increment('available_quantity');
        }

        log_activity($transaction, 'Book Returned', 'Book "' . $transaction->book_title . '" returned. Fees: ₱' . number_format($transaction->total_fees, 2));

        return back()->with('success', 'Book returned successfully. Fees assessed: ₱' . number_format($transaction->total_fees, 2));
    }

    // ─── Library Visits ──────────────────────────────────────────
    public function visits(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = LibraryVisit::with('student', 'librarian');

        if (request('search')) {
            $search = request('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (request('date_from')) {
            $query->whereDate('time_in', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('time_in', '<=', request('date_to'));
        }

        $visits = $query->latest('time_in')->paginate(20)->withQueryString();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.librarian.partials.visits-results', compact('visits'))->render(),
            ]);
        }

        return view('portal.librarian.visits', compact('visits'));
    }

    public function clockIn(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::find($data['student_id']);
        $existingVisit = LibraryVisit::where('student_id', $student->id)
            ->whereNull('time_out')
            ->first();

        if ($existingVisit) {
            return back()->with('error', $student->first_name . ' ' . $student->last_name . ' is already clocked in.');
        }

        LibraryVisit::create([
            'student_id' => $student->id,
            'librarian_id' => auth()->id(),
            'time_in' => now(),
        ]);

        return back()->with('success', $student->first_name . ' ' . $student->last_name . ' clocked in.');
    }

    public function clockOut(LibraryVisit $visit)
    {
        $visit->update(['time_out' => now()]);
        return back()->with('success', 'Student clocked out.');
    }

    // ─── JSON Search Endpoints ──────────────────────────────────
    public function searchBooks(Request $request)
    {
        $query = Book::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('serial_number')) {
            $query->where('serial_number', 'like', '%' . $request->serial_number . '%');
        }

        if ($request->filled('publisher')) {
            $query->where('publisher', 'like', '%' . $request->publisher . '%');
        }

        if ($request->input('availability') === 'available') {
            $query->where('available_quantity', '>', 0);
        } elseif ($request->input('availability') === 'unavailable') {
            $query->where('available_quantity', 0);
        }

        if ($request->input('active') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->input('active') !== 'all') {
            $query->where('is_active', true);
        }

        if ($request->filled('year_from')) {
            $query->where('year_published', '>=', (int) $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('year_published', '<=', (int) $request->year_to);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->price_max);
        }

        $books = $query->orderBy('title')->paginate(20);

        return response()->json($books);
    }

    public function searchLoans(Request $request)
    {
        $query = LibraryTransaction::with('student', 'book');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('book_title', 'like', "%{$search}%")
                    ->orWhereHas('book', function ($bq) use ($search) {
                        $bq->where('title', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->input('overdue') === '1') {
            $query->where('status', 'Borrowed')
                ->where('return_date', '<', now());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('borrow_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('borrow_date', '<=', $request->date_to);
        }

        $transactions = $query->latest('borrow_date')->paginate(20);

        return response()->json($transactions);
    }
}
