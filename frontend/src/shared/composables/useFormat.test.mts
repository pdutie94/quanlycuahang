import assert from 'node:assert/strict';
import test from 'node:test';
import { formatCompactMoney } from './useFormat.ts';

test('formats report money with compact Vietnamese units', () => {
  const cases = [
    [0, '0 đ'],
    [950000, '950.000 đ'],
    [1300000, '1,3 tr đ'],
    [1320000, '1,32 tr đ'],
    [9999999, '10 tr đ'],
    [12400000, '12,4 tr đ'],
    [1320000000, '1,32 tỷ đ'],
    [-1320000, '-1,32 tr đ'],
    [null, '0 đ'],
    [undefined, '0 đ'],
    ['1320000', '1,32 tr đ'],
    ['not-a-number', '0 đ']
  ] as const;

  for (const [value, expected] of cases) {
    assert.equal(formatCompactMoney(value), expected, `value: ${String(value)}`);
  }
});

test('does not expose non-finite values as money', () => {
  assert.equal(formatCompactMoney(Number.NaN), '0 đ');
  assert.equal(formatCompactMoney(Number.POSITIVE_INFINITY), '0 đ');
  assert.equal(formatCompactMoney(Number.NEGATIVE_INFINITY), '0 đ');
});
