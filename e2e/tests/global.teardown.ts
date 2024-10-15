import { FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import {logger} from "../utils/logger";

module.exports = async (config: FullConfig) => {

  logger('Teardown script started.');

  const filesAndFoldersToDelete = [
    path.join(__dirname, '../.auth/user.json'),
  ];

  filesAndFoldersToDelete.forEach((filePath) => {
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
      logger(`Deleted: ${filePath}`);
    } else {
      logger(`File not found: ${filePath}`);
    }
  });

  logger('Teardown script end.');

};
