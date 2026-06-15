<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LibrarianController extends Controller
{
    public function index()
    {
        return view('portal.librarian.dashboard');
    }
}
