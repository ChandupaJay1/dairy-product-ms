/**
 * Offline Sync and Local Cache Storage Manager
 * 
 * Handles offline order queueing and auto-sync when field sales reps
 * or drivers traverse rural Polonnaruwa zones (Medirigiriya, Dimbulagala, Kaudulla)
 * with zero cellular mobile reception.
 */

const OfflineStorage = (() => {
    const QUEUE_KEY = 'polonnaruwa_offline_order_queue';
    const CACHE_SHOPS_KEY = 'polonnaruwa_cached_shops';
    const CACHE_PRODUCTS_KEY = 'polonnaruwa_cached_products';
    const CACHE_MANIFEST_KEY = 'polonnaruwa_cached_manifest';
    const CACHE_ADMIN_KEY = 'polonnaruwa_cached_admin';
    const CACHE_SETTLEMENT_KEY = 'polonnaruwa_cached_settlement';
    const CACHE_REPORTS_KEY = 'polonnaruwa_cached_reports';
    let isSyncing = false;

    function getPendingQueue() {
        try {
            const raw = localStorage.getItem(QUEUE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch {
            return [];
        }
    }

    function enqueueOrder(orderPayload) {
        const queue = getPendingQueue();
        const pendingItem = {
            id: 'OFFLINE-' + Date.now(),
            queuedAt: new Date().toISOString(),
            payload: orderPayload
        };
        queue.push(pendingItem);
        localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
        updateOfflineBadge();
        return pendingItem;
    }

    function removeQueueItem(itemId) {
        const queue = getPendingQueue().filter(item => item.id !== itemId);
        localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
        updateOfflineBadge();
    }

    function clearQueue() {
        localStorage.removeItem(QUEUE_KEY);
        updateOfflineBadge();
    }

    function cacheOperationalData(key, data) {
        try {
            localStorage.setItem(key, JSON.stringify(data));
        } catch (err) {
            console.warn('Cache write failed:', err);
        }
    }

    function getCachedData(key) {
        try {
            const raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    function updateOfflineBadge() {
        const badge = document.getElementById('offlineSyncBadge');
        const count = getPendingQueue().length;
        if (badge) {
            if (count > 0) {
                badge.style.display = 'inline-flex';
                badge.className = 'status-badge-network status-badge-offline pulse-effect';
                badge.innerHTML = `🔄 ${count} Queued Offline (Sync)`;
                badge.title = 'Click to sync pending orders with MySQL Database';
            } else {
                badge.style.display = 'none';
                badge.className = 'status-badge-network status-badge-offline';
                badge.textContent = '0 Queued';
            }
        }
    }

    /**
     * Synchronizes all offline queued orders once online connection is re-established.
     */
    async function syncQueue() {
        if (isSyncing) return;
        const queue = getPendingQueue();
        if (queue.length === 0) {
            updateOfflineBadge();
            return;
        }

        if (!navigator.onLine) {
            SecurityClient.showToast('⚠️ Cannot sync while offline. Waiting for cellular network...', 'warning');
            return;
        }

        isSyncing = true;
        SecurityClient.showToast(`🔄 Synchronizing ${queue.length} offline orders with MySQL database...`, 'warning', 4000);
        let successCount = 0;
        let failCount = 0;

        for (const item of [...queue]) {
            try {
                const response = await SecurityClient.secureFetch('/api/orders/create', {
                    method: 'POST',
                    body: JSON.stringify(item.payload)
                });

                if (response && response.success) {
                    removeQueueItem(item.id);
                    successCount++;
                } else {
                    failCount++;
                    console.warn('Sync rejected for item:', item.id, response ? response.error : 'Unknown');
                }
            } catch (err) {
                failCount++;
                console.warn('Failed to sync item:', item.id, err);
            }
        }

        isSyncing = false;
        updateOfflineBadge();

        if (successCount > 0) {
            SecurityClient.showToast(`✅ Successfully synced ${successCount} order(s) directly into MySQL Database!`, 'success', 5000);
            if (window.App && typeof window.App.refreshCurrentView === 'function') {
                window.App.refreshCurrentView();
            }
        }

        if (failCount > 0) {
            SecurityClient.showToast(`⚠️ ${failCount} order(s) could not sync. Retrying when signal stabilizes.`, 'error', 5000);
        }
    }

    return {
        getPendingQueue,
        enqueueOrder,
        removeQueueItem,
        clearQueue,
        cacheOperationalData,
        getCachedData,
        updateOfflineBadge,
        syncQueue,
        CACHE_SHOPS_KEY,
        CACHE_PRODUCTS_KEY,
        CACHE_MANIFEST_KEY,
        CACHE_ADMIN_KEY,
        CACHE_SETTLEMENT_KEY,
        CACHE_REPORTS_KEY
    };
})();

window.OfflineStorage = OfflineStorage;
