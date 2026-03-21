type LogLevel = 'debug' | 'info' | 'warn' | 'error'

interface LogEntry {
  timestamp: string
  level: LogLevel
  message: string
  data?: unknown
}

class Logger {
  private logs: LogEntry[] = []
  private maxLogs = 200

  log(level: LogLevel, message: string, data?: unknown): void {
    const entry: LogEntry = {
      timestamp: new Date().toISOString(),
      level,
      message,
      data,
    }

    this.logs.push(entry)
    if (this.logs.length > this.maxLogs) {
      this.logs.shift()
    }

    // Also log to console
    const prefix = `[${entry.timestamp}] [${level.toUpperCase()}]`
    if (level === 'error') {
      console.error(`${prefix} ${message}`, data)
    } else if (level === 'warn') {
      console.warn(`${prefix} ${message}`, data)
    } else if (level === 'info') {
      console.info(`${prefix} ${message}`, data)
    } else {
      console.debug(`${prefix} ${message}`, data)
    }
  }

  debug(message: string, data?: unknown): void {
    this.log('debug', message, data)
  }

  info(message: string, data?: unknown): void {
    this.log('info', message, data)
  }

  warn(message: string, data?: unknown): void {
    this.log('warn', message, data)
  }

  error(message: string, data?: unknown): void {
    this.log('error', message, data)
  }

  getLogs(): LogEntry[] {
    return [...this.logs]
  }

  clear(): void {
    this.logs = []
  }

  export(): string {
    return this.logs.map((entry) => `${entry.timestamp} [${entry.level.toUpperCase()}] ${entry.message}`).join('\n')
  }
}

export const logger = new Logger()
