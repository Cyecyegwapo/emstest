<?php

namespace App\Http\Controllers;

use App\Models\Events;
use App\Models\EventCategory;
use App\Models\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request)
    {
        $query = Events::where('status', Events::STATUS_PUBLISHED);

        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Filter by search term
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $events = $query->latest()->paginate(12);
        $categories = EventCategory::where('status', true)->get();

        return view('events.index', compact('events', 'categories'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        $categories = EventCategory::where('status', true)->get();
        return view('admin.events.create', compact('categories'));
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:event_categories,id',
            'featured_image' => 'nullable|image|max:2048',
            'status' => 'required|in:' . implode(',', [
                Events::STATUS_DRAFT,
                Events::STATUS_PUBLISHED
            ]),
        ]);

        // Handle file upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('events', 'public');
            $validated['featured_image'] = $path;
        }

        // Add the creator
        $validated['created_by'] = auth()->id();

        Events::create($validated);

        return redirect()->route('admin.events.index')
                         ->with('success', 'Event created successfully!');
    }

    /**
     * Display the specified event.
     */
    public function show(Events $event)
    {
        // For public view, only show published events
        if ($event->status !== Events::STATUS_PUBLISHED &&
            (!auth()->check() ||
             (auth()->user()->role !== User::ROLE_ADMIN &&
              auth()->user()->role !== User::ROLE_SUPER_ADMIN))) {
            abort(404);
        }

        // Check if current user is registered
        $isRegistered = false;
        if (auth()->check()) {
            $isRegistered = $event->registrations()
                                  ->where('user_id', auth()->id())
                                  ->exists();
        }

        return view('events.show', compact('event', 'isRegistered'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        $categories = EventCategory::where('status', true)->get();
        return view('admin.events.edit', compact('event', 'categories'));
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Events $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'registration_deadline' => 'required|date|before:start_date',
            'max_participants' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:event_categories,id',
            'featured_image' => 'nullable|image|max:2048',
            'status' => 'required|in:' . implode(',', [
                Events::STATUS_DRAFT,
                Events::STATUS_PUBLISHED,
                Events::STATUS_CANCELLED,
                Events::STATUS_COMPLETED
            ]),
        ]);

        // Handle file upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($event->featured_image) {
                Storage::disk('public')->delete($event->featured_image);
            }

            $path = $request->file('featured_image')->store('events', 'public');
            $validated['featured_image'] = $path;
        }

        $event->update($validated);

        return redirect()->route('admin.events.index')
                         ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Events $event)
    {
        // Check if the event has registrations
        if ($event->registrations()->count() > 0) {
            return back()->with('error', 'Cannot delete event with registrations.');
        }

        // Delete image if exists
        if ($event->featured_image) {
            Storage::disk('public')->delete($event->featured_image);
        }

        $event->delete();

        return redirect()->route('admin.events.index')
                         ->with('success', 'Event deleted successfully!');
    }
}
