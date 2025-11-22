<?php

namespace App\Controller;

use App\Entity\Result;
use App\Form\ResultType;
use App\Repository\ResultRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/result')]
final class ResultController extends AbstractController
{
    #[Route(name: 'app_result_index', methods: ['GET'])]
    public function index(ResultRepository $resultRepository): Response
    {
        return $this->render('result/index.html.twig', [
            'results' => $resultRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_result_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $result = new Result();
        $form = $this->createForm(ResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $fileName = $fileUploader->upload($imageFile);
                $result->setImage($fileName);
            }
            
            $entityManager->persist($result);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('result/new.html.twig', [
            'result' => $result,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_result_show', methods: ['GET'])]
    public function show(Result $result): Response
    {
        return $this->render('result/show.html.twig', [
            'result' => $result,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_result_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Result $result, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $oldImage = $result->getImage();
        $form = $this->createForm(ResultType::class, $result);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                if ($oldImage) {
                    $fileUploader->deleteFile($oldImage);
                }
                $fileName = $fileUploader->upload($imageFile);
                $result->setImage($fileName);
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('result/edit.html.twig', [
            'result' => $result,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_result_delete', methods: ['POST'])]
    public function delete(Request $request, Result $result, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        // Supprimer l'image associée
        if ($result->getImage()) {
            $fileUploader->deleteFile($result->getImage());
        }
        
        $entityManager->remove($result);
        $entityManager->flush();
        $this->addFlash('success', 'Résultat supprimé avec succès !');

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
}
