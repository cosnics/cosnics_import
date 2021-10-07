#!/usr/bin/env node

const path = require('path');
const fs = require('fs');
const util = require('util');

const rename = util.promisify(fs.rename);
const copyFile = util.promisify(fs.copyFile);

const targetWebFolder = path.resolve('__dirname', '../../../../../../../../../', 'web/Chamilo/Core/Repository/ContentObject/Presence/Resources');

function logErr(err) {
  console.log(err);
}

function logMove(file, toWebFolder = true) {
  console.log(`${path.basename(file)} moved ${toWebFolder ? 'to web folder' : ''}`);
}

async function processCssFile(srcCssFile, srcCssRenamedFile, targetCssFile) {
  if (fs.existsSync(srcCssFile))
  {
    try {
      await rename(srcCssFile, srcCssRenamedFile);
      logMove(srcCssFile, false);
    } catch (err) {
      logErr(err);
    }
      
    try {
      await copyFile(srcCssRenamedFile, targetCssFile);
      logMove(srcCssFile, true);
    } catch (err) {
      logErr(err);
    }
  }
}

async function processJsFiles(srcJsFolder, srcJsFiles, targetJsFolder) {
  for (const jsFile of srcJsFiles) {
    const srcJsFile = path.resolve(srcJsFolder, jsFile);

    if (fs.existsSync(srcJsFile))
    {
      const targetJsFile = path.resolve(targetJsFolder, jsFile);
      try {
        await copyFile(srcJsFile, targetJsFile);
        logMove(jsFile);
      } catch (err) {
        logErr(err);
      }
    }
  }
}

const srcCssFile = path.resolve(__dirname, '../Javascript/cosnics-presence.css');
const srcCssRenamedFile = path.resolve(__dirname, '../Css/Aqua/cosnics-presence.css');
const targetCssFile = path.resolve(targetWebFolder, 'Css/Aqua/cosnics-presence.css');

processCssFile(srcCssFile, srcCssRenamedFile, targetCssFile);

const srcJsFolder = path.resolve(__dirname, '../Javascript/');
const srcJsFiles = ['cosnics-presence.common.js', 'cosnics-presence.umd.js', 'cosnics-presence.umd.min.js'];
const targetJsFolder = path.resolve(targetWebFolder, 'Javascript/');

processJsFiles(srcJsFolder, srcJsFiles, targetJsFolder);




