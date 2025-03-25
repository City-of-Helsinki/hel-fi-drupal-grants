
/**
 * Initializes IndexedDB for storing form state.
 *
 * @return {Promise<boolean>} - A promise that resolves to true if IndexedDB
 * is initialized successfully, false otherwise.
 */
export const initDB = (): Promise<boolean> => new Promise((resolve) => {
  const request = window.indexedDB.open('grants-form-state', 1);

  request.onupgradeneeded = () => {
    const db = request.result;

    if (!db.objectStoreNames.contains('form-state')) {
      db.createObjectStore('form-state');
    };
  }

  request.onsuccess = () => {
    resolve(true);
  };

  request.onerror = () => {
    resolve(false);
  };
});

  /**
   * Stores given data in IndexedDB.
   *
   * @param {object} data - Form state data to be stored in IndexedDB.
   * @param {string} formId - Unique ID for the form.
   * @return {Promise<object|string|null>} - A promise that resolves to the stored data
   * on success, error message on failure, or null if request errors.
   */
export const addData = <T>(data: T, formId: string): Promise<T|string|null> => new Promise((resolve) => {
  const request = window.indexedDB.open('grants-form-state', 1);

  request.onsuccess = () => {
    const db = request.result;

    const tx = db.transaction('form-state', 'readwrite');
    const store = tx.objectStore('form-state');
    store.put({...data, timestamp: Date.now()}, formId);
    tx.oncomplete = () => {
      resolve(data);
    };
  };

  request.onerror = () => {
    const error = request.error?.message;

    if (error) {
      resolve(error);
    }
    else {
      resolve('Unknown error');
    }
  };

});

/**
 * Checks indexedDB for cached data.
 *
 * @param {string} formId - Unique ID for the form
  @return {Promise<object|string|null>} - A promise that resolves to the stored data
 */
export const getData = (formId: string): Promise<any> => new Promise((resolve) => {
  const request = window.indexedDB.open('grants-form-state', 1);

  request.onsuccess = () => {
    const db = request.result;

    const tx = db.transaction('form-state', 'readonly');
    const store = tx.objectStore('form-state');
    const result = store.get(formId);

    result.onsuccess = () => {
      resolve(result.result);
    };
  };
});
