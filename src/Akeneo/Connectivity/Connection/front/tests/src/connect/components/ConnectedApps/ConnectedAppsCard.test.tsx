import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import {screen, waitForElement} from '@testing-library/react';
import fetchMock from 'jest-fetch-mock';
import {renderWithProviders, historyMock} from '../../../../test-utils';
import {ConnectedAppCard} from '@src/connect/components/ConnectedApps/ConnectedAppCard';
import {act} from 'react-dom/test-utils';
import userEvent from '@testing-library/user-event';
import {ConnectedAppPage} from '@src/connect/pages/ConnectedAppPage';

jest.mock('@src/connect/pages/ConnectedAppPage', () => ({
    ...jest.requireActual('@src/connect/pages/ConnectedAppPage'),
    ConnectedAppPage: jest.fn(() => null),
}));

beforeEach(() => {
    fetchMock.resetMocks();
    historyMock.reset();
    jest.clearAllMocks();
});

test('The connected app card renders', async () => {
    const item = {
        id: '0dfce574-2238-4b13-b8cc-8d257ce7645b',
        name: 'App A',
        scopes: ['scope A1'],
        connection_code: 'connectionCodeA',
        logo: 'http://www.example.test/path/to/logo/a',
        author: 'author A',
        categories: ['category A1', 'category A2'],
        certified: false,
        partner: 'partner A',
    };

    renderWithProviders(<ConnectedAppCard item={item} />);
    await waitForElement(() => screen.getByText('App A'));

    expect(screen.queryByText('App A')).toBeInTheDocument();
    expect(
        screen.queryByText('akeneo_connectivity.connection.connect.connected_apps.list.card.developed_by' + ' author A')
    ).toBeInTheDocument();
    expect(screen.queryByText('category A1')).toBeInTheDocument();
    expect(screen.queryByText('category A2')).toBeNull();
    expect(
        screen.queryByText('akeneo_connectivity.connection.connect.connected_apps.list.card.manage_app')
    ).toBeInTheDocument();

    act(() => {
        userEvent.click(
            screen.getByText('akeneo_connectivity.connection.connect.connected_apps.list.card.manage_app')
        );
    });

    expect(historyMock.history.location.pathname).toBe('/connect/connected-apps/0dfce574-2238-4b13-b8cc-8d257ce7645b');
});
