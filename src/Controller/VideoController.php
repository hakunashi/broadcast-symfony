<?php

namespace App\Controller;

use App\Entity\Video;
use App\Form\VideoType;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

class VideoController extends AbstractController
{

    public function __construct(private Security $security) {
    }

    #[Route('/', name: 'video_index')]
    public function index(VideoRepository $videoRepository): Response
    {
        $videos = $videoRepository->findAll();

        return $this->render('video/index.html.twig', [
            'videos' => $videos,
        ]);
    }

    #[Route('/video/new', name:'video_item_create')]
    public function addVideo(Request $request, EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $video = new Video();
        $user = $this->security->getUser();
        $form = $this->createForm(VideoType::class, $video);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $path = $form->get('path')->getData();
            $thumb = $form->get('thumb')->getData();

            if($path && $thumb) {
                $originalFilenamePath = pathinfo($path->getClientOriginalName(), PATHINFO_FILENAME);
                $originalFilenameThumb = pathinfo($thumb->getClientOriginalName(), PATHINFO_FILENAME);

                $videoSafeFilename = $slugger->slug($originalFilenamePath);
                $thumbSafeFilename = $slugger->slug($originalFilenameThumb);

                $videoFilename = $videoSafeFilename . '-' . uniqid() . '.' . $path->guessExtension();
                $thumbFilename = $thumbSafeFilename . '-' . uniqid() . '.' . $path->guessExtension();

                try {
                    $path->move(
                        'uploads/video/video',
                        $videoFilename
                    );
                    $thumb->move(
                        'uploads/video/thumbnail',
                        $thumbFilename
                    );
                    $video->setPath($videoFilename)
                        ->setThumb($thumbFilename);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier"));
                }
            }

            $video->setViews(0)
                ->setUploadDate(new \DateTimeImmutable())
                ->setUser($user) ;
            $em->persist($video);
            $em->flush();

        return $this->redirectToRoute('video_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('video/video-new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/video/{id}', name:'video_item')]
    public function item(VideoRepository $videoRepository, EntityManagerInterface $em, int $id)
    {
        $video = $videoRepository->findOneBy(['id' => $id]);

        $video->setViews($video->getViews()+1);
        $em->persist($video);
        $em->flush();

        return $this->render('video/video-item.html.twig', [
            'video' => $video
        ]);
    }
    
    
}
