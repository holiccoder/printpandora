import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(
    new URL('../../resources/js/pages/showcases.tsx', import.meta.url),
    'utf8',
);

test('showcase cards fill their media frame without vertical letterboxing', () => {
    assert.match(source, /aspect-\[4\/3\]/);
    assert.match(source, /h-full w-full object-cover/);
    assert.doesNotMatch(source, /h-full w-full object-contain/);
});
