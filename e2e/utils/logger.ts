import {isDebuggingEnabled} from './debugging_helpers'

/**
 * The logger function.
 *
 * The intention of this function is to replace the
 * console.log command. When using this logger, messages
 * will only get logged to the console if debugging has
 * been enabled.
 *
 * @param {string} message
 *   The message we want to log.
 * @param {any[]} [parameters]
 *   Any array of parameters.
 */
const logger = (message: string, ...parameters: any[]): void => {
  if (isDebuggingEnabled()) {
    let logMessage = `[Debugging message]: ${message}`;

    if (parameters.length > 0) {
      console.log(logMessage, ...parameters);
    } else {
      console.log(logMessage);
    }
  }
};

export {
  logger
};
