<?php
namespace App\Http\Controllers;

use App\Models\MoodleEvent;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected $eventService;
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $user = $request->user(); // hoặc auth()->user()


        $events = $this->eventService->getUserEvents($user, $request->from, $request->to);

        return response()->json($events);
    }

}
