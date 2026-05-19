'use strict';

const { createLogger: winstonLogger, format, transports } = require('winston');
const fs   = require('fs');
const path = require('path');

function createLogger(config = {}) {
    const logDir = path.join(__dirname, '../logs');
    if (!fs.existsSync(logDir)) fs.mkdirSync(logDir, { recursive: true });

    return winstonLogger({
        level: config.logLevel || 'info',
        format: format.combine(
            format.timestamp({ format: 'HH:mm:ss' }),
            format.errors({ stack: true }),
            format.printf(({ timestamp, level, message }) =>
                `[${timestamp}] ${level.toUpperCase().padEnd(5)} ${message}`
            )
        ),
        transports: [
            new transports.Console({
                format: format.combine(
                    format.colorize(),
                    format.printf(({ timestamp, level, message }) =>
                        `[${timestamp}] ${level} ${message}`
                    )
                ),
            }),
            new transports.File({
                filename: path.join(logDir, 'agent.log'),
                maxsize:  5 * 1024 * 1024,
                maxFiles: 3,
                tailable: true,
            }),
        ],
    });
}

module.exports = { createLogger };
