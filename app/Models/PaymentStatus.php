<?php

class PaymentStatus
{
    const PAID = 'paid';
    const DEBT = 'debt';

    public static function all()
    {
        return [self::PAID, self::DEBT];
    }
}
