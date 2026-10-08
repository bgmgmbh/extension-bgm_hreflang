<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "BGM hreflang".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace BGM\BgmHreflang\Event;

/**
 * Dispatched before a single hreflang tag is rendered in the frontend.
 */
class FrontendBeforeRenderSingleTagEvent extends AbstractEvent {}
