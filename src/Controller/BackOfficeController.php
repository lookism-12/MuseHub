<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class BackOfficeController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $templatePath = $this->getParameter('kernel.project_dir') . '/templates/template-admiro/index.html';
        
        if (!file_exists($templatePath)) {
            throw $this->createNotFoundException('Template not found');
        }
        
        $content = file_get_contents($templatePath);
        
        $content = preg_replace('/(href|src)="assets\//', '$1="/BO/assets/', $content);
        $content = preg_replace('/(href|src)="\.\.\/assets\//', '$1="/BO/assets/', $content);
        
        return new Response($content);
    }
}
