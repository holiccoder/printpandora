<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = $request->user()
            ->supportTickets()
            ->withCount('replies')
            ->latest()
            ->paginate(10);

        return Inertia::render('shop/tickets/index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('shop/tickets/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'order_id' => $validated['order_id'] ?? null,
            'status' => 'open',
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return redirect()->route('shop.tickets.show', $ticket->id)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Request $request, int $id): Response
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->with(['replies.user', 'order'])
            ->findOrFail($id);

        return Inertia::render('shop/tickets/show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'order_id' => $ticket->order_id,
                'created_at' => $ticket->created_at->toDateTimeString(),
                'replies' => $ticket->replies->map(fn ($r) => [
                    'id' => $r->id,
                    'message' => $r->message,
                    'user_name' => $r->user?->name ?? 'Support',
                    'is_admin' => $r->user_id === null,
                    'created_at' => $r->created_at->toDateTimeString(),
                ]),
            ],
        ]);
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent.');
    }
}
