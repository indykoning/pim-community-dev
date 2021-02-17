import React from 'react';
import {Octocrumb} from './Octocrumb';
import {render, screen} from '../../storybook/test-util';

test('it renders its children properly', () => {
  render(<Octocrumb>Octocrumb content</Octocrumb>);

  expect(screen.getByText('Octocrumb content')).toBeInTheDocument();
});

// Those tests should pass directly if you follow the contributing guide.
// If you add required props to your Component, these tests will fail
// and you will need to add these required props here as well
test('Octocrumb supports forwardRef', () => {
  const ref = {current: null};

  render(<Octocrumb ref={ref} />);
  expect(ref.current).not.toBe(null);
});

test('Octocrumb supports ...rest props', () => {
  render(<Octocrumb data-testid="my_value" />);
  expect(screen.getByTestId('my_value')).toBeInTheDocument();
});
