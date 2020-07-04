<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Utils\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Exception;
use RuntimeException;

class ConferenceController extends AbstractController
{
    /**
     * @var Environment
     */
    private Environment $twig;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * ConferenceController constructor.
     * @param Environment $twig
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {

        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/", name="homepage")
     *
     * @param ConferenceRepository $repository
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(ConferenceRepository $repository): Response
    {
        return new Response($this->twig->render(
            'conference/index.html.twig', [
                'conferences' => $repository->findAll()
            ])
        );
    }

    /**
     * @Route(name="conference_show", path="/conference/show/{slug}")
     *
     * @param Request $request
     * @param Conference $conference
     * @param CommentRepository $commentRepository
     * @param SpamChecker $spamChecker
     * @param string $photoDir
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        SpamChecker $spamChecker,
        string $photoDir
    ): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            if($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch(FileException $e) {

                }

                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri()
            ];

            if ($spamChecker->getSpamScore($comment, $context) > 0) {
                throw new RuntimeException('this is likely spam, go away!');
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('conference_show', ['slug' => $conference->getSlug()]);
        }
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return new Response($this->twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]));
    }
}
