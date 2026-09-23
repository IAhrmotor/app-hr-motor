<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Contracts\View\View;

class ContactController extends Controller
{
    public function show(Contact $contact): View
    {
        return view('contacts.show', compact('contact'));
    }
}
