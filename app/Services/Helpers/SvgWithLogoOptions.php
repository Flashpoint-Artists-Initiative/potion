<?php

declare(strict_types=1);

namespace App\Services\Helpers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\QROptions;

/**
 * @codeCoverageIgnore
 */
class SvgWithLogoOptions extends QROptions
{
    // path to svg logo
    public string $svgLogo;

    // logo scale in % of QR Code size, clamped to 10%-30%
    public float $svgLogoScale = 0.2;

    // css class for the logo (defined in $svgDefs)
    public string $svgLogoCssClass = '';

    public function __construct(public string $topText = '', public string $bottomText = '')
    {
        $this->svgLogo = public_path('images/alchemy_logo.svg');
        $this->version = 6;
        $this->outputInterface = QRSvgWithLogo::class;
        $this->outputType = 'custom';
        $this->outputBase64 = true;
        $this->eccLevel = EccLevel::H; // ECC level H is necessary when using logos
        $this->connectPaths = true;
        $this->scale = 50;
    }

    // check logo
    // protected function set_svgLogo(string $svgLogo): void
    // {

    //     if (! file_exists($svgLogo) || ! is_readable($svgLogo)) {
    //         throw new QRCodeException('invalid svg logo');
    //     }

    //     $this->svgLogo = $svgLogo;
    // }

    // clamp logo scale
    // protected function set_svgLogoScale(float $svgLogoScale): void
    // {
    //     $this->svgLogoScale = max(0.05, min(0.3, $svgLogoScale));
    // }
}
