<?php

class OrderStatus
{
    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';

    public static function all()
    {
        return [self::PENDING, self::COMPLETED, self::CANCELLED];
    }
}
