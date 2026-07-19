/**
 * Script for installing packages from external cosine modules
 * Runs after `npm i`, finds external modules package.json, then installs their dependencies
 */

import { glob } from "glob";
import path from "node:path";
import fs from "node:fs";
import { spawn } from "node:child_process";

const scriptPath = import.meta.dirname.split('system')[0];

const packages = glob.sync(
	path.resolve(
		import.meta.dirname,
		scriptPath, 
		"modules/**/assets/ts/package.json"
	)
);

(async () => {
	for (const path of packages) {
		const content = fs.readFileSync(path, { encoding: "utf-8" });
		const json = JSON.parse(content);

		if (!json.dependencies && !json.dependencies.length) {
			console.warn(`${path} does not specify dependencies`);
			continue;
		}

		const moduleName = path.match(/(\w*?)\/assets\/ts\/package.json$/)?.[1];

		console.log(`Installing dependencies of '${moduleName}'`);

		const installTargets = Object.entries(json.dependencies).map(([name, version]) => `${name}@${version}`)

		await new Promise((resolve, reject) => {
			const npm = spawn("npm", ["i", "--no-save", ...installTargets]);

			npm.stderr.on("data", (d) => console.error(d.toString()));

			npm.on("close", code => code == 0 ? resolve() : reject(code));
		})
	}
})();