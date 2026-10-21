import { startDemo } from './runtime.mjs';

const args = process.argv.slice(2);
let port = process.env.PORT;
for (let i = 0; i < args.length; i++) {
  if (args[i] === '--port') port = args[++i];
  else if (args[i].startsWith('--port=')) port = args[i].slice(7);
  else throw new Error('Usage: npm start -- [--port 9401]');
}
console.log('Starting your saved WordPress demo. The first start takes longer.');
let demo;
try {
  demo = await startDemo(port === undefined ? {} : { port });
  console.log(`\nWebsite: ${demo.origin}/\nCMS:     ${demo.origin}/wp-admin/\nLogin:   demo_admin\nPassword: ExcavatorDemo2026!\n\nContent is saved in this checkout\'s .runtime directory. Ctrl+C stops the site.\n`);
  for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, async () => { await demo.dispose(); process.exit(0); });
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
