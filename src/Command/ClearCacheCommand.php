<?php

/**
 * MIT License
 *
 * Copyright (c) 2020 Wolf Utz<wpu@hotmail.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace OmegaCode\JwtSecuredApiCore\Command;

use OmegaCode\JwtSecuredApiCore\Factory\CacheAdapterFactory;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TODO add options to define caches to clear.
 */
class ClearCacheCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    private AbstractAdapter $cache;

    public function __construct()
    {
        $this->cache = CacheAdapterFactory::build();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Clears the application cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cache->clear('app.configuration');
        $this->cache->clear('request');
        $output->writeln('Successfully cleared all available caches!');

        return 0;
    }
}
