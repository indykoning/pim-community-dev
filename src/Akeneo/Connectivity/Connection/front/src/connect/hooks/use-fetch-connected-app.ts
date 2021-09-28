import {useRoute} from '../../shared/router';
import {useCallback} from 'react';

export const useFetchConnectedApp = (connectedAppId: string) => {
    const url = useRoute('akeneo_connectivity_connection_apps_rest_get_connected_app', {
        connectedAppId: connectedAppId,
    });

    return useCallback(async () => {
        const response = await fetch(url, {
            method: 'GET',
            headers: [['X-Requested-With', 'XMLHttpRequest']],
        });
        if (false === response.ok) {
            return Promise.reject(`${response.status} ${response.statusText}`);
        }

        return response.json();
    }, [url]);
};
