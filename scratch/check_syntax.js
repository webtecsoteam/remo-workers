const fs = require('fs');
const vm = require('vm');
const path = require('path');

const filepath = path.join(__dirname, '../client/includes/footer.php');
let content = fs.readFileSync(filepath, 'utf8');

// Extract the <script> block content
const scriptMatch = content.match(/<script>([\s\S]*?)<\/script>/i);
if (!scriptMatch) {
  console.log('No script block found!');
  process.exit(1);
}

let jsCode = scriptMatch[1];

// Strip out PHP blocks <?php ... ?> and replace them with a valid JS equivalent like '""' or '0'
jsCode = jsCode.replace(/<\?php[\s\S]*?\?>/g, '""');

// Try compiling the script using Node's VM module
try {
  new vm.Script(jsCode);
  console.log('JavaScript syntax is PERFECT!');
} catch (err) {
  console.error('JS Syntax Error detected:');
  console.error(err.message);
  // Print surrounding lines to help debug
  const lines = jsCode.split('\n');
  const lineNo = err.stack.match(/evalmachine\.<anonymous>:(\d+)/);
  if (lineNo) {
    const errorLine = parseInt(lineNo[1], 10);
    console.error(`\nError around line ${errorLine}:`);
    for (let i = Math.max(0, errorLine - 5); i < Math.min(lines.length, errorLine + 5); i++) {
      console.error(`${i + 1}: ${lines[i]}`);
    }
  }
  process.exit(1);
}
