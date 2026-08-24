import fs from 'fs';
import path from 'path';
import { logger } from '../logger';

const STATUS_FILE = path.join(__dirname, '../../test-results/react-received-status.json');

/**
 * Read the recorded received status for every submitted React form.
 */
function readStatus(): Record<string, boolean> {
  if (!fs.existsSync(STATUS_FILE)) {
    return {};
  }
  return JSON.parse(fs.readFileSync(STATUS_FILE, 'utf-8'));
}

/**
 * Record whether a React form reached the received state after submit.
 *
 * @param formId
 *   The form identifier.
 * @param received
 *   True when the received confirmation was shown.
 */
export function recordReactReceived(formId: string, received: boolean): void {
  const dir = path.dirname(STATUS_FILE);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  const status = readStatus();
  status[formId] = received;
  fs.writeFileSync(STATUS_FILE, JSON.stringify(status, null, 2));
}

/**
 * Fail the run when every submitted React form failed to reach the received state.
 *
 * A single failure is treated as a network issue, but all of them failing
 * points to a systemic problem worth investigating.
 */
export function reportReactReceived(): void {
  const status = readStatus();
  const formIds = Object.keys(status);
  if (formIds.length === 0) {
    return;
  }
  const receivedCount = formIds.filter((id) => status[id]).length;
  logger(`React forms received: ${receivedCount}/${formIds.length}.`);
  fs.rmSync(STATUS_FILE, { force: true });
  if (receivedCount === 0) {
    throw new Error(`All ${formIds.length} submitted React forms failed to reach the received state.`);
  }
}
