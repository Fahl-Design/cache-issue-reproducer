<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsCommand(
    name: 'app:cache-test',
    description: 'Add a short description for your command',
)]
class CacheTestCommand extends Command
{
    public function __construct(
        /**
         * @phpstan-var \Symfony\Component\Cache\Adapter\TagAwareAdapter
         */
        private readonly TagAwareCacheInterface $fancyCachePool,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('add-tags', null, InputOption::VALUE_NONE, 'add tags to cache item and see problem')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $addTags = $input->getOption('add-tags');
        $counter = 0;
        $key = 'myfancy_cache_key_'.($addTags ? 'with_tags_' : '');
        $key .= \uniqid('key', false);
        $io->section('cache key: '.$key);

        if ($addTags) {
            $io->writeln('[+] now with tags #1');
            $io->writeln($this->getDataFromCacheWithTags($io, $key, $counter));
            $io->writeln('[+] now with tags #2');
            $io->writeln($this->getDataFromCacheWithTags($io, $key, $counter));
            $io->writeln('[+] now with tags #3');
            $io->writeln($this->getDataFromCacheWithTags($io, $key, $counter));
        } else {
            $io->writeln('[+] no tags #1');
            $io->writeln($this->getDataFromCacheWithoutTags($io, $key, $counter));
            $io->writeln('[+] no tags #2');
            $io->writeln($this->getDataFromCacheWithoutTags($io, $key, $counter));
            $io->writeln('[+] no tags #3');
            $io->writeln($this->getDataFromCacheWithoutTags($io, $key, $counter));
        }

        return Command::SUCCESS;
    }

    private function getDataFromCacheWithTags(SymfonyStyle $io, string $key, int &$counter): string
    {
        return $this->fancyCachePool->get(
            key: $key,
            callback: function (ItemInterface $item) use (&$counter, $io): string {
                ++$counter;
                $returnValue = \uniqid('cached_value', true);
                $io->success('cache callback was hit to build cache with tags: '.$counter.' added value: '.$returnValue);
                $item->tag(['tag1', 'tag2']);

                return 'my cached value with tags_'.$counter.'_'.$returnValue;
            });
    }

    private function getDataFromCacheWithoutTags(SymfonyStyle $io, string $key, int &$counter): string
    {
        return $this->fancyCachePool->get(
            key: $key,
            callback: function () use (&$counter, $io): string {
                ++$counter;
                $returnValue = \uniqid('cached_value', true);
                $io->success('cache callback was hit to build no tags: '.$counter.' added value: '.$returnValue);

                return 'my cached value_'.$counter.'_'.$returnValue;
            });
    }
}
