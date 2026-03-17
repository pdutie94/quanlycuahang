<?php

class ManualLineItemBuilder
{
    public static function buildFromArrays($names, $units, $qtys, $pricesBuy, $pricesSell)
    {
        $safeNames = is_array($names) ? $names : [];
        $safeUnits = is_array($units) ? $units : [];
        $safeQtys = is_array($qtys) ? $qtys : [];
        $safePricesBuy = is_array($pricesBuy) ? $pricesBuy : [];
        $safePricesSell = is_array($pricesSell) ? $pricesSell : [];

        $itemsPrepared = [];
        $totalBuy = 0;
        $totalSell = 0;

        $max = max(
            count($safeNames),
            count($safeUnits),
            count($safeQtys),
            count($safePricesBuy),
            count($safePricesSell)
        );

        for ($i = 0; $i < $max; $i++) {
            $name = isset($safeNames[$i]) ? trim((string) $safeNames[$i]) : '';
            $unitName = isset($safeUnits[$i]) ? trim((string) $safeUnits[$i]) : '';
            $qtyRaw = isset($safeQtys[$i]) ? (string) $safeQtys[$i] : '';
            $priceBuyRaw = isset($safePricesBuy[$i]) ? (string) $safePricesBuy[$i] : '';
            $priceSellRaw = isset($safePricesSell[$i]) ? (string) $safePricesSell[$i] : '';

            if ($name === '' && $unitName === '' && $qtyRaw === '' && $priceBuyRaw === '' && $priceSellRaw === '') {
                continue;
            }

            $qty = (float) str_replace(',', '.', $qtyRaw);
            if (!is_finite($qty) || $qty <= 0) {
                $qty = 0.0;
            }

            $priceBuy = Money::toInt($priceBuyRaw);
            if ($priceBuy < 0) {
                $priceBuy = 0;
            }
            $priceSell = Money::toInt($priceSellRaw);
            if ($priceSell < 0) {
                $priceSell = 0;
            }

            if ($qty <= 0 && $priceBuy <= 0 && $priceSell <= 0 && $name === '') {
                continue;
            }

            $amountBuy = (int) round($qty * $priceBuy);
            if ($amountBuy < 0) {
                $amountBuy = 0;
            }
            $amountSell = (int) round($qty * $priceSell);
            if ($amountSell < 0) {
                $amountSell = 0;
            }

            $totalBuy += $amountBuy;
            $totalSell += $amountSell;

            $itemsPrepared[] = [
                'item_name' => $name,
                'unit_name' => $unitName,
                'qty' => $qty,
                'price_buy' => $priceBuy,
                'amount_buy' => $amountBuy,
                'price_sell' => $priceSell,
                'amount_sell' => $amountSell,
            ];
        }

        return [
            'items' => $itemsPrepared,
            'total_buy_amount' => $totalBuy,
            'total_sell_amount' => $totalSell,
        ];
    }
}

