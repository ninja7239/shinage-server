<?php

declare(strict_types=1);

/*
 * Licensed under MIT. See file /LICENSE.
 */

namespace App\Controller\Management\Screens;

use App\Entity\Presentation;
use App\Entity\Screen;
use App\Entity\User;
use App\Repository\PresentationsRepository;
use App\Security\LoggedInUserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ScreenDataController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PresentationsRepository */
    private $presentationsRepository;

    /** @var LoggedInUserRepositoryInterface */
    private $loggedInUserRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PresentationsRepository $presentationsRepository,
        LoggedInUserRepositoryInterface $loggedInUserRepository
    ) {
        $this->entityManager = $entityManager;
        $this->presentationsRepository = $presentationsRepository;
        $this->loggedInUserRepository = $loggedInUserRepository;
    }

    public function indexAction(Request $request, string $guid): Response
    {
        $screen = $this->entityManager->find(Screen::class, $guid);

        $readonlyAttr = ' readonly';
        $readonly = true;

        if ($this->isGranted('manage', $screen)) {
            $readonlyAttr = '';
            $readonly = false;

            $form = $this->createFormBuilder($screen, ['translation_domain' => 'ScreenSettings'])
                ->add('name', TextType::class, ['required' => false, 'empty_data' => ''])
                ->add('location', TextType::class, ['required' => false, 'empty_data' => ''])
                ->add('adminc', TextType::class, ['required' => false, 'empty_data' => ''])
                ->add('notes', TextareaType::class, ['required' => false, 'empty_data' => ''])
                ->add('defaultPresentation', EntityType::class, [
                    'class' => Presentation::class,
                    'choices' => $this->getPresentationsChoices(),
                    'required' => false,
                ])
                ->add('save', SubmitType::class)
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->addFlash('success', 'Saved successfully');
                $this->entityManager->flush();
            }
        } else {
            $form = $this->createFormBuilder($screen, ['translation_domain' => 'ScreenSettings'])
                ->add('name', TextType::class, ['attr' => ['disabled' => 'disabled']])
                ->add('location', TextType::class, ['attr' => ['disabled' => 'disabled']])
                ->add('adminc', TextType::class, ['attr' => ['disabled' => 'disabled']])
                ->add('notes', TextareaType::class, ['attr' => ['disabled' => 'disabled']])
                ->getForm();
        }

        return $this->render('manage/screens/data.html.twig', [
            'form' => $form->createView(),
            'screen' => $screen,
            'readonlyAttr' => $readonlyAttr,
            'readonly' => $readonly,
        ]);
    }

    /**
     * @return Presentation[]
     */
    private function getPresentationsChoices(): array
    {
        $user = $this->loggedInUserRepository->getLoggedInUserOrDenyAccess();
        if (false === $user instanceof User) {
            throw new AccessDeniedException(
                'Presentations of user could not be fetched as no valid user was found in session.'
            );
        }

        $choices = [];
        foreach ($this->presentationsRepository->getPresentationsForsUser($user) as $presentation) {
            $choices[] = $presentation;
        }

        return $choices;
    }
}
