import { chromium, type FullConfig } from '@playwright/test';

// Option 1: Export a function
module.exports = async (config: FullConfig) => {
    // Perform teardown logic here

    // const storedData = process.env.storedData;
    //
    // console.log('Global Teardown: Performing cleanup tasks', storedData);
    //
    // if (storedData) {
    //     const decoded = await JSON.parse(storedData);
    //
    //     console.log('PROCESS ENV', decoded);
    // }

    console.log('TEARDOWN');

};
