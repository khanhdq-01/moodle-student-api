<?php

namespace App\Interfaces;

interface EventRepositoryInterface
{
    public function getUserEventsBetween($userId, $from, $to);
}
