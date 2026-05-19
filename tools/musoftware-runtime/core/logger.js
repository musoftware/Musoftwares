'use strict';

const { createLogger: winstonLogger, format, transports } = require('winston');
const fs   = require('fs');
const path = require('path');

function createLogger(config = {}) {
    const logDir = config.logsDir || path.join(__dirname, '../logs');
    fs.mkdirSync(logDir, { recursive: true });

    const consoleFmt = format.combine(
        format.colorize(),
        format.timestamp({ format: 'HH:mm:ss' }),
        format.printf(({ timestamp, level, message }) =>
            `[${timestamp}] ${level} ${message}`
        )
    );

    const fileFmt = format.combine(
        format.timestamp(),
        format.errors({ stack: true }),
        format.json()
    );

    return winstonLogger({
        level: config.logLevel || 'info',
        transports: [
            new transports.Console({ format: consoleFmt }),
            new transports.File({
                filename: path.join(logDir, 'runtime.log'),
                format:   fileFmt,
                maxsize:  5 * 1024 * 1024,
                maxFiles: 3,
                tailable: true,
            }),
        ],
    });
}

module.exports = { createLogger };
