import { test as setup, expect } from '@playwright/test';
import { getKeyValue } from '../../utils/helpers';
import { TEST_USER_UUID } from '../../utils/test_data';

type ATVDocument = {
    id: string;
    type: string;
    service: string;
    transaction_id: string;
}

type PaginatedDocumentlist = {
    count: number;
    next: string | null;
    previous: string | null;
    results: ATVDocument[]
}

const APP_ENV = getKeyValue('APP_ENV');
const ATV_API_KEY = getKeyValue('ATV_API_KEY');
const ATV_BASE_URL = getKeyValue('ATV_BASE_URL');

const BASE_HEADERS = { 'X-API-KEY': ATV_API_KEY };

setup.beforeAll(() => {
    expect(ATV_API_KEY).toBeTruthy()
    expect(ATV_BASE_URL).toBeTruthy()
    expect(APP_ENV).toBeTruthy()
    expect(APP_ENV.toUpperCase()).not.toContain("PROD");
})

setup('Check for maintenance mode', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator(".maintenance-page")).toBeHidden();
});

setup('Remove existing grant profiles', async () => {
    if (APP_ENV.toUpperCase().includes("PROD")) return;

    const APP_ENV_FOR_ATV = getAppEnvForATV();

    const initialUrl = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV_FOR_ATV}&user_id=${TEST_USER_UUID}&type=grants_profile&service_name=AvustushakemusIntegraatio`;

    let currentUrl: string | null = initialUrl;

    let deletedDocumentsCount = 0;

    while (currentUrl != null) {
        const documentList = await fetchDocumentList(currentUrl);

        if (!documentList) return;

        currentUrl = documentList.next;

        const documentIds = documentList.results.map(r => r.id);

        const deletionPromises = documentIds.map(deleteDocumentById);
        const deletionResults = await Promise.all(deletionPromises);

        deletedDocumentsCount += deletionResults.filter(result => result).length;
    }
});

const fetchDocumentList = async (url: string) => {
    try {
        const res = await fetch(url, { headers: BASE_HEADERS });

        if (!res.ok) {
            throw new Error(`HTTP error! Status: ${res.status}`);
        }

        const json: PaginatedDocumentlist = await res.json();
        return json;
    } catch (error) {
        console.error("Error fetching document list:", error);
        return null;
    }
};

const deleteDocumentById = async (id: string) => {
    try {
        const url = `${ATV_BASE_URL}/v1/documents/${id}`;
        const res = await fetch(url, { method: 'DELETE', headers: BASE_HEADERS });

        if (!res.ok) {
            throw new Error(`HTTP error! Status: ${res.status}`);
        }
        return true;
    } catch (error) {
        console.error("Error deleting document:", error);
        return false;
    }
};

// Similarily as in ApplicationHandler.php
const getAppEnvForATV = () => {
    switch (APP_ENV) {
        case "development":
            return "DEV"
        case "testing":
            return "TEST"
        case "staging":
            return "STAGE"
        default:
            return APP_ENV.toUpperCase()
    }
}
