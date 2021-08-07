<?php

declare(strict_types=1);

namespace Hgraca\BenchmarkIds;

final class Timer
{
    private float $start;
    private float $lastTime;

    private final function __construct()
    {
    }

    public static function startTimer(): self
    {
        $self = new self();
        $self->start = Timer::microseconds();

        return $self;
    }

    public function stopTimer(): float
    {
        return $this->lastTime = Timer::microseconds() - $this->start;
    }

    public function getLastTime(): float
    {
        return $this->lastTime;
    }

    /**
     * 1 milliseconds = 1,000 microseconds
     * 1 second       = 1,000,000 microseconds
     * 1 minute       = 60,000,000 microseconds
     * 1 hour         = 3,600,000,000 microseconds
     *
     * @return float
     */
    private static function microseconds(): float
    {
        return microtime(true);
    }
}
