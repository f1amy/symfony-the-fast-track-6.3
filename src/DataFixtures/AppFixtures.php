<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use App\Enum\PublishState;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $numberOfConferences = $faker->numberBetween(20, 30);

        for ($i = 0; $i < $numberOfConferences; $i++) {
            $conference = $this->createConference($manager, $faker);

            $manager->persist($conference);
        }

        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('admin'));
        $manager->persist($admin);

        $manager->flush();
    }

    private function createConference(ObjectManager $manager, Generator $faker): Conference
    {
        $conference = new Conference();

        $conference->setCity($faker->city());
        $conference->setYear($faker->year());
        $conference->setIsInternational($faker->boolean());

        $numberOfComments = $faker->numberBetween(0, 20);

        for ($i = 0; $i < $numberOfComments; $i++) {
            $comment = $this->createComment($conference, $faker);

            $manager->persist($comment);
        }

        return $conference;
    }

    private function createComment(Conference $conference, Generator $faker): Comment
    {
        $comment = new Comment();

        $comment->setConference($conference);
        $comment->setAuthor($faker->firstName());
        $comment->setEmail($faker->email());
        $comment->setText($faker->realText());

        $weightedPublishStates = $this->getWeightedPublishStates();
        $comment->setState($faker->randomElement($weightedPublishStates));

        return $comment;
    }

    /**
     * @return array<PublishState>
     */
    private function getWeightedPublishStates(): array
    {
        return array_merge(
            array_fill(0, 3, PublishState::Submitted),
            array_fill(0, 2, PublishState::Spam),
            array_fill(0, 5, PublishState::Published),
        );
    }
}
