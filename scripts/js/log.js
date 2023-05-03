export default function log(message) {
  // Logging human-readable timestamp.
  // eslint-disable-next-line no-console
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
}
