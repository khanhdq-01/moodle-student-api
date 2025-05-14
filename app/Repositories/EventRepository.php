<?php

namespace App\Repositories;

use App\Models\MoodleEvent;
use App\Interfaces\EventRepositoryInterface;

class EventRepository implements EventRepositoryInterface
{
    public function getUserEventsBetween($userId, $from, $to)
    {
        return MoodleEvent::whereBetween('timestart', [$from, $to])
            ->where(function ($query) use ($userId) {
                $query->where('eventtype', 'site')
                    ->orWhere(function ($q) use ($userId) {
                        $q->where('eventtype', 'user')
                           ->where('userid', $userId);
                    });
            })
            ->orderBy('timestart', 'asc')
            ->get();
    }
}
