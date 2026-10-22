import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const view = await readFile(new URL('../resources/views/pages/cashier.blade.php', import.meta.url), 'utf8');

assert.match(view, /x-show="successModal"[\s\S]*role="dialog"[\s\S]*aria-modal="true"/);
assert.match(view, /if \(data\.success\) \{[\s\S]*this\.successRedirect = data\.redirect;[\s\S]*this\.successModal = true;/);
assert.match(view, /@click="window\.location\.href = successRedirect"/);
assert.doesNotMatch(view, /if \(data\.success\) \{\s*window\.location\.href = data\.redirect;/);
assert.match(view, /this\.errorMessage = data\.message \|\| 'Terjadi kesalahan\.'/);
assert.match(view, /x-show="errorMessage"[^>]*role="alert"/);
