import test from 'node:test';
import assert from 'node:assert/strict';
import { serializeRequests } from '../scripts/runtime.mjs';

test('overlapping requests wait, preserve results, and continue after a failure', async () => {
  let release;
  const gate = new Promise(resolve => { release = resolve; });
  const calls = [];
  const request = serializeRequests(async input => {
    calls.push(input);
    if (input === 'first') await gate;
    if (input === 'failure') throw new Error('expected test rejection');
    return input;
  });
  const first = request('first');
  const second = request('second');
  await Promise.resolve();
  assert.deepEqual(calls, ['first']);
  release();
  assert.deepEqual(await Promise.all([first, second]), ['first', 'second']);
  const failure = request('failure');
  const after = request('after');
  await assert.rejects(failure, /expected test rejection/);
  assert.equal(await after, 'after');
  assert.deepEqual(calls, ['first', 'second', 'failure', 'after']);
});
