<?php

namespace App\Support\Spreadsheet;

use OpenSpout\Common\Entity\Style\Style;

/**
 * OpenSpout 3's style builder, on top of OpenSpout 5's immutable Style.
 */
final class StyleBuilder
{
    private Style $style;

    public function __construct()
    {
        $this->style = new Style;
    }

    public function setFontBold(): self
    {
        $this->style = $this->style->withFontBold(true);

        return $this;
    }

    public function build(): Style
    {
        return $this->style;
    }
}
