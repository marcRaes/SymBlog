<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\PostLike;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * Encodeur de mot de passe
     *
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $users = [];
        $categories = [];
        $genders = ['male', 'female'];

        // Création de 20 utilisateurs fakés
        for ($i = 0; $i <= 20; $i++) {
            $user = new User();
            $user->setEmail($faker->email)
                ->setUsername($faker->firstName($genders[mt_rand(0, 1)]))
                ->setPassword($this->encoder->encodePassword($user, $faker->password(8, 20)));

            $manager->persist($user);
            $users[] = $user;
        }

        // Création de 10 catégories fakées
        for ($i = 1; $i <= 10; $i++) {
            $category = new Category();
            $category->setTitle($faker->realText(20, 1))
                ->setDescription('<p>' . $faker->realText() . '</p>');

            $manager->persist($category);
            $categories[] = $category;
        }

        // Crée entre 10 et 20 articles fakés
        for ($i = 1; $i <= mt_rand(10, 20); $i++) {
            $article = new Article();

            for ($j = 0; $j <= mt_rand(3, 10); $j++) {
                if (!isset($content)) {
                    $content = '<p>' . $faker->realText(2000, 5)  . '</p>';
                } else {
                    $content .= '<p>' . $faker->realText(2000, 5)  . '</p>';
                }
            }

            $article->setTitle($faker->realText(50, 1))
                ->setDescription('<p>' . $faker->realText() . '</p>')
                ->setContent($content)
                ->setImage($faker->imageUrl())
                ->setCreatedAt(new \DateTimeImmutable(($faker->dateTimeBetween('-6 months'))->format('d-m-Y H:i')))
                ->setCategory($faker->randomElement($categories));

            $manager->persist($article);

            // Crée entre 5 et 15 commentaires fakés
            for ($k = 1; $k <= mt_rand(5, 15); $k++) {
                $comment = new Comment;

                $content = '<p>' . $faker->realText(150, mt_rand(1, 3)) . '</p>';

                $days = (new \DateTimeImmutable())->diff($article->getCreatedAt())->days;

                $comment->setAuthor($faker->name())
                    ->setContent($content)
                    ->setCreatedAt(new \DateTimeImmutable(($faker->dateTimeBetween('-' . $days . ' days'))->format('d-m-Y H:i')))
                    ->setArticle($article);

                $manager->persist($comment);
            }

            // Crée entre 0 et 10 likes fakés
            for ($j = 0; $j < mt_rand(0, 10); $j++) {
                $like = new PostLike();
                $like->setArticle($article)
                    ->setUser($faker->randomElement($users));

                $manager->persist($like);
            }
        }

        $manager->flush();
    }
}
