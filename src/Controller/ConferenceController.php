<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\Comment\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Exception;

class ConferenceController extends AbstractController
{
    /** @var Environment */
    private Environment $twig;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var MessageBusInterface */
    private MessageBusInterface $bus;

    /**
     * ConferenceController constructor.
     * @param Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     */
    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {

        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
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
        $response = new Response($this->twig->render(
            'conference/index.html.twig', [
                'conferences' => $repository->findAll()
            ])
        );

        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * @Route(path="/conference_header", name="conference_header")
     *
     * @param ConferenceRepository $repository
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function conferenceHeader(ConferenceRepository $repository): Response
    {
        $response = new Response($this->twig->render(
            'conference/header.html.twig', [
                'conferences' => $repository->findAll()
            ]
        ));

        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * @Route(name="conference_show", path="/conference/show/{slug}")
     *
     * @param Request $request
     * @param Conference $conference
     * @param CommentRepository $commentRepository
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
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri()
            ];

            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

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
