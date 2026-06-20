import test from 'node:test';
import assert from 'node:assert/strict';

import { computeSpinRotation } from '../../resources/js/spin-wheel.js';

test('computeSpinRotation lands the first winner under the pointer', () => {
    const rotation = computeSpinRotation(0, 0, 6, 1800);

    assert.equal(rotation % 360, 330);
});

test('computeSpinRotation accounts for the wheel current orientation on later spins', () => {
    const firstRotation = computeSpinRotation(0, 0, 6, 1800);
    const secondRotation = computeSpinRotation(firstRotation, 1, 6, 1800);

    assert.equal(firstRotation % 360, 330);
    assert.equal(secondRotation % 360, 270);
});
