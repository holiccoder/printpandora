import assert from 'node:assert/strict';
import test from 'node:test';

import { squareInchesToSquareMetres } from '../../resources/js/lib/pricing.ts';

test('converts standard sticker dimensions from square inches to square metres', () => {
    assert.ok(Math.abs(squareInchesToSquareMetres(2, 2) - 0.00258064) < 1e-12);
});

test('converts custom sticker dimensions from square inches to square metres', () => {
    assert.ok(Math.abs(squareInchesToSquareMetres(2, 3) - 0.00387096) < 1e-12);
});

test('returns zero for invalid sticker dimensions', () => {
    assert.equal(squareInchesToSquareMetres(0, 2), 0);
    assert.equal(squareInchesToSquareMetres(Number.NaN, 2), 0);
    assert.equal(squareInchesToSquareMetres(2, Number.POSITIVE_INFINITY), 0);
});
