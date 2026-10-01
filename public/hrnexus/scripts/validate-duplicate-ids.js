#!/usr/bin/env node
/**
 * Validate that every HTML document contains unique id attributes.
 * This intentionally matches only real HTML id attributes, not data-id,
 * JavaScript variables, or CSS selectors.
 */
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..', 'public');
const idAttr = /(?<![\w:-])id\s*=\s*["']([^"']+)["']/gi;
const files = [];

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full);
    else if (/\.(html?|xhtml)$/i.test(entry.name)) files.push(full);
  }
}
walk(root);

let failures = 0;

for (const file of files) {
  const html = fs.readFileSync(file, 'utf8');
  const seen = new Map();
  let match;
  while ((match = idAttr.exec(html)) !== null) {
    const id = match[1];
    const line = html.slice(0, match.index).split('\n').length;
    if (seen.has(id)) {
      const firstLine = seen.get(id);
      console.error(`Duplicate id="${id}" in ${path.relative(process.cwd(), file)} (lines ${firstLine} and ${line})`);
      failures++;
    } else {
      seen.set(id, line);
    }
  }
}

if (failures) {
  console.error(`\nFound ${failures} duplicate HTML id occurrence(s).`);
  process.exit(1);
}

console.log(`Duplicate-ID validation passed: ${files.length} HTML files checked.`);
