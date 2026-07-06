<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LibraryTransaction;
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
        $books = Book::when(request('search'), function ($q) {
            $q->where('title', 'like', '%' . request('search') . '%')
                ->orWhere('author', 'like', '%' . request('search') . '%')
                ->orWhere('isbn', 'like', '%' . request('search') . '%');
        })
        ->orderBy('title')
        ->paginate(20);

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
        ]);

        $data['available_quantity'] = $data['quantity'];

        Book::create($data);

        return redirect()->route('librarian.books')->with('success', 'Book "' . $data['title'] . '" added successfully.');
    }
}
