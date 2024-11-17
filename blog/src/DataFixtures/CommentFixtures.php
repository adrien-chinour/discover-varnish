<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 1000; $i++) {
            $article = $this->getReference(sprintf('article-%d', $faker->numberBetween(0, 999)), Article::class);

            for ($k = 0; $k < $faker->numberBetween(0, 10); $k++) {
                $comment = Comment::create(
                    author: $faker->userName(),
                    content: $faker->paragraph(),
                    publishedAt: \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year')),
                    article: $article,
                );

                $manager->persist($comment);
            }
        }

        $manager->flush();
    }
}
