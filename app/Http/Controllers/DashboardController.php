<?php

namespace App\Http\Controllers;


use App\Models\EventRegistration;
use App\Models\Events;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Regular user dashboard
     */
    public function index()
    {
        $user = auth()->user();

        // Get user's registered events
        $registrations = $user->registrations()
                              ->with('event')
                              ->latest()
                              ->take(5)
                              ->get();

        // Get upcoming events
        $upcomingEvents = Events::upcoming()
                               ->take(5)
                               ->get();

        return view('dashboard.index', compact('registrations', 'upcomingEvents'));
    }

    /**
     * Admin dashboard
     */
    public function admin()
    {
        // Get event statistics
        $eventStats = [
            'total' => Events::count(),
            'upcoming' => Events::upcoming()->count(),
            'draft' => Events::where('status', Events::STATUS_DRAFT)->count(),
            'completed' => Events::where('status', Events::STATUS_COMPLETED)->count(),
        ];

        // Get registration statistics
        $registrationStats = [
            'total' => EventRegistration::count(),
            'pending' => EventRegistration::where('status', EventRegistration::STATUS_PENDING)->count(),
            'confirmed' => EventRegistration::where('status', EventRegistration::STATUS_CONFIRMED)->count(),
            'cancelled' => EventRegistration::where('status', EventRegistration::STATUS_CANCELLED)->count(),
        ];

        // Get recent events
        $recentEvents = Event::latest()
                             ->take(5)
                             ->get();

        // Get recent registrations
        $recentRegistrations = EventRegistration::with(['user', 'event'])
                                                ->latest()
                                                ->take(10)
                                                ->get();

        return view('admin.dashboard', compact(
            'eventStats',
            'registrationStats',
            'recentEvents',
            'recentRegistrations'
        ));
    }

    /**
     * Super Admin dashboard
     */
    public function superAdmin()
    {
        // Include everything from admin dashboard
        $eventStats = [
            'total' => Events::ount(),
            'upcoming' => Events::pcoming()->count(),
            'draft' => Events::here('status', Events::STATUS_DRAFT)->count(),
            'completed' => Events::ere('status', Events::STATUS_COMPLETED)->count(),
        ];

        $registrationStats = [
            'total' => EventRegistration::count(),
            'pending' => EventRegistration::where('status', EventRegistration::STATUS_PENDING)->count(),
            'confirmed' => EventRegistration::where('status', EventRegistration::STATUS_CONFIRMED)->count(),
            'cancelled' => EventRegistration::where('status', EventRegistration::STATUS_CANCELLED)->count(),
        ];

        // User statistics
        $userStats = [
            'total' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'users' => User::where('role', User::ROLE_USER)->count(),
        ];

        // Monthly event statistics for the past year
        $monthlyCounts = DB::table('events')
            ->select(DB::raw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count'))
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Most active users (by event registrations)
        $activeUsers = User::select('users.id', 'users.name', DB::raw('COUNT(event_registrations.id) as registration_count'))
            ->leftJoin('event_registrations', 'users.id', '=', 'event_registrations.user_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('registration_count', 'desc')
            ->take(10)
            ->get();

        // Most popular events
        $popularEvents = Events::select('events.id', 'events.title', DB::raw('COUNT(event_registrations.id) as registration_count'))
            ->leftJoin('event_registrations', 'events.id', '=', 'event_registrations.event_id')
            ->groupBy('events.id', 'events.title')
            ->orderBy('registration_count', 'desc')
            ->take(10)
            ->get();

        return view('admin.super-dashboard', compact(
            'eventStats',
            'registrationStats',
            'userStats',
            'monthlyCounts',
            'activeUsers',
            'popularEvents'
        ));
    }
}
