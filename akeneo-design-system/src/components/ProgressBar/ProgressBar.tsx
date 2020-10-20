import React, {Ref, useEffect, useRef, useState} from 'react';
import styled, {css, keyframes} from 'styled-components';
import {AkeneoThemedProps, getColor, getColorForLevel, getFontSize, Level} from '../../theme';
import {useWindowSize} from '../../hooks/useWindowSize';
import {uuid} from '../../shared';

const ProgressBarContainer = styled.div``;

const progressBarAnimation = keyframes`
  from { background-position: 0 0; }
  to { background-position: 20px 0; }
`;

const Header = styled.div<{isMinimized: boolean} & AkeneoThemedProps>`
  display: flex;
  align-items: stretch;
  flex-direction: row;
  font-size: ${getFontSize('default')};
  margin-bottom: -4px;
  ${props =>
    props.isMinimized &&
    css`
      flex-direction: column;
      margin-bottom: 0px;
    `}
`;

const Title = styled.div`
  color: ${getColor('grey140')};
  flex-shrink: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex-grow: 1;
  padding-right: 20px;
`;

const ProgressLabel = styled.div`
  color: ${getColor('grey120')};
  flex-shrink: 0;
  padding-bottom: 4px;
`;

const ProgressBarBackground = styled.div<{size: ProgressBarSize} & AkeneoThemedProps>`
  background: ${getColor('grey80')};
  height: ${props => getHeightFromSize(props.size)};
  overflow: hidden;
  position: relative;
`;

const ProgressBarFill = styled.div.attrs<{width: number; level: Level; indeterminate: boolean; light: boolean}>(
  props => ({
    style: {width: `${props.width}%`},
  })
)<{level: Level; light: boolean} & AkeneoThemedProps>`
  ${({level, light}: {level: Level; light: boolean} & AkeneoThemedProps) => css`
    background: ${getColorForLevel(level, light ? 60 : 100)};
  `}

  height: 100%;
  left: 0;
  position: absolute;
  top: 0;
  transition: width 0.3s;

  ${props =>
    props.indeterminate &&
    css`
      background-image: linear-gradient(
        315deg,
        rgba(255, 255, 255, 0.6) 25%,
        rgba(255, 255, 255, 0.4) 25%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0.6) 50%,
        rgba(255, 255, 255, 0.6) 75%,
        rgba(255, 255, 255, 0.4) 75%,
        rgba(255, 255, 255, 0.4) 100%
      );
      background-size: 20px 20px;
      transition: width 200ms ease;
      animation: ${progressBarAnimation} 1s linear infinite;
    `}
`;

const getHeightFromSize = (size: ProgressBarSize): string => {
  switch (size) {
    case 'large':
      return '10px';
    case 'small':
    default:
      return '4px';
  }
};

const computeWidthFromPercent = (percent: number | 'indeterminate'): number => {
  if (percent === 'indeterminate' || percent > 100) {
    return 100;
  }

  if (percent < 0) {
    return 0;
  }

  return percent;
};

type ProgressBarSize = 'small' | 'large';

type ProgressBarProps = {
  /**
   * Define the level of the progress bar.
   */
  level: Level;

  /**
   * The progression of the progress bar in percentage (from 0 to 100) when type is determinate,
   * when type is indeterminate use indeterminate.
   */
  percent: number | 'indeterminate';

  /**
   * Is the style of the progress bar should be light
   */
  light?: boolean;

  /**
   * The progress bar title.
   */
  title?: string;

  /**
   * Describe the progress with a label (example: 46%, 30 minutes left).
   */
  progressLabel?: string;

  /**
   * Define the size of the progress bar.
   */
  size?: ProgressBarSize;
} & React.HTMLAttributes<HTMLDivElement>;

/**
 * Progress bar to provide users with feedback on what is going on.
 */
const ProgressBar = React.forwardRef<HTMLDivElement, ProgressBarProps>(
  (
    {level, percent, title, progressLabel, light = false, size = 'small', ...rest}: ProgressBarProps,
    forwardedRef: Ref<HTMLDivElement>
  ) => {
    const [labelId] = useState<string>(`label_${uuid()}`);
    const [progressBarId] = useState<string>(`progress_${uuid()}`);
    const [isMinimized, setIsMinimized] = useState(false);

    const internalRef = useRef<HTMLDivElement>(null);
    const ref = null === forwardedRef ? internalRef : forwardedRef;

    const windowSize = useWindowSize();
    useEffect(() => {
      if (typeof ref !== 'function' && null !== ref.current) {
        const element = (ref.current as unknown) as HTMLDivElement;
        setIsMinimized(element.getBoundingClientRect().width <= 300);
      }
    }, [windowSize]);

    const progressBarProps = {};
    if (percent !== 'indeterminate') {
      progressBarProps['aria-valuenow'] = computeWidthFromPercent(percent);
      progressBarProps['aria-valuemin'] = 0;
      progressBarProps['aria-valuemax'] = 100;
    }

    if (title) {
      progressBarProps['aria-labelledby'] = labelId;
    }

    return (
      <ProgressBarContainer ref={ref} {...rest}>
        {(title || progressLabel) && (
          <Header isMinimized={isMinimized}>
            <Title id={labelId} htmlFor={progressBarId}>
              {title}
            </Title>
            <ProgressLabel>{progressLabel}</ProgressLabel>
          </Header>
        )}
        <ProgressBarBackground id={progressBarId} role="progressbar" {...progressBarProps} size={size}>
          <ProgressBarFill
            level={level}
            light={light}
            indeterminate={percent === 'indeterminate'}
            width={computeWidthFromPercent(percent)}
          />
        </ProgressBarBackground>
      </ProgressBarContainer>
    );
  }
);

export {ProgressBar};
