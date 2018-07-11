<?php
/**
 * @see       https://github.com/zendframework/zend-jmap for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-jmap/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Jmap;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
        ];
    }
}
