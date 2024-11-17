<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:routes:export',
    description: 'Export CSV des routes',
)]
final class ExportRoutesCommand extends Command
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SerializerInterface $serializer,
        private readonly RouterInterface $router,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $articles = array_map(
            fn(Article $article) => [
                'title' => $article->title(),
                'slug' => $article->slug(),
                'url' => $this->router->generate('article', ['slug' => $article->slug()]),
            ],
            $this->articleRepository->findAll(),
        );

        file_put_contents(
            'articles.csv',
            $this->serializer->serialize($articles, 'csv', ['as_collection' => true])
        );

        $categories = array_map(
            fn(Category $category) => [
                'title' => $category->name(),
                'url' => $this->router->generate('category', ['slug' => $category->slug()]),
            ],
            $this->categoryRepository->findAll(),
        );

        file_put_contents(
            'categories.csv',
            $this->serializer->serialize($categories, 'csv', ['as_collection' => true])
        );

        return Command::SUCCESS;
    }
}
