<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * List all messages across every exchange, newest first, with an
     * optional keyword search and exchange filter.
     */
    public function index(Request $request)
    {
        $search     = trim((string) $request->get('q', ''));
        $exchangeId = $request->get('exchange');

        $query = Message::with(['sender', 'recipient', 'exchange']);

        if ($search !== '') {
            $query->where('message_text', 'like', "%{$search}%");
        }

        if ($exchangeId) {
            $query->where('exchange_id', $exchangeId);
        }

        return view('admin.messages.index', [
            'messages'   => $query->latest()->paginate(20)->withQueryString(),
            'search'     => $search,
            'exchangeId' => $exchangeId,
        ]);
    }

    /**
     * Remove a message (moderation action).
     */
    public function destroy(Message $message)
    {
        $message->delete();

        return back()->with('success', 'Message removed.');
    }
}
