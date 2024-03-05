import path from "path";
import fs from "fs";

/**
 * The readEnvFile function.
 *
 * This function reads the content of the '.test_env' file
 * and returns an array of its lines.
 *
 * @returns string[]
 *   Array of lines from the '.test_env' file.
 *
 */
const readEnvFile = (): string[] => {
  try {
    const envFilePath = path.resolve(__dirname, '../.test_env');
    const envFile = fs.readFileSync(envFilePath, 'utf-8');
    return envFile.split('\n');
  } catch (error) {
    console.error('[Error reading .test_env file]');
    return [];
  }
};

export {
  readEnvFile,
}
