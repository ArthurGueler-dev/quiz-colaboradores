const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');

const dbDir = path.join(__dirname, '..', 'db');
const dbPath = path.join(dbDir, 'quiz.db');
const schemaPath = path.join(dbDir, 'schema.sql');
const seedPath = path.join(dbDir, 'seed.sql');

fs.mkdirSync(dbDir, { recursive: true });
const db = new Database(dbPath);

if (fs.existsSync(schemaPath)) {
	const schemaSql = fs.readFileSync(schemaPath, 'utf8');
	if (schemaSql.trim()) db.exec(schemaSql);
}

if (!fs.existsSync(seedPath)) {
	console.log('Seed não encontrado em db/seed.sql');
	process.exit(0);
}
const seedSql = fs.readFileSync(seedPath, 'utf8');
if (seedSql.trim()) db.exec(seedSql);

console.log('Seed aplicado com sucesso.');
