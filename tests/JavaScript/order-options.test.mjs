import assert from 'node:assert/strict';
import test from 'node:test';

import { formatOrderOptions } from '../../resources/js/lib/order-options.ts';

test('formats special finish side details instead of coercing objects to text', () => {
    assert.equal(
        formatOrderOptions({
            special_finish: ['laser'],
            special_finish_on_sides: { laser: 'both_sides' },
        }),
        'Special Finish: Laser, Special Finish On Sides: Laser: Both Sides',
    );
});
