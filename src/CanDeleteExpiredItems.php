<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Cache;

/**
 * CanDeleteExpiredItems
 */
interface CanDeleteExpiredItems
{
    /**
     * Removes all expired items from the pool or cache.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteExpiredItems(): bool;
}