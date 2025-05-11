<?php

declare(strict_types=1);

namespace App\Services\Helpers;

use chillerlan\QRCode\Output\QRMarkupSVG;

/**
 * @property SvgWithLogoOptions $options
 */
class QRSvgWithLogo extends QRMarkupSVG
{
    /**
     * {@inheritDoc}
     */
    protected function paths(): string
    {
        $size = (int) ceil($this->moduleCount * $this->options->svgLogoScale);

        // we're calling QRMatrix::setLogoSpace() manually, so QROptions::$addLogoSpace has no effect here
        $this->matrix->setLogoSpace($size - 5, $size);

        $svg = parent::paths();
        $svg .= $this->getLogo();
        $svg .= $this->getText($this->options->topText, 7);
        $svg .= $this->getText($this->options->bottomText, 95);

        return $svg;
    }

    /**
     * returns a <g> element that contains the SVG logo and positions it properly within the QR Code
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/g
     * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/transform
     */
    protected function getLogo(): string
    {
        // @todo: customize the <g> element to your liking (css class, style...)
        return sprintf(
            '%5$s<g transform="translate(%1$s %1$s) scale(%2$s)" class="%3$s">%5$s	%4$s%5$s</g>',
            (($this->moduleCount - ($this->moduleCount * $this->options->svgLogoScale)) / 2),
            $this->options->svgLogoScale,
            $this->options->svgLogoCssClass,
            file_get_contents($this->options->svgLogo),
            PHP_EOL
        );
    }

    protected function getText(string $text, int $offset): string
    {
        return sprintf('<text
            x="50%%"
            y="%d%%"
            font-size="0.1em" 
            dominant-baseline="auto" 
            text-anchor="middle" 
            font-family="ui-sans-serif,system-ui,sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji"
            >%s</text>',
            $offset,
            $text
        );
    }
}
