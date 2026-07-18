<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LibraryTransaction;
use App\Models\Student;
use Illuminate\Http\Request;

class LibrarianController extends Controller
{
    public function index()
    {
        $totalBooks = Book::count();
        $availableBooks = Book::sum('available_quantity');
        $borrowedBooks = LibraryTransaction::where('status', 'Borrowed')->count();
        $overdueBooks = LibraryTransaction::where('status', 'Borrowed')
            ->where('return_date', '<', now())
            ->count();

        $recentTransactions = LibraryTransaction::with('student')
            ->latest('borrow_date')
            ->take(5)
            ->get();

        return view('portal.librarian.dashboard', compact('totalBooks', 'availableBooks', 'borrowedBooks', 'overdueBooks', 'recentTransactions'));
    }

    public function books()
    {
        $query = Book::query();

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
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
            'publisher' => 'nullable|string|max:255',
            'year_published' => 'nullable|integer|min:1900|max:' . date('Y'),
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ]);

        $data['available_quantity'] = $data['quantity'];

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

    // ─── Loan Management ────────────────────────────────────────
    public function loans()
    {
        $query = LibraryTransaction::with('student');

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('overdue') === '1') {
            $query->where('status', 'Borrowed')
                ->where('return_date', '<', now());
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('book_title', 'like', "%{$search}%")
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
        $books = Book::where('available_quantity', '>', 0)->orderBy('title')->get();
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
        ]);

        $book = Book::find($data['book_id']);
        if ($book->available_quantity <= 0) {
            return back()->with('error', 'This book is not available for borrowing.');
        }

        LibraryTransaction::create([
            'student_id' => $data['student_id'],
            'librarian_id' => auth()->id(),
            'book_title' => $book->title,
            'borrow_date' => $data['borrow_date'],
            'return_date' => $data['return_date'],
            'status' => 'Borrowed',
        ]);

        $book->decrement('available_quantity');

        return redirect()->route('librarian.loans')->with('success', 'Book borrowed successfully.');
    }

    public function returnBook(LibraryTransaction $transaction)
    {
        $transaction->status = 'Returned';
        $transaction->save();

        $book = Book::where('title', $transaction->book_title)->first();
        if ($book) {
            $book->increment('available_quantity');
        }

        return back()->with('success', 'Book returned successfully.');
    }
}
