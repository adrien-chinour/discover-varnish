<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Parsedown;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $categories = array_map(
            fn($name) => Category::create(name: $name, slug: (new AsciiSlugger())->slug($name)->lower()->toString()),
            ['Sport', 'Politique', 'Économie', 'Culture', 'Santé', 'Sciences', 'Technologie', 'international']
        );

        foreach ($categories as $category) {
            $this->addReference($category->slug(), $category);
            $manager->persist($category);
        }

        $manager->flush();

        for ($i = 0; $i < 1000; $i++) {
            $article = Article::create(
                title: $title = $faker->sentence,
                slug: (new AsciiSlugger())->slug($title)->lower()->toString(),
                description: $faker->paragraph(),
                image: sprintf('https://picsum.photos/id/%d/1200/600', $faker->numberBetween(1, 100)),
                content: $this->getContent(),
                author: $faker->name(),
                publishedAt: \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year')),
            );

            $article->setCategory($this->getReference($faker->randomElement($categories)->slug(), Category::class));

            $this->setReference(sprintf('article-%d', $i), $article);

            $manager->persist($article);
        }

        $manager->flush();
    }

    private function getContent(): string
    {
        $response = HttpClient::create()->request('GET', 'https://jaspervdj.be/lorem-markdownum/markdown.txt', [
            'query' => [
                'no-code' => 'on',
                'no-external-links' => 'on',
            ],
        ]);

        return (new Parsedown())->parse($response->getContent());
    }
}
