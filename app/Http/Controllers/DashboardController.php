<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $metrics = new DashboardMetrics($user);

        return view('dashboard', [
            'user' => $user,
            'role' => $user->role,
            'services' => $user->role->dashboardServices(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'metrics' => $metrics->forRole(),
            'canAssignDuties' => $metrics->canAssignDuties(),
        ]);
    }
}
