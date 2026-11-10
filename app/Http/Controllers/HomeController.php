<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Auth;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function index(Request $request)
    {
        $role = Auth::role();

        if ($role === null) {
            return redirect(url('/login'));
        }

        return redirect(url($role->homePath()));
    }
}
