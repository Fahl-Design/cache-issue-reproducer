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

use function uniqid;

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

        if ($input->getOption('add-tags')) {
            $io->writeln('[+] now with tags #1');
            $io->writeln($this->getDataFromCacheWithTags($io));
            $io->writeln('[+] now with tags #2');
            $io->writeln($this->getDataFromCacheWithTags($io));
            $io->writeln('[+] now with tags #3');
            $io->writeln($this->getDataFromCacheWithTags($io));
        } else {
            $io->writeln('[+] no tags #1');
            $io->writeln($this->getDataFromCacheWithoutTags($io));
            $io->writeln('[+] no tags #2');
            $io->writeln($this->getDataFromCacheWithoutTags($io));
            $io->writeln('[+] no tags #3');
            $io->writeln($this->getDataFromCacheWithoutTags($io));
        }

        return Command::SUCCESS;
    }

    private function getDataFromCacheWithTags(SymfonyStyle $io): string
    {
        return $this->fancyCachePool->get(
            key: 'fancy_cache_key_3',
            callback: function (ItemInterface $item) use ($io): string {
                $io->info('cache callback was hit to build cache with tags');
                $item->tag(['tag1', 'tag2']);

                return 'my cached value';
            });
    }

    private function getDataFromCacheWithoutTags(SymfonyStyle $io): string
    {
        return $this->fancyCachePool->get(
            key: 'fancy_cache_key_4',
            callback: function (ItemInterface $item) use ($io): string {
                dump($item->isHit() ? 'hit' : 'no hit');
                $io->info('cache callback was hit to build no tags');
//                $item->tag(['tag1', 'tag2']);

                return 'my cached value';
            });
    }

}
