#!/usr/bin/env node

const { execSync } = require('node:child_process');

const color = {
    reset: '\x1b[0m',
    cyan: '\x1b[36m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    magenta: '\x1b[35m',
};

const env = process.env;
const shouldPopulate = String(env.AUTO_POPULATE_COMIPEMS || 'false').toLowerCase() === 'true';
const lotes = Number.parseInt(env.AUTO_POPULATE_LOTES || '2', 10) || 2;
const materiasRaw = env.AUTO_POPULATE_MATERIAS || 'Matemáticas,Física,Química,Biología,Historia Universal,Historia de México,Español,Geografía,Formación Cívica y Ética';
const materias = materiasRaw
    .split(',')
    .map((m) => m.trim())
    .filter(Boolean);

function run(command) {
    execSync(command, {
        stdio: 'inherit',
        env,
    });
}

function logInfo(message) {
    console.log(`${color.cyan}${message}${color.reset}`);
}

function logOk(message) {
    console.log(`${color.green}${message}${color.reset}`);
}

function logWarn(message) {
    console.log(`${color.yellow}${message}${color.reset}`);
}

function logError(message) {
    console.error(`${color.red}${message}${color.reset}`);
}

function main() {
    logInfo('🚀 Build pipeline multiplataforma iniciado');

    if (shouldPopulate) {
        logInfo(`🧠 AUTO_POPULATE_COMIPEMS=true | lotes=${lotes}`);

        for (const materia of materias) {
            logWarn(`📚 Poblando materia: ${materia}`);

            try {
                const escapedMateria = materia.replace(/"/g, '\\"');
                run(`php artisan db:populate-comipems "${escapedMateria}" ${lotes}`);
                logOk(`✅ Materia poblada: ${materia}`);
            } catch (error) {
                logError(`⚠️ Falló la población para '${materia}'. Continuando con la siguiente...`);
            }
        }
    } else {
        logWarn('⏭️ AUTO_POPULATE_COMIPEMS=false, se omite población de reactivos.');
    }

    logInfo('🏗️ Ejecutando build de assets con Vite...');
    run('npx vite build');
    logOk('✅ Build completado');
}

try {
    main();
} catch (error) {
    logError('❌ El pipeline de build falló.');
    process.exit(1);
}
