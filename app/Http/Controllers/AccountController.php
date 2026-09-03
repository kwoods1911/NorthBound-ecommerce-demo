<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $account) {}

    public function show(Request $request): Response
    {
        return Inertia::render('account/Index', $this->account->overview($request->user()));
    }
}
