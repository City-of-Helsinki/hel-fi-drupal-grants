import { chromium, type FullConfig } from '@playwright/test';
import {logger} from "../utils/logger";

// Option 1: Export a function
module.exports = async (config: FullConfig) => {
    // Perform teardown logic here

    // const storedData = process.env.storedData;
    //
    // logger('Global Teardown: Performing cleanup tasks', storedData);
    //
    // if (storedData) {
    //     const decoded = await JSON.parse(storedData);
    //
    //     logger('PROCESS ENV', decoded);
    // }

    logger('TEARDOWN');

};
