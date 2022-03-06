<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\PostLike;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\PostLikeRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BlogController extends AbstractController
{
    /**
     * @Route("/blog", name="blog")
     */
    public function index(ArticleRepository $repoArticles): Response
    {
        $articles = $repoArticles->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles'  =>  $articles
        ]);
    }

    /**
     * @Route("/", name="home")
     *
     * @return void
     */
    public function home()
    {
        return $this->render('blog/home.html.twig');
    }

    /**
     * @Route("/blog/new", name="blog_create")
     * @Route("/blog/{id}/edit", name="blog_edit")
     */
    public function form(Article $article = null, Request $request)
    {
        if (!$article) {
            $article = new Article();
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$article->getId()) {
                $article->setCreatedAt(new \DateTimeImmutable());
            }

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('blog_show', [
                'id' => $article->getId()
            ]);
        }

        return $this->render('blog/create.html.twig', [
            'formArticle'  =>  $form->createView(),
            'editMode'  =>  $article->getId() !== null
        ]);
    }

    /**
     * @Route("/blog/{id}", name="blog_show")
     *
     * @return void
     */
    public function show(Article $article, Request $request)
    {
        $comment = new Comment;
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTimeImmutable())
                ->setArticle($article);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($comment);
            $manager->flush();

            $this->redirectToRoute('blog_show', [
                'id'    =>  $article->getId()
            ]);
        }

        return $this->render('blog/show.html.twig', [
            'article'   => $article,
            'commentForm'  =>  $form->createView()
        ]);
    }

    /**
     * Permet de liker ou unliker un article
     *
     * @Route("/article/{id}/like", name="article_like")
     * 
     * @param Article $article
     * @param ObjectManager $manager
     * @param PostLikeRepository $likeRepo
     * @return Response
     */
    public function like(Article $article, PostLikeRepository $likeRepo): Response
    {
        $manager = $this->getDoctrine()->getManager();

        // Récupére l'utilisateur
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté
        if (!$user) return $this->json([
            'code' => 403,
            'message' => 'Unauthorized'
        ], 403);

        // Si l'utilisateur à déja aimé l'article => Suppression du Like
        if ($article->isLikedByUser($user)) {
            $like = $likeRepo->findOneBy([
                'article' => $article,
                'user' => $user
            ]);

            $manager->remove($like);
            $manager->flush();

            return $this->json([
                'code' => 200,
                'message' => 'Like bien supprimé',
                'likes' => $likeRepo->count(['article' => $article])
            ], 200);
        }

        $like = new PostLike();
        $like->setArticle($article)
            ->setUser($user);

        $manager->persist($like);
        $manager->flush();

        return $this->json([
            'code' => 200,
            'message' => 'Ca marche bien',
            'likes' => $likeRepo->count(['article' => $article])
        ], 200);
    }
}
