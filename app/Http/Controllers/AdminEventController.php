<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::withCount('bookings')->orderBy('start_date')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        Event::create($data);

        return redirect()->route('admin.events.index')->with('status', 'Promo created successfully.');
    }

    public function show(Event $event): View
    {
        $event->load('bookings.user', 'bookings.court');
        return view('admin.events.show', compact('event'));
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);

        return redirect()->route('admin.events.index')->with('status', 'Promo updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->bookings()->exists()) {
            return back()->with('error', 'This promo has bookings and cannot be deleted.');
        }

        $event->delete();

        return redirect()->route('admin.events.index')->with('status', 'Promo deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
