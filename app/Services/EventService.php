<?php

namespace App\Services;

use App\Interfaces\EventRepositoryInterface;

class EventService
{
    protected $eventRepository;

    public function __construct(EventRepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function getUserEvents($user, $from, $to)
    {
        $fromTimestamp = strtotime($from);
        $toTimestamp = strtotime($to);

        return $this->eventRepository->getUserEventsBetween($user->id, $fromTimestamp, $toTimestamp);
    }
}
