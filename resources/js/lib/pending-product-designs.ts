export type ProductDesignMode = 'upload' | 'design-for-you' | 'canva';

export interface PendingProductDesignFiles {
    design_file: File[];
    logo_file: File[];
    example_files: File[];
}

export interface PendingProductDesignDraft {
    mode: ProductDesignMode;
    productId: number;
    productName: string;
    productSlug: string;
    email: string;
    orderName: string;
    businessName: string;
    cardInfo: string;
    businessCardType: string;
    designServiceCode: string;
    termsAccepted: boolean;
    files: PendingProductDesignFiles;
}

export interface PendingProductDesignRecord extends PendingProductDesignDraft {
    clientId: string;
}

const DATABASE_NAME = 'printpandora-checkout';
const DATABASE_VERSION = 1;
const STORE_NAME = 'pending-product-designs';

let databasePromise: Promise<IDBDatabase> | null = null;

function openDatabase(): Promise<IDBDatabase> {
    if (typeof window === 'undefined' || !window.indexedDB) {
        return Promise.reject(
            new Error('This browser cannot temporarily save design files.'),
        );
    }

    if (databasePromise) {
        return databasePromise;
    }

    databasePromise = new Promise((resolve, reject) => {
        const request = window.indexedDB.open(DATABASE_NAME, DATABASE_VERSION);

        request.onupgradeneeded = () => {
            if (!request.result.objectStoreNames.contains(STORE_NAME)) {
                request.result.createObjectStore(STORE_NAME, {
                    keyPath: 'clientId',
                });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () =>
            reject(
                request.error ??
                    new Error('Unable to open temporary file storage.'),
            );
    });

    return databasePromise;
}

function createClientId(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export async function savePendingProductDesign(
    draft: PendingProductDesignDraft,
): Promise<string> {
    const clientId = createClientId();
    const record: PendingProductDesignRecord = { ...draft, clientId };
    const database = await openDatabase();

    await new Promise<void>((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        transaction.objectStore(STORE_NAME).put(record);
        transaction.oncomplete = () => resolve();
        transaction.onerror = () =>
            reject(
                transaction.error ??
                    new Error('Unable to save the selected design files.'),
            );
    });

    return clientId;
}

export async function getPendingProductDesign(
    clientId: string,
): Promise<PendingProductDesignRecord | null> {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const request = database
            .transaction(STORE_NAME, 'readonly')
            .objectStore(STORE_NAME)
            .get(clientId);
        request.onsuccess = () => resolve(request.result ?? null);
        request.onerror = () =>
            reject(
                request.error ??
                    new Error('Unable to read the selected design files.'),
            );
    });
}

export async function removePendingProductDesign(
    clientId: string,
): Promise<void> {
    const database = await openDatabase();

    await new Promise<void>((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        transaction.objectStore(STORE_NAME).delete(clientId);
        transaction.oncomplete = () => resolve();
        transaction.onerror = () =>
            reject(
                transaction.error ??
                    new Error('Unable to clear the temporary design files.'),
            );
    });
}
