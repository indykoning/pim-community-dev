import {renderHook} from '@testing-library/react-hooks';
import {mockFetchResponses} from '../../../test-utils';
import {useFetchConnectedAppScopeMessages} from '@src/connect/hooks/use-fetch-connected-app-scope-messages';

test('it fetches the connected app scope messages', async () => {
    const expectedScopeMessages = [
        {
            icon: 'channel_settings',
            type: 'edit',
            entities: 'channel_settings',
        },
    ];

    mockFetchResponses({
        'akeneo_connectivity_connection_apps_rest_get_all_connected_app_scope_messages?connectedAppId=0dfce574-2238-4b13-b8cc-8d257ce7645b':
            {
                json: expectedScopeMessages,
            },
    });
    const {result} = renderHook(() => useFetchConnectedAppScopeMessages('0dfce574-2238-4b13-b8cc-8d257ce7645b'));
    const scopeMessages = await result.current();

    expect(scopeMessages).toStrictEqual(expectedScopeMessages);
});
