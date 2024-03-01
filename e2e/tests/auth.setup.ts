import {test as setup} from '@playwright/test';
import { checkLoginStateAndLogin } from "../utils/auth_helpers";


setup.setTimeout(60000)

setup('authenticate', async ({page}) => {
  await checkLoginStateAndLogin(page);
});
