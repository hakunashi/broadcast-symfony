<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\Video;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const NB_VIDEO = 4;

    private const CATEGORIES = ["humour", "vlog", "gaming", "kids", "documentaire", "story telling", "react"];

    public function __construct(private UserPasswordHasherInterface $hasher) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_TW');

        $user = new User();

        $user->setUsername('nashi')
            ->setPassword($this->hasher->hashPassword($user, 'toto'));
        $manager->persist($user);

        $categories = [];

        foreach (self::CATEGORIES as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);

            $manager->persist($category);
            $categories[] = $category;
        }

        for ($i = 0; $i < self::NB_VIDEO; $i++) {
            $video = new Video();
            $video->setUser($user)
                ->setCategory($faker->randomElement($categories))
                ->setPath('file_example_MP4_480_1_5MG.mp4')
                ->setUploadDate(new DateTimeImmutable())
                ->setThumb('LOGO-FRANCE-TRAVAIL.png')
                ->setViews(0)
                ->setTitle($faker->words(10, true)) ;
        
            $manager->persist($video);
        }

        $manager->flush();
    }
}
