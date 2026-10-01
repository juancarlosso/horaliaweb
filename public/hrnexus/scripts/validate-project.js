#!/usr/bin/env node
/**
 * HRNexus static integrity validator.
 * Checks real HTML id attributes (never data-id), CSS/SCSS brace balance,
 * and basic HTML/script integrity without requiring external dependencies.
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const publicDir = path.join(root, 'public');
const scssDir = path.join(root, 'src', 'scss');
let failures = 0;

function files(dir, extRe) {
  const out=[];
  function walk(d){
    for(const e of fs.readdirSync(d,{withFileTypes:true})){
      const p=path.join(d,e.name);
      if(e.isDirectory()) walk(p);
      else if(extRe.test(e.name)) out.push(p);
    }
  }
  walk(dir); return out;
}

function stripCssCommentsAndStrings(s) {
  s = s.replace(/\/\*[\s\S]*?\*\//g, '');
  s = s.replace(/(^|\n)\s*\/\/[^\n]*/g, '$1');
  return s.replace(/"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/g, '');
}

function checkBraces(file, kind) {
  const raw=fs.readFileSync(file,'utf8');
  const s=stripCssCommentsAndStrings(raw);
  let depth=0, line=1;
  for(let i=0;i<s.length;i++){
    if(s[i]==='\n') line++;
    if(s[i]==='{') depth++;
    if(s[i]==='}') { depth--; if(depth<0){
      console.error(`[${kind}] Extra closing brace: ${path.relative(root,file)}:${line}`); failures++; depth=0;
    }}
  }
  if(depth!==0){ console.error(`[${kind}] Unbalanced braces (${depth} open): ${path.relative(root,file)}`); failures++; }
}

// 1) Real HTML id uniqueness, including inline style blocks ignored.
const htmlFiles=files(publicDir,/\.(html?|xhtml)$/i);
const idAttr=/(?<![\w:-])id\s*=\s*(["'])([^"']+)\1/gi;
for(const file of htmlFiles){
  const html=fs.readFileSync(file,'utf8');
  const seen=new Map(); let m;
  idAttr.lastIndex=0;
  while((m=idAttr.exec(html))){
    const id=m[2]; const line=html.slice(0,m.index).split('\n').length;
    if(seen.has(id)){
      console.error(`[HTML] Duplicate id="${id}": ${path.relative(root,file)}:${seen.get(id)} and :${line}`); failures++;
    } else seen.set(id,line);
  }
}

// 2) CSS/SCSS brace balance, including inline <style> blocks.
for(const file of files(scssDir,/\.scss$/i)) checkBraces(file,'SCSS');
for(const file of files(publicDir,/\.css$/i)) checkBraces(file,'CSS');
for(const file of htmlFiles){
  const html=fs.readFileSync(file,'utf8');
  const re=/<style(?:\s[^>]*)?>([\s\S]*?)<\/style>/gi; let m;
  while((m=re.exec(html))){
    const s=stripCssCommentsAndStrings(m[1]);
    if((s.match(/{/g)||[]).length !== (s.match(/}/g)||[]).length){
      console.error(`[CSS] Unbalanced inline style: ${path.relative(root,file)}`); failures++;
    }
  }
}

// 3) Check every inline JavaScript block with Node's parser.
const tmpDir=path.join(root,'.validation-tmp'); fs.mkdirSync(tmpDir,{recursive:true});
for(const file of htmlFiles){
  const html=fs.readFileSync(file,'utf8'); const re=/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi; let m, n=0;
  while((m=re.exec(html))){
    const code=m[1].trim(); if(!code || /src\s*=/.test(m[0])) continue;
    const tmp=path.join(tmpDir,`inline-${process.pid}-${n++}.js`); fs.writeFileSync(tmp,code);
    try { execFileSync(process.execPath,['--check',tmp],{stdio:'pipe'}); }
    catch(e){
      console.error(`[JS] Syntax error: ${path.relative(root,file)} inline block ${n}`);
      console.error(String(e.stderr||'').trim()); failures++;
    } finally { try{fs.unlinkSync(tmp)}catch{} }
  }
}
try{fs.rmSync(tmpDir,{recursive:true,force:true});}catch{}

console.log(`Integrity scan: ${htmlFiles.length} HTML files checked.`);
if(failures){ console.error(`Integrity scan failed with ${failures} issue(s).`); process.exit(1); }
console.log('Integrity scan passed: no duplicate HTML IDs, unbalanced CSS/SCSS braces, or inline JavaScript syntax errors found.');
